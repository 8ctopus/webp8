<?php

/**
 * Build phar
 *
 * @note php.ini setting phar.readonly must be set to false
 * parts taken from composer compiler https://github.com/composer/composer/blob/master/src/Composer/Compiler.php
 */

declare(strict_types=1);

namespace Oct8pus\Webp8;

use Symfony\Component\Finder\Finder;
use Phar;
use SplFileInfo;

require __DIR__ . '/../vendor/autoload.php';

$filename = 'webp8.phar';

// clean up before creating a new phar
if (file_exists($filename)) {
    unlink($filename);
}

// create phar
$phar = new Phar($filename);

$phar->setSignatureAlgorithm(Phar::SHA1);

// start buffering, mandatory to modify stub
$phar->startBuffering();

// add src files
$finder = new Finder();

$finder->files()
    ->ignoreVCS(true)
    ->name('*.php')
    ->notName('BuildPhar.php')
    ->in(__DIR__);

foreach ($finder as $file) {
    $phar->addFile($file->getRealPath(), getRelativeFilePath($file));
}

// add vendor files
$finder = new Finder();

$finder->files()
    ->ignoreVCS(true)
    ->name('*.php')
    ->exclude('Tests')
    ->exclude('tests')
    ->exclude('docs')
    ->in(__DIR__ . '/../vendor/');

foreach ($finder as $file) {
    $phar->addFile($file->getRealPath(), getRelativeFilePath($file));
}

$entrypoint = 'src/EntryPoint.php';

// create default "boot" loader
$bootLoader = $phar->createDefaultStub($entrypoint);

// add shebang to bootloader
$stub = "#!/usr/bin/env php\n";

$bootLoader = $stub . $bootLoader;

// set bootloader
$phar->setStub($bootLoader);

$phar->stopBuffering();

// compress to gzip
//$phar->compress(Phar::GZ);

echo 'Create phar - OK';

/**
 * Get file relative path
 *
 * @param SplFileInfo $file
 *
 * @return string
 */
function getRelativeFilePath(SplFileInfo $file) : string
{
    $realPath = $file->getRealPath();
    $pathPrefix = dirname(__DIR__) . DIRECTORY_SEPARATOR;

    $pos = strpos($realPath, $pathPrefix);
    $relativePath = ($pos !== false) ? substr_replace($realPath, '', $pos, strlen($pathPrefix)) : $realPath;

    return strtr($relativePath, '\\', '/');
}
