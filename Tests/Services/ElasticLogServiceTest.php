<?php

namespace Trinity\Bundle\LoggerBundle\Tests\Services;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use PHPUnit\Framework\TestCase;
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

        $clientBuilder = $this->getMockBuilder(ClientBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $clientBuilder->expects($this->any())
            ->method('setHosts')
            ->with([0 => '111.222.33.4:9200'])
            ->will(
                $this->returnValue($clientBuilder)
            );

        $clientBuilder->expects($this->any())
            ->method('setHandler')
            ->will(
                $this->returnValue($clientBuilder)
            );

        $indicesNamespaces = $this->getMockBuilder(IndicesNamespace::class)
            ->disableOriginalConstructor()
            ->getMock();

        $esclient = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $esclient->expects($this->any())
            ->method('index')
            ->will(
                $this->returnValue(['_id' => 'testValue'])
            );

        $esclient->expects($this->any())
            ->method('indices')
            ->will(
                $this->returnValue($indicesNamespaces)
            );

        $esclient->expects($this->once())
            ->method('update')
            ->will(
                $this->returnValue(true)
            );

        $clientBuilder->expects($this->once())
            ->method('build')
            ->will(
                $this->returnValue($esclient)
            );

        $els  = new ElasticLogService('111.222.33.4:9200', 'necktie', 50, $clientBuilder);

        $userInterface = $this->getMockBuilder(UserInterface::class)->disableOriginalConstructor()->getMock();

        $entity = new EntityActionLog();
        $entity->setUser($userInterface);

        $this->assertEquals('testValue', $els->writeIntoAsync('testTypeName', $entity, 4));
        $this->assertEquals('testValue', $els->writeInto('testTypeName', $entity, 4));

        $els->update('tesTypeName', '1', $types, $values, 4);

        $this->assertInstanceOf(ElasticLogService::class, $els->setIndex('test'));

        $this->assertTrue(
            array_key_exists(
                'system',
                $this->invokeMethod($els, 'getElasticArray', [$entity])
            )
        );

        $els  = new ElasticLogService('111.222.33.4:9200', 'necktie');

        $this->assertInstanceOf(ElasticLogService::class, $els->setIndex('test'));
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
