<?php

class PDM_RevisionManager
{

    const CONFIG_FILE = "pdm.json";

    const SUFFIX_APPLY = "_up.sql";
    const SUFFIX_REVERT = "_down.sql";
    const SUFFIX_INFO = ".json";

    const STATEMENT_DELIMITER = "-- EOS";

    /**
     * list of known revisions (only names)
     * @var array
     */
    private $revisions = array();

    /**
     * set of revision instances
     * key is revision name
     * value is instance
     * @var array
     */
    private $revisionInstances = array();

    /**
     * set of regular patterns to match revision files
     * @var array
     */
    private $revisionPatterns = array();

    /**
     * current revision where database is
     * @var string
     */
    private $currentRevision = null;

    /**
     * path to directory where revisions are stored
     * @var string
     */
    private $path;

    /**
     * set of roots - revisions without any parent
     * @var array
     */
    private $roots = array();

    /**
     * list of heads - revision without any child
     * @var array
     */
    private $heads = array();

    /**
     * parameters for databse connection
     * @var array
     */
    private $dbParams = [];

    /**
     * connection to DB
     * @var PDO
     */
    private $dbConnection = null;

    /**
     * initialize instance
     * @param string $path path to directory with revisions
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    /**
     * create new revision
     * @param  string $revisionName name of revision
     * @param  string $owner        name of owner
     * @return PDM_Revision         instance of new revision
     */
    public function createRevision($revisionName, $owner)
    {
        // create files
        $basePath = $this->path . $revisionName;

        // empty SQL files
        touch($basePath . self::SUFFIX_APPLY);
        touch($basePath . self::SUFFIX_REVERT);

        // info
        $info = [ "owner" => $owner, "created_at" => time(), "created_at_hr" => date("c"), "parents" => [] ];

        // test parent
        if ($this->currentRevision)
        {
            $info["parents"][] = $this->currentRevision;
            $this->revisionInstances[$this->currentRevision]
                ->getInfo()->children[] = $revisionName;
        }

        file_put_contents($basePath . self::SUFFIX_INFO, json_encode($info));

        // create revision
        $revision = new PDM_Revision($this->path, $revisionName);
        $revision->getInfo()->setFromArray($info);
        $this->revisionInstances[$revisionName] = $revision;
        $this->revisions[] = $revisionName;

        return $revision;
    }

    /**
     * return databse PDO connection
     * @return PDO connection to database
     */
    public function getConnection()
    {
        if (!$this->dbConnection)
        {
            $this->dbConnection = new \PDO($this->dbParams["dsn"],
                $this->dbParams["username"], $this->dbParams["password"],
                [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION ]);
        }

        return $this->dbConnection;
    }

    public function updateTo($revision)
    {
        $revisions = $this->getRevisionApplyOrder($this->currentRevision,
            $revision);

        $connection = $this->getConnection();
        $connection->beginTransaction();
        $revert = false;

        try
        {
            for ($i = 0; $i < count($revisions); ++$i)
            {
                // get revision
                $revisionName = $revisions[$i];
                $currentRevision = $this->revisionInstances[$revisionName];

                $updateFileName = $this->path . $revisionName . self::SUFFIX_APPLY;

                if (!$this->applyFile($updateFileName, $connection))
                {
                    echo "Error when processing file '$updateFileName'" . PHP_EOL;
                    throw new \Exception("Some error happend", 1);
                }
            }

            $this->currentRevision = $revision;
            $connection->commit();
        }
        catch (\Exception $e)
        {
            $revert = true;
            echo "Error: " . $e->getMessage() . PHP_EOL;
            $connection->rollback();
        }

        if ($revert)
        {
            echo "Applying revert file" . PHP_EOL;
            // some error ocours - revert db to initial state
            for (; $i >= 0; --$i)
            {
                // get revision
                $revisionName = $revisions[$i];
                $currentRevision = $this->revisionInstances[$revisionName];

                $revertFileName = $this->path . $revisionName . self::SUFFIX_REVERT;
                $this->applyFile($revertFileName, $connection, true);
            }
        }

        return !$revert;
    }

    /**
     * reload manager data
     * @param  boolean $autoInit initialize directory if config
     *                            file does not exists
     */
    public function reload($autoInit=true)
    {
        // load config file
        if (!file_exists($this->path . self::CONFIG_FILE))
        {
            if ($autoInit)
            {
                $this->initializeDirectory();
            }
            else
            {
                throw new PDM_Exception("Directory is not initialized");
            }
        }

        // load config file
        $config = json_decode(file_get_contents(
            $this->path . self::CONFIG_FILE), true);

        $this->revisions = $config["revisions"];
        $this->revisionPatterns = $config["revision_patterns"];
        $this->currentRevision = $config["current_revision"];
        $this->dbParams = $config["db_params"];

        $this->reloadRevisions();
    }

    public function rescanDirectory()
    {
        // refresh file list
        $dir = dir($this->path);

        while ($fileName = $dir->read())
        {
            // extract revision name
            $revisionName = $this->extractRevisionName($fileName);
        }
    }

    public function getNotAppliedHeads()
    {
        $heads = array();

        foreach ($this->heads as $head)
        {
            if ($this->currentRevision != $head)
            {
                $heads[] = $head;
            }
        }

        return $heads;
    }

    /**
     * return current revision
     * @return string current revision name or NULL if no revision is applied
     */
    public function getCurrentRevision()
    {
        return $this->currentRevision;
    }

    /**
     * return set of revision name
     * @return array set of revisions
     */
    public function getRevisions()
    {
        return $this->revisions;
    }

    public function getRevisionApplyOrder($start, $end)
    {
        $reverseRevisions = [];

        $currentRevision = $end;

        while ($currentRevision && $currentRevision != $start)
        {
            $reverseRevisions[] = $currentRevision;
            $instance = $this->revisionInstances[$currentRevision];

            $parents = $instance->getInfo()->parents;

            if (count($parents) > 1)
                throw new PDM_Exception("Merges are not implemented");
            elseif (!$parents)
                $currentRevision = null;
            else
                $currentRevision = $parents[0];
        }

        return array_reverse($reverseRevisions);
    }

    /**
     * save new information into local config file
     */
    public function save()
    {
        // genrate config data
        $data = [
            "current_revision" => $this->currentRevision,
            "revisions" => $this->revisions,
            "revision_patterns" => $this->revisionPatterns,
            "db_params" => $this->dbParams,
        ];

        file_put_contents($this->path . self::CONFIG_FILE, json_encode($data,
            JSON_PRETTY_PRINT));
    }

    /**
     * initialize target directory
     * if directory is initialized, config files are rewriten
     */
    public function initializeDirectory()
    {
        // initialize config file
        $config = [
            "revisions" => [],
            "revision_patterns" => [
                "/(^[0-9]{8}_[0-9]{6}_[a-zA-Z0-9]+)/"
            ],
            "current_revision" => null,
            "db_params" => [
                "dsn" => "mysql:host=localhost;dbname=guitarian",
                "username" => "root",
                "password" => ""
            ],
        ];

        file_put_contents($this->path . self::CONFIG_FILE, json_encode($config, JSON_PRETTY_PRINT));

        echo "Directory " . $this->path . " is initialized. Please update your CMS ignore file" . PHP_EOL;
        echo "Update database connection settings in " . self::CONFIG_FILE . "." . PHP_EOL;
    }

    /**
     * reload revision info from list of revisions
     */
    private function reloadRevisions()
    {
        // reset data and load items
        $this->revisionInstances = array();

        foreach ($this->revisions as $revisionName)
        {
            // create and register revision
            $revision = new PDM_Revision($this->path, $revisionName);

            $this->revisionInstances[$revisionName] = $revision;

            // test filles
            $basePath = $this->path . $revisionName;

            if (is_file($basePath . self::SUFFIX_APPLY))
                $revision->setFoundStatus(PDM_Revision::FILE_ASQL, true);

            if (is_file($basePath . self::SUFFIX_REVERT))
                $revision->setFoundStatus(PDM_Revision::FILE_RSQL, true);

            if (is_file($basePath . self::SUFFIX_INFO))
            {
                $revision->setFoundStatus(PDM_Revision::FILE_INFO, true);

                // load info
                $info = json_decode(file_get_contents(
                    $basePath . self::SUFFIX_INFO), true);

                $revision->getInfo()->setFromArray($info);
            }
        }

        // setup children
        foreach ($this->revisionInstances as $name => $revision)
        {
            $parents = $revision->getInfo()->parents;

            foreach ($parents as $parentName)
            {
                $this->revisionInstances[$parentName]
                    ->getInfo()->children[] = $name;
            }
        }

        // setup roots and heads
        $this->setupRoots();
        $this->setupHeads();
    }

    /**
     * find roots
     */
    private function setupRoots()
    {
        foreach ($this->revisionInstances as $name => $revision)
        {
            if (!$revision->getInfo()->parents)
            {
                $this->roots[] = $revision->getRevisionName();
            }
        }
    }

    /**
     * find heads
     */
    private function setupHeads()
    {
        foreach ($this->revisionInstances as $name => $revision)
        {
            if (!$revision->getInfo()->children)
            {
                $this->heads[] = $revision->getRevisionName();
            }
        }
    }

    /**
     * create instance of manager
     * @param  string $path path to directory with revisions
     * @param  boolean $loadData auto load data
     * @return PDM_RevisionManager       manager instance
     */
    public static function createManager($path, $loadData=true)
    {
        $path = PDM_Utils::normalizeDirPath($path);

        $dir = dir($path);

        while ($fileName = $dir->read())
        {
            $fullName = $path . $fileName;
        }

        $manager = new self($path);

        if ($loadData)
        {
            $manager->reload();
        }

        return $manager;
    }

    private function applyFile($fileName, $connection, $force=false)
    {
        $retVal = true;

        // read file and split it into statements
        $sql = file_get_contents($fileName);
        $statements = explode(self::STATEMENT_DELIMITER, $sql);

        try
        {
            foreach ($statements as $statement)
            {
                try
                {
                    echo "Executing statement: " . PHP_EOL;
                    echo $statement . PHP_EOL;
                    $connection->exec($statement);
                }
                catch (\PDOException $e)
                {
                    echo "Error: " . $e->getMessage() . PHP_EOL;

                    if ($force)
                    {
                        echo "Force mode is enabled, continuing in executing statements" . PHP_EOL;
                    }
                    else
                    {
                        $retVal = false;
                        break;
                    }
                }
            }
        }
        catch (\Exception $e)
        {
            // some cock sucking error
            echo "Unexpected error happend: " . $e->getMessage() . PHP_EOL;
            $retVal = false;
        }

        return $retVal;
    }

    public function extractRevisionName($fileName)
    {
        foreach ($this->revisionPatterns as $pattern)
        {
            $match = [];

            if (preg_match($pattern, $fileName, $match))
            {
                if (!in_array($match[1], $this->revisions))
                {
                    $this->revisions[] = $match[1];
                }
            }
        }
    }

}