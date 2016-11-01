<?php
/**
 * @package Buan
 */
namespace Buan;

use \PDO;

class Database
{

    /**
     * Stores information about each available connection.
     *
     * @var array
     */
    static $connections = [];

    /**
     * Stores an array of open DB connection resources. Indexed by the name of the
     * connection.
     *
     * @var array
     */
    static $openConnections = [];

    /**
     * Adds the new connection information to the list of available connections.
     * $connection is an array containing the elements:
     *    string name            = Name of the connection (eg. 'default')
     *    string driver        = PDO driver to be used (eg. 'mysql')
     *    string dsn            = Connection string
     *    string username    = Username
     *    string password    = Password
     *    string host            = Where the database server is located (eg. 'localhost')
     *    string database    = Name of the actual database
     *
     * If you use 'dsn', then you don't need 'host' and 'database', and vice-versa.
     *
     * @param array Database connection information
     * @return void
     */
    static public function addConnection($connection)
    {

        // Store connection information
        self::$connections[$connection['name']] = $connection;
    }

    /**
     * Returns information about the specified connection.
     *
     * @param string $connectionName Connection name
     * @return array
     */
    static public function getConnectionInfo($connectionName = 'default')
    {

        // Result
        return isset(self::$connections[$connectionName]) ? self::$connections[$connectionName] : [];
    }

    /**
     * Returns a database connection resource (PDO) or FALSE and a thrown
     * exception if no such connection exists, or could not be achieved.
     *
     * @param string $connectionName Name of connection to retrieve, or "default" if not specified
     * @return false|PdoWrapper
     * @throws Exception
     */
    static public function getConnection($connectionName = 'default')
    {

        // Check that the connection exists
        if (!isset(self::$connections[$connectionName])) {
            throw new Exception('The Database connection "' . $connectionName . '" does not exist.');
        }
        $connection = self::$connections[$connectionName];

        // If the requested connection is already open then return that connection
        // resource
        if (isset(self::$openConnections[$connection['name']])) {
            return self::$openConnections[$connection['name']];
        }

        // Check that the PDO extension has been loaded into PHP
        // We'll assume, for reasons of good practice, that extensions can only be
        // loaded via the "extension" directive in "php.ini"
        if (!in_array($connection['driver'], PDO::getAvailableDrivers())) {

            // Halt
            throw new Exception('Required PDO driver "pdo_' . $connection['driver'] . '" has not been enabled in php.ini');
        }

        // Establish a connection with the database
        try {
            // Construct DSN if it hasn't been explicitly defined in $connection
            if (isset($connection['dsn'])) {
                if (preg_match("/dbname=([a-z0-9_]+)?$/i", $connection['dsn'], $m)) {
                    $connection['database'] = $m[1];
                }
            } else {
                if ($connection['driver'] == 'mysql') {
                    $connection['dsn'] = 'mysql:host=' . $connection['host'] . ';dbname=' . $connection['database'];
                } else {
                    if ($connection['driver'] == 'sqlite') {
                        if (is_dir(Config::get('app.dir.temp'))) {
                            $connection['dsn'] = 'sqlite:' . Config::get('app.dir.temp') . '/' . $connection['database'] . '.db';
                        } else {
                            throw new Exception('Invalid folder defined in [app.dir.temp]');
                        }
                    } else {
                        throw new Exception('Database DSN cannot be resolved.');
                    }
                }
            }
            self::$connections[$connectionName] = $connection;

            // Attempt connection
            $connResource = new PdoWrapper($connection['dsn'], @$connection['username'], @$connection['password'], @$connection['options']);

            // Set PDO attributes
            $connResource->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            if ($connection['driver'] == 'mysql') {
                $connResource->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);    // see http://bugs.php.net/bug.php?id=35793
            }
        } catch (Exception $e) {

            // Log
            throw new Exception('Failed to establish "' . $connection['name'] . '" connection: ' . $e->getMessage());
        }

        // Store the resource in memory and return it
        self::$openConnections[$connection['name']] = $connResource;
        return $connResource;
    }

    /**
     * Returns a database connection resource (PDO).
     *
     * @param Model $model If given, the connection used by this Model is returned
     * @return PdoWrapper
     * @throws Exception
     */
    static public function getConnectionByModel($model = null)
    {

        // Determine which connection $model uses
        $connectionName = is_null($model) || is_null($model->getDbConnectionName()) ? 'default' : $model->getDbConnectionName();

        // Return the connection
        try {
            return self::getConnection($connectionName);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Closes the db connection and releases the resource associated with it.
     *
     * @param string $connectionName Name of connection
     * @return bool
     */
    static public function closeConnection($connectionName)
    {
        self::$connections[$connectionName] = null;
        unset(self::$connections[$connectionName]);
        return true;
    }
}

?>