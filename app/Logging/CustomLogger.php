<?php

namespace App\Logging;

use Monolog\Logger;
use Monolog\Handler\ElasticsearchHandler;
use Monolog\Formatter\ElasticsearchFormatter;
use Elasticsearch\ClientBuilder;

class CustomLogger
{
    /**
     * Create a custom Monolog instance.
     *
     * @param  array  $config
     * @return \Monolog\Logger
     */
    public function __invoke(array $config)
    {
        $date = date('Y-m-d');
        $indexName = env('ELASTICSEARCH_INDEX_PREFIX') . $date;
        $client = ClientBuilder::create()
            ->setHosts([env('ELK_HOST')])  // Your Elasticsearch host
            ->build();

        $handler = new ElasticsearchHandler($client);
        // $handler->setIndex($indexName);
        // setIndex() OR
        $formatter = new ElasticsearchFormatter($indexName, 'Y-m-d\TH:i:s\Z');  // Pass index name and date format
        $handler->setFormatter($formatter);

        $logger = new Logger('elasticsearch');
        $logger->pushHandler($handler);

        return $logger;
    }
}
