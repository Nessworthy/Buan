<?php
/**
 * @package Buan
 *
 * DEPRECATED: (AS OF 12th Aug 2009) THIS SHOULD NOT BE USED UNTIL IT HAS BEEN
 * REWORKED TO FIT WITH THE NEW EXTENSIONS SYSTEM.
 */
namespace Buan;

abstract class Extension
{

    /*
     * @property string $name
     * Extension name (matches name of the folder in which the extension is stored)
     */
    public $name = '';

    /*
     * @property string $title
     * Extension title
     * [USER-DEFINED]
     */
    public $title = '';

    /*
     * @property string $description
     * Extension description
     * [USER-DEFINED]
     */
    public $description = '';

    /*
     * @property array $dependancies
     * Extension dependancies
     * [USER-DEFINED]
     */
    public $dependancies = [];

    /*
     * @property string $version
     * Extension version
     * [USER-DEFINED]
     */
    public $version = "0";

    /*
     * @method Extension __construct( string $name, string $namespace )
     * $name			= Name of this Extension
     * $namespace	= Location of this extension (app | core)
     *
     * Do NOT call this constructor directly. Use ::getExtensionByName() instead.
     *
     * The following Config variables are set automatically:
     * ext.*.docRoot
     * ext.*.urlRoot
     */
    public function __construct($name, $namespace)
    {

        // Store properties
        $this->name = $name;

        // Create some config settings in the [ext.*] namespace
        $config = Config::get($namespace);
        Config::set('ext.' . $this->name . '.docRoot', $config['dir']['extensions'] . '/' . $this->name);
        if (preg_match("/" . preg_quote($config['docRoot'], "/") . "/i", $config['dir']['extensions']) > 0) {
            $urlRoot = preg_replace("/" . preg_quote($config['docRoot'], "/") . "/i", "", $config['dir']['extensions']) . '/' . $this->name;
            Config::set('ext.' . $this->name . '.urlRoot', str_replace("\\", "/", $urlRoot));
        } else {
            Config::set('ext.' . $this->name . '.urlRoot', '');
        }
    }

    /*
     * @method array getAvailableList()
     *
     * Returns a list of Extension objects that are available to the current
     * application. This includes all extensions listed in both the core
     * extensions folder and the application's extension folder.
     */
    final public static function getAvailableList()
    {

        // Gather extensions from both the 'core' and 'application' folders
        $extensions = [];
        $extFolders = [Config::get('app.dir.extensions'), Config::get('core.dir.extensions')];
        foreach ($extFolders as $extFolder) {

            // Ignore invalid folders
            if ($extFolder == '') {
                continue;
            }

            // Iterate through folder
            try {
                $dir = new \DirectoryIterator($extFolder);
                foreach ($dir as $obj) {

                    // Ignore parent paths and non-directories
                    if ($obj->isDot() || !$obj->isDir()) {
                        continue;
                    }

                    // Get the Extension instance
                    $extName = $obj->getFilename();
                    $ext = self::getExtensionByName($extName);
                    if (!is_null($ext)) {
                        $extensions[$extName] = $ext;
                    }
                }
            } catch (Exception $e) {
                SystemLog::add("Failed to open {$extFolder} for parsing.", SystemLog::WARNING);
            }
        }

        // Result
        return $extensions;
    }

    /*
     * @method Extension getExtensionByName( string $extName )
     * $extName	= Extension name (ie. name of the extension's folder)
     *
     * Returns an Extension instance that corresponds to the specified extension.
     */
    final public static function getExtensionByName($extName)
    {

        // Vars
        $extClassName = Inflector::extensionName_extensionClass($extName);
        static $extensions = [];
        if (isset($extensions[$extName])) {
            return $extensions[$extName];
        }

        // Find the extension
        // Extensions in "app" will override the same extension in "core".
        $extFolders = ['app' => Config::get('app.dir.extensions'), 'core' => Config::get('core.dir.extensions')];
        foreach ($extFolders as $namespace => $extFolder) {
            if ($extFolder == '' || !file_exists("$extFolder/$extName") || in_array($extName, Config::get('app.dir.ignored'))) {
                continue;
            }
            if (!is_file("$extFolder/$extName/$extClassName.php")) {
                SystemLog::add('Extension "' . $extName . '" is missing a class definition.', SystemLog::WARNING);
            } else {
                if (!isset($extensions[$extName])) {
                    $extFilePath = sprintf(
                        '%s/%s/%s.php',
                        $extFolder,
                        $extName,
                        $extClassName
                    );
                    include_once($extFilePath);
                    $ext = new $extClassName($extName, $namespace);
                    $extensions[$extName] = $ext;
                }
            }
        }

        // Result
        return isset($extensions[$extName]) ? $extensions[$extName] : null;
    }

    /*
     * @method bool validateDependancies()
     *
     * Checks that all dependancies are installed.
     */
    final public function validateDependancies()
    {

        // Go through all dependancies and check they are installed
        $validated = true;
        foreach ($this->dependancies as $dependancy) {
            $E = Extension::getExtensionByName($dependancy);
            if (!$E->isInstalled()) {
                $validated = false;
            }
        }

        // Result
        return $validated;
    }

    /*
     * @method array getDependants( [bool $isInstalled] )
     * $isInstalled	= Results flag
     *
     * Returns an array of Extension instances that are dependant on $this Extension.
     * If $isInstalled===TRUE then installed ones are returned.
     * If $isInstaleld===FALSE then non-installed ones are returned.
     * If $isInstalled===NULL then ALL are returned.
     */
    final public function getDependants($isInstalled = null)
    {

        // Find all extensions which depend on $this Extension
        $dependants = [];
        $extensions = Extension::getAvailableList();
        foreach ($extensions as $extension) {
            if (in_array($this->name, $extension->dependancies)) {
                if ($isInstalled === true) {
                    if ($extension->isInstalled()) {
                        $dependants[] = $extension;
                    }
                } else {
                    if ($isInstalled === false) {
                        if (!$extension->isInstalled()) {
                            $dependants[] = $extension;
                        }
                    } else {
                        $dependants[] = $extension;
                    }
                }
            }
        }

        // Result
        return $dependants;
    }

    /*
     * @method bool initDependancies()
     *
     * Initialise all dependancies.
     * Your Extension's init() method MUST call this method first and return
     * FALSE if it fails.
     */
    final public function initDependancies()
    {

        // Gather dependancies and inititialise them all
        $success = true;
        foreach ($this->dependancies as $dependancy) {
            $E = Extension::getExtensionByName($dependancy);
            if (!$E->init()) {
                $success = false;
            }
        }

        // Result
        return $success;
    }

    /*
     * Abstract methods, all of which MUST be implemented by each Extension.
     */

    /*
     * @method array getButtonmanagerMap()
     *
     * Generates and returns a list of buttons that are displayed in the
     * Extension Manager interface.
     * This mapping allows the Extension author to map a GUI button element in
     * the front-end to a method in their Extension class.
     *
     * The format of the returned array is:
     *	$result = array(
     *		0=>array(
     *			'method'=>'class-method-to-be-called',
     *			'label'=>'button-label'
     *		),
     *		...
     *	);
     */
    abstract public function getManagerButtonMap();

    /*
     * @method bool isInstalled()
     *
     * Perform some checks to see if the Extension is already installed.
     * Return TRUE is if it's installed, or FALSE otherwise.
     */
    abstract public function isInstalled();

    /*
     * @method bool install()
     *
     * Contains the installation routine.
     */
    abstract public function install();

    /*
     * @method bool uninstall()
     *
     * Contains the uninstallation routine.
     */
    abstract public function uninstall();

    /*
     * @method bool init()
     *
     * This allows the Extension to perform some intitialising routines during
     * the environment setup. These are all run prior to the application's
     * bootstrap script (see note below).
     *
     * Return TRUE on successful initialisation, or FALSE otherwise.
     *
     * Your implementation of this MUST call "$this->initDependancies()" before
     * anything else and return FALSE if that call returns FALSE. ie:
     *	if(!$this->initDependancies()) {
     *		return FALSE;
     *	}
     *
     * Common tasks that an initialisation routine would perform are:
     * - Define new ModelRelations for any Models that the Extension introduces
     * - Add Model and Manager directory paths to the BuanAutoLoader
     * - Define Config variables to be used by certain scripts in the extension
     *
     * IMPORTANT NOTE:
     * Do NOT start a session within this method. For example, if the application
     * is storing some custom object instances in the session, then the
     * bootstrap script will probably be adding some paths to the BuanAutoLoader
     * mechanism. However, if you start a session before these paths have been
     * added then those stored instances will never be woken up correctly. This
     * is because each extension's init() method is called BEFORE the bootstrap
     * file.
     *
     * Instead, just let the application developer know that your extension
     * requires a session to be started so they can do this in their bootstrap
     * script.
     *
     * If you need to access data held within $_SESSION during initialisation,
     * then it's suggested that you create a custom method that does this and
     * then ask the application developer to simply call this method in their
     * bootstrap script (after they have started the session), like so:
     *	$ext = Extension::getExtensionByName('my-extension');
     *	$ext->customMethod();
     */
    abstract public function init();
}
