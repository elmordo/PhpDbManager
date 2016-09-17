<?php


abstract class PDM_AbstractController implements PDM_ControllerInterface
{

    const ACTION_POSTFIX = "Action";

    /**
     * messages from last call
     * @var array
     */
    private $messages = array();

    /**
     * return description of whole controller
     * @return string info
     */
    abstract function getDescription();

    /**
     * return set of available actions
     * @return array set of actions
     */
    abstract function getActions();

    /**
     * return information about action
     * @param  string $actionName name of action
     * @return string             action description
     */
    abstract function getActionDescription($actionName);

    /**
     * return action messages
     * @return array set of messages
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * call action
     * @param  string $actionName name of action
     * @param  array  $params     parameters
     * @return bool               action result
     */
    public function callAction($actionName, array $params)
    {
        $methodName = $actionName . self::ACTION_POSTFIX;

        if (!method_exists($this, $methodName))
        {
            throw new DomainException("Action '$actionName' is undefined");
        }

        $this->{$methodName}($params);
    }

    protected function createManager()
    {
        // get file path
        global $argv;

        $path = getenv("PDM_PATH") ? dirname($argv[0]) : __DIR__;

        $revisionManager = PDM_RevisionManager::createManager($path);

        return $revisionManager;
    }

}
