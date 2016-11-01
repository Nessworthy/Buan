<?php
/**
* @package UnitTest
*/
use Buan\AutoLoader;
use Buan\Config;
use Buan\Database;
use Buan\ModelRelation;

/*
# Config variables
*/
Config::set('app.command.urlPrefix', 'do');
Config::set('app.command.parameter', 'do');
Config::set('app.command.default', '');
Config::set('app.dir.controllers', dirname(dirname(__FILE__)).'/controllers');
Config::set('app.dir.ignored', array('.', '..', '.svn', 'cvs'));
Config::set('app.docRoot', dirname(dirname(__FILE__)));
Config::set('app.domain', 'localhost');
Config::set('app.password', 'password');
Config::set('app.dir.databases', dirname(dirname(__FILE__)).'/db');

AutoLoader::addClassPath(dirname(dirname(__FILE__)).'/models');
AutoLoader::addClassPath(dirname(dirname(__FILE__)).'/models/managers');

/*
# Database
*/
Database::addConnection(array(
	'name'=>'default',
	'driver'=>'sqlite',
	'dsn'=>'sqlite:'.Config::get('app.dir.databases').'/model.s3db'
));
/* Alternative for testing whilst building test on dev machine
Database::addConnection(array(
	'name'=>'default',
	'driver'=>'mysql',
	'dsn'=>'mysql:host=localhost;dbname=buan_test',
	'username'=>'root',
	'password'=>''
));*/

/*
# Model relationships
*/
ModelRelation::define('Person(1):PersonAddress(M):Address(1)');
ModelRelation::define('Person(1):Account(1)');
ModelRelation::define('Person(1):PersonAlias(M,2)');
ModelRelation::define('Person(1):PersonBook(M):Book(1)');

ModelRelation::define('Library(1):Book(M)');

ModelRelation::define('Category(1):Category(M)');

ModelRelation::define('Person(1):Friend.person_a_id.person_b_id(M):Person(1)');
?>