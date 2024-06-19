<?php

// These lines are for DEVELOPMENT only.  You should never display errors
// in a production environment.
error_reporting(E_ALL);
ini_set('display_errors', '1');

include 'classes/Autoloader.php';
$autoloader = new Autoloader();
$autoloader->use_dash_for_underscore();
spl_autoload_register(array($autoloader, 'loader'));

try {
    $a = new A_Foo_Bar();
    $b = new B();
    $c = new bar_Baz();
} catch (Exception $e) {
    echo $e->getMessage();
}

// EOF
