<?php

declare(strict_types=1);

namespace Oct8pus\Webp;

use Symfony\Component\Console\Application;

$file = '/vendor/autoload.php';

require file_exists(__DIR__ . $file) ? __DIR__ . $file : dirname(__DIR__) . $file;

$app = new Application('webp8', '1.0.3');
$app->add(new CommandConvert());
$app->add(new CommandCleanup());

$app->run();
