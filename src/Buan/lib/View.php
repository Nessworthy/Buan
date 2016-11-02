<?php
/**
 * @event onrender
 * Invoked after the View has rendered it's template and stored the result in
 * $this->buffer
 *
 * @package Buan
 */
namespace Buan;

class View extends EventDispatcher
{

    /**
     * Stores the result of calling "this->render()".
     *
     * @var string
     */
    public $buffer = '';

    /**
     * The Controller to which this View has access, if required.
     *
     * @var Controller
     */
    private $controller = null;

    /**
     * The instance of the rendering engine that will render this view.
     *
     * @var IViewEngine
     */
    private $engine;

    /**
     * A singleton instance of the "GlobalView". When self::getGlobalView()
     * is executed for the first time, the resulting View will be store in this
     * property. Any subsequent calls to self::getGlobalView() will return this
     * cached instance.
     *
     * @var View
     */
    static private $globalView = null;

    /**
     * Contains all headers that will be included in the HTTP response.
     *
     * @var array
     */
    private $headers = [];

    /**
     * All helpers are loaded into this structure.
     *
     * @var \StdClass
     */
    public $helpers;

    /**
     * List of external Javascript sources (absolute URLs).
     *
     * @var array
     */
    private $javascripts = [];

    private $javascriptsLoader = null;

    /**
     * The View that will be used to render the Javascript sources to $this. The
     * class used by this instance is the same as that used by $this.
     *
     * @var View
     */
    private $javascriptsView;

    /**
     * Stores all View instances that will be rendered when this View's "onrender"
     * event is invoked.
     *
     * @var array
     */
    private $renderQueue = [];

    /**
     * Each slot contains a nested View object, indexed by a unique identifier.
     *
     * @var array
     */
    private $slots = [];

    /**
     * The template source file's path.
     *
     * @var string
     */
    protected $source = null;

    /**
     * List of external stylesheet sources (absolute URLs). Each element in this
     * array is an array of URLs and it's index is the media-type for which the
     * URLs are valid styles.
     *
     * @var array
     */
    public $stylesheets = [];

    /**
     * The View that will be used to render the stylesheet sources to $this. The
     * class used by this instance is the same as that used by $this.
     *
     * @var View
     */
    private $stylesheetsView;

    /**
     * Holds all template variables.
     *
     * @var array
     */
    private $v = [];

    /**
     *  Holds the file version for the current asset tag to use.
     */
    private $assetVersion;

    /**
     * Constructor.
     *
     * @param IViewEngine Rendering engine to use for this view.
     * @return View
     */
    function __construct($engine = null)
    {

        // Setup properties
        $this->engine = $engine === null ? new PhpViewEngine() : $engine;
        $this->engine->setView($this);
        $this->helpers = new \StdClass();

        // Setup listeners for 'onrender' event
        $this->addEventListener('onrender', [$this, 'processRenderQueue']);
    }

    /**
     * Returns $this->v[$varName], or sets it to NULL if it doesn't exist.
     *
     * @param string Variable name
     * @return mixed
     */
    function __get($varName)
    {

        // Return
        // NOTE: Setting a non-existent variable allows us to use "$template->myVar[] = TRUE"
        //	 (see http://uk2.php.net/manual/en/language.oop5.overloading.php#69370)
        // NOTE: This still doesn't work, seemingly when an array element contains an Object
        // eg.	$view->objectList[7] = "something";	- "something" never gets stored
        if (!isset($this->v[$varName])) {
            $this->v[$varName] = null;
        }

        // Explicitly casting an array fixes the "Indirect modification of overloaded property" bug(?) in PHP5.2+.
        // Converting the array to an ArrayObject returns the variable in read/write mode rather than just read-mode.
        if (is_array($this->v[$varName])) {
            $this->v[$varName] = new \ArrayObject($this->v[$varName], \ArrayObject::ARRAY_AS_PROPS);
            return $this->v[$varName];
        }
        return $this->v[$varName];
    }

    /**
     * Stores the given variable in the self::$v array.
     *
     * Enables you to set a template variable using the nicer syntax of:
     *    $template->myVar = 'value here';
     *
     * The one variable name you need t be careful of is "buffer" - don't use it!
     *
     * @param string Variable name
     * @param mixed Variable value
     * @return void
     */
    function __set($varName, $varValue)
    {
        $this->v[$varName] = $varValue;
    }

    function getAssetVersion()
    {
        if (is_null($this->assetVersion)) {
            $add = "?v=" . date('ymd');
            $this->assetVersion = $add;
        }
        return $this->assetVersion;
    }

    /**
     * Adds external Javascript sources to the View.
     *
     * @example From within a PHP template:
     * <code>
     * $this->addJavascripts('/js/resource1.js', '/js/resource2.js');
     * </code>
     *
     * @param string $src,... Unlimited list of external Javascript URLs
     * @return void
     */
    public function addJavascripts()
    {

        // Get unique list of new javascripts to be added
        $view = $this->getJavascriptsView();
        $args = func_get_args();
        if (empty($args)) {
            return;
        }
        $toAdd = array_diff($args, $this->javascripts);
        $src = $this->javascriptsView->getSource();

        $add = $this->getAssetVersion();

        // Add scripts to the js loader
        if (($loader = $this->getJavascriptsLoader()) !== null) {
            if (empty($src)) {
                // TODO: Wat?
                if (substr($s, 0, 4) == 'http' || substr($s, 0, 2) == '//') {
                    // Don't add variable to external scripts
                    $src = "<script src=\"{$loader}?\" type=\"text/javascript\"></script>\n";
                } else {
                    $src = "<script src=\"{$loader}{$add}?\" type=\"text/javascript\"></script>\n";
                }
            }
            foreach ($toAdd as $s) {
                if (substr($s, 0, 4) == 'http' || substr($s, 0, 2) == '//') {
                    $src = "<script type=\"text/javascript\" src=\"{$s}\"></script>\n{$src}";
                } else {
                    $src = str_replace('" type=', 's[]=' . urlencode($s) . '?' . $add . '&" type=', $src);
                }
            }
            $this->javascriptsView->setSource($src);
        } else {
            // Add individual <script> tags for each source
            foreach ($toAdd as $s) {
                if (substr($s, 0, 4) == 'http' || substr($s, 0, 2) == '//') {
                    // don't add variable to external scripts.
                    $src .= "<script type=\"text/javascript\" src=\"{$s}\"></script>\n";
                } else {
                    $src .= "<script type=\"text/javascript\" src=\"{$s}{$add}\"></script>\n";
                }
            }
            $this->javascriptsView->setSource($src);
        }
    }

    /**
     * Adds external stylesheet sources to the View.
     * The first argument given is special and can take the form of either a URL, a
     * conditional IE statement or one of the following key phrases:
     *        screen | print | all | screen,print
     *
     * If one of these phrases is used then it will be included in the "media"
     * attribute of the <link> nodes.
     *
     * If a conditional statement
     *
     * @example From within a PHP template:
     * <code>
     * $this->addStylesheets('/css/resource1.css', '/css/resource2.css');
     * $this->addStylesheets('print', '/css/resource3.css');
     * </code>
     *
     * @param string $src,... Unlimited list of external stylesheet URLs
     * @return void
     */
    public function addStylesheets()
    {

        // Determine conditional
        $args = func_get_args();
        $conditional = strpos($args[0], " IE ") !== false ? array_shift($args) : null;

        // Determine media
        $media = 'all';
        $medium = ['screen', 'print', 'screen,print', 'print,screen', 'all'];
        if (in_array(strtolower(str_replace(" ", "", $args[0])), $medium)) {
            $media = array_shift($args);
        }

        $add = $this->getAssetVersion();

        // Add stylesheets
        $view = $this->getStylesheetsView();
        if (!empty($args)) {
            /*if(!isset($this->stylesheets[$media])) {
                $this->stylesheets[$media] = array();
            }
            $this->stylesheets[$media] = array_unique(array_merge($this->stylesheets[$media], $args));*/
            foreach ($args as $a) {
                $this->stylesheets[] = (object) [
                    'conditional' => $conditional,
                    'media' => $media,
                    'url' => $a
                ];
            }
        }

        // Update the stylesheets View's source
        $output = '';
        $done = [];
        foreach ($this->stylesheets as $m => $src) {
            if (!isset($done[$src->url])) {
                if (substr($src->url, 0, 4) == 'http' || substr($src->url, 0, 2) == '//') {
                    // Don't add the variable if we're loading a remote script.
                    $thisSrc = $src->url;
                } else {
                    if (substr($src->url, -4) == ".css") {
                        $thisSrc = $src->url . $add;
                    } else {
                        $thisSrc = $src->url;
                    }
                }
                $output .= $src->conditional !== null ? "<!--[if {$src->conditional}]><link rel=\"stylesheet\" type=\"text/css\" href=\"{$thisSrc}\" media=\"{$src->media}\"  /><![endif]-->\n" : "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$thisSrc}\" media=\"{$src->media}\" />\n";
                $done[$src->url] = true;
            }
        }
        $this->stylesheetsView->setSource($output);
    }

    /**
     * Appends $view and $bufferView to $this->renderQueue and returns it's
     * allocated buffer-tag.
     *
     * @param View The View that will be rendered
     * @param View The View into which the rendered output will be placed
     * @return string
     */
    function addToRenderQueue($view, $bufferView)
    {
        $bufferTag = "[[buffertag:" . (md5(rand(0, 999999))) . "]]";
        $this->renderQueue[$bufferTag] = [$view, $bufferView];
        return $bufferTag;
    }

    /**
     * Attaches the View object to the specified slot.
     *
     * @param View View to be attached
     * @param string Slot to which the $view will be attached
     * @return void
     */
    function attachViewToSlot($view, $slotId)
    {

        // Store View
        $this->slots[$slotId] = $view;
    }

    /**
     * Executes the "/global-view" UrlCommand, stores the resulting View in the
     * cached self::$globalView property and returns it.
     *
     * @return View
     */
    static public function getGlobalView()
    {
        if (self::$globalView === null) {
            $gvCommand = UrlCommand::create('/global-view');
            self::$globalView = $gvCommand->execute();
        }
        return self::$globalView;
    }

    /**
     * Returns the value of the specified header.
     *
     * @param string Name of header
     * @return void
     */
    public function getHeader($header)
    {
        return isset($this->headers[$header]) ? $this->headers[$header] : '';
    }

    public function getJavascriptsLoader()
    {
        return $this->javascriptsLoader;
    }

    /**
     * Returns a View that generates the <script> nodes for each Javascript
     * resource when rendered.
     *
     * @return View
     */
    public function getJavascriptsView()
    {
        if ($this->javascriptsView === null) {
            $this->javascriptsView = new View(new StringViewEngine());
        }

        return $this->javascriptsView;
    }

    /**
     * Returns the View stored in the specified slot, or an empty Buan\View if the
     * slot does not exist.
     *
     * @param string ID of the slot you want to retrieve
     * @return \View
     */
    public function getSlot($slotId)
    {
        if (isset($this->slots[$slotId])) {
            return $this->slots[$slotId];
        } else {
            SystemLog::add("There is no View object in slot \"{$slotId}\"", SystemLog::WARNING);
            return new View();
        }
    }

    /**
     * Check whether there's a view stored for a specific slot.
     * @param string $slotId The ID of the slot to check against.
     * @return bool True if there is a view, false if not.
     */
    public function hasSlot($slotId)
    {
        if (isset($this->slots[$slotId])) {
            return true;
        }
        return false;
    }

    /**
     * Return this View's source.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Returns a View that generates the <link> nodes for each stylesheet resource
     * when rendered.
     *
     * @return View
     */
    public function getStylesheetsView()
    {
        if ($this->stylesheetsView === null) {
            //$className = get_class($this);
            $this->stylesheetsView = new View(new StringViewEngine());
        }
        return $this->stylesheetsView;
    }

    /**
     * Returns all defined template variables.
     *
     * @return array
     */
    public function getVariables()
    {
        return $this->v;
    }

    /**
     * Load the specified view helper.
     *
     * @param string Helper id
     * @return void
     */
    public function loadHelper($helper)
    {

        // Helper: html
        if ($helper === 'html') {
            $this->helpers->html = new ViewHelper\Html($this);
        }

        // Helper: i18n
        if ($helper === 'i18n') {
            $this->helpers->i18n = new ViewHelper\I18n($this);
        }

        // Forward the call to this View's rendering engine as it too may want to
        // perform some actions when a helper is loaded.
        if ($this->engine !== null) {
            $this->engine->loadHelper($helper);
        }
    }

    /**
     * Renders all Views in $this->renderQueue, substituting the buffer tags in the
     * associated buffer View with the results of these renderings.
     *
     * @return void
     */
    public function processRenderQueue()
    {

        // renders all views in $this->postRenderViews and substitutes their renderTag in $this->buffer
        foreach ($this->renderQueue as $bufferTag => $queued) {
            $view = $queued[0];
            $bufferView = $queued[1] === null ? $this : $queued[1];
            $vbuffer = $view->render();
            $bufferView->buffer = str_replace($bufferTag, $vbuffer, $bufferView->buffer);
        }
    }

    /**
     * Renders and renders this View.
     *
     * @return string
     */
    function render()
    {

        // Invoke "onprerender" event
        $this->dispatchEvent(new Event('onprerender'));

        // Send headers
        // Thanks to output buffering nested Views can set headers too without
        // worrying about them already having been output.
        ob_start();
        foreach ($this->headers as $header => $value) {
            header($header . ($value === null ? '' : ": {$value}"));
        }
        unset($header, $value);
        ob_get_clean();

        // Invoke the rendering engine
        $this->buffer = $this->engine->render();

        // Invoke "onrender" event
        $this->dispatchEvent(new Event('onrender'));

        // Result
        return $this->buffer;
    }

    /**
     * Tells the rendering queue to wait until $view has been rendered before then
     * rendering $this View and inserting it into the $bufferView.
     *
     * If $bufferView is omitted then $view is used in it's place.
     *
     * The returned string is a placeholder tag that should be placed in the View
     * referenced by $bufferView. It will be replaced with $this' rendered output.
     *
     * @param View The View after which $this View will be rendered
     * @param View The View that will actually contains the buffer placeholder
     * @return string
     */
    public function renderAfter($view, $bufferView = null)
    {

        // Add $this View to the $view's "onrender" queue
        return $view->addToRenderQueue($this, $bufferView !== null ? $bufferView : $view);
    }

    /**
     * Allows you to replace the GlobalView with a custom View instance.
     *
     * @param View View to be used as the GlobalView
     * @return void
     */
    static public function setGlobalView($view = null)
    {
        self::$globalView = $view;
    }

    /**
     * Define the Controller to which this View has direct access.
     *
     * @param Controller Controller instance
     * @return void
     */
    public function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * Prepare an HTTP response header.
     *
     * @param string Name of the header (eg. "Content-Type")
     * @param string Header value (eg. "text/html; charset=utf-8")
     * @return void
     */
    function setHeader($header, $value = null)
    {
        $this->headers[$header] = $value;
    }

    /**
     * Define a script that will be used to load all external javascript sources
     * in one HTTP request.
     *
     * NOTE: Any js sources beginning with "http" will NOT be sent to the loader
     * script.
     *
     * @param string Absolute URL to the loader script
     * @return void
     */
    function setJavascriptsLoader($loader)
    {
        $this->javascriptsLoader = $loader;
    }

    /**
     * Defines this View's source template that will be used at render-time.
     *
     * @param string Absolute path to the template file
     * @return void
     */
    function setSource($templatePath = null)
    {
        $this->source = $templatePath;
    }
}
