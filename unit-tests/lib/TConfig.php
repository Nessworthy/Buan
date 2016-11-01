<?php
/*
# $Id$
*/

/*
# @class TConfig
*/
class TConfig extends Config {

	/*
	# @method mixed getProtectedProperty( string $varName )
	# $varName	= Property name
	#
	# Returns the value of the specified protected property.
	*/
	static public function getProtectedProperty($varName) {
		return self::$$varName;
	}
}
?>