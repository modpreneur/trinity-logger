<?php

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\EventListener;

use Trinity\Bundle\LoggerBundle\Event\ElasticLoggerEvent;
use Trinity\Bundle\LoggerBundle\Services\ElasticLogService;

/**
 * Class ElasticLoggerListener.
 */
class ElasticLoggerListener
{
    /** @var ElasticLogService */
    protected $logger;


    /**
     * ElasticLoggerListener constructor.
     *
     * @param ElasticLogService $logger
     */
    public function __construct(ElasticLogService $logger)
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
