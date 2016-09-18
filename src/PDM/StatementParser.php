<?php


class PDM_StatementParser
{

    private $startStatements;

    private $nests;

    private $delimiter;

    public function __construct(array $startStatements, array $nests, $delimiter)
    {
        $this->startStatements = $startStatements;
        $this->nests = $nests;
        $this->delimiter = $delimiter;
    }

}