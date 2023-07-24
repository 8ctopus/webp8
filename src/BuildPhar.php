<?php

/**
 * Build phar
 *
 * @note php.ini setting phar.readonly must be set to false
 * parts taken from composer compiler https://github.com/composer/composer/blob/master/src/Composer/Compiler.php
 */

declare(strict_types=1);

namespace Oct8pus\Webp8;

use Phar;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

require __DIR__ . '/../vendor/autoload.php';

$filename = __DIR__ . '/bin/webp8.phar';

// clean up before creating a new phar
if (file_exists($filename)) {
    unlink($filename);
}

$gzip = "{$filename}.gz";

if (file_exists($gzip)) {
    unlink($gzip);
}

// create phar
$phar = new Phar($filename);

$phar->setSignatureAlgorithm(Phar::SHA256);

// start buffering, mandatory to modify stub
$phar->startBuffering();

// add src files
$finder = new Finder();

$finder->files()
    ->ignoreVCS(true)
    ->name('*.php')
    ->notName('BuildPhar.php')
    ->in(__DIR__ . '/src/');

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
    ->exclude('LICENSE')
    ->exclude('README.md')
    ->exclude('CHANGELOG.md')
    ->exclude('composer.json')
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

// compress to gzip - doesn't work, phar no longer executable
//$phar->compress(Phar::GZ, '.phar.gz');

//$phar->convertToExecutable(null, Phar::GZ, '.phar.gz');

$sha256 = hash('sha256', file_get_contents($filename), false);

echo <<<OUTPUT
Create phar - OK
{$filename} - {$sha256}

OUTPUT;


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
    $pathPrefix = dirname(__DIR__) . \DIRECTORY_SEPARATOR;

    $pos = strpos($realPath, $pathPrefix);
    $relativePath = ($pos !== false) ? substr_replace($realPath, '', $pos, strlen($pathPrefix)) : $realPath;

    return strtr($relativePath, '\\', '/');
}
