<?php
/**
* Container for all Cache classes.
*
* @package Buan
*/
namespace Buan;
class DiskCache implements ICache {

	/**
	* Directory to which cache files will be written.
	*
	* @var string
	*/
	private $dir;

	/**
	* Throws an Exception if the directory is not valid.
	*
	* @param string Directory to which cache files will be written
	* @return Buan\DiskCache
	*/
	public function __construct($dir) {

		// If the directory doesn't exists, try to create it. Otherwise throw an
		// exception
		if(!is_dir($dir)) {
			if(!mkdir($dir, 0777, TRUE)) {
				throw new Exception("Cache target directory is missing: {$dir}");
			}
		}
		else if(!is_writable($dir)) {
			throw new Exception("Cache target directory is not writable: {$dir}");
		}
		$this->dir = $dir;
	}

	public function expire($key) {
		$hash = md5($key);
		return file_exists("{$this->dir}/{$hash}.cache") ? unlink("{$this->dir}/{$hash}.cache") : FALSE;
	}

	/**
	* Load data from the cache.
	*
	* @param string Storage key
	* @return mixed|FALSE
	*/
	public function get($key) {
		$hash = md5($key);
		return file_exists("{$this->dir}/{$hash}.cache") ? unserialize(file_get_contents("{$this->dir}/{$hash}.cache")) : FALSE;
	}

	/**
	* Test if an opbject has expired.
	*
	* @param string Key
	* @return bool
	*/
	public function hasExpired($key, $time=NULL) {
		$t = $this->readExpiryTable();
		$time = $time===NULL ? time() : $time;
		return empty($t[$key]) ? FALSE : $t[$key]<=$time;
	}

	/**
	* Reads the contents of the expiry table and retrns.
	*
	* @param array
	*/
	private function readExpiryTable() {
		$et = "{$this->dir}/expirytable";
		$t = file_exists($et) ? file_get_contents($et) : '';
		return empty($t) ? array() : (array)json_decode($t);
	}

	/**
	* Store an object in the cache against the given key.
	*
	* @param string Key
	* @param mixed Data to store
	* @return bool
	*/
	public function set($key, $data) {
		$hash = md5($key);
		if(!file_put_contents("{$this->dir}/{$hash}.cache", serialize($data))) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	* Set the expire time on an object.
	*
	* @param string Object key
	* @param int Object will expire on this timestamp date. Use 0 for no expiry
	* @param bool
	*/
	public function setExpiry($key, $expire=0) {

		// Load expiry table
		$t = $this->readExpiryTable();
		$t[$key] = $expire;
		return $this->writeExpiryTable($t);
	}

	/**
	* Writes expiry data to the table.
	*
	* @param array Expiry data
	* @return bool
	*/
	public function writeExpiryTable($data) {

		// Create file
		$et = "{$this->dir}/expirytable";
		if(!file_exists($et)) {
			if(!touch($et) || !chmod($et, 0777)) {
				return FALSE;
			}
		}

		// Write
		if(!file_put_contents($et, json_encode($data))) {
			return FALSE;
		}
		return TRUE;
	}
}
?>