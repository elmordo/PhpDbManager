<?php

class PDM_RevisionManager extends PDM_Injectable
{

    const CONFIG_FILE = "pdm.json";

    const SUFFIX_APPLY = "_up.sql";
    const SUFFIX_REVERT = "_down.sql";
    const SUFFIX_INFO = ".json";

    const STATEMENT_DELIMITER = "-- EOS";

    /**
     * set of revision instances
     * key is revision name
     * value is instance
     * @var array
     */
    private $revisionInstances = array();

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
     * initialize instance
     * @param string $path path to directory with revisions
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * create new revision
     * @param  string $revisionName name of revision
     * @param  string $owner        name of owner
     * @return PDM_Revision         instance of new revision
     */
    public function createRevision($revisionName, $owner)
    {
        parent::__construct();

        // create files
        $settings = $this->getSL()->get("settings");
        $basePath = $settings->getDirectory() . "/" . $revisionName;

        // empty SQL files
        touch($basePath . self::SUFFIX_APPLY);
        touch($basePath . self::SUFFIX_REVERT);

        // info
        $info = [ "owner" => $owner, "created_at" => time(), "created_at_hr" => date("c"), "parents" => [] ];

        // test parent
        if ($settings->currentRevision)
        {
            $info["parents"][] = $settings->currentRevision;
            $this->revisionInstances[$settings->currentRevision]
                ->getInfo()->children[] = $revisionName;
        }

        file_put_contents($basePath . self::SUFFIX_INFO, json_encode($info));

        // create revision
        $revision = new PDM_Revision($this->path, $revisionName);
        $revision->getInfo()->setFromArray($info);
        $this->revisionInstances[$revisionName] = $revision;
        $settings->revisions[] = $revisionName;

        return $revision;
    }

    public function reapplyCurrentRevision()
    {
        $settings = $this->getSL()->get("settings");
        $connection = $this->getSL()->get("db");
        $revision = $this->getCurrentRevision();

        if ($revision !== null)
        {
            $parents = $revision->getInfo()->parents;

            if (count($parents) > 1)
            {
                echo "Unable to reapply merge revision" . PHP_EOL;
                return;
            }

            // reapply data
            $dir = $settings->getDirectory();
            $name = $revision->getRevisionName();

            $revertFile = sprintf("%s/%s%s", $dir, $name, self::SUFFIX_REVERT);
            $applyFile = sprintf("%s/%s%s", $dir, $name, self::SUFFIX_APPLY);

            $this->applyFile($revertFile, $connection, true);

            if (!$this->applyFile($applyFile, $connection))
            {
                // reapply failed - set parent of current revision as new current revision
                echo "Error in reapplying revision " . $name .
                    ". Parent of this revision will be current revision now" . PHP_EOL;
                $settings->currentRevision = $parents ? $parents[0] : null;
            }
        }
        else
        {
            echo "No revision to reapply" . PHP_EOL;
        }
    }

    public function updateTo($revision)
    {
        $settings = $this->getSL()->get("settings");
        $revisions = $this->getRevisionApplyOrder(
            $settings->currentRevision, $revision);

        $connection = $this->getSL()->get("db");
        $connection->beginTransaction();
        $revert = false;
        $revisionDir = $settings->getDirectory();

        try
        {
            for ($i = 0; $i < count($revisions); ++$i)
            {
                // get revision
                $revisionName = $revisions[$i];
                $currentRevision = $this->revisionInstances[$revisionName];

                $updateFileName = $revisionDir . "/" . $revisionName . self::SUFFIX_APPLY;

                if (!$this->applyFile($updateFileName, $connection))
                {
                    echo "Error when processing file '$updateFileName'" . PHP_EOL;
                    throw new \Exception("Some error happend", 1);
                }
            }

            $settings->currentRevision = $revision;
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
     * refresh manager data
     */
    public function reload()
    {
        $settings = $this->getSL()->get("settings");
        $this->reloadRevisions($settings);
    }

    public function rescanDirectory()
    {
        // refresh file list
        $settings = $this->getSL()->get("settings");
        $dir = dir($settings->getDirectory());

        while ($fileName = $dir->read())
        {
            // extract revision name
            try
            {
                $revisionName = $this->extractRevisionName($fileName, $settings->revisionPatterns);

                if (!in_array($revisionName, $settings->revisions))
                    $settings->revisions[] = $revisionName;
            }
            catch (Exception $e)
            {
                // nothing to do
            }
        }
    }

    public function getNotAppliedHeads()
    {
        $heads = array();
        $currentRevision = $this->getSL()->get("settings")->currentRevision;

        foreach ($this->heads as $head)
        {
            if ($currentRevision != $head)
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
        $currentRevision = $this->getSL()->get("settings")->currentRevision;
        return is_null($currentRevision) ? null : $this->revisionInstances[$currentRevision];
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
     * initialize target directory
     * if directory is initialized, config files are rewriten
     */
    public function initializeDirectory()
    {
        // initialize config file
        $this->settings = new PDM_Settings();
        $this->save();

        echo "Directory " . $this->path . " is initialized. Please update your CMS ignore file" . PHP_EOL;
        echo "Update database connection settings in " . self::CONFIG_FILE . "." . PHP_EOL;
    }

    /**
     * reload revision info from list of revisions
     */
    private function reloadRevisions(PDM_Settings $settings)
    {
        // reset data and load items
        $this->revisionInstances = array();
        $directory = $settings->getDirectory();

        foreach ($settings->revisions as $revisionName)
        {
            // create and register revision
            $revision = new PDM_Revision($directory, $revisionName);

            $this->revisionInstances[$revisionName] = $revision;

            // test filles
            $basePath = $directory . "/" . $revisionName;

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

    private function applyFile($fileName, $connection, $force=false)
    {
        $retVal = true;

        if (!is_file($fileName))
        {
            printf("File '%s' was not found" . PHP_EOL, $fileName);
            return false;
        }

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

    public function extractRevisionName($fileName, array $patterns)
    {
        foreach ($patterns as $pattern)
        {
            $match = [];

            if (preg_match($pattern, $fileName, $match))
            {
                return $match[1];
            }
        }

        throw new Exception("Filename does not match");
    }

}