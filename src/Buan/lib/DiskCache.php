<?php
/**
 * Container for all Cache classes.
 *
 * @package Buan
 */
namespace Buan;

class DiskCache implements ICache
{

    /**
     * Directory to which cache files will be written.
     *
     * @var string
     */
    private $dir;

    /**
     * Throws an Exception if the directory is not valid.
     *
     * @param string $dir Directory to which cache files will be written
     * @throws Exception
     */
    public function __construct($dir)
    {

        // If the directory doesn't exists, try to create it. Otherwise throw an
        // exception
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new Exception("Cache target directory is missing: {$dir}");
            }
        } else {
            if (!is_writable($dir)) {
                throw new Exception("Cache target directory is not writable: {$dir}");
            }
        }
        $this->dir = $dir;
    }

    public function expire($key)
    {
        $hash = md5($key);
        return file_exists("{$this->dir}/{$hash}.cache") ? unlink("{$this->dir}/{$hash}.cache") : false;
    }

    /**
     * Load data from the cache.
     *
     * @param string $key Storage key
     * @return mixed|FALSE
     */
    public function get($key)
    {
        $hash = md5($key);
        return file_exists("{$this->dir}/{$hash}.cache") ? unserialize(file_get_contents("{$this->dir}/{$hash}.cache")) : false;
    }

    /**
     * Test if an object has expired.
     *
     * @param string $key Key
     * @param int $time
     * @return bool
     */
    public function hasExpired($key, $time = null)
    {
        $t = $this->readExpiryTable();
        $time = $time === null ? time() : $time;
        return empty($t[$key]) ? false : $t[$key] <= $time;
    }

    /**
     * Reads the contents of the expiry table and returns.
     *
     * @param array
     * @return array
     */
    private function readExpiryTable()
    {
        $et = "{$this->dir}/expirytable";
        $t = file_exists($et) ? file_get_contents($et) : '';
        return empty($t) ? [] : (array) json_decode($t);
    }

    /**
     * Store an object in the cache against the given key.
     *
     * @param string $key Key
     * @param mixed $data Data to store
     * @return bool
     */
    public function set($key, $data)
    {
        $hash = md5($key);
        if (!file_put_contents("{$this->dir}/{$hash}.cache", serialize($data))) {
            return false;
        }
        return true;
    }

    /**
     * Set the expire time on an object.
     *
     * @param string Object key
     * @param int Object will expire on this timestamp date. Use 0 for no expiry
     * @param bool
     * @return bool
     */
    public function setExpiry($key, $expire = 0)
    {

        // Load expiry table
        $t = $this->readExpiryTable();
        $t[$key] = $expire;
        return $this->writeExpiryTable($t);
    }

    /**
     * Writes expiry data to the table.
     *
     * @param array $data Expiry data
     * @return bool
     */
    public function writeExpiryTable($data)
    {

        // Create file
        $et = "{$this->dir}/expirytable";
        if (!file_exists($et)) {
            if (!touch($et) || !chmod($et, 0777)) {
                return false;
            }
        }

        // Write
        if (!file_put_contents($et, json_encode($data))) {
            return false;
        }
        return true;
    }
}
