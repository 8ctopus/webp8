<?php declare(strict_types=1);

// program entry point
if (file_exists(__DIR__ .'/../vendor/autoload.php'))
    require(__DIR__ .'/../vendor/autoload.php');
else
    require(__DIR__ .'/vendor/autoload.php');

$app = new Symfony\Component\Console\Application('webp8', '0.1.6');
$app->add(new Oct8pus\Webp\CommandConvert());
$app->add(new Oct8pus\Webp\CommandCleanup());

$app->run();
