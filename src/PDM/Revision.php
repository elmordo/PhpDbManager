<?php

class PDM_Revision
{

    const FILE_ASQL = "sqlapply";

    const FILE_RSQL = "sqlrevert";

    const FILE_INFO = "info";

    const FILE_OK = "ok";

    /**
     * base name of revision
     * @var string
     */
    private $revisionName;

    /**
     * path to dir where revisions are stored
     * @var string
     */
    private $path;

    /**
     * true if apply sql was found
     * @var boolean
     */
    private $sqlApplyFound = false;

    /**
     * true if revert sql was found
     * @var boolean
     */
    private $sqlRevertFound = false;

    /**
     * true if info file was found
     * @var boolean
     */
    private $infoFound = false;

    /**
     * true if revision is marked as applied
     * @var boolean
     */
    private $okFound = false;

    public function __construct($path, $revisionName)
    {
        $this->setPath($path);
        $this->revisionName = $revisionName;
    }

    /**
     * set found status for one file type
     * @param str $fileType type of file
     * @param boolean $status   new status
     */
    public function setFoundStatus($fileType, $status)
    {
        switch ($fileType)
        {

            case self::FILE_ASQL:
            $this->sqlApplyFound = $status;
            break;

            case self::FILE_INFO:
            $this->infoFound = $status;
            break;

            case self::FILE_RSQL:
            $this->sqlRevertFound = $status;
            break;

            case self::FILE_OK:
            $this->okFound = $status;
            break;

            default:
            throw new \DomainException("Unknonw file type '$fileType'");
        }
    }

    /**
     * return found status of specified file type
     * @param  string $fileType type of file
     * @return bool           file status
     */
    public function getFoundStatus($fileType)
    {
        switch ($fileType)
        {

            case self::FILE_ASQL:
            return $this->sqlApplyFound;

            case self::FILE_INFO:
            return $this->infoFound;

            case self::FILE_RSQL:
            return $this->sqlRevertFound;

            case self::FILE_OK:
            return $this->okFound;

            default:
            throw new \DomainException("Unknonw file type '$fileType'");
        }
    }

    /**
     * return true if revision has all requested files
     * @return boolean revision status
     */
    public function isValid()
    {
        return $this->sqlApplyFound && $this->sqlRevertFound
            && $this->infoFound;
    }

    public function setPath($val)
    {
        $this->path = \PDM_Utils::normalizeDirPath($val);
    }

}

