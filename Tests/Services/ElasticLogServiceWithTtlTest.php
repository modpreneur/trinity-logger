<?php
/**
 * Created by PhpStorm.
 * User: gabriel
 * Date: 26.1.17
 * Time: 12:30
 */

namespace Trinity\Bundle\LoggerBundle\Tests\Services;


use PHPUnit\Framework\TestCase;
use Trinity\Bundle\LoggerBundle\Services\DefaultTtlProvider;
use Trinity\Bundle\LoggerBundle\Services\ElasticLogService;
use Trinity\Bundle\LoggerBundle\Services\ElasticLogServiceWithTtl;

/**
 * Class ElasticLogServiceWithTtlTest
 * @package Trinity\Bundle\LoggerBundle\Tests\Services
 */
class ElasticLogServiceWithTtlTest extends TestCase
{
    /** @var string  */
    protected $logName = 'logName';

    /** @var int  */
    protected $ttl = 0;

    /** @var null  */
    protected $object = null;


    public function testWrite()
    {
        $base = $this->getBase();
        /** @var ElasticLogServiceWithTtl $ttlLogger */
        $ttlLogger = $base['ttlLogger'];

        $base['logger']->expects($this->once())->method('writeInto')->with($this->logName, $this->object, $this->ttl);

        $ttlLogger->writeInto($this->logName, $this->object);
    }

    public function testAsyncWrite()
    {
        $base = $this->getBase();
        /** @var ElasticLogServiceWithTtl $ttlLogger */
        $ttlLogger = $base['ttlLogger'];

        $base['logger']->expects($this->once())->method('writeIntoAsync')
            ->with($this->logName, $this->object, $this->ttl);

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

        $base['logger']->expects($this->once())->method('update')
            ->with($this->logName, $logId, $updateKeys, $updateValues, $this->ttl);

        $ttlLogger->update($this->logName, $logId, $updateKeys, $updateValues, true);
    }

    /**
     * @return array
     */
    private function getBase()
    {
        $logger = $this->getMockBuilder(ElasticLogService::class)->disableOriginalConstructor()->getMock();
        $ttlProvider = $this->getMockBuilder(DefaultTtlProvider::class)->disableOriginalConstructor()->getMock();
        $ttlProvider->expects($this->once())->method('getTtlForType')->with($this->logName)
            ->will($this->returnValue($this->ttl));


        $ttlLogger = new ElasticLogServiceWithTtl(
            $ttlProvider,
            $logger
        );

        return [
            'logger' => $logger,
            'ttlProvider' => $ttlProvider,
            'ttlLogger' => $ttlLogger,
        ];
    }
}
