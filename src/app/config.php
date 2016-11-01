<?php
/**
* $Id$
*
* Application configuration.
*
* This script is executed prior to anything else so it's a good place to ...
* - Define application-wide settings through Config::set()
* - Add all necessary class paths
* - Define some database connection through Database::addConnection()
* - Define Model relationships through ModelRelation::define()
* - Start a session
*/

// Dependancies
use Buan\AutoLoader;
use Buan\Config;
use Buan\Database;
use Buan\ModelRelation;

/*
# Global config variables.
# The 'app' namespace has been set aside for all application-specific settings.
#
# List of variables used by the core:
# string app.command.default	= Default command if none is specified in the URL
# string app.command.parameter	= $_REQUEST parameter that stores the command
# string app.command.urlPrefix	= Prefix applied to commands passed via the URL
# string app.dir.controllers	= Abs. path to Folder containing Controller classes
# array  app.dir.ignored		= List of folders that are ignored by iterating
# string app.docRoot			= Abs. path of application's public document root
# string app.domain				= Fully qualifed domain on which app is running
# string app.password			= Password to access core Buan interfaces
# string app.urlRoot			= Rel. path from docRoot to application index.php
#
# SECURITY NOTE:
# One of the first things you should do when setting up your site is to change
# the "app.password" setting.
#
# MOD_REWRITE:
# If you do not have mod_rewrite available on your apache installation then set
# the [app.command.urlPrefix] config variable to "/index.php"
*/
$__pwd = dirname(dirname(__FILE__));
Config::set('app.command.default', '/core');
Config::set('app.command.parameter', 'do');
Config::set('app.command.urlPrefix', '');
Config::set('app.dir.controllers', "{$__pwd}/app/controllers");
Config::set('app.dir.ignored', array('.', '..', '.svn', 'cvs'));
Config::set('app.docRoot', "{$__pwd}");
Config::set('app.domain', isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
Config::set('app.password', 'admin');
Config::set('app.urlRoot', '');

/*
# Model and ModelManager class paths
# If you use Models and ModelManagers then add their locations to the global
# class path.
*/
AutoLoader::addClassPath("{$__pwd}/models");
AutoLoader::addClassPath("{$__pwd}/models/managers");

/*
# Database connections.
# You may add any number of database connections using Database::addConnection().
# If you are going to use databases at all, you MUST define one named "default".
#
# See documentation for "Database::addConnection()".
*/

/*
# Model relationships.
# Define all the relationships between your Models here.
#
# Sess documentation for "ModelRelation::define()".
*/

/*
# Custom setup
*/

/*
# Clean up
*/
unset($__pwd);
?>