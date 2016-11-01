<?php
/**
 * Global event management. All global event listening and dispatching is handled
 * via this static class.
 *
 * All event names are in the "lower-hyphenated-string" format
 *
 * @package Buan
 */
namespace Buan;

abstract class GlobalEventDispatcher
{

    /**
     * Holds the instance of the EventDispatcher through which event handling will
     * be managed.
     *
     * @var EventDispatcher
     */
    private static $dispatcher = null;

    /**
     * Route all static method calls through to the $dispatcher instance
     *
     * @param string $method Method being called
     * @param array $args Arguments
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        if (self::$dispatcher === null) {
            self::$dispatcher = new EventDispatcher();
        }
        return call_user_func_array([self::$dispatcher, $method], $args);
    }
}
