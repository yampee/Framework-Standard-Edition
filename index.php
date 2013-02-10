<?php

/*
 * Bootstrap file
 *
 * Dispatch the request to the right action, using its format and the
 * defined routes. Execute the action and return its result, with
 * HTTP headers and cache.
 *
 * This file create a Kernel instance and execute your code.
 */

// Enable or not the developpment mode
$developementEnabled = true;


// Boot Yampee
define('__APP__', dirname(__FILE__));

require 'app/autoload.php';

$kernel = new Yampee_Kernel($developementEnabled);
$kernel ->handle(Yampee_Http_Request::createFromGlobals())
		->send();