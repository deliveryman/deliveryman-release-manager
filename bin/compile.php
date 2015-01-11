<?php
/**
 * Compiles local sources to PHAR 
 * 
 * @author Alexander Sergeychik
 */
require_once __DIR__ . '/../vendor/autoload.php';

$phar = new \Phar('release-manager.phar', 0);
$phar->setSignatureAlgorithm(\Phar::SHA1);

$phar->startBuffering();
$phar->buildFromDirectory(__DIR__ . '/..');

$phar->delete('bin/compile');
$phar->delete('bin/release-manager');

$phar->stopBuffering();

$stub=<<<EOF
#!/usr/bin/env php
<?php
/**
 * Release manager stub
 * 
 * @author Alexander Sergeychik
 */
Phar::mapPhar('release-manager');
include 'phar://release-manager/bin/release-manager.php';
__HALT_COMPILER();	
EOF;
	
$phar->setStub($stub);
