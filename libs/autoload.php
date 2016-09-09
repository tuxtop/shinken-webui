<?php

/**
 * Autoload PHP classes
 */

spl_autoload_register(function($class){
    foreach (array( '.php', '.class.php' ) as $ext)
    {
        foreach (array( 'libs', 'app', 'vendors' ) as $dir)
        {
            $file = str_replace('\\', '/', $class).$ext;
            $path = $_SERVER['DOCUMENT_ROOT']."/${dir}/${file}";
            if (!file_exists($path)) continue;
            include($path);
            return true;
        }
    }
    throw new \Exception("Could not find class ${class}!");
});

?>
