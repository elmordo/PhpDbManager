<?php

class PDM_RevisionController extends PDM_AbstractController
{

    public function createAction(array $params)
    {
        // generate new file name
        $user = trim(shell_exec("whoami"));

        // generate base file name
        $baseName = sprintf("%s_%s", date("Ymd_His"), $user);

        $revisionManager = $this->getSL()->get("revision_manager");
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

        $this->getSL()->get("settings")->save();

        echo "Revision was created." . PHP_EOL;
        echo "Revision name is: '$baseName'" . PHP_EOL;
    }

    public function rescanAction(array $params)
    {
        $manager = $this->getSL()->get("revision_manager");
        $settings = $this->getSL()->get("settings");

        $oldRevisions = $settings->revisions;
        $manager->rescanDirectory();
        $newRevisions = $settings->revisions;

        if ($oldRevisions === $newRevisions)
        {
            echo _("No new revision found") . PHP_EOL;
        }
        else
        {
            echo _("New revisions found:") . PHP_EOL;

            foreach ($newRevisions as $revisionName)
            {
                if (!in_array($revisionName, $oldRevisions))
                {
                    echo $revisionName . PHP_EOL;
                }
            }
        }

        $this->getSL()->get("settings")->save();
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
        return [ "create", "rescan" ];
    }

    /**
     * return information about action
     * @param  string $actionName name of action
     * @return string             action description
     */
    public function getActionDescription($actionName)
    {
        switch ($actionName) {
            case "create":
            return "Create new revision";

            case "rescan":
            return "Scan revision directory for new revisions";

            default:
            throw new \DomainException("Action '$actionName' is not supported");
        }
    }

}
