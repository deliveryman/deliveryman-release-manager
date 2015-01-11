<?php
namespace Deliveryman\ReleaseManager;

use Symfony\Component\Console\Application;

require_once __DIR__ . '/../vendor/autoload.php';

$package = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true);
$application = new Application($package['description'], $package['version']);

// bootstrap commands
$iterator = new \DirectoryIterator(__DIR__ . '/../src/' . str_replace('\\', '/', __NAMESPACE__) . '/Command');
foreach ($iterator as $file) {
	if (!$file->isFile()) continue;
	
	$class = __NAMESPACE__ . '\\Command\\' . $file->getBasename('.php');
	$reflectionClass = new \ReflectionClass($class);
	if (!$reflectionClass->isAbstract()) {
		$file = $reflectionClass->newInstance();
		$application->add($file);
	}
}

$application->run();