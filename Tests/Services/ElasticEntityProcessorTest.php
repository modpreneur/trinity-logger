<?php

namespace Trinity\Bundle\LoggerBundle\Tests\Services;

use Trinity\Bundle\LoggerBundle\Services\ElasticEntityProcessor;
use Trinity\Bundle\LoggerBundle\Tests\Entity\DatetimeTestLog;
use Trinity\Bundle\LoggerBundle\Tests\UnitTestBase;

/**
 * Class ElasticEntityProcessorTest
 */
class ElasticEntityProcessorTest extends UnitTestBase
{
    public function testToArrayDateTime(): void
    {
        $processor = new ElasticEntityProcessor();

        $log = new DatetimeTestLog();

        //create the -4 time zone
        $log->setDate(new \DateTime('now', new \DateTimeZone('America/New_York')));
        $log->setString('string');

        $returned = $this->invokeMethod($processor, 'getElasticArray', [$log]);

        static::assertArrayHasKey('date', $returned);
        static::assertEquals($returned['date'], $log->getDate()->getTimestamp()*1000);

        static::assertArrayHasKey('string', $returned);
        static::assertEquals($returned['string'], $log->getString());

        static::assertArrayHasKey(ElasticEntityProcessor::METADATA_FIELD, $returned);
        $metadata = $returned[ElasticEntityProcessor::METADATA_FIELD];

        static::assertArrayHasKey('EntitiesToDecode', $metadata);
        static::assertEquals($metadata['EntitiesToDecode'], []);

        static::assertArrayHasKey(ElasticEntityProcessor::METADATA_DATETIME_FIELDS, $metadata);
        static::assertEquals($metadata[ElasticEntityProcessor::METADATA_DATETIME_FIELDS], ['date', 'createdAt']);

        static::assertArrayHasKey('SourceEntityClass', $metadata);
        static::assertEquals($metadata['SourceEntityClass'], get_class($log));
    }


    public function testDecodeArrayFormat()
    {
        $processor = new ElasticEntityProcessor();

        $log = new DatetimeTestLog();

        //create the -4 time zone
        $log->setDate(new \DateTime('now', new \DateTimeZone('America/New_York')));
        $log->setString('string');

        //convert to an array
        $arrayedLog = $processor->getElasticArray($log);
        //convert back to an entity
        /** @var DatetimeTestLog $decodedLog */
        $decodedLog = $processor->decodeArrayFormat($arrayedLog);

        static::assertInstanceOf(DatetimeTestLog::class, $decodedLog);
        static::assertEquals($log->getDate()->getTimestamp(), $decodedLog->getDate()->getTimestamp());
        static::assertEquals($log->getString(), $decodedLog->getString());
    }

    /**
     * This test has no special meaning
     * It just serves to understand the php's datetime
     */
    public function testPHPTimeZonesNow()
    {
        //create current UTC time
        $utc = new \DateTime('now', new \DateTimeZone('UTC'));
        //create the -4 time zone
        $newYork = new \DateTime('now', new \DateTimeZone('America/New_York'));
        //create the +9 time zone
        $tokyo = new \DateTime('now', new \DateTimeZone('Asia/Tokyo'));

        //check if the timezone names are correct
        static::assertEquals('UTC', $utc->getTimezone()->getName());
        static::assertEquals('America/New_York', $newYork->getTimezone()->getName());
        static::assertEquals('Asia/Tokyo', $tokyo->getTimezone()->getName());

        //check the timestamps
        static::assertEquals($utc->getTimestamp(), $newYork->getTimestamp());
        static::assertEquals($utc->getTimestamp(), $tokyo->getTimestamp());
    }

    /**
     * This test has no special meaning
     * It just serves to understand the php's datetime
     */
    public function testPHPTimeZonesSpecificDate()
    {
        $time = '2017-07-17 12:24:36';

        //create current UTC time
        $utc = new \DateTime($time, new \DateTimeZone('UTC'));
        //create the -4 time zone
        $newYork = new \DateTime($time, new \DateTimeZone('America/New_York'));
        //create the +9 time zone
        $tokyo = new \DateTime($time, new \DateTimeZone('Asia/Tokyo'));

        //check if the timezone names are correct
        static::assertEquals('UTC', $utc->getTimezone()->getName());
        static::assertEquals('America/New_York', $newYork->getTimezone()->getName());
        static::assertEquals('Asia/Tokyo', $tokyo->getTimezone()->getName());

        //check the timestamps - the timestamps are correct
        // because the same date in different timezone has different timestamp
        static::assertEquals($utc->getTimestamp(), $newYork->getTimestamp() - 60*60*4); //- 4 hours
        static::assertEquals($utc->getTimestamp(), $tokyo->getTimestamp() + 60*60*9); //+9 hours
    }
}
