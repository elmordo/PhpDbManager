<?php


abstract class PDM_AbstractController
    extends PDM_Injectable implements PDM_ControllerInterface
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
        parent::__construct();

        $methodName = $actionName . self::ACTION_POSTFIX;

        if (!method_exists($this, $methodName))
        {
            throw new DomainException("Action '$actionName' is undefined");
        }

        $this->{$methodName}($params);
    }

}
