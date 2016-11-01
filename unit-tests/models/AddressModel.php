<?php
/*
* This model uses a composite primary key on the fields "postcode" and "houseno"
*
* @package UnitTest
*/
use Buan\Model;
class AddressModel extends Model {

	/*
	# @property string $dbTableName
	# Database table name.
	*/
	protected $dbTableName = 'address';

	/*
	# @property string $dbTablePrimaryKey
	# Primary key columns.
	*/
	protected $dbTablePrimaryKey = 'postcode,houseno';
}
?>