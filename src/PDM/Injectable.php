<?php

class PDM_Injectable
{

    /**
     * service locator instance
     * @var [type]
     */
    private $sl;

    public function __construct(PDM_ServiceLocator $sl=null)
    {
        if ($sl === null)
            $sl = PDM_ServiceLocator::getDefault();

        $this->sl = $sl;
    }

    /**
     * get service locator
     * @return PDM_ServiceLocator service locator
     */
    public function getSL()
    {
        return $this->sl;
    }

    /**
     * set new service locator
     * @param PDM_ServiceLocator $sl service locator to set
     */
    public function setSL($sl)
    {
        $this->sl = $sl;
    }

}