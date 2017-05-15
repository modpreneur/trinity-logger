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

        $this->assertInstanceOf(ExceptionLog::class, $exceptionLog->setLog('test'));

        $this->assertEquals('test', $exceptionLog->getLog());

        $exceptionLog->setReadable('readable');

        $this->assertEquals('readable', $exceptionLog->getReadable());

        $this->assertInstanceOf(ExceptionLog::class, $exceptionLog->setServerData('serverData'));

        $this->assertEquals('serverData', $exceptionLog->getServerData());

        $this->assertInstanceOf(ExceptionLog::class, $exceptionLog->setLevel(98765432));

        $this->assertEquals(98765432, $exceptionLog->getLevel());

        $exceptionLog->setUrl('http//www.test.net');

        $this->assertEquals('http//www.test.net', $exceptionLog->getUrl());

        $exceptionLog->setIp('123.456.78.9');

        $this->assertEquals('123.456.78.9', $exceptionLog->getIp());

        $exceptionLog->setSystem('system');

        $this->assertEquals('system', $exceptionLog->getSystem());

        $exceptionLog->setUser($mockUser);

        $this->assertInstanceOf(UserInterface::class, $exceptionLog->getUser());

        $this->assertEquals(null, $exceptionLog->__toString());

        $errors = [
            400 => 'Error',
            500 => 'Critical',
            550 => 'Alert',
            600 => 'Emergency',
        ];

        $this->assertEquals($errors, $exceptionLog->getPossibleLevels());
    }
}
