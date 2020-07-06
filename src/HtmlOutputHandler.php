<?php declare(strict_types=1);

namespace octopus;

use Monolog\Handler\AbstractProcessingHandler;

class HtmlOutputHandler extends AbstractProcessingHandler
{
    function __construct()
    {
        echo('<pre>');
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record): void
    {
        echo($record['formatted']);
    }
}
