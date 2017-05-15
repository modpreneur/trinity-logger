<?php
/**
 * Created by PhpStorm.
 * User: gabriel
 * Date: 25.1.17
 * Time: 16:01
 */

namespace Trinity\Bundle\LoggerBundle\Tests\Services;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Trinity\Bundle\LoggerBundle\Entity\ExceptionLog;
use Trinity\Bundle\LoggerBundle\Services\DatabaseHandler;
use Trinity\Bundle\LoggerBundle\Services\ElasticLogServiceWithTtl;
use Trinity\Component\Core\Interfaces\UserInterface;

/**
 * Class DatabaseHandlerTest
 * @package Trinity\Bundle\LoggerBundle\Tests\Services
 */
class DatabaseHandlerTest extends TestCase
{
    public function testLogRecord()
    {
        $uri = 'http://some_uri.fn';
        $clientIp = '127.0.0.2';

        $session = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->disableOriginalConstructor()->getMock();
        $requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $esLogger = $this->getMockBuilder(ElasticLogServiceWithTtl::class)->disableOriginalConstructor()->getMock();
        $user = $this->getMockBuilder(UserInterface::class)->disableOriginalConstructor()->getMock();

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $requestStack->expects($this->once())->method('getCurrentRequest')->will($this->returnValue($request));
        $request->expects($this->once())->method('getUri')->will($this->returnValue($uri));
        $request->expects($this->once())->method('getClientIp')->will($this->returnValue($clientIp));

        $token = $this->getMockBuilder(TokenInterface::class)->disableOriginalConstructor()->getMock();
        $tokenStorage->expects($this->once())->method('getToken')->will($this->returnValue($token));

        $session->expects($this->once())->method('set')->with('readable', 'TestErrorMessage');
        $session->expects($this->once())->method('isStarted')->will($this->returnValue(true));

        $token->expects($this->exactly(4))->method('getUser')->will($this->returnValue($user));


        $esLogger->expects($this->once())->method('writeInto')->with(
            ExceptionLog::NAME,
            $this->callback(
                function (ExceptionLog $log) use ($clientIp, $uri, $user) {
                    $this->assertEquals('TestErrorMessage', $log->getReadable());
                    $this->assertEquals(Logger::ERROR, $log->getLevel());
                    $this->assertEquals('testServerData', $log->getServerData());
                    $this->assertEquals($clientIp, $log->getIp());
                    $this->assertEquals($uri, $log->getUrl());
                    $this->assertEquals('PDOException:R:  testErrorMessage'.PHP_EOL, $log->getLog());
                    $this->assertEquals($user, $log->getUser());
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
            'level'         => Logger::ERROR,
            'channel'       => 'testChannel',
            'message'       => 'PDOException:R:  testErrorMessage'.PHP_EOL,
            'context'       => [],
            'extra'         => [
                'serverData'    => 'testServerData',
            ],

        ];

        $handler->handle($record);
    }


    public function testLogRecord2()
    {
        $uri = 'http://some_uri.fn';
        $clientIp = '127.0.0.2';

        $session = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->disableOriginalConstructor()->getMock();
        $requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $esLogger = $this->getMockBuilder(ElasticLogServiceWithTtl::class)->disableOriginalConstructor()->getMock();
        $user = $this->getMockBuilder(UserInterface::class)->disableOriginalConstructor()->getMock();

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $requestStack->expects($this->once())->method('getCurrentRequest')->will($this->returnValue(null));
        //$request->expects($this->once())->method('getUri')->will($this->returnValue($uri));
        //$request->expects($this->once())->method('getClientIp')->will($this->returnValue($clientIp));

        $token = $this->getMockBuilder(TokenInterface::class)->disableOriginalConstructor()->getMock();
        //$tokenStorage->expects($this->once())->method('getToken')->will($this->returnValue($token));

        //$session->expects($this->once())->method('set')->with('readable', 'TestErrorMessage');
        //$session->expects($this->once())->method('isStarted')->will($this->returnValue(true));

        $token->expects($this->any(4))->method('getUser')->will($this->returnValue($user));

        $databaseHandler = new DatabaseHandler($session, $tokenStorage, $requestStack, $esLogger);

        $record = [
            'level'         => 900,
            'channel'       => 'doctrine',
            'message'       => 'testErrorMessage'.PHP_EOL,
            'context'       => [],
            'extra'         => [
                'serverData'    => 'testServerData',
            ],

        ];

        $this->assertEmpty($this->invokeMethod($databaseHandler, 'write', [$record]));

        $record = [
            'level'         => 900,
            'channel'       => 'testChannel',
            'message'       => 'Uncaught'.PHP_EOL,
            'context'       => [],
            'extra'         => [
                'serverData'    => 'testServerData',
            ],

        ];

        $this->assertEmpty($this->invokeMethod($databaseHandler, 'write', [$record]));

        $record = [
            'level'         => Logger::ERROR,
            'channel'       => 'testChannel',
            'message'       => 'PDOException:R:  testErrorMessage'.PHP_EOL,
            'context'       => [],
            'extra'         => [
                'serverData'    => 'REQUEST_URI: testServerData',
            ],

        ];

        $this->assertEmpty($this->invokeMethod($databaseHandler, 'write', [$record]));
    }


    public function testGetReadable()
    {
        $uri = 'http://some_uri.fn';
        $clientIp = '127.0.0.2';

        $session = $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->disableOriginalConstructor()->getMock();
        $requestStack = $this->getMockBuilder(RequestStack::class)->disableOriginalConstructor()->getMock();
        $esLogger = $this->getMockBuilder(ElasticLogServiceWithTtl::class)->disableOriginalConstructor()->getMock();
        $user = $this->getMockBuilder(UserInterface::class)->disableOriginalConstructor()->getMock();

        $request = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
      //  $requestStack->expects($this->once())->method('getCurrentRequest')->will($this->returnValue(null));
        //$request->expects($this->once())->method('getUri')->will($this->returnValue($uri));
        //$request->expects($this->once())->method('getClientIp')->will($this->returnValue($clientIp));

        $token = $this->getMockBuilder(TokenInterface::class)->disableOriginalConstructor()->getMock();
        //$tokenStorage->expects($this->once())->method('getToken')->will($this->returnValue($token));

        //$session->expects($this->once())->method('set')->with('readable', 'TestErrorMessage');
        //$session->expects($this->once())->method('isStarted')->will($this->returnValue(true));

        $token->expects($this->any(4))->method('getUser')->will($this->returnValue($user));

        $databaseHandler = new DatabaseHandler($session, $tokenStorage, $requestStack, $esLogger);

        $e = [
            'message' => 'testMessage'
        ];

        $this->assertEquals('', $this->invokeMethod($databaseHandler, 'getReadable', [$e]));
    }


    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
