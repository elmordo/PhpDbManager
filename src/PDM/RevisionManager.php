<?php

class PDM_DirectoryScanner
{
    public static function scanDirectory($path)
    {
        if ($path == "")
            $path = "./";

        if ($path[strlen($path) - 1] != "/")
            $path .= "/";

        $dir = dir($this->path);

        while ($fileName = $dir->read())
        {
            $fullName = $path . $fileName;
        }
    }

}