<?php
/**
* @package Buan
*/
namespace Buan;
use \PDO;
class PdoStatementWrapper extends PDOStatement {

	/*
	# @property PDO $pdo
	# PDO instance.
	*/
	public $dbh;

	/*
	# @method void __construct( PDO $pdo )
	# $pdo	= PDO resource
	#
	# Construct the instance.
	*/
    protected function __construct($pdo) {

		// Store properties
        $this->pdo = $pdo;
    }
}
?>