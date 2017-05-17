<?php

namespace Trinity\Bundle\LoggerBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Trinity\Bundle\LoggerBundle\Entity\BaseElasticLog;

/**
 * Class BaseElasticLogTest
 * @package Trinity\Bundle\LoggerBundle\Tests\Entity
 */
class BaseElasticLogTest extends TestCase
{
    public function testEntity(): void
    {
        /** @var BaseElasticLog $baseElasticLogWithoutParameters */
        $baseElasticLogWithoutParameters = new BaseElasticLog();

        static::assertEquals('', $baseElasticLogWithoutParameters->getId());

        /** @var BaseElasticLog $baseElasticLog */
        $baseElasticLog = new BaseElasticLog('necktie');

        static::assertEquals('necktie', $baseElasticLog->getId());

        $baseElasticLog->setCreatedAt(25042017);

        static::assertEquals(25042017, $baseElasticLog->getCreatedAt());

        $baseElasticLog->setCreatedAt(23);

        static::assertEquals(23, $baseElasticLog->getCreatedAt());

        $baseElasticLog->setTtl(50);

        static::assertEquals(50, $baseElasticLog->getTtl());

        $baseElasticLog->setTtl(34);

        static::assertEquals(34, $baseElasticLog->getTtl());
    }
}
