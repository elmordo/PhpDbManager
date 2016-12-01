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

if (!$settings->isStored())
{
    $settings->save("./pdm.json");
    echo "Initial configuration was saved into pdm.json" . PHP_EOL;
}

$locator->set("settings", $settings);

// connect to database
try
{
    $connection = new \PDO($settings->dbParams["dsn"],
        $settings->dbParams["username"], $settings->dbParams["password"],
        [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION ]);
}
catch (PDOException $e)
{
    echo "Unable to connect to database. " .
        "Please update your settings in pdm.json file" . PHP_EOL;
    exit(1);
}

$locator->set("db", $connection);

// create dispatcher and register it into service locator
$dispatcher = new PDM_Dispatcher();
$locator->set("dispatcher", $dispatcher);

$dispatcher->registerController("revision", new PDM_RevisionController());
$dispatcher->registerController("db", new PDM_DbController());
$dispatcher->registerController("project", new PDM_ProjectController());

$args = getArguments();

$dispatcher->dispatch($args);
