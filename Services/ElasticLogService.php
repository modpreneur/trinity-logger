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
    /** @var Client */
    private $ESClient;

    /** @var  string */
    private $environment;

    /** @var bool */
    protected $useAsync;

    /** @var ElasticEntityProcessor */
    protected $entityProcessor;


    /**
     * ElasticLogService constructor.
     *
     * @param ElasticEntityProcessor $entityProcessor
     * @param string $clientHost IP:port, default port is 9200
     * @param bool $useAsync
     * @param string $environment
     * @param int $asyncQueLength
     * @param ClientBuilder $clientBuilder
     *
     * @throws \RuntimeException
     */
    public function __construct(
        ElasticEntityProcessor $entityProcessor,
        string $clientHost,
        bool $useAsync,
        string $environment,
        int $asyncQueLength = 50,
        ClientBuilder $clientBuilder = null
    ) {
        $this->entityProcessor = $entityProcessor;
        $this->useAsync = $useAsync;
        $this->environment = $environment;

        $params = \parse_url($clientHost);

        if (!\array_key_exists('port', $params)) {
            if (\array_key_exists('scheme', $params) && $params['scheme'] === 'https') {
                $clientHost .= ':443';
            } else {
                $clientHost .= ':9200';
            }
        }

        $handlerParams = [
            'max_handles' => $asyncQueLength,
        ];

        $defaultHandler = ClientBuilder::defaultHandler($handlerParams);

        if (!$clientBuilder) {
            $clientBuilder = ClientBuilder::create();   // Instantiate a new ClientBuilder
        }
        $this->ESClient = $clientBuilder
            ->setHosts([$clientHost])// Set the hosts
            ->setHandler($defaultHandler)
            ->build();
    }


    /**
     * @deprecated DELETE IT
     */
    public function flush()
    {
        \var_dump('Delete this use.');
    }


    /**
     * @param BaseElasticLog $elasticLog
     */
    public function writeAsync(BaseElasticLog $elasticLog): void
    {
        $this->writeIntoAsync($elasticLog::getLogName(), $elasticLog);
    }


    /**
     * @param string $typeName //log name
     * @param $entity //entity
     *
     * @return void
     */
    public function writeIntoAsync(string $typeName, $entity): void
    {
        if (!$this->useAsync) {
            $this->writeInto($typeName, $entity);
            return;
        }


        /*
         * Transform entity into array. Elastic can do it for you, but result is not in your hands.
         */
        $entityArray = $this->entityProcessor->getElasticArray($entity);
        $params = [
            'index' => $this->getIndex(),
            'type' => $typeName,
            'body' => $entityArray,
            'client' => ['future' => 'lazy'],
        ];
        $this->ESClient->index($params);
        //does not return anything to full use the lazy(async) feature
    }


    /**
     * @param BaseElasticLog $elasticLog
     *
     * @return string
     */
    public function write(BaseElasticLog $elasticLog): string
    {
        return $this->writeInto($elasticLog::getLogName(), $elasticLog);
    }


    /**
     * @param string $typeName //log name
     * @param $entity //entity
     *
     * @return string //ID of the logged
     */
    public function writeInto(string $typeName, $entity): string
    {
        /*
         * Transform entity into array. Elastic can do it for you, but result is not in your hands.
         */
        $entityArray = $this->entityProcessor->getElasticArray($entity);

        $params = [
            'index' => $this->getIndex(),
            'type' => $typeName,
            'body' => $entityArray,
        ];
        $response = $this->ESClient->index($params);
        $this->ESClient->indices()->refresh(['index' => $this->getIndex()]);

        if ($entity instanceof BaseElasticLog) {
            $entity->setId($response['_id']);
        }

        return $response['_id'] . '#' . $response['_index'];
    }


    /**
     * @param string $typeName
     * @param string $id
     * @param array $types
     * @param array $values
     */
    public function update(string $typeName, string $id, array $types, array $values): void
    {
        [$realID, $index] = \explode('#', $id);

        $body = \array_combine($types, $values);
        $params = [
            'index' => $index,
            'type' => $typeName,
            'id' => $realID,
            'body' => ['doc' => $body],
        ];

        $this->ESClient->update($params);
    }


    /**
     * @param BaseElasticLog $entity
     */
    public function updateEntity(BaseElasticLog $entity): void
    {
        [$realID, $index] = \explode('#', $entity->getId());
        $params = [
            'index' => $index,
            'type' => $entity::getLogName(),
            'id' => $realID,
            'body' => ['doc' => $this->entityProcessor->getElasticArray($entity)],
        ];
        $this->ESClient->update($params);
    }


    /**
     * Return today's index name as formatted day (YYYY-DD-YY) with possible prefix ('test-')
     *
     * @param bool $test
     *
     * @return string
     */
    private function getIndex(bool $test = false): string
    {
        $time = new \DateTime();
        $format = $time->format('Y-m-d');
        if ($this->environment === 'test' || $test === true) {
            $format = 'test-'. $format;
        }

        return $format;
    }


    /**
     * @param string $entityName
     * @param array $disabledProperties
     * @param bool $test
     */
    public function putMapping(string $entityName, array $disabledProperties, bool $test = false): void
    {
        $client = $this->ESClient;
        $index  = $this->getIndex($test);

        $properties = [];

        foreach ($disabledProperties as $disabledProperty) {
            $properties[$disabledProperty] = [
                'enabled' => false
            ];
        }

        $client->indices()->create([
            'index' => $index
        ]);

        $client->indices()->putMapping(
            [
                'index' => $index,
                'type' => $entityName,
                'body' => [
                    $entityName => [
                        'properties' => $properties
                    ]
                ]
            ]
        );
    }
}
