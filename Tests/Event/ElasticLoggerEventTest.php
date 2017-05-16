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
    public function testConstructAndGets()
    {
        /** @var EntityInterface $entity */
        $entity = new EntityActionLog();

        /** @var ElasticLoggerEvent $elasticLoggerEvent */
        $elasticLoggerEvent = new ElasticLoggerEvent('log', $entity);

        $this->assertEquals('log', $elasticLoggerEvent->getLog());

        $this->assertInstanceOf(EntityActionLog::class, $elasticLoggerEvent->getEntity());
    }
}