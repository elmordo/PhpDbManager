<?php

class PDM_RevisionController extends PDM_AbstractController
{

    public function createAction(array $params)
    {
        // generate new file name
        $user = trim(shell_exec("whoami"));

        // generate base file name
        $baseName = sprintf("%s_%s", date("Ymd_Hms"), $user);

        $revisionManager = $this->createManager();
        $unappliedHeads = $revisionManager->getNotAppliedHeads();

        if ($unappliedHeads)
        {
            echo "There some unapplied changes - cannot crete new revision." . PHP_EOL;
            echo "unapplied changes are: " . PHP_EOL;

            foreach ($unappliedHeads as $head)
                echo $head . PHP_EOL;

            return;
        }

        $revision = $revisionManager->createRevision($baseName, $user);

        $revisionManager->save();

        echo "Revision was created." . PHP_EOL;
        echo "Revision name is: '$baseName'" . PHP_EOL;
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
