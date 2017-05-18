<?php
/**
 * Created by PhpStorm.
 * User: gabriel
 * Date: 26.1.17
 * Time: 12:30
 */

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\Tests\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Trinity\Bundle\LoggerBundle\Entity\BaseElasticLog;
use Trinity\Bundle\LoggerBundle\Services\DefaultTtlProvider;
use Trinity\Bundle\LoggerBundle\Services\ElasticLogService;
use Trinity\Bundle\LoggerBundle\Services\ElasticLogServiceWithTtl;
use Trinity\Component\Core\Interfaces\EntityInterface;

/**
 * Class ElasticLogServiceWithTtlTest
 * @package Trinity\Bundle\LoggerBundle\Tests\Services
 */
class ElasticLogServiceWithTtlTest extends TestCase
{
    /** @var string */
    protected $logName = 'logName';
    /** @var int */
    protected $ttl = 0;
    /** @var BaseElasticLog|EntityInterface|null */
    protected $object = null;


    public function testWrite(): void
    {
        $base = $this->getBase();

        /** @var ElasticLogServiceWithTtl $ttlLogger */
        $ttlLogger = $base['ttlLogger'];

        $this->object = $base['baseElasticLog'];

        $ttlLogger->writeInto($this->logName, $this->object);
    }


    public function testAsyncWrite()
    {
        $base = $this->getBase();
        /** @var ElasticLogServiceWithTtl $ttlLogger */
        $ttlLogger = $base['ttlLogger'];

        $this->object = $base['entityInterface'];

        $ttlLogger->writeIntoAsync($this->logName, $this->object);
    }


    public function testUpdate()
    {
        $base = $this->getBase();
        /** @var ElasticLogServiceWithTtl $ttlLogger */
        $ttlLogger = $base['ttlLogger'];

        $updateKeys = ['firstKey', 'secondKey'];
        $updateValues = ['firstValue', 'secondValue'];
        $logId = 'logId';

        $base['logger']->expects(static::once())->method('update')
            ->with($this->logName, $logId, $updateKeys, $updateValues, $this->ttl);

        $ttlLogger->update($this->logName, $logId, $updateKeys, $updateValues, true);
    }


    /**
     * @return array
     */
    private function getBase()
    {
        /** @var EntityInterface|Mock $entityInterface */
        $entityInterface = $this->getMockBuilder(EntityInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ElasticLogService|Mock $logger */
        $logger = $this->getMockBuilder(ElasticLogService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $logger->expects(static::any())
            ->method('writeInto');

        $logger->expects(static::any())
            ->method('writeIntoAsync');

        /** @var DefaultTtlProvider|Mock $ttlProvider */
        $ttlProvider = $this->getMockBuilder(DefaultTtlProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ttlProvider->expects(static::once())
            ->method('getTtlForType')
            ->with($this->logName)
            ->will(
                static::returnValue($this->ttl)
            );
        /** @var BaseElasticLog|Mock $baseElasticLog */
        $baseElasticLog = $this->getMockBuilder(BaseElasticLog::class)
            ->disableOriginalConstructor()
            ->getMock();

        $ttlLogger = new ElasticLogServiceWithTtl(
            $ttlProvider,
            $logger
        );

        return [
            'logger' => $logger,
            'ttlProvider' => $ttlProvider,
            'ttlLogger' => $ttlLogger,
            'baseElasticLog' => $baseElasticLog,
            'entityInterface' => $entityInterface,
        ];
    }
}
