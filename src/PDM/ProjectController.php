<?php


class PDM_ProjectController extends PDM_AbstractController
{

    public function initAction()
    {
        $manager = $this->createManager();
        $manager->save();
    }

    public function getDescription()
    {
        return _("Provides functionality for common operations");
    }

    public function getActions()
    {
        return [ "init" ];
    }

    public function getActionDescription($actionName)
    {
        switch ($actionName)
        {
            case "init":
            return "Initialize project in current directory (do nothing if project is initialized)";

            default:
            throw new DomainException("Action '$actionName' does not exist", 1);
        }
    }

}