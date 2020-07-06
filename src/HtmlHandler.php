<?php declare(strict_types=1);

namespace cwebp;

use Monolog\Handler\AbstractProcessingHandler;

class HtmlHandler extends AbstractProcessingHandler
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
