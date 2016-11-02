<?php
/**
 * @package Buan
 */
namespace Buan;

class UrlCommand
{

    /**
     * Action name.
     *
     * @var string
     */
    private $actionName = '';

    /**
     * Controller name.
     *
     * @var string
     */
    private $controllerName = '';

    /**
     * Holds details of a custom url-parser function/method.
     * TODO: Is this even used any longer?
     *
     * @var string|array
     */
    //static protected $customUrlParser = NULL;

    /**
     * Action parameters.
     *
     * @var array
     */
    private $parameters = [];

    /**
     * Stores the absolute folder-path that leads to the Controller class.
     *
     * @var string
     */
    private $path = '';

    /**
     * All registered routing function stored here, in the order they were added.
     *
     * @var array
     */
    static private $routes = [];

    /**
     * A "new UrlCommand()" statement should only be issued internally within this
     * class. Use the "UrlCommand::create()" method instead within your application.
     *
     * @param string $path Absolute folder path in which the Controller can be found
     * @param string $controllerName Controller name in lower-hyphenated format, eg. controller-name
     * @param string $actionName Action name in lower-hyphenated format, eg. action-name
     * @param array $parameters Additional parameters to be passed to the Action (their order
     *    in this array is significant)
     */
    function __construct($path = '', $controllerName = 'index', $actionName = 'index', $parameters = [])
    {

        // Store properties
        $this->path = $path;
        $this->controllerName = $controllerName;
        $this->actionName = $actionName;
        $this->parameters = $parameters;
    }

    /**
     * Add a custom url routing rule which the UrlCommand::create() method will
     * take into account when searching for relevant Controllers to execute the
     * requested URL.
     *
     * The first argument is basically a regular expression that will be run on
     * the URL using preg_match(). If a match is found then Buan will start looking
     * for the controller in the path specified in the second argument.
     *
     * Note that by the time the command string is evaluated against the regular
     * expression it will no longer have it's leading forward slash.
     *
     * param string Regular expression, including delimiters (eg.
     *        "/^\/my-controller.*$/i")
     * param string Absolute path to the folder that will be searched for matching
     *        controller classes
     * return void
     */
    /*static public function addCustomRoute($regex, $path) {
        $route = new \StdClass();
        $route->regex = $regex;
        $route->path = $path;
        $routes = Config::get('core.customUrlRoutes');
        $routes[] = $route;
        Config::set('core.customUrlRoutes', $routes);
    }*/

    /**
     * Register a routing function. Routes are executed in the order they are
     * added.
     *
     * The function is passed a single argument - the command string (ie. URL).
     * It should then return either NULL, or a UrlCommand instance which will
     * finally be executed.
     *
     * @param callable|array Function to execute (closure | param for call_user_func_array())
     */
    static public function addRoute($router)
    {
        self::$routes[] = $router;
    }

    /**
     * Creates a UrlCommand instance and populates it with the necessary
     * attributes determined by the command string.
     *
     * @param string $commandString Command in the format "controller/action/param1/param2/..."
     * @return UrlCommand
     */
    static public function create($commandString)
    {

        /*// If registered, pass the URI through to the custom parser
        if(self::$customUrlParser!==NULL) {
            if(is_array(self::$customUrlParser)) {
                if(is_string(self::$customUrlParser[0])) {
                    eval("$c = ".self::$customUrlParser[0]."::".self::$customUrlParser[1]."(\"$commandString\");");
                    return $c;
                }
                else {
                    return self::$customUrlParser[0]->{self::$customUrlParser[1]}($commandString);
                }
            }
            else {
                return self::$customUrlParser($commandString);
            }
        }*/

        // Split command into it's individual components and ensure we have
        // at least a controller-name and an action-name (use a default of
        // "index" for each).
        $commandString = preg_replace("/^\/+/", "", preg_replace("/\/+$/", "", $commandString));
        $args = $commandString == '' ? [] : explode('/', $commandString);
        while (sizeof($args) < 2) {
            $args[] = 'index';
        }
        $controllerName = array_shift($args);
        $actionName = array_shift($args);
        $parameters = $args;

        // Unencode all parameters
        foreach ($parameters as $k => $p) {
            $parameters[$k] = urldecode($p);
        }

        // Pass the URI through all registered routes and return with UrlCommand
        // if match is found. Otherwise, containue on to the default matching.
        foreach (self::$routes as $r) {
            $uc = call_user_func($r, $commandString, $controllerName, $actionName, $parameters);
            if ($uc !== null) {
                return $uc;
            }
        }
        unset($uc);

        // Check if any custom routing rules have been defined that match the given
        // command string and apply them
        $searchComponents = [];
        /*$routes = Config::get('core.customUrlRoutes');
        if(!empty($routes)) {
            foreach($routes as $k=>$r) {
                if(preg_match($r->regex, $commandString)) {
                    $searchComponents["customroute{$k}"] = array(
                        'path'=>$r->path,
                        'controllerName'=>$controllerName,
                        'actionName'=>$actionName,
                        'parameters'=>$parameters
                    );
                }
            }
        }*/

        // Build a list of search components.
        // The order of this list is significant in that the search is carried
        // out in the same order, ie:
        //	custom routes > app > extensions > core
        //
        $searchComponents['app'] = [
            'path' => Config::get('app.dir.controllers'),
            'controllerName' => $controllerName,
            'actionName' => $actionName,
            'parameters' => $parameters
        ];
        $ext = Config::get('ext');
        if (is_array($ext)) {
            foreach ($ext as $extName => $extConfig) {
                if (isset($extConfig['dir']['controllers'])) {
                    $searchComponents["ext-$extName"] = [
                        'path' => $extConfig['dir']['controllers'],
                        'controllerName' => $controllerName,
                        'actionName' => $actionName,
                        'parameters' => $parameters
                    ];
                }
            }
        }
        $searchComponents['core'] = [
            'path' => Config::get('core.dir.controllers'),
            'controllerName' => $controllerName,
            'actionName' => $actionName,
            'parameters' => $parameters
        ];

        // Search for the Controller's class
        $preferredIndex = null;
        while (!isset($componentIndex)) {

            // Find class file
            // We first check the "preferred" component, and if that turns up nothing
            // then look in all other components
            if ($preferredIndex !== null) {
                $C = $searchComponents[$preferredIndex];
                $className = $searchComponents[$preferredIndex]['className'] = Inflector::controllerCommand_controllerClass($C['controllerName']);
                if (is_file($C['path'] . "/$className.php")) {
                    $componentIndex = $preferredIndex;
                    break;
                }
            }
            foreach ($searchComponents as $k => $C) {
                $className = $searchComponents[$k]['className'] = Inflector::controllerCommand_controllerClass($C['controllerName']);
                if (is_file($C['path'] . "/$className.php")) {
                    $componentIndex = $k;
                    break 2;
                }
            }

            // Match $controllerName to a sub-folder within one of the components.
            // If a folder is found then this component will temporarily take
            // precedence.
            foreach ($searchComponents as $k => $C) {
                if (is_dir($C['path'] . "/" . $C['controllerName'])) {
                    $params = $C['parameters'];
                    $searchComponents[$k] = [
                        'path' => $C['path'] . "/" . $C['controllerName'],
                        'controllerName' => $C['actionName'],
                        'actionName' => count($C['parameters']) > 0 ? array_shift($params) : 'index',
                        'parameters' => $params
                    ];
                    $preferredIndex = $k;
                    continue 2;
                }
            }

            // No controller class was found within any of the components, so set all
            // controller names to "unknown" and re-run the search
            foreach ($searchComponents as $k => $C) {
                if ($C['controllerName'] == 'unknown' && $C['path'] != '') {
                    $searchComponents[$k]['path'] = preg_replace("/\/[^\/]+$/", "", $C['path']);
                } else {
                    $searchComponents[$k]['controllerName'] = 'unknown';
                }
            }
        }

        // Create new UrlCommand
        $C = $searchComponents[$componentIndex];
        $command = new UrlCommand($C['path'], $C['controllerName'], $C['actionName'], $C['parameters']);

        return $command;
    }

    /**
     * Register a custom function/method to handle the generation of UrlCommands
     * from given URIs.
     *
     * @param string|array Name of a global function (string), or class
     *        name/instance and method name (array)
     * @return void
     */
    static public function registerUrlParser($function)
    {
    }

    /**
     * Returns this command as a string representation, in the format:
     *        /controller-name/action-name/param0/param1
     *
     * @return string
     */
    public function toString()
    {

        // Result
        return preg_replace("/^\/+/", "/",
            preg_replace("/\/+/", "",
                '/' . $this->path . '/' . $this->controllerName . '/' . $this->actionName . '/' . implode('/', $this->parameters)
            )
        );
    }

    /**
     * Generate the command part of the URL (ie. everything after the domain name)
     * based on the given arguments.
     *
     * If any of the $param arguments use non-numeric indexes, then the key=>value
     * pairs in those parameters will be added to the query element of the
     * generated URL (ie. after the "?" in the URL)
     *
     * @example
     * $url = UrlCommand::createUrl('controller', 'action', 'param1', 'param2');
     *
     * @param string $controllerName
     * @param string $actionName Action name (eg. action-name)
     * @param mixed[] $params Action parameters. Order is significant
     * @return string
     */
    static public function createUrl($controllerName = 'index', $actionName = 'index', ...$params)
    {

        // Dispatch event for listeners to modify the initial parameters as needed
        if (GlobalEventDispatcher::hasEventListeners('buan-pre-create-url')) {
            $ev = new Event('buan-pre-create-url');
            $ev->data = (object) [
                'controller' => $controllerName,
                'action' => $actionName
            ];
            GlobalEventDispatcher::dispatchEvent($ev);
            $controllerName = $ev->data->controller;
            $actionName = $ev->data->action;
        }

        // Build the URL
        $urlPrefix = Config::get('app.command.urlPrefix');
        $url = $urlCopy = Config::get('app.urlRoot') . '/' . ($urlPrefix == '' ? '' : "$urlPrefix/") . $controllerName . '/' . urlencode($actionName);
        $query = [];
        foreach($params as $param) {
            if (is_array($param)) {
                foreach ($param as $k => $subParam) {

                    // Detect numeric key
                    if ((int) $k === $k) {
                        $url .= '/' . urlencode($subParam);
                    } // String key, append to query portion of the url
                    else {
                        $query[] = $subParam === null ? $k : (is_array($subParam) ? http_build_query([$k => $subParam]) : "{$k}=" . urlencode($subParam));
                    }
                }
            } else {
                $url .= '/' . urlencode($param);
            }
        }
        if ($actionName == 'index' && $url == $urlCopy) {
            $url = preg_replace("/\/index$/i", "", $url);
        }
        $url = preg_replace("/\/+/", "/", preg_replace("/\/$/", "", $url)) . (count($query) > 0 ? '?' . implode("&", $query) : '');

        // Result
        return $url;
    }

    /**
     * Generates the same URL as UrlCommand::createUrl(), but prefixes this with
     * the application's domain name.
     * Note that the protocol (eg. http, https, ftp, etc) is NOT included in the
     * returned URL.
     *
     * @param string $controllerName Controller name (in lower-hyphenated format, eg. controller-name)
     * @param string $actionName Action name (in lower-hyphenated format, eg. action-name)
     * @param mixed $params Action parameters. Order is significant
     * @return string
     */
    static public function createAbsoluteUrl($controllerName = 'index', $actionName = 'index', ...$params)
    {

        // Gather additional arguments into any array
        $allParams = [];
        foreach($params as $param) {
            if (is_array($param)) {
                foreach ($param as $k => $p) {
                    $allParams[$k] = $p;
                }
            } else {
                $allParams[] = $param;
            }
        }

        // Result
        $p = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT'] : 80;
        $port = $p != 80 && $p != 443 ? ":" . $p : '';
        return Config::get('app.domain') . $port . UrlCommand::createUrl($controllerName, $actionName, $allParams);
    }

    /**
     * Executes the command and returns the resulting View object.
     *
     * @return View
     */
    public function execute()
    {

        // Create an instance of the Controller specified in this command
        $className = Inflector::controllerCommand_controllerClass($this->controllerName);
        include_once($this->path . "/$className.php");
        $controller = new $className($this->parameters, new HttpRequest());

        // Invoke the required action, passing any given parameters, and store the resulting View object
        $view = $controller->invokeAction($this->actionName);

        // Result
        return $view;
    }

    /**
     * Returns the name of this command's controller.
     *
     * @return string
     */
    function getControllerName()
    {

        // Result
        return $this->controllerName;
    }

    /**
     * Returns the name of this command's action.
     *
     * @return string
     */
    function getActionName()
    {

        // Result
        return $this->actionName;
    }

    /**
     * Returns this command's parameters.
     *
     * @return array
     */
    function getParameters()
    {

        // Result
        return $this->parameters;
    }
}
