<?php

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Trinity\Bundle\LoggerBundle\Entity\EntityActionLog;
use Trinity\Bundle\LoggerBundle\Event\ElasticLoggerEvent;
use Trinity\Bundle\LoggerBundle\EventListener\ElasticLoggerListener;
use Trinity\Bundle\LoggerBundle\Services\ElasticLogService;

/**
 * Class ElasticLoggerListenerTest
 * @package Trinity\Bundle\LoggerBundle\Tests\EventListener
 */
class ElasticLoggerListenerTest extends TestCase
{
    public function testConstructGetsAndSets(): void
    {
        /** @var ElasticLogService|MockObject $elasticLogServiceWithTtl */
        $elasticLogService = $this->getMockBuilder(ElasticLogService::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityActionLog $entity */
        $entity = new EntityActionLog();

        /** @var ElasticLoggerEvent|MockObject $elasticLoggerEvent */
        $elasticLoggerEvent = $this->getMockBuilder(ElasticLoggerEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $elasticLoggerEvent->expects(static::once())->method('getLog')->willReturn('log');

        $elasticLoggerEvent->expects(static::once())->method('getEntity')->willReturn($entity);

        /** @var ElasticLoggerListener $elasticLoggerListener */
        $elasticLoggerListener = new ElasticLoggerListener($elasticLogService);

        $elasticLoggerListener->onLog($elasticLoggerEvent);
    }
}
