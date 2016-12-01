<?php


class PDM_Settings
{

    const ITEM_REVISIONS = "revisions";
    const ITEM_CURRENT_REVISION = "current_revision";
    const ITEM_DB_PARAMS = "db_params";
    const ITEM_VERSION = "version";
    const ITEM_REVISION_PATTERNS = "revision_patterns";

    const CURRENT_VERSION = "1.0";

    public $version;

    public $currentRevision;

    public $revisions = [];

    public $revisionPatterns = [];

    public $dbParams = [];

    public static function loadFromFile($fileName="pdm.json")
    {
        $data = is_file($fileName) ? json_decode(file_get_contents($fileName)) : [];

        return new self($data);
    }

    public function __construct(array $data=array())
    {
        $this->fromArray($data);
    }
    public function save($fileName="pdm.json")
    {
        file_put_contents($fileName, json_encode($this->toArray()));
    }

    public function toArray()
    {
        return [
            self::ITEM_REVISIONS => $this->revisions,
            self::ITEM_VERSION => $this->version,
            self::ITEM_CURRENT_REVISION => $this->currentRevision,
            self::ITEM_DB_PARAMS => $this->dbParams,
            self::ITEM_REVISION_PATTERNS => $this->revisionPatterns,
        ];
    }

    public function fromArray(array $data)
    {
        $this->setValueOrDefault($this->revisions, $data, self::ITEM_REVISIONS, []);
        $this->setValueOrDefault($this->version, $data, self::ITEM_VERSION, self::CURRENT_VERSION);
        $this->setValueOrDefault($this->currentRevision, $data, self::ITEM_CURRENT_REVISION, null);
        $this->setValueOrDefault($this->revisionPatterns, $data, self::ITEM_REVISION_PATTERNS, $this->getDefaultPatterns());
        $this->setValueOrDefault($this->dbParams, $data, self::ITEM_DB_PARAMS, $this->getSampleDbParams());
    }

    public function getDefaultPatterns()
    {
        return [ "/(^[0-9]{8}_[0-9]{6}_[a-zA-Z0-9 _]+)/" ];
    }

    public function getSampleDbParams()
    {
        return [
            "dsn" => "mysql:host=localhost;dbname=mydatabase",
            "username" => "root",
            "password" => ""
        ];
    }

    protected function setValueOrDefault(&$target, array &$data, $valueName, $default)
    {
        $target = isset($data[$valueName]) ? $data[$valueName] : $default;
    }

}
