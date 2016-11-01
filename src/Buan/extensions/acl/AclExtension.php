<?php
/*
# $Id$
*/

/*
# @class AclExtension
*/
class AclExtension extends Extension {

	/*
	# @property string $title
	# Extension title.
	*/
	public $title = 'Access Control Lists';

	/*
	# @property string $description
	# Extension description.
	*/
	public $description = 'Provides a authorization layer to your Models via the use of ACLs.';

	/*
	# @property array $dependancies
	# List of other Extensions on which this Extension is dependant.
	*/
	public $dependancies = array();

	/*
	# @property string $version
	# Extension version
	*/
	public $version = 1.1;

	/*
	# @method array getManagerButtonMap()
	#
	# Returns a list of method-name/button-label pairings that are used to add buttons to the Extension Manager interface.
	# When a button is then pressed in the interface, it will trigger the associated method in this class.
	# Returned array format:
	#	array(
	#		array('method'=>'method-name', 'label'=>'button-label'),
	#		array(...),
	#		...
	#	)
	*/ 
	public function getManagerButtonMap() {

		// Vars
		$buttons = array();

		// Generate the button mapping
		if($this->isInstalled()) {
			$buttons = array(
				array('method'=>'uninstall', 'label'=>'Uninstall'),
				array('method'=>'manage', 'label'=>'Manage', 'onclick'=>"self.location='".UrlCommand::createUrl('acl')."';")
			);
		}
		else {
			$buttons = array(
				array('method'=>'install', 'label'=>'Install')
			);
		}

		// Result
		return $buttons;
	}

	/*
	# @method bool isInstalled()
	#
	# Determine if this extension is installed.
	*/
	public function isInstalled() {

		// Vars
		static $isInstalled = NULL;
		if(!is_null($isInstalled)) {
			return $isInstalled;
		}

		// Get a database connection
		try {
			$DB = Database::getConnection();
		}
		catch(Exception $e) {
			SystemLog::add($e->getMessage(), SystemLog::WARNING);
			$isInstalled = FALSE;
			return FALSE;
		}

		// Determine if all tables are present in the database
		$sql = 'SHOW TABLES';
		$stmt = ModelManager::sqlQuery($sql);
		$tables = array();
		while($table = $stmt->fetch(PDO::FETCH_NUM)) {
			$tables[] = strtolower($table[0]);
		}
		if(in_array('acl_repo', $tables) && in_array('acl_role', $tables) && in_array('acl_resource', $tables) && in_array('acl_role_member', $tables) && in_array('acl_entry', $tables)) {
			$isInstalled = TRUE;
			return TRUE;
		}

		// Result
		$isInstalled = FALSE;
		return FALSE;
	}

	/*
	# @method bool install()
	#
	# Install the extension.
	*/
	public function install() {

		// Get DB connection
		try {
			$DB = Database::getConnection();
		}
		catch(Exception $e) {
			SystemLog::add('This extension requires a database. ('.$e->getMessage().')', SystemLog::WARNING);
			return FALSE;
		}

		// Table definitions
		$tableSql = array(
			'acl_repo'=>'CREATE TABLE IF NOT EXISTS acl_repo (
						id int unsigned auto_increment,
						name varchar(32),
						primary key (id)
					);',
			'acl_role'=>'CREATE TABLE IF NOT EXISTS acl_role (
						id int unsigned auto_increment,
						acl_repo_id int unsigned,
						acl_type_id int unsigned default 0,
						alias varchar(100),
						notes varchar(200) default "",
						primary key (id)
					);',
			'acl_resource'=>'CREATE TABLE IF NOT EXISTS acl_resource (
						id int unsigned auto_increment,
						acl_repo_id int unsigned,
						acl_type_id int unsigned default 0,
						parent_id int unsigned default 0,
						alias varchar(100),
						notes varchar(200) default "",
						primary key (id)
					);',
			'acl_role_member'=>'CREATE TABLE IF NOT EXISTS acl_role_member (
						id int unsigned auto_increment,
						acl_role_id int unsigned,
						parent_id int unsigned default 0,
						primary key (id)
					);',
			'acl_type'=>'CREATE TABLE IF NOT EXISTS acl_type (
						id int unsigned auto_increment,
						acl_repo_id int unsigned,
						type varchar(32),
						permissions varchar(100),
						primary key (id)
					);',
			'acl_entry'=>'CREATE TABLE IF NOT EXISTS acl_entry (
						id int unsigned auto_increment,
						acl_role_id int unsigned,
						acl_resource_id int unsigned,
						pallow varchar(50),
						pdeny varchar(50),
						primary key (id)
					);'
		);

		// Create tables
		try {
			$DB->beginTransaction();
			foreach($tableSql as $table=>$sql) {
				if(!ModelManager::sqlQuery($sql)) {
					throw new PDOException("Invalid SQL");
				}
			}
			$DB->commit();
			return TRUE;
		}
		catch(PDOException $e) {
			SystemLog::add($e->getMessage(), SystemLog::WARNING);
			try {
				$DB->rollBack();
			}
			catch(PDOException $e) {
				SystemLog::add($e->getMessage(), SystemLog::WARNING);
			}
		}

		// Catch-all result
		return FALSE;
	}

	/*
	# @method bool uninstall()
	#
	# Uninstall the extension.
	*/
	public function uninstall() {

		// Get DB connection
		try {
			$DB = Database::getConnection();
		}
		catch(Exception $e) {
			SystemLog::add('This extension requires a database. ('.$e->getMessage().')', SystemLog::WARNING);
			return FALSE;
		}

		// Table definitions
		$tables = array('acl_repo', 'acl_role', 'acl_resource', 'acl_role_member', 'acl_type', 'acl_entry');

		// Create tables
		try {
			$DB->beginTransaction();
			foreach($tables as $table) {
				if(!ModelManager::sqlQuery('DROP TABLE IF EXISTS '.$table)) {
					throw new PDOException("Invalid SQL");
				}
			}
			$DB->commit();
			return TRUE;
		}
		catch(PDOException $e) {
			SystemLog::add($e->getMessage(), SystemLog::WARNING);
			try {
				$DB->rollBack();
			}
			catch(PDOException $e) {
				SystemLog::add($e->getMessage(), SystemLog::WARNING);
			}
		}

		// Catch-all result
		return FALSE;
	}

	/*
	# @method bool upgrade()
	#
	# Upgrade the extension.
	*/
	public function upgrade() {
	}

	/*
	# @method bool init()
	#
	# Perform some initialisation routines.
	*/
	public function init() {

		// Initialise dependancies
		if(!$this->initDependancies()) {
			return FALSE;
		}

		// Define some config settings in the [ext.acl] scope.
		Config::set('ext.acl.dir.controllers', dirname(__FILE__).'/controllers');
		Config::set('ext.acl.dir.models', dirname(__FILE__).'/models');
		Config::set('ext.acl.dir.modelManagers', dirname(__FILE__).'/models/managers');
		Config::set('ext.acl.dir.views', dirname(__FILE__).'/views');

		// Add class search paths
		BuanAutoLoader::addClassPath(dirname(__FILE__).'/lib');
		BuanAutoLoader::addClassPath(Config::get('ext.acl.dir.models'));
		BuanAutoLoader::addClassPath(Config::get('ext.acl.dir.modelManagers'));

		// Define Model relationships
		ModelRelation::define("AclRepo(1):AclRole(M)");
		ModelRelation::define("AclRepo(1):AclResource(M)");
		ModelRelation::define("AclRole(1):AclEntry(M):AclResource(1)");
		ModelRelation::define("AclResource(1):AclResource.parent_id(M)");
		ModelRelation::define("AclRole(1):AclRoleMember.parent_id.acl_role_id(M):AclRole(1)");
		ModelRelation::define("AclRepo(1):AclType(M)");
		ModelRelation::define("AclType(1):AclRole(M)");
		ModelRelation::define("AclType(1):AclResource(M)");

		// Result
		return TRUE;
	}

	/*
	# @method void defineRoleModels( $modelName0, $modelName1, ... )
	# $modelNameX	= Model name
	#
	# This method will define a 1:1 relationship between AclRole and all the
	# models specified.
	*/
	public function defineRoleModels() {
		$modelNames = func_get_args();
		foreach($modelNames as $modelName) {
			ModelRelation::define("AclRole(1):{$modelName}(1)");
		}
	}

	/*
	# @method void defineResourceModels( $modelName0, $modelName1, ... )
	# $modelNameX	= Model name
	#
	# This method will define a 1:1 relationship between AclResource and all the
	# models specified.
	*/
	public function defineResourceModels() {
		$modelNames = func_get_args();
		foreach($modelNames as $modelName) {
			ModelRelation::define("AclResource(1):{$modelName}(1)");
		}
	}
}
?>