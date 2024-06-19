<?php

// These lines are for DEVELOPMENT only.  You should never display errors
// in a production environment.
error_reporting(E_ALL);
ini_set('display_errors', '1');

include 'classes/Autoloader.php';
use NS\Autoloader;

$autoloader = new Autoloader();
$autoloader->use_namespaces();
spl_autoload_register(array($autoloader, 'loader'));

use Foo\A;
use Bar\B;
use Baz\C;

try {
    $a = new A();
    $b = new B();
    $c = new C();
} catch (Exception $e) {
    echo $e->getMessage();
}

// EOF
