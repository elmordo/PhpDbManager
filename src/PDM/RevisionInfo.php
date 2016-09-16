<?php


class PDM_RevisionInfo
{

    public $owner;

    public $createdAt;

    public $createdAtReadable;

    public $parents=array();

    public $children=array();

    public function __construct(array $data=array())
    {
        $this->setFromArray($data);
    }

    public function setFromArray(array $data)
    {
        $this->owner = isset($data["owner"]) ? $data["owner"] : null;
        $this->createdAt = isset($data["created_at"]) ? $data["created_at"] : null;
        $this->createdAtReadable = isset($data["created_at_hr"]) ? $data["created_at_hr"] : null;
        $this->parents = isset($data["parents"]) ? $data["parents"] : array();
        $this->children = isset($data["children"]) ? $data["children"] : array();
    }

}