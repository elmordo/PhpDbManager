<?php

interface PDM_ControllerInterface
{

    /**
     * return description of whole controller
     * @return string info
     */
    function getDescription();

    /**
     * return set of available actions
     * @return array set of actions
     */
    function getActions();

    /**
     * return information about action
     * @param  string $actionName name of action
     * @return string             action description
     */
    function getActionDescription($actionName);

    /**
     * return action messages
     * @return array set of messages
     */
    function getMessages();

    /**
     * call action
     * @param  string $actionName name of action
     * @param  array  $params     parameters
     * @return bool               action result
     */
    function callAction($actionName, array $params);

}