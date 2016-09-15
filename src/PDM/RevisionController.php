<?php

class PDM_RevisionController extends PDM_AbstractController
{

    public function createAction(array $params)
    {
        // generate new file name
        $user = system("whoami");

        if (!$user)
        {
            echo "kunda";
        }
    }

    /**
     * return description of whole controller
     * @return string info
     */
    public function getDescription()
    {
        return _("Provides revision manipulation methods");
    }

    /**
     * return set of available actions
     * @return array set of actions
     */
    public function getActions()
    {
        return [ "create" ];
    }

    /**
     * return information about action
     * @param  string $actionName name of action
     * @return string             action description
     */
    public function getActionDescription($actionName)
    {
        switch ($actionName) {
            case 'create':
            return "Create new revision";

            default:
            throw new \DomainException("Action '$actionName' is not supported");
        }
    }

}
