<?php

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\Tests\Services;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Trinity\Bundle\LoggerBundle\Entity\EntityActionLog;
use Trinity\Bundle\LoggerBundle\Services\ElasticEntityProcessor;
use Trinity\Bundle\LoggerBundle\Services\ElasticLogService;
use Elasticsearch\Namespaces\IndicesNamespace;
use Trinity\Bundle\LoggerBundle\Tests\UnitTestBase;
use Trinity\Component\Core\Interfaces\UserInterface;

/**
 * Class ElasticLogServiceTest
 * @package Trinity\Bundle\LoggerBundle\Tests\Services
 */
class ElasticLogServiceTest extends UnitTestBase
{
    public function testConstruct(): void
    {
        $types = ['green', 'red', 'yellow'];
        $values = ['avocado', 'apple', 'banana'];

        /** @var ElasticEntityProcessor|Mock $processor */
        $processor = $this->getMockBuilder(ElasticEntityProcessor::class)->getMock();

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

        /** @var Client|Mock $esClient */
        $esClient = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $esClient->expects(static::at(0))
            ->method('index')
            ->will(
                static::returnValue(['_id' => 2])
            );

        $esClient->expects(static::at(1))
            ->method('index')
            ->will(
                static::returnValue(['_id' => '3'])
            );

        $esClient->expects(static::any())
            ->method('indices')
            ->will(
                static::returnValue($indicesNamespaces)
            );

        $esClient->expects(static::once())
            ->method('update')
            ->will(
                static::returnValue(true)
            );

        $clientBuilder->expects(static::once())
            ->method('build')
            ->will(
                static::returnValue($esClient)
            );

        $els = new ElasticLogService(
            $processor,
            '111.222.33.4:9200',
            true,
            'test',
            50,
            $clientBuilder
        );

        /** @var UserInterface|Mock $userInterface */
        $userInterface = $this->getMockBuilder(UserInterface::class)->disableOriginalConstructor()->getMock();

        $entity = new EntityActionLog();
        $entity->setUser($userInterface);

        $els->writeIntoAsync('testTypeName', $entity, 4);

        $els->writeInto('testTypeName', $entity, 4);
        static::assertEquals('3', $entity->getId());

        $els->update('tesTypeName', '1', $types, $values, 4);
    }
}
