<?php declare(strict_types=1);

/**
 * Compile webp8 into phar
 * @note php.ini setting phar.readonly must be set to false
 * parts taken from composer compiler https://github.com/composer/composer/blob/master/src/Composer/Compiler.php
 */

use Symfony\Component\Finder\Finder;

require(__DIR__ .'/../vendor/autoload.php');

$filename = 'webp8.phar';

// clean up before creating a new phar
if (file_exists($filename))
    unlink($filename);

// create phar
$phar = new \Phar($filename);

$phar->setSignatureAlgorithm(\Phar::SHA1);

// start buffering, mandatory to modify stub
$phar->startBuffering();

// add src files
$finder = new Finder();

$finder->files()
    ->ignoreVCS(true)
    ->name('*.php')
    ->notName('Compiler.php')
    ->in(__DIR__);

foreach ($finder as $file)
    $phar->addFile($file->getRealPath(), getRelativeFilePath($file));

// add vendor files
$finder = new Finder();

$finder->files()
    ->ignoreVCS(true)
    ->name('*.php')
    ->exclude('Tests')
    ->exclude('tests')
    ->exclude('docs')
    ->in(__DIR__ .'/../vendor/');

foreach ($finder as $file)
    $phar->addFile($file->getRealPath(), getRelativeFilePath($file));

/*
// create phar loader
$file = __DIR__ .'/Phar.php';

if (!file_exists($file)) {
    // FIX ME
    exit();
}

// add shebang
$stub  = '';//"#!/usr/bin/env php\n";
$contents = file_get_contents($file, false);

$a = $phar->createDefaultStub();

$phar->setStub($stub . $contents);
*/

// set entry point
$phar->setDefaultStub('src/webp8.php');

$phar->stopBuffering();

// compress to gzip
//$phar->compress(Phar::GZ);

echo('phar successfully created');

/**
 * Get file relative path
 * @param  \SplFileInfo $file
 * @return string
 */
function getRelativeFilePath(SplFileInfo $file): string
{
    $realPath = $file->getRealPath();
    $pathPrefix = dirname(__DIR__) . DIRECTORY_SEPARATOR;

    $pos = strpos($realPath, $pathPrefix);
    $relativePath = ($pos !== false) ? substr_replace($realPath, '', $pos, strlen($pathPrefix)) : $realPath;

    return strtr($relativePath, '\\', '/');
}
