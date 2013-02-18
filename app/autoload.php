<?php

/**
 * Autoloader
 */
require __APP__.'/vendor/yampee/components/loader/src/Yampee/Loader/Universal.php';

$loader = new Yampee_Loader_Universal();

$loader->registerPrefix(array(
	'Yampee' => array(
		__APP__.'/vendor/yampee/framework/src',
		__APP__.'/vendor/yampee/components/annotations/src',
		__APP__.'/vendor/yampee/components/cache/src',
		__APP__.'/vendor/yampee/components/database/src',
		__APP__.'/vendor/yampee/components/dependency-injection/src',
		__APP__.'/vendor/yampee/components/event-dispatcher/src',
		__APP__.'/vendor/yampee/components/form/src',
		__APP__.'/vendor/yampee/components/http/src',
		__APP__.'/vendor/yampee/components/loader/src',
		__APP__.'/vendor/yampee/components/logger/src',
		__APP__.'/vendor/yampee/components/redis/src',
		__APP__.'/vendor/yampee/components/routing/src',
		__APP__.'/vendor/yampee/components/translation/src',
		__APP__.'/vendor/yampee/components/yaml/src',
	),
	'Twig' => __APP__.'/vendor/twig/lib',
));

$loader->registerFallback(array(
	__APP__.'/src/controllers',
	__APP__.'/src/services',
	__APP__.'/src/libraries',
	__APP__.'/src/models',
));

require __APP__.'/vendor/swiftmailer/lib/swift_required.php';