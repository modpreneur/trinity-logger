<?php

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\EventListener;

use Trinity\Bundle\LoggerBundle\Event\ElasticLoggerEvent;
use Trinity\Bundle\LoggerBundle\Services\ElasticLogServiceWithTtl;

/**
 * Class ElasticLoggerListener.
 */
class ElasticLoggerListener
{
    /** @var ElasticLogServiceWithTtl */
    protected $logger;


    /**
     * ElasticLoggerListener constructor.
     *
     * @param ElasticLogServiceWithTtl $logger
     */
    public function __construct(ElasticLogServiceWithTtl $logger)
    {
        $this->logger = $logger;
    }


    /**
     * @param ElasticLoggerEvent $e
     */
    public function onLog(ElasticLoggerEvent $e): void
    {
        $this->logger->writeIntoAsync(
            $e->getLog(),
            $e->getEntity()
        );
    }
}
