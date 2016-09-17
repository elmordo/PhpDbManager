<?php

spl_autoload_register(function ($name)
    {
        $parts = explode("_", $name);

        if ($parts[0] != "PDM")
        {
            return false;
        }

        $path = __DIR__ . "/" . implode("/", $parts) . ".php";

        if (is_file($path))
        {
            include $path;
            return true;
        }

        array_shift($parts);
        $path = __DIR__ . "/" . implode("/", $parts) . ".php";

        if (is_file($path))
        {
            include $path;
            return true;
        }

        return false;
    });

function getArguments()
{
    global $argv;
    return $argv;
}

$dispatcher = new PDM_Dispatcher();
$dispatcher->registerController("revision", new PDM_RevisionController());
$dispatcher->registerController("db", new PDM_DbController());
$dispatcher->registerController("project", new PDM_ProjectController());

$args = getArguments();

$dispatcher->dispatch($args);
