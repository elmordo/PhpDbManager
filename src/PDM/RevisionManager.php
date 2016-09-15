<?php

class PDM_RevisionManager
{

    const CONFIG_FILE = "pdm.json";

    const SUFFIX_APPLY = "_up.sql";
    const SUFFIX_REVERT = "_down.sql";
    const SUFFIX_INFO = ".json";

    /**
     * all revisions where key revision name and value is instance
     * @var array
     */
    private $revisions = array();

    /**
     * set of regular patterns to match revision files
     * @var array
     */
    private $revisionPatterns = array();

    /**
     * current revision where database is
     * @var string
     */
    private $currentHead = null;

    /**
     * path to directory where revisions are stored
     * @var string
     */
    private $path;

    public function __construct($path)
    {
        $this->path = $path;
    }

    public function createRevision($revisionName, $owner)
    {
        // create files
        $basePath = $this->path . $revisionName;

        // empty SQL files
        touch($basePath . self::SUFFIX_APPLY);
        touch($basePath . self::SUFFIX_REVERT);

        // info
        $info = [ "owner" => $owner, "created_at" => time(), "created_at_hr" => date("c"), "parents" => [] ];
        file_put_contents($basePath . self::SUFFIX_INFO, json_encode($info));

        // create revision
        $revision = new PDM_Revision($this->path, $revisionName);
        $this->revisions[$revisionName] = $revision;

        // test parent
        if ($this->currentHead)
        {
            $revision->addParent($this->currentHead);
            $this->revisions[$this->currentHead]->addChild($revisionName);
        }
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
            $this->path . self::CONFIG_FILE));

        $this->revisions = $config["revisions"];
        $this->revisionPatterns = $config["revision_patterns"];
        $this->currentHead = $config["current_head"];
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
                "/(^[0-9]{6}_[0-9]{6}_[a-zA-Z0-9]+)/"
            ],
            "current_head" => null
        ];
        file_put_contents($this->path . self::CONFIG_FILE, json_encode($config));

        echo "Directory " . $this->path . " is initialized. Please update your CMS file" . PHP_EOL;
    }

    /**
     * create instance of manager
     * @param  string $path path to directory with revisions
     * @return PDM_RevisionManager       manager instance
     */
    public static function createManager($path)
    {
        $path = PDM_Utils::normalizeDirPath($path);

        $dir = dir($path);

        while ($fileName = $dir->read())
        {
            $fullName = $path . $fileName;
        }

        $manager = new self($path);
        $manager->reload();

        return $manager;
    }

}