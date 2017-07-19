<?php
/**
 * Created by PhpStorm.
 * User: gabriel
 * Date: 26.2.16
 * Time: 18:04.
 */

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\Services;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Trinity\Bundle\LoggerBundle\Entity\BaseElasticLog;
use Trinity\Bundle\LoggerBundle\Interfaces\LoggerTtlProviderInterface;

/**
 * This class provides functionality to check stored TTL of the log classes and remove those that have lived to long.
 *
 * Class ElasticLogService.
 */
class ElasticExpireLogService
{
    /** @var array */
    private $logClasses;

    /** @var  LoggerTtlProviderInterface */
    private $ttlProvider;

    /** @var Client */
    private $esClient;


    /**
     * ElasticExpireLogService constructor.
     *
     * @param array $logClasses
     * @param string $clientHost
     */
    public function __construct(array $logClasses, $clientHost, $ttlProvider)
    {
        $this->ttlProvider = $ttlProvider;
        $this->logClasses = $logClasses;
        $params = \explode(':', $clientHost);
        $port = $params[1] ?? 9200;


        $this->esClient = ClientBuilder::create() // Instantiate a new ClientBuilder
            ->setHosts(["${params[0]}:${port}"])// Set the hosts
            ->build();
    }


    /**
     * Checks all logs classes registered to trinity logger. For each log the oldest possible time of creation is
     * determined and all olders logs are deleted.
     *
     * returned report is in format of nested arrays:
     *         $report[$logName] = ['ttl' => $ttl, 'deleted' => $deleted];
     *
     * @return array $report
     */
    public function checkLogs(): array
    {
        $report = [];
        /** @var BaseElasticLog $logClass */
        foreach ($this->logClasses as $logClass) {
            $ttl = $this->ttlProvider->getTtlForType($logClass::getLogName());
            $oldest = new \DateTime();
            $oldest->modify("-$ttl days");
            $stamp = $oldest->getTimestamp();
            $response = $this->esClient->deleteByQuery([
                'index' => '*',
                'type' => $logClass::getLogName(),
                'body' => [
                    'query' => [
                        'range' => [
                            'createdAt' => [
                                'lt' => $stamp,
                            ]
                        ]
                    ]
                ]
            ]);
            $report[$logClass::getLogName()] = ['ttl' => $ttl, 'deleted' => $response['deleted']];
        }
        return $report;
    }
}
