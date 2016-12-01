<?php

class PDM_Dispatcher extends PDM_Injectable
{

    private $controllers = array();

    public function getControllers()
    {
        parent::__construct();

        return $this->controllers;
    }

    public function registerController($cmd, \PDM_ControllerInterface $controller)
    {
        $this->controllers[$cmd] = $controller;
    }

    public function dispatch(array $parameters)
    {
        // remove first parameter with script path
        array_shift($parameters);

        if (!$parameters)
        {
            // param list is empty -> display help
            echo "Available commands" . PHP_EOL . PHP_EOL;

            foreach ($this->controllers as $name => $controller)
            {
                echo $name . " - " . $controller->getDescription() . PHP_EOL;
            }

            return;
        }

        $controllerName = array_shift($parameters);
        $controller = $this->controllers[$controllerName];

        if (!$parameters)
        {
            // no parameter left
            $this->printControllerHelp($controllerName);
            return;
        }

        $actionName = array_shift($parameters);

        try
        {
            $controller->callAction($actionName, $parameters);
        }
        catch (\DomainException $e)
        {
            echo "Action '$actionName' does not exists" . PHP_EOL;
            $this->printControllerHelp($controllerName);
        }
    }

    public function printControllerHelp($controllerName)
    {
        echo "Controller: " . $controllerName . PHP_EOL . PHP_EOL;
        $controller = $this->controllers[$controllerName];

        $actions = $controller->getActions();

        foreach ($actions as $actionName)
        {
            echo $actionName . " - " . $controller->getActionDescription($actionName) . PHP_EOL;
        }
    }

}