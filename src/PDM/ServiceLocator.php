<?php

class PDM_ServiceLocator
{

    private $services = array();

    private static $defaultInstance = null;

    public function __construct()
    {
        if (self::$defaultInstance === null)
            self::$defaultInstance = $this;
    }

    public function set($name, $instance)
    {
        $this->services[$name] = $instance;
    }

    public function get($name)
    {
        if (!isset($this->services[$name]));
    }

}