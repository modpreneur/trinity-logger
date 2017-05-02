<?php

namespace Trinity\Bundle\LoggerBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Trinity\Bundle\LoggerBundle\Entity\EntityActionLog;
use Trinity\Bundle\LoggerBundle\Event\ElasticLoggerEvent;
use Trinity\Bundle\LoggerBundle\EventListener\ElasticLoggerListener;
use Trinity\Bundle\LoggerBundle\Services\ElasticLogServiceWithTtl;

/**
 * Class ElasticLoggerListenerTest
 * @package Trinity\Bundle\LoggerBundle\Tests\EventListener
 */
class ElasticLoggerListenerTest extends TestCase
{
    public function testConstructGetsAndSets()
    {

        $elasticLogServiceWithTtl = $this->getMockBuilder(ElasticLogServiceWithTtl::class)->disableOriginalConstructor()->getMock();

        $entity = new EntityActionLog();

        $elasticLoggerEvent = new ElasticLoggerEvent('log', $entity);

        $elasticLoggerListener = new ElasticLoggerListener($elasticLogServiceWithTtl);

        $elasticLoggerListener->onLog($elasticLoggerEvent);


    }
}
