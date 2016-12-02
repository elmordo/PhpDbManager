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

    static public function getDefault()
    {
        return self::$defaultInstance;
    }

    public function set($name, $instance)
    {
        $this->services[$name] = $instance;
    }

    public function get($name)
    {
        if (!isset($this->services[$name]))
        {
            throw Exception("Service is not registered");
        }

        return $this->services[$name];
    }

}