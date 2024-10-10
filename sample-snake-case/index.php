<?php

// These lines are for DEVELOPMENT only.  You should never display errors
// in a production environment.
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'classes/Autoloader.php';
$autoloader = new Autoloader();
$autoloader->use_snake_case();
spl_autoload_register(array($autoloader, 'loader'));

try {
    $a = new AFooBar();
    $b = new B();
    $c = new camelCased();
} catch (Exception $e) {
    echo $e->getMessage();
}

// EOF
