<?php

class PDM_RevisionManager
{

    const SUFFIX_APPLY = "_up.sql";
    const SUFFIX_REVERT = "_down.sql";
    const SUFFIX_INFO = ".json";
    const SUFFIX_OK = ".ok";

    /**
     * all revisions where key revision name and value is instance
     * @var array
     */
    private $revisions=array();

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
        $info = [ "owner" => $owner, "created_at" => time(), "created_at_hr" => date("c"), "parents" => [], "children" => [] ];
        file_put_contents($basePath . self::SUFFIX_INFO, json_encode($info));

        $revision = new PDM_Revision($this->path, $revisionName);
    }

    public static function createManager($path)
    {
        $path = PDM_Utils::normalizeDirPath($path);

        $dir = dir($path);

        while ($fileName = $dir->read())
        {
            $fullName = $path . $fileName;
        }

        return new self($path);
    }

}