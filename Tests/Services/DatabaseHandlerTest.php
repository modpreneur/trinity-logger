<?php
/**
 * Created by PhpStorm.
 * User: gabriel
 * Date: 25.1.17
 * Time: 16:01
 */

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\Tests\Services;

use Monolog\Logger;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Trinity\Bundle\LoggerBundle\Entity\ExceptionLog;
use Trinity\Bundle\LoggerBundle\Services\DatabaseHandler;
use Trinity\Bundle\LoggerBundle\Services\ElasticLogService;
use Trinity\Bundle\LoggerBundle\Tests\UnitTestBase;
use Trinity\Component\Core\Interfaces\UserInterface;

/**
 * Class DatabaseHandlerTest
 * @package Trinity\Bundle\LoggerBundle\Tests\Services
 */
class DatabaseHandlerTest extends UnitTestBase
{
    public function testLogRecord(): void
    {
        $uri = 'http://some_uri.fn';
        $clientIp = '127.0.0.2';

        /** @var Session|Mock $session */
        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var TokenStorageInterface|Mock $tokenStorage */
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var RequestStack|Mock $requestStack */
        $requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ElasticLogService|Mock $esLogger */
        $esLogger = $this->getMockBuilder(ElasticLogService::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var UserInterface|Mock $user */
        $user = $this->getMockBuilder(UserInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Request|Mock $request */
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestStack->expects(static::once())
            ->method('getCurrentRequest')
            ->will(
                static::returnValue($request)
            );

        $request->expects(static::once())
            ->method('getUri')
            ->will(
                static::returnValue($uri)
            );

        $request->expects(static::once())
            ->method('getClientIp')
            ->will(
                static::returnValue($clientIp)
            );

        $token = $this->getMockBuilder(TokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tokenStorage->expects(static::once())
            ->method('getToken')
            ->will(
                static::returnValue($token)
            );

        $session->expects(static::once())
            ->method('set')
            ->with('readable', 'TestErrorMessage');

        $session->expects(static::once())
            ->method('isStarted')
            ->will(
                static::returnValue(true)
            );

        $token->expects(static::exactly(4))
            ->method('getUser')
            ->will(
                static::returnValue($user)
            );


        $esLogger->expects(static::once())
            ->method('writeInto')
            ->with(
                ExceptionLog::LOG_NAME,
                static::callback(
                    function (ExceptionLog $log) use ($clientIp, $uri, $user) {
                        static::assertEquals('TestErrorMessage', $log->getReadable());
                        static::assertEquals(Logger::ERROR, $log->getLevel());
                        static::assertEquals('testServerData', $log->getServerData());
                        static::assertEquals($clientIp, $log->getIp());
                        static::assertEquals($uri, $log->getUrl());
                        static::assertEquals('PDOException:R:  testErrorMessage' . PHP_EOL, $log->getLog());
                        static::assertEquals($user, $log->getUser());
                        return true;
                    }
                )
            );

        $handler = new DatabaseHandler(
            $session,
            $tokenStorage,
            $requestStack,
            $esLogger
        );

        $record = [
            'level' => Logger::ERROR,
            'channel' => 'testChannel',
            'message' => 'PDOException:R:  testErrorMessage' . PHP_EOL,
            'context' => [],
            'extra' => [
                'serverData' => 'testServerData',
            ],

        ];

        $handler->handle($record);
    }


    public function testLogRecord2(): void
    {
        /** @var Session|Mock $session */
        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var TokenStorageInterface|Mock $tokenStorage */
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var RequestStack|Mock $requestStack */
        $requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ElasticLogService|Mock $esLogger */
        $esLogger = $this->getMockBuilder(ElasticLogService::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var UserInterface|Mock $user */
        $user = $this->getMockBuilder(UserInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $requestStack->expects(static::once())
            ->method('getCurrentRequest')
            ->will(
                static::returnValue(null)
            );

        /** @var TokenInterface|Mock $token */
        $token = $this->getMockBuilder(TokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $token->expects(static::any())
            ->method('getUser')
            ->will(
                static::returnValue($user)
            );

        $session->expects(static::any())
            ->method('all')
            ->willReturn([]);

        $databaseHandler = new DatabaseHandler($session, $tokenStorage, $requestStack, $esLogger);

        $record = [
            'level' => 100,
            'channel' => 'doctrine',
            'message' => 'testErrorMessage' . PHP_EOL,
            'context' => [],
            'extra' => [
                'serverData' => 'testServerData',
            ],

        ];

        static::assertEmpty($this->invokeMethod($databaseHandler, 'write', [$record]));

        $record = [
            'level' => 900,
            'channel' => 'testChannel',
            'message' => 'Uncaught' . PHP_EOL,
            'context' => [],
            'extra' => [
                'serverData' => 'testServerData',
            ],

        ];

        static::assertEmpty($this->invokeMethod($databaseHandler, 'write', [$record]));

        $record = [
            'level' => Logger::ERROR,
            'channel' => 'testChannel',
            'message' => 'PDOException:R:  testErrorMessage' . PHP_EOL,
            'context' => [],
            'extra' => [
                'serverData' => 'REQUEST_URI: testServerData',
            ],

        ];

        static::assertEmpty($this->invokeMethod($databaseHandler, 'write', [$record]));
    }


    public function testGetReadable(): void
    {
        /** @var Session|Mock $session */
        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var TokenStorageInterface|Mock $tokenStorage */
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var RequestStack|Mock $requestStack */
        $requestStack = $this->getMockBuilder(RequestStack::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ElasticLogService|Mock $esLogger */
        $esLogger = $this->getMockBuilder(ElasticLogService::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var UserInterface|Mock $user */
        $user = $this->getMockBuilder(UserInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var TokenInterface|Mock $token */
        $token = $this->getMockBuilder(TokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $token->expects(static::any())
            ->method('getUser')
            ->will(
                static::returnValue($user)
            );

        $databaseHandler = new DatabaseHandler($session, $tokenStorage, $requestStack, $esLogger);

        $e = [
            'message' => 'testMessage'
        ];

        static::assertEquals('', $this->invokeMethod($databaseHandler, 'getReadable', [$e]));
    }
}
