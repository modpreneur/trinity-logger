<?php

namespace Trinity\Bundle\LoggerBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Trinity\Component\Core\Interfaces\EntityInterface;

/**
 * Class ElasticLoggerEvent.
 */
class ElasticLoggerEvent extends Event
{
    const EVENT_NAME = 'trinity.logger.elastic_logger_event';
    /** @var  string */
    protected $log;
    /** @var  EntityInterface */
    protected $entity;


    /**
     * ElasticLoggerEvent constructor.
     *
     * @param string $log
     * @param EntityInterface $entity
     */
    public function __construct(string $log, EntityInterface $entity)
    {
        $this->log = $log;
        $this->entity = $entity;
    }


    /**
     * @return string
     */
    public function getLog(): string
    {
        return $this->log;
    }


    /**
     * @return EntityInterface
     */
    public function getEntity(): EntityInterface
    {
        return $this->entity;
    }
}
