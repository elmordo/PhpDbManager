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
            echo "Controller: " . $controllerName . PHP_EOL . PHP_EOL;

            $actions = $controller->getActions();

            foreach ($actions as $actionName)
            {
                echo $actionName . " - " . $controller->getActionDescription($actionName) . PHP_EOL;
            }

            return;
        }

        $actionName = array_shift($parameters);
        $controller->callAction($actionName, $parameters);
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