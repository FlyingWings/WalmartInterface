<?php


function load_files($directory){
    if(is_dir($directory)) {
        $dir = opendir($directory);
        $sub_file = [];
        while (($file = readdir($dir)) !== false) {
            if ($file == "." || $file == "..") continue;
            if (is_dir($directory . DIRECTORY_SEPARATOR . $file)) {
                $sub_file[] = load_files($directory . DIRECTORY_SEPARATOR . $file);
            } else {
                require_once($directory . DIRECTORY_SEPARATOR . $file);
                $sub_file[] = $directory . DIRECTORY_SEPARATOR . $file;
            }
        }
        closedir($dir);
        return $sub_file;
    }
    return [];
}

function dd(){
    array_map("var_dump", func_get_args());
    exit;
}

define("ROOT", __DIR__);
define("LIBRARY", ROOT. DIRECTORY_SEPARATOR. "libraries");

load_files(LIBRARY);
require_once("vendor/autoload.php");
