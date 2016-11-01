<?php
/*
* The most basic model, leaving Buan to determine the database table name and
* primary key.
*
* @package UnitTest
*/
use Buan\Model;
class PersonModel extends Model {

	/*
	# @method string getFavouriteColour()
	#
	# Getter method to return value of "favourite_colour" field.
	*/
	public function getFavouriteColour() {
		return $this->dbData['favourite_colour'];
	}

	/*
	# @method string setFavouriteColour( string $v )
	# $v	= Colour
	#
	# Setter method to define value in "favourite_colour" field.
	# Prefixes the given value $v with "XYZ" for testing purposes.
	*/
	public function setFavouriteColour($v) {
		$this->dbData['favourite_colour'] = "XYZ{$v}";
		return TRUE;
	}
}
?>