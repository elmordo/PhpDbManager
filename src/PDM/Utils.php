<?php

class PDM_Utils
{

    public static function normalizeDirPath($path)
    {
        if ($path == "") $path = "./";

        if ($path[strlen($path) - 1] != "/") $path .= "/";

        return $path;
    }

}