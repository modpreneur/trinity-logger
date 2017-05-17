<?php

namespace Trinity\Bundle\LoggerBundle\Tests\Services;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Trinity\Bundle\LoggerBundle\Entity\EntityActionLog;
use Trinity\Bundle\LoggerBundle\Services\ElasticLogService;
use Elasticsearch\Namespaces\IndicesNamespace;
use Trinity\Component\Core\Interfaces\UserInterface;

/**
 * Class ElasticLogServiceTest
 * @package Trinity\Bundle\LoggerBundle\Tests\Services
 */
class ElasticLogServiceTest extends TestCase
{

    /** @var string  */
    protected $logName = 'logName';

    /** @var int  */
    protected $ttl = 0;

    /** @var null  */
    protected $object = null;

    public $handlerParams = [
        'max_handles' => 50
    ];


    public function testConstruct()
    {
        $types = ['green', 'red', 'yellow'];
        $values = ['avocado', 'apple', 'banana'];

        /** @var ClientBuilder|Mock $clientBuilder */
        $clientBuilder = $this->getMockBuilder(ClientBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $clientBuilder->expects(static::any())
            ->method('setHosts')
            ->with([0 => '111.222.33.4:9200'])
            ->will(
                static::returnValue($clientBuilder)
            );

        $clientBuilder->expects(static::any())
            ->method('setHandler')
            ->will(
                static::returnValue($clientBuilder)
            );

        /** @var IndicesNamespace|Mock $indicesNamespaces */
        $indicesNamespaces = $this->getMockBuilder(IndicesNamespace::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Client|Mock $esclient */
        $esclient = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $esclient->expects(static::any())
            ->method('index')
            ->will(
                static::returnValue(['_id' => 'testValue'])
            );

        $esclient->expects(static::any())
            ->method('indices')
            ->will(
                static::returnValue($indicesNamespaces)
            );

        $esclient->expects(static::once())
            ->method('update')
            ->will(
                static::returnValue(true)
            );

        $clientBuilder->expects(static::once())
            ->method('build')
            ->will(
                static::returnValue($esclient)
            );

        $els  = new ElasticLogService('111.222.33.4:9200', 'necktie', 50, $clientBuilder);

        /** @var UserInterface|Mock $userInterface */
        $userInterface = $this->getMockBuilder(UserInterface::class)->disableOriginalConstructor()->getMock();

        $entity = new EntityActionLog();
        $entity->setUser($userInterface);

        static::assertEquals('testValue', $els->writeIntoAsync('testTypeName', $entity, 4));
        static::assertEquals('testValue', $els->writeInto('testTypeName', $entity, 4));

        $els->update('tesTypeName', '1', $types, $values, 4);

        static::assertInstanceOf(ElasticLogService::class, $els->setIndex('test'));

        static::assertTrue(
            array_key_exists(
                'system',
                $this->invokeMethod($els, 'getElasticArray', [$entity])
            )
        );

        $els  = new ElasticLogService('111.222.33.4:9200', 'necktie');

        static::assertInstanceOf(ElasticLogService::class, $els->setIndex('test'));
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
