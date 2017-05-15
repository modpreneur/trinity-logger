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
    public function testEntity()
    {
        /** @var BaseElasticLog $baseElasticLogWithoutParameters */
        $baseElasticLogWithoutParameters = new BaseElasticLog();

        $this->assertEquals('', $baseElasticLogWithoutParameters->getId());

        /** @var BaseElasticLog $baseElasticLog */
        $baseElasticLog = new BaseElasticLog('necktie');

        $this->assertEquals('necktie', $baseElasticLog->getId());

        $baseElasticLog->setCreatedAt(25042017);

        $this->assertEquals(25042017, $baseElasticLog->getCreatedAt());

        $baseElasticLog->setCreatedAt(null);

        $this->assertNull($baseElasticLog->getCreatedAt());

        $baseElasticLog->setTtl(50);

        $this->assertEquals(50, $baseElasticLog->getTtl());

        $baseElasticLog->setTtl(null);

        $this->assertEquals(null, $baseElasticLog->getTtl());
    }
}
