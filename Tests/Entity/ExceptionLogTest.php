<?php

namespace Trinity\Bundle\LoggerBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Trinity\Bundle\LoggerBundle\Entity\ExceptionLog;
use Trinity\Component\Core\Interfaces\UserInterface;

/**
 * Class ExceptionLogTest
 * @package Trinity\Bundle\LoggerBundle\Tests\Entity
 */
class ExceptionLogTest extends TestCase
{
    public function testEntity()
    {
        /** @var ExceptionLog $exceptionLog */
        $exceptionLog = new ExceptionLog();

        /** @var MockUser $mockUser */
        $mockUser = new MockUser();

        static::assertInstanceOf(ExceptionLog::class, $exceptionLog->setLog('test'));

        static::assertEquals('test', $exceptionLog->getLog());

        $exceptionLog->setReadable('readable');

        static::assertEquals('readable', $exceptionLog->getReadable());

        static::assertInstanceOf(ExceptionLog::class, $exceptionLog->setServerData('serverData'));

        static::assertEquals('serverData', $exceptionLog->getServerData());

        static::assertInstanceOf(ExceptionLog::class, $exceptionLog->setLevel(98765432));

        static::assertEquals(98765432, $exceptionLog->getLevel());

        $exceptionLog->setUrl('http//www.test.net');

        static::assertEquals('http//www.test.net', $exceptionLog->getUrl());

        $exceptionLog->setIp('123.456.78.9');

        static::assertEquals('123.456.78.9', $exceptionLog->getIp());

        $exceptionLog->setSystem('system');

        static::assertEquals('system', $exceptionLog->getSystem());

        $exceptionLog->setUser($mockUser);

        static::assertInstanceOf(UserInterface::class, $exceptionLog->getUser());

        static::assertEquals(null, $exceptionLog->__toString());

        $errors = [
            400 => 'Error',
            500 => 'Critical',
            550 => 'Alert',
            600 => 'Emergency',
        ];

        static::assertEquals($errors, $exceptionLog::getPossibleLevels());
    }
}
