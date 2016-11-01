<?php
/**
* $Id$
*
* @package Buan
*/
namespace Buan;
interface IExtension {

	/**
	* Perform some configuration. Refer to the individual extension's
	* documentation to discover what arguments are available in this method.
	*
	* The arguments defined in the extension's ::configure() method must have
	* default values in order to maintain compatibility with this interface
	* declaration.
	*/
	public function configure();
}
?>