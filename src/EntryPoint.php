<?php

declare(strict_types=1);

namespace Oct8pus\Webp;

use Exception;
use Symfony\Component\Console\Application;

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require __DIR__ . '/vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    throw new Exception('autoload not found');
}

$app = new Application('webp8', '1.0.3');
$app->add(new CommandConvert());
$app->add(new CommandCleanup());

$app->run();
