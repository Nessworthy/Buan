<?php
/**
* Thin wrapper class for PDO.
* Nested transactions are emulated.
* TODO: Once nested transactions are supported, we need something here that will support them instead of emulating them.
*
* @package Buan
*/
namespace Buan;
use \PDO;
class PdoWrapper extends PDO {

	/*
	# @property array $transactions
	# Array containing information about the active transations on each DB connection.
	*/
	static $transactions = array();

	/*
	# @method BuanPdo __construct( string $dsn, [string $username], [string $password], [array $options] )
	# $dsn		= Connection string
	# $username	= Username
	# $password	= Password
	# $options	= Driver options
	#
	# Constructor.
	*/
	public function __construct($dsn, $username=NULL, $password=NULL, $options=NULL) {

		// Construct
		parent::__construct($dsn, $username, $password, $options);

		// Set attributes
		// NOTE: Cannot use a custom PdoStatement class if using a persistent
		// connection, therefore I've commented it out here, making
		// BuanPdoStatementWrapper pretty much redundant.
		//$this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('PdoStatementWrapper', array($this)));
	}

	/*
	# @method void beginTransaction()
	#
	# This wrapper ensures that only one transaction is active on a connection at any one time.
	*/
	public function beginTransaction() {

		// Vars
		$objHash = spl_object_hash($this);

		// Check if a transaction is already active on this connection and, if so, increment the nesting level count
		if(isset(self::$transactions[$objHash])) {
			self::$transactions[$objHash]['nesting']++;
			return TRUE;
		}

		// Start a transaction
		try {
			parent::beginTransaction();
			self::$transactions[$objHash] = array(
				'nesting'=>1
			);
			return TRUE;
		}
		catch(PDOException $e) {
			throw new PDOException($e->getMessage());
			return FALSE;
		}
	}

	/*
	# @method bool commit()
	#
	# Commits the current transaction.
	*/
	public function commit() {

		// Vars
		$objHash = spl_object_hash($this);

		// Commit only at nesting level 1
		if(self::$transactions[$objHash]['nesting']==1) {
			try {
				parent::commit();
				self::$transactions[$objHash]--;
				if(self::$transactions[$objHash]==0) {
					unset(self::$transactions[$objHash]);
				}
				return TRUE;
			}
			catch(PDOException $e) {
				throw new PDOException($e->getMessage());
				return FALSE;
			}
		}
		else {
			self::$transactions[$objHash]['nesting']--;
			return TRUE;
		}
	}

	/*
	# @method bool rollBack()
	#
	# Rolls back the current transaction.
	*/
	public function rollBack() {
	
		// Vars
		$objHash = spl_object_hash($this);

		// Rollback only at nesting level 1
		if(self::$transactions[$objHash]['nesting']==1) {
			try {
				parent::rollBack();
				self::$transactions[$objHash]['nesting']--;
				if(self::$transactions[$objHash]['nesting']==0) {
					unset(self::$transactions[$objHash]);
				}
				return TRUE;
			}
			catch(PDOException $e) {
				throw new PDOException($e->getMessage());
				return FALSE;
			}
		}
		else {
			self::$transactions[$objHash]['nesting']--;
			return TRUE;
		}
	}
}
?>