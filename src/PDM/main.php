<?php

define("PDM_CONFIG_FILENAME", "pdm.json");

if (!function_exists("_"))
{
    function _($msg) { return $msg; };
}

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


$path = getenv("PDM_PATH") ? getenv("PDM_PATH") : dirname($argv[0]);
$path = PDM_Utils::normalizeDirPath($path) . PDM_CONFIG_FILENAME;

// load configuration
$settings = PDM_Settings::loadFromFile($path);

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

$revisionManager = new PDM_RevisionManager();
$locator->set("revision_manager", $revisionManager);
$revisionManager->reload();

// create dispatcher and register it into service locator
$dispatcher = new PDM_Dispatcher();
$locator->set("dispatcher", $dispatcher);

$dispatcher->registerController("revision", new PDM_RevisionController());
$dispatcher->registerController("db", new PDM_DbController());
$dispatcher->registerController("project", new PDM_ProjectController());

$args = getArguments();

$dispatcher->dispatch($args);
