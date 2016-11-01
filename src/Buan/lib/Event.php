<?php
/**
 * An event wrapper.
 *
 * @package Buan
 */
namespace Buan;

class Event
{

    /**
     * Name of this event.
     *
     * @var string
     */
    public $name = null;

    /**
     * Storage for passing data within the event so it persists from one listener
     * to the next
     *
     * @var \stdClass
     */
    public $data;

    /**
     * Constructor.
     *
     * @param string $name Event name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->data = new \stdClass();
    }
}
