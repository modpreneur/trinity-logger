<?php

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\Services;

use Trinity\Bundle\LoggerBundle\Entity\BaseElasticLog;
use Trinity\Bundle\LoggerBundle\Interfaces\LoggerTtlProviderInterface;
use Trinity\Component\Core\Interfaces\EntityInterface;

/**
 * @deprecated TTL does not work
 * Class ElasticLogService.
 */
class ElasticLogServiceWithTtl
{
    /** @var  LoggerTtlProviderInterface */
    protected $ttlProvider;
    /** @var  ElasticLogService */
    protected $elasticLogger;


    /**
     * ElasticLogServiceWithTtl constructor.
     *
     * @param LoggerTtlProviderInterface $ttlProvider
     * @param ElasticLogService $elasticLogger
     */
    public function __construct(LoggerTtlProviderInterface $ttlProvider, ElasticLogService $elasticLogger)
    {
        $this->ttlProvider = $ttlProvider;
        $this->elasticLogger = $elasticLogger;
    }


    /**
     * The type(log) has to have enabled ttl in its mapping.
     *
     * @param string $typeName //log name
     * @param BaseElasticLog $entity //entity
     *
     * @return string //ID of the logged
     */
    public function writeInto(string $typeName, $entity): string
    {
        return $this->elasticLogger->writeInto($typeName, $entity, $this->ttlProvider->getTtlForType($typeName));
    }


    /**
     * The type(log) has to have enabled ttl in its mapping.
     *
     *
     * @param $typeName //log name
     * @param EntityInterface $entity //entity
     *
     * @return void
     */
    public function writeIntoAsync(string $typeName, $entity): void
    {
        $this->elasticLogger->writeIntoAsync($typeName, $entity, $this->ttlProvider->getTtlForType($typeName));
    }


    /**
     * @param string $typeName
     * @param string $id
     * @param array $types
     * @param array $values
     * @param bool $extendExpiration
     */
    public function update(
        string $typeName,
        string $id,
        array $types,
        array $values,
        bool $extendExpiration = true
    ): void {
        $ttl = 0;
        if ($extendExpiration) {
            $ttl = $this->ttlProvider->getTtlForType($typeName);
        }

        $this->elasticLogger->update($typeName, $id, $types, $values, $ttl);
    }
}
