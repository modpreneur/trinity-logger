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

/**
 * Class ElasticLogService.
 */
class ElasticLogService
{
    /**
     * @var Client;
     */
    private $ESClient;
    /**
     * @var string index
     */
    private $index;

    /** @var bool */
    protected $useAsync;

    /** @var ElasticEntityProcessor */
    protected $entityProcessor;

    /**
     * ElasticLogService constructor.
     *
     * @param ElasticEntityProcessor $entityProcessor
     * @param string $clientHost IP:port, default port is 9200
     * @param string $index name of DB
     * @param bool $useAsync
     * @param int $asyncQueLength
     * @param ClientBuilder $clientBuilder
     */
    public function __construct(
        ElasticEntityProcessor $entityProcessor,
        string $clientHost,
        $index,
        bool $useAsync,
        $asyncQueLength = 50,
        ClientBuilder $clientBuilder = null
    ) {
        $this->entityProcessor = $entityProcessor;
        $this->useAsync = $useAsync;
        $this->index = $index ?: 'necktie';

        $params = \explode(':', $clientHost);
        $port = $params[1] ?? 9200;

        $handlerParams = [
            'max_handles' => (int)$asyncQueLength,
        ];

        $defaultHandler = ClientBuilder::defaultHandler($handlerParams);

        if ($clientBuilder) {
            $this->ESClient = $clientBuilder
                ->setHosts(["${params[0]}:${port}"])// Set the hosts

                ->setHandler($defaultHandler)
                ->build();
        } else {
            $this->ESClient = $this->createBuilder($params, $port, $defaultHandler);
        }
    }


    /**
     * @param string $index
     *
     * @return ElasticLogService
     */
    public function setIndex($index): ElasticLogService
    {
        $this->index = $index;

        return $this;
    }


    /**
     * If the ttl is not set default mapping in elastic is used (if exist).
     * The type(log) has to have enabled ttl in its mapping.
     *
     *
     * @param string $typeName //log name
     * @param $entity //entity
     * @param int $ttl
     *
     * @return void
     */
    public function writeIntoAsync(string $typeName, $entity, int $ttl = 0): void
    {
        if (!$this->useAsync) {
            $this->writeInto($typeName, $entity, $ttl);
            return;
        }


        /*
         * Transform entity into array. Elastic can do it for you, but result is not in your hands.
         */
        $entityArray = $this->entityProcessor->getElasticArray($entity);
        $params = [
            'index' => $this->index,
            'type' => $typeName,
            'body' => $entityArray,
            'client' => ['future' => 'lazy'],
        ];

        if ($ttl) {
            $params['ttl'] = "{$ttl}d";
        }

        $this->ESClient->index($params);
        //does not return anything to full use the lazy(async) feature
    }


    /**
     * Should flush the async queue.
     */
    public function flush(): void
    {
        $this->ESClient->indices()->refresh(['index' => $this->index]);
    }

    /**
     * If the ttl is not set default mapping in elastic is used (if exist).
     * The type(log) has to have enabled ttl in its mapping.
     *
     * @param string $typeName //log name
     * @param $entity //entity
     * @param int $ttl // in days
     *
     * @return string //ID of the logged
     */
    public function writeInto(string $typeName, $entity, int $ttl = 0): string
    {
        /*
         * Transform entity into array. Elastic can do it for you, but result is not in your hands.
         */
        $entityArray = $this->entityProcessor->getElasticArray($entity);

        $params = [
            'index' => $this->index,
            'type' => $typeName,
            'body' => $entityArray,
        ];

        if ($ttl) {
            $params['ttl'] = "{$ttl}d";
        }

        $response = $this->ESClient->index($params);

        $this->ESClient->indices()->refresh(['index' => $this->index]);

        if ($entity instanceof BaseElasticLog) {
            $entity->setId($response['_id']);
        }

        return $response['_id'];
    }

    /**
     * @param string $typeName
     * @param string $id
     * @param array $types
     * @param array $values
     * @param int $ttl
     */
    public function update(string $typeName, string $id, array $types, array $values, int $ttl = 0): void
    {
        $body = \array_combine($types, $values);
        $params = [
            'index' => $this->index,
            'type' => $typeName,
            'id' => $id,
            'body' => ['doc' => $body],
        ];

        if ($ttl) {
            $params['ttl'] = "{$ttl}d";
        }

        $this->ESClient->update($params);
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
        $client = ClientBuilder::create()// Instantiate a new ClientBuilder
        ->setHosts(["${params[0]}:${port}"])// Set the hosts
        ->setHandler($defaultHandler)
            ->build();

        return $client;
    }
}
