<?php

namespace Trinity\Bundle\LoggerBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use Trinity\Bundle\LoggerBundle\Entity\EntityActionLog;
use Trinity\Bundle\LoggerBundle\Event\ElasticLoggerEvent;
use Trinity\Component\Core\Interfaces\EntityInterface;

/**
 * Class ElasticLoggerEventTest
 * @package Trinity\Bundle\LoggerBundle\Tests\Event
 */
class ElasticLoggerEventTest extends TestCase
{
    public function testConstructAndGets(): void
    {
        /** @var EntityInterface $entity */
        $entity = new EntityActionLog();

        /** @var ElasticLoggerEvent $elasticLoggerEvent */
        $elasticLoggerEvent = new ElasticLoggerEvent('log', $entity);

        static::assertEquals('log', $elasticLoggerEvent->getLog());

        static::assertInstanceOf(EntityActionLog::class, $elasticLoggerEvent->getEntity());
    }
}
