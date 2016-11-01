<?php
/**
* Interface for cache classes.
*
* @package Buan
*/
namespace Buan;
interface ICache {

	/**
	* Retrieve an item from the cache. This method must ensure the cached item
	* has not yet expired before returning it.
	*
	* If no item exists at the specified key, return FALSE.
	*
	* @param string Storage key
	* @return mixed|FALSE
	*/
	public function get($key);

	/**
	* Check if a cached object has expired.
	*
	* @param string Key
	* @param mixed Time at which to test against (should default to current time)
	* @return bool
	*/
	public function hasExpired($key, $time=NULL);

	/**
	* Store a data object at the given key.
	*
	* If storage fails for some reason, it returns FALSE. Otherwise returns TRUE.
	*
	* @param string Storage key
	* @param mixed Object to store
	* @param string|int 
	* @return bool
	*/
	public function set($key, $value);

	/**
	* Set the expiry date on a cached object. Return TRUE on successful setting.
	*
	* @param string Key
	* @param mixed Date (specific to implementation)
	* @return bool
	*/
	public function setExpiry($key, $expire);

	/**
	* Unsets the cached object specified by the ket.
	*
	* @param string Storage key
	* @return bool
	*/
	public function expire($key);
}
?>