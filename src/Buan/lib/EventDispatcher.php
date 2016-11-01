<?php
/**
 * If your class needs to manage events for each instance of itself, then extend
 * from this class.
 *
 * @package Buan
 */
namespace Buan;

class EventDispatcher
{

    /*
    # @property array $listeners
    # Holds all events listeners for this Object.
    # Array indices are event names, eg. "onload", "onrender", etc. and each element is an array
    # of listeners each in the following format:
    #	object|NULL 'object'	=> A class instance (if the listener is a class method) or NULL (if the listener is a global function)
    #	string 'function'	=> Name of the class method or global function name
    #	array 'arguments'	=> Array of arguments to pass to the method/function on invocation
    */
    private $listeners = [];

    /**
     * Add a listener to the specified event. Returns an object containing some
     * basic info about the listener.
     *
     * The listener can be any one of:
     *        A global function name, eg. "myFunction"
     *        A class instance method, eg. array($instance, "methodName")
     *        A static class method, eg. array("className", "methodName")
     *        A lambda function, eg. function() { ... }
     *
     * @param string $eventName Event name
     * @param callable $listener The listener
     * @return \StdClass
     */
    public function addEventListener($eventName, $listener)
    {
        $info = new \stdClass();
        $info->id = uniqid(rand());
        $this->listeners[$eventName][$info->id] = $listener;

        return $info;
    }

    /**
     * Execute all listeners on the specified event.
     *
     * Note that any additional arguments you pass to this method will be passed as
     * values and NOT as references.
     *
     * @param IasEvent Event to dispatch
     * @params mixed You can pass any number of arguments that will be passed to
     *        the listeners
     * @return void
     */
    public function dispatchEvent($event)
    {
        if (!empty($this->listeners[$event->name])) {
            $args = func_get_args();
            foreach ($this->listeners[$event->name] as $l) {
                call_user_func_array($l, $args);
            }
        }
    }

    /**
     * Determine if there are any registered listeners on the specified event.
     *
     * @param string Event name
     * @return bool
     */
    public function hasEventListeners($eventName)
    {
        return !empty($this->listeners[$eventName]);
    }

    /**
     * Remove the listener identified by the given ID.
     *
     * @param string $eventName Event name
     * @param string $listenerId Listener ID (as returned by addEventListener())
     * @result void
     */
    public function removeEventListener($eventName, $listenerId)
    {
        if (isset($this->listeners[$eventName][$listenerId])) {
            unset($this->listeners[$eventName][$listenerId]);
        }
    }
}
