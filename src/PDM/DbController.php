<?php


class PDM_DbController extends PDM_AbstractController
{

    public function updateAction(array $params)
    {
        $manager = $this->getSL()->get("revision_manager");
        $heads = $manager->getNotAppliedHeads();

        switch (count($heads))
        {
            case 0:
            echo "No revision to apply" . PHP_EOL;
            return;

            case 1:
            break;

            default:
            echo "There is more than one head: " . PHP_EOL;
            foreach ($heads as $head)
            {
                echo $head . PHP_EOL;
            }

            echo PHP_EOL;
            echo "Do a merge first" . PHP_EOL;
            return;
        }


        $manager->updateTo($heads[0]);
        $manager->save();
    }

    public function revertAction(array $params)
    {
        # code...
    }

    public function getDescription()
    {
        return "Manage database actions (apply and revert changes)";
    }

    public function getActions()
    {
        return [ "update", "revert" ];
    }

    public function getActionDescription($actionName)
    {
        switch ($actionName)
        {

            case "update":
            return "Apply changes to latest revision";

            case "revert":
            return "Do nothing yet";

            default:
            throw new DomainException("Action '$actionName' does not exsits", 1);

        }
    }

}
