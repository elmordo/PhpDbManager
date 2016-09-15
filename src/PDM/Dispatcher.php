<?php

class PDM_Dispatcher
{

    private $controllers = array();

    public function getControllers()
    {
        return $this->controllers;
    }

    public function registerController($cmd, \PDM_ControllerInterface $controller)
    {
        $this->controllers[$cmd] = $controller;
    }

    public function dispatch(array $parameters)
    {
        # code...
    }

    /**
     * return set of supported actions
     * @return array set of supported actions
     */
    public function getAvailableActions()
    {
        return [ self::ACTION_CREATE => _("Create new revision") ];
    }

}