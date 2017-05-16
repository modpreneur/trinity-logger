<?php

namespace Trinity\Bundle\LoggerBundle\Tests\Services;

use Doctrine\ORM\EntityManager;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Namespaces\IndicesNamespace;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Trinity\Bundle\LoggerBundle\Entity\EntityActionLog;
use Trinity\Bundle\LoggerBundle\Services\ElasticReadLogService;
use Elasticsearch\Common\Exceptions\Missing404Exception as NFException;
use Trinity\Bundle\SearchBundle\NQL\Column;
use Trinity\Bundle\SearchBundle\NQL\From;
use Trinity\Bundle\SearchBundle\NQL\NQLQuery;
use Trinity\Bundle\SearchBundle\NQL\OrderBy;
use Trinity\Bundle\SearchBundle\NQL\OrderingColumn;
use Trinity\Bundle\SearchBundle\NQL\Select;
use Trinity\Bundle\SearchBundle\NQL\Table;
use Trinity\Bundle\SearchBundle\NQL\Where;
use Trinity\Bundle\SearchBundle\NQL\WherePart;

/**
 * Class ElasticLogServiceTest
 * @package Trinity\Bundle\LoggerBundle\Tests\Services
 */
class ElasticReadLogServiceTest extends TestCase
{

    public function testGetById()
    {
        /** @var Table|Mock $table */
        $table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();

        $table->expects($this->any())
            ->method('getName')
            ->will(
                $this->returnValue('NotificationLog')
            );

        /** @var From|Mock $from */
        $from = $this->getMockBuilder(From::class)
            ->disableOriginalConstructor()
            ->getMock();

        $from->expects($this->any())
            ->method('getTables')
            ->will(
                $this->returnValue([0 => $table])
            );

        $column1 = new Column('test1');
        $column2 = new Column('test2');

        $orderingColumn1 = new OrderingColumn('test1');
        $orderingColumn2 = new OrderingColumn('test2');

        /** @var Select|Mock $select */
        $select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $select->expects($this->any())
            ->method('getColumns')
            ->will(
                $this->returnValue([$column1, $column2])
            );

        /** @var Where|Mock $where */
        $where = $this->getMockBuilder(Where::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var OrderBy|Mock $orderBy */
        $orderBy = $this->getMockBuilder(OrderBy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderBy->expects($this->any())
            ->method('getColumns')
            ->will(
                $this->returnValue([$orderingColumn1, $orderingColumn2])
            );

        /** @var NQLQuery|Mock $nqlQuery */
        $nqlQuery = $this->getMockBuilder(NQLQuery::class)
            ->disableOriginalConstructor()
            ->getMock();

        $nqlQuery->expects($this->any())
            ->method('getFrom')
            ->will(
                $this->returnValue($from)
            );

        $nqlQuery->expects($this->any())
            ->method('getSelect')
            ->will(
                $this->returnValue($select)
            );

        $nqlQuery->expects($this->any())
            ->method('getWhere')
            ->will(
                $this->returnValue($where)
            );

        $nqlQuery->expects($this->any())
            ->method('getOrderBy')
            ->will(
                $this->returnValue($orderBy)
            );

        /** @var EntityManager|Mock $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ClientBuilder|Mock $clientBuilder */
        $clientBuilder = $this->getMockBuilder(ClientBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Client|Mock $client */
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entity = new EntityActionLog();

        $response = [
            '_id' => 2,
            '_ttl' => 50,
            '_source' => [
                'ttl' => 76,
                'EntitiesToDecode' => [
                    'System',
                    'ChangedEntityId'
                ],
                'SourceEntityClass' => $entity,
                'System' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog',
                'ChangedEntityId' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog'
            ]
        ];

        $client->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValue($response)
            );

        $client->expects($this->any())
            ->method('count')
            ->will(
                $this->returnValue(['count' => 34])
            );

        /** @var IndicesNamespace|Mock $indicesNamespace */
        $indicesNamespace = $this->getMockBuilder(IndicesNamespace::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = [
            'hits' => [
                'hits' => [
                    'first' => [
                        '_source' => [
                            'ttl' => 76,
                            'EntitiesToDecode' => [
                                'System',
                                'ChangedEntityId'
                            ],
                            'SourceEntityClass' => $entity,
                            'System' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog',
                            'ChangedEntityId' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog'
                        ],
                        '_ttl' => 34
                    ]
                ]
            ]
        ];

        $indicesNamespace->expects($this->any())
            ->method('refresh')
            ->will(
                $this->returnValue(true)
            );

        $client->expects($this->any())
            ->method('indices')
            ->will(
                $this->returnValue($indicesNamespace)
            );

        $client->expects($this->any())
            ->method('search')
            ->will(
                $this->returnValue($result)
            );

        $clientBuilder->expects($this->any())
            ->method('setHosts')
            ->will(
                $this->returnValue($clientBuilder)
            );

        $clientBuilder->expects($this->any())
            ->method('build')
            ->will(
                $this->returnValue($client)
            );

        $elasticReadLogServiceNoBuilder = new ElasticReadLogService(
            '111.222.33.4:9200',
            $entityManager,
            'necktie'
        );

        $this->assertInstanceOf(ElasticReadLogService::class, $elasticReadLogServiceNoBuilder);

        $elasticReadLogService = new ElasticReadLogService(
            '111.222.33.4:9200',
            $entityManager,
            'necktie',
            $clientBuilder
        );

        $this->assertInstanceOf(
            EntityActionLog::class,
            $elasticReadLogService->setIndex('test123')->getById('test', 'necktie')
        );

        $query = [
            'key1' => 'value1',
            'key2' => 'value2'
        ];

        $this->assertEquals(34, $elasticReadLogService->getCount('test', $query));

        $searchParams = [
            'ttl',
            'test2'
        ];

        $select = [
            'select1',
            'select2'
        ];

        $this->assertInstanceOf(
            EntityActionLog::class,
            $elasticReadLogService->getMatchingEntities('test', $searchParams, 4, $select)[0]
        );

        $configuration = [
            'columns' => 'test'
        ];

        $this->assertInstanceOf(
            EntityActionLog::class,
            $elasticReadLogService->getByQuery($nqlQuery, 'test', $configuration)[0][0]
        );

        $this->assertNull($elasticReadLogService->getByQuery($nqlQuery, 'test', $configuration)[1]);
        $this->assertEmpty($elasticReadLogService->getByQuery($nqlQuery, 'test', $configuration)[2]);
    }


    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testGeyByIdException()
    {
        /** @var EntityManager|Mock $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ClientBuilder|Mock $clientBuilder */
        $clientBuilder = $this->getMockBuilder(ClientBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Client|Mock $client */
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $client->expects($this->any())
            ->method('get')
            ->will(
                $this->throwException(new NFException())
            );

        $clientBuilder->expects($this->any())
            ->method('setHosts')
            ->will(
                $this->returnValue($clientBuilder)
            );

        $clientBuilder->expects($this->any())
            ->method('build')
            ->will(
                $this->returnValue($client)
            );

        $elasticReadLogServiceNoBuilder = new ElasticReadLogService(
            '111.222.33.4:9200',
            $entityManager,
            'necktie'
        );

        $this->assertInstanceOf(ElasticReadLogService::class, $elasticReadLogServiceNoBuilder);

        $elasticReadLogService = new ElasticReadLogService(
            '111.222.33.4:9200',
            $entityManager,
            'necktie',
            $clientBuilder
        );

        $this->assertInstanceOf(
            EntityActionLog::class,
            $elasticReadLogService->setIndex('test123')->getById('test', 'necktie')
        );
    }


    public function testGeyCountException()
    {
        /** @var EntityManager|Mock $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        /** @var ClientBuilder|Mock $clientBuilder */
        $clientBuilder = $this->getMockBuilder(ClientBuilder::class)->disableOriginalConstructor()->getMock();

        /** @var Client|Mock $client */
        $client = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();
        $client->expects($this->any())->method('count')->will($this->throwException(new NFException()));

        $clientBuilder->expects($this->any())->method('setHosts')->will($this->returnValue($clientBuilder));
        $clientBuilder->expects($this->any())->method('build')->will($this->returnValue($client));

        $query = [
            'key1' => 'value1',
            'key2' => 'value2'
        ];

        $elasticReadLogService = new ElasticReadLogService(
            '111.222.33.4:9200',
            $entityManager,
            'necktie',
            $clientBuilder
        );

        $this->assertEquals(0, $elasticReadLogService->getCount('test', $query));
    }


    public function testGetMatchingEntities()
    {
        /** @var EntityManager|Mock $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ClientBuilder|Mock $clientBuilder */
        $clientBuilder = $this->getMockBuilder(ClientBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Client|Mock $client */
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entity = new EntityActionLog();

        $response = [
            '_id' => 2,
            '_ttl' => 50,
            '_source' => [
                'ttl' => 76,
                'EntitiesToDecode' => [
                    'System',
                    'ChangedEntityId'
                ],
                'SourceEntityClass' => $entity,
                'System' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog',
                'ChangedEntityId' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog'
            ]
        ];
        $client->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValue($response)
            );

        $client->expects($this->any())
            ->method('count')
            ->will(
                $this->returnValue(['count' => 34])
            );

        /** @var IndicesNamespace|Mock $indicesNamespace */
        $indicesNamespace = $this->getMockBuilder(IndicesNamespace::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result1 = [
            'hits' => [
                'hits' => [
                    'first' => [
                        '_source' => [
                            'ttl' => 76,
                            'EntitiesToDecode' => [
                                'System',
                                'ChangedEntityId'
                            ],
                            'SourceEntityClass' => $entity,
                            'System' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog',
                            'ChangedEntityId' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog'
                        ],
                        '_ttl' => 34,
                        '_id' => '',
                    ]
                ]
            ]
        ];

        $result2 = [
            'hits' => [
                'hits' => [
                    'first' => [
                        '_source' => [
                            'ttl' => 76,
                            'EntitiesToDecode' => [
                                'System',
                                'ChangedEntityId'
                            ],
                            'SourceEntityClass' => $entity,
                            'System' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog',
                            'ChangedEntityId' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog'
                        ],
                        '_ttl' => 34,
                        '_id' => '',
                    ]
                ]
            ],
            'aggregations' => 'test',
        ];

        $indicesNamespace->expects($this->any())
            ->method('refresh')
            ->will(
                $this->returnValue(true)
            );

        $client->expects($this->any())
            ->method('indices')
            ->will(
                $this->returnValue($indicesNamespace)
            );

        $client->expects($this->at(1))
            ->method('search')
            ->will(
                $this->returnValue($result1)
            );

        $client->expects($this->any())
            ->method('search')
            ->will(
                $this->returnValue($result2)
            );

        $clientBuilder->expects($this->any())
            ->method('setHosts')
            ->will(
                $this->returnValue($clientBuilder)
            );

        $clientBuilder->expects($this->any())
            ->method('build')
            ->will(
                $this->returnValue($client)
            );

        $elasticReadLogService = new ElasticReadLogService(
            '111.222.33.4:9200',
            $entityManager,
            'necktie',
            $clientBuilder
        );

        $searchParams = [
            'test1',
            'test2'
        ];

        $select = [
            'select1',
            'select2'
        ];

        $this->assertInstanceOf(
            EntityActionLog::class,
            $elasticReadLogService->getMatchingEntities('test', $searchParams, 4, $select)[0]
        );

        $this->assertEquals(
            'test',
            $elasticReadLogService->getMatchingEntities('test', $searchParams, 4, $select)['aggregations']
        );
    }


    public function testGetMatchingEntitiesException()
    {
        /** @var EntityManager|Mock $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ClientBuilder|Mock $clientBuilder */
        $clientBuilder = $this->getMockBuilder(ClientBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Client|Mock $client */
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entity = new EntityActionLog();

        $response = [
            '_id' => 2,
            '_ttl' => 50,
            '_source' => [
                'ttl' => 76,
                'EntitiesToDecode' => [
                    'System',
                    'ChangedEntityId'
                ],
                'SourceEntityClass' => $entity,
                'System' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog',
                'ChangedEntityId' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog'
            ]
        ];
        $client->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValue($response)
            );

        $client->expects($this->any())
            ->method('count')
            ->will(
                $this->returnValue(['count' => 34])
            );

        /** @var IndicesNamespace|Mock $indicesNamespace */
        $indicesNamespace = $this->getMockBuilder(IndicesNamespace::class)
            ->disableOriginalConstructor()
            ->getMock();

        $indicesNamespace->expects($this->any())
            ->method('refresh')
            ->will(
                $this->returnValue(true)
            );

        $client->expects($this->any())
            ->method('indices')
            ->will(
                $this->returnValue($indicesNamespace)
            );

        $client->expects($this->any())
            ->method('search')
            ->will(
                $this->throwException(new NFException())
            );

        $clientBuilder->expects($this->any())
            ->method('setHosts')
            ->will(
                $this->returnValue($clientBuilder)
            );

        $clientBuilder->expects($this->any())
            ->method('build')
            ->will(
                $this->returnValue($client)
            );

        $elasticReadLogService = new ElasticReadLogService(
            '111.222.33.4:9200',
            $entityManager,
            'necktie',
            $clientBuilder
        );

        $searchParams = [
            'test1',
            'test2'
        ];

        $select = [
            'select1',
            'select2'
        ];

        $this->assertEmpty($elasticReadLogService->getMatchingEntities('test', $searchParams, 4, $select));
    }


    /**
     * @expectedException \RuntimeException
     */
    public function testGetByQuery()
    {
        /** @var Table|Mock $table */
        $table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();

        $table->expects($this->any())
            ->method('getName')
            ->will(
                $this->returnValue('NotificationLog')
            );

        /** @var From|Mock $from */
        $from = $this->getMockBuilder(From::class)
            ->disableOriginalConstructor()
            ->getMock();

        $from->expects($this->any())
            ->method('getTables')
            ->will(
                $this->returnValue([0 => $table])
            );

        $column1 = new Column('test1');
        $column2 = new Column('id');

        $orderingColumn1 = new OrderingColumn('test1');
        $orderingColumn2 = new OrderingColumn('test2');
        $orderingColumn3 = new OrderingColumn('_id');
        $orderingColumn4 = new OrderingColumn('changedEntityClass');

        /** @var Select|Mock $select */
        $select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $select->expects($this->any())
            ->method('getColumns')
            ->will(
                $this->returnValue([$column1, $column2])
            );

        $wherePart1 = new WherePart();
        $wherePart1->value = 'OR';
        $wherePart1->operator = '=';
        $wherePart1->type = 'condition';
        $wherePart1->key = $column1;

        $wherePart2 = new WherePart();
        $wherePart2->operator = '!=';
        $wherePart2->type = 'condition';
        $wherePart2->key = $column2;

        $wherePart3 = new WherePart();
        $wherePart3->operator = '!=';
        $wherePart3->type = 'test';
        $wherePart3->key = $column2;

        /** @var Where|Mock $where */
        $where = $this->getMockBuilder(Where::class)
            ->disableOriginalConstructor()
            ->getMock();

        $where->expects($this->any())
            ->method('getConditions')
            ->will(
                $this->returnValue([$wherePart1, $wherePart2, $wherePart3])
            );

        /** @var OrderBy|Mock $orderBy */
        $orderBy = $this->getMockBuilder(OrderBy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderBy->expects($this->any())
            ->method('getColumns')
            ->will(
                $this->returnValue([$orderingColumn1, $orderingColumn2, $orderingColumn3, $orderingColumn4])
            );

        /** @var NQLQuery|Mock $nqlQuery */
        $nqlQuery = $this->getMockBuilder(NQLQuery::class)
            ->disableOriginalConstructor()
            ->getMock();

        $nqlQuery->expects($this->any())
            ->method('getFrom')
            ->will(
                $this->returnValue($from)
            );

        $nqlQuery->expects($this->any())
            ->method('getSelect')
            ->will(
                $this->returnValue($select)
            );

        $nqlQuery->expects($this->any())
            ->method('getOffset')
            ->will(
                $this->returnValue(3)
            );

        $nqlQuery->expects($this->any())
            ->method('getLimit')
            ->will(
                $this->returnValue(4)
            );

        $nqlQuery->expects($this->any())
            ->method('getWhere')
            ->will(
                $this->returnValue($where)
            );

        $nqlQuery->expects($this->any())
            ->method('getOrderBy')
            ->will(
                $this->returnValue($orderBy)
            );

        /** @var EntityManager|Mock $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ClientBuilder|Mock $clientBuilder */
        $clientBuilder = $this->getMockBuilder(ClientBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Client|Mock $client */
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entity = new EntityActionLog();

        $response = [
            '_id' => 2,
            '_ttl' => 50,
            '_source' => [
                'ttl' => 76,
                'EntitiesToDecode' => [
                    'System',
                    'ChangedEntityId'
                ],
                'SourceEntityClass' => $entity,
                'System' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog',
                'ChangedEntityId' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog'
            ]
        ];

        $client->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValue($response)
            );

        $client->expects($this->any())
            ->method('count')
            ->will(
                $this->returnValue(['count' => 34])
            );

        /** @var IndicesNamespace|Mock $indicesNamespace */
        $indicesNamespace = $this->getMockBuilder(IndicesNamespace::class)
            ->disableOriginalConstructor()
            ->getMock();

        $result = [
            'hits' => [
                'hits' => [
                    'first' => [
                        '_source' => [
                            'ttl' => 76,
                            'EntitiesToDecode' => [
                                'System',
                                'ChangedEntityId'
                            ],
                            'SourceEntityClass' => $entity,
                            'System' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog',
                            'ChangedEntityId' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog'
                        ],
                        '_ttl' => 34
                    ]
                ],
                'max_score' => 43
            ]
        ];

        $indicesNamespace->expects($this->any())
            ->method('refresh')
            ->will(
                $this->returnValue(true)
            );

        $client->expects($this->any())
            ->method('indices')
            ->will(
                $this->returnValue($indicesNamespace)
            );

        $client->expects($this->any())
            ->method('search')
            ->will(
                $this->returnValue($result)
            );

        $clientBuilder->expects($this->any())
            ->method('setHosts')
            ->will(
                $this->returnValue($clientBuilder)
            );

        $clientBuilder->expects($this->any())
            ->method('build')
            ->will(
                $this->returnValue($client)
            );


        $elasticReadLogService = new ElasticReadLogService(
            '111.222.33.4:9200',
            $entityManager,
            'necktie',
            $clientBuilder
        );

        $configuration = [
            'columns' => [
                0 => [
                    'id' => 'int',
                    'name' => 'string'
                ],
                1 => [
                    'id' => 'int',
                    'name' => 'string'
                ],
                2 => [
                    'id' => 'int',
                    'name' => 'string',
                    'type' => 'enum',
                ],
            ]
        ];

        $this->assertInstanceOf(
            EntityActionLog::class,
            $elasticReadLogService->getByQuery($nqlQuery, 'test', $configuration)[0][0]
        );

        $this->assertInstanceOf(
            EntityActionLog::class,
            $elasticReadLogService->getByQuery($nqlQuery, 'test')[0][0]
        );
    }


    public function testGetStatusByEntity()
    {
        /** @var EntityManager|Mock $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ClientBuilder|Mock $clientBuilder */
        $clientBuilder = $this->getMockBuilder(ClientBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Client|Mock $client */
        $client = $this->getMockBuilder(Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entity = new EntityActionLog();

        $response = [
            '_id' => 2,
            '_ttl' => 50,
            '_source' => [
                'ttl' => 76,
                'EntitiesToDecode' => [
                    'System',
                    'ChangedEntityId'
                ],
                'SourceEntityClass' => $entity,
                'System' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog',
                'ChangedEntityId' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog'
            ]
        ];

        $client->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValue($response)
            );

        $client->expects($this->any())
            ->method('count')
            ->will(
                $this->returnValue(['count' => 34])
            );

        $result1 = [
            'hits' => [
                'hits' => [
                    'first' => [
                        '_source' => [
                            'changeSet' => '{"info":1,"b":2,"c":3,"d":4,"e":5}',
                            'ttl' => 76,
                            'EntitiesToDecode' => [
                                'System',
                                'ChangedEntityId'
                            ],
                            'SourceEntityClass' => $entity,
                            'System' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog',
                            'ChangedEntityId' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog'
                        ],
                        '_ttl' => 34,
                        '_id' => 'test',
                    ]
                ]
            ]
        ];

        $result2 = [
            'hits' => [
                'hits' => [
                    'first' => [
                        '_source' => [
                            'ttl' => 76,
                            'EntitiesToDecode' => [
                                'System',
                                'ChangedEntityId'
                            ],
                            'SourceEntityClass' => $entity,
                            'System' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog',
                            'ChangedEntityId' => 'Trinity\Bundle\LoggerBundle\Entity\EntityActionLog'
                        ],
                        '_ttl' => 34,
                        '_id' => 'test',
                    ]
                ]
            ]
        ];

        /** @var IndicesNamespace|Mock $indicesNamespace */
        $indicesNamespace = $this->getMockBuilder(IndicesNamespace::class)
            ->disableOriginalConstructor()
            ->getMock();

        $indicesNamespace->expects($this->any())
            ->method('refresh')
            ->will(
                $this->returnValue(true)
            );

        $client->expects($this->any())
            ->method('indices')
            ->will(
                $this->returnValue($indicesNamespace)
            );

        $client->expects($this->at(1))
            ->method('search')
            ->will(
                $this->returnValue($result1)
            );

        $client->expects($this->at(2))
            ->method('search')
            ->will(
                $this->returnValue($result2)
            );

        $client->expects($this->at(3))
            ->method('search')
            ->will(
                $this->returnValue($result2)
            );

        $client->expects($this->at(4))
            ->method('search')
            ->will(
                $this->returnValue($result2)
            );

        $client->expects($this->at(5))
            ->method('search')
            ->will(
                $this->throwException(new NFException())
            );

        $clientBuilder->expects($this->any())
            ->method('setHosts')
            ->will(
                $this->returnValue($clientBuilder)
            );

        $clientBuilder->expects($this->any())
            ->method('build')
            ->will(
                $this->returnValue($client)
            );

        $elasticReadLogService = new ElasticReadLogService(
            '111.222.33.4:9200',
            $entityManager,
            'necktie',
            $clientBuilder
        );

        $entity = new EntityActionLog();

        $this->assertEquals(76, $elasticReadLogService->getStatusByEntity($entity)[0]['ttl']);

        $this->assertEquals(76, $elasticReadLogService->getStatusByEntity($entity)[0]['ttl']);

        $this->assertEmpty($elasticReadLogService->getStatusByEntity($entity));
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
