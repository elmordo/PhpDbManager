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

// create and setup service locator
$locator = new PDM_ServiceLocator();

// load configuration
$settings = PDM_Settings::loadFromFile("./pdm.json");
$locator->set("settings", $settings);

// connect to database
$connection = new \PDO($settings->dbParams["dsn"],
    $settings->dbParams["username"], $settings->dbParams["password"],
    [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION ]);
$locator->set("db", $connection);

// create dispatcher and register it into service locator
$dispatcher = new PDM_Dispatcher();
$locator->set("dispatcher", $dispatcher);

$dispatcher->registerController("revision", new PDM_RevisionController());
$dispatcher->registerController("db", new PDM_DbController());
$dispatcher->registerController("project", new PDM_ProjectController());

$args = getArguments();

$dispatcher->dispatch($args);
