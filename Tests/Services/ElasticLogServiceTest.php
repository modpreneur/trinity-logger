<?php

namespace Trinity\Bundle\LoggerBundle\Tests\Services;


use Closure;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Endpoints\AbstractEndpoint;
use Guzzle\Http\Message\Request;
use GuzzleHttp\Ring\Client\CurlMultiHandler;
use GuzzleHttp\Ring\Client\Middleware;
use GuzzleHttp\Ring\Future\FutureArray;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockBuilder;
use Trinity\Bundle\LoggerBundle\Services\DefaultTtlProvider;
use Trinity\Bundle\LoggerBundle\Services\ElasticLogService;
use Trinity\Bundle\LoggerBundle\Services\ElasticLogServiceWithTtl;

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

   /* public function testSetIndex()
    {
        $property = $this->getBase();

        /** @var ElasticLogService $ELS */
        /*$ELS = $property['ELS'];

        //$ELClient = $property['ESClient'];



        $this->assertInstanceOf(ElasticLogService::class, $ELS->setIndex('test123'));

    }
    */
/*
    public function testWriteIntoAsync()
    {
        $property = $this->getBase();*/

        /** @var ElasticLogService $ELS */
        /*$ELS = $property['ELS'];
        $FutureArray = $this->getMockBuilder(FutureArray::class)->disableOriginalConstructor()->getMock();
        //$ESClient = $property['ESClient'];
        $ESClient = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();
        $params = [
            'index' => 'necktie',
            'type' => 'logName',
            'body' => 'client',
            'client' => ['future' => 'lazy'],
        ];

        $ESClient->expects($this->any())->method('index')->with($params)
            ->will($this->returnValueMap(['_id' => $FutureArray]));

        //$ELS->writeIntoAsync($this->logName, $this->object, $this->ttl);
        $this->assertInstanceOf(FutureArray::class, $ELS->writeIntoAsync($this->logName, $this->object, $this->ttl));

    }*/

   /* public function testUpdate(){

        $property = $this->getBase();


        //$ELS = $this->getMockBuilder(ElasticLogService::class)->disableOriginalConstructor()->getMock();
        $ELS = $property['ELS'];

        $types = ['green', 'red', 'yellow'];
        $values = ['avocado', 'apple', 'banana'];


        //$ESClient = $property['ESClient'];


        /*$ELS->expects($this->once())->method('update')->with($this->logName, 'necktie', $types, $values)
            ->willReturn(true);*/


        //$ELS->update($this->logName, 'necktie', $types, $values);

        //$this->assertEquals(true, );

        //$this->assertInstanceOf(ElasticLogService::class, );
        //update(string $typeName, string $id, array $types, array $values, int $ttl = 0)
    //}

    /**
     * @return array
     */
    private function getBase()
    {


        $types = ['green', 'red', 'yellow'];
        $values = ['avocado', 'apple', 'banana'];

        $ESClient = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();


        /*$Closure = $this->getMockBuilder(Closure::class)->disableOriginalConstructor()->getMock();*/

        $params = [
            'index' => 'necktie',
            'type' => $this->logName,
            'id' => 'necktie',
            'body' => [
                'doc' => array_combine($types, $values)
            ]
        ];

        $ESClient->expects($this->once())->method('update')->with($params)
            ->willReturn(true);

        /*$ESClient->expects($this->once())->method('setHandler')->with($params)
            ->willReturn(true);*/

        $default = $this->getMockBuilder(CurlMultiHandler::class)->disableOriginalConstructor()->getMock();
        $clientBuilder = $this->getMockBuilder(ClientBuilder::class)->disableOriginalConstructor()->getMock();
        //$abstractEndpoint->expects($this->any())->method('resultOrFuture')
          //  ->will($this->returnValue(['_id' => $FutureArray]));

        $clientBuilder->expects($this->any())->method('defaultHandler')->with($this->handlerParams)
            ->will($this->returnValue($default));

        $clientBuilder->expects($this->any())->method('setHosts')->with(["111.222.33.4:9200"])
        ->will($this->returnValue($clientBuilder));
        /*                  TODO: na mockovat konstruktor popripade predelat zjistit pocet referenci problem s Mockem na Callable
        $clientBuilder->expects($this->any())->method('setHandler')->with(\Closure::fromCallable(Request)
            ->will($this->returnValue());
            */
        $clientBuilder->expects($this->any())->method('build')->will($this->returnValue($ESClient));

        $clientBuilder->expects($this->any())->method('create')->will($this->returnValue($clientBuilder));

        $ELS  = new ElasticLogService('111.222.33.4:9200', 'necktie', 50, $clientBuilder);

        return [
            'ELS' => $ELS
        ];
    }

}