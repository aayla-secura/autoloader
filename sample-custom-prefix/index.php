<?php

// These lines are for DEVELOPMENT only.  You should never display errors
// in a production environment.
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'classes/Autoloader.php';
$autoloader = new Autoloader();
$autoloader->set_file_prefix("class-");
spl_autoload_register(array($autoloader, 'loader'));

try {
    $a = new A();
    $b = new B();
    $c = new C();
} catch (Exception $e) {
    echo $e->getMessage();
}

// EOF
