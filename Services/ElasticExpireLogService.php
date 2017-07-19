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
use Symfony\Component\Validator\Constraints\Date;
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
    public function __construct(array $logClasses, $clientHost)
    {
        $this->logClasses = $logClasses;
        $params = \explode(':', $clientHost);
        $port = $params[1] ?? 9200;


        $this->esClient = ClientBuilder::create() // Instantiate a new ClientBuilder
            ->setHosts(["${params[0]}:${port}"])// Set the hosts
            ->build();
    }

    /**
     * @param $params
     * @param $port
     * @param $defaultHandler
     *
     * @return Client
     */
    private function createBuilder($params, $port, $defaultHandler): Client
    {
        return ClientBuilder::create()// Instantiate a new ClientBuilder
        ->setHosts(["${params[0]}:${port}"])// Set the hosts
            ->build();
    }


    public function checkLogs()
    {
        /** @var BaseElasticLog $logClass */
        foreach ($this->logClasses as $logClass) {
            $ttl = $this->ttlProvider->getTtlForType($logClass::getLogName());
            $oldest = new \DateTime();
            $oldest->modify("+$ttl days");

            $stamp = $oldest->getTimestamp() * 1000;

            \var_dump($this->esClient->deleteByQuery([
                'index' => '*',
                'type' => $logClass::getLogName(),
                'body' => [
                    'query' => [
                        'range' => [
                            'createdAt' => [
                                'gt' => $stamp,
                            ]
                        ]
                    ]
                ]
            ]));
        }
    }
}
