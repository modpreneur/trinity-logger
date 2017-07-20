<?php

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\Tests\Services;

use Doctrine\ORM\EntityManager;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Namespaces\IndicesNamespace;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Trinity\Bundle\LoggerBundle\Entity\EntityActionLog;
use Trinity\Bundle\LoggerBundle\Services\ElasticEntityProcessor;
use Trinity\Bundle\LoggerBundle\Services\ElasticReadLogService;
use Elasticsearch\Common\Exceptions\Missing404Exception as NFException;
use Trinity\Bundle\LoggerBundle\Tests\Entity\MockUser;
use Trinity\Bundle\LoggerBundle\Tests\UnitTestBase;
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
class ElasticReadLogServiceTest extends UnitTestBase
{
    public function testGetById(): void
    {
        /** @var Table|Mock $table */
        $table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();

        $table->expects(static::any())
            ->method('getName')
            ->will(
                static::returnValue('NotificationLog')
            );

        /** @var From|Mock $from */
        $from = $this->getMockBuilder(From::class)
            ->disableOriginalConstructor()
            ->getMock();

        $from->expects(static::any())
            ->method('getTables')
            ->will(
                static::returnValue([0 => $table])
            );

        $column1 = new Column('test1');
        $column2 = new Column('test2');

        $orderingColumn1 = new OrderingColumn('test1');
        $orderingColumn2 = new OrderingColumn('test2');

        /** @var Select|Mock $select */
        $select = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $select->expects(static::any())
            ->method('getColumns')
            ->will(
                static::returnValue([$column1, $column2])
            );

        /** @var Where|Mock $where */
        $where = $this->getMockBuilder(Where::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var OrderBy|Mock $orderBy */
        $orderBy = $this->getMockBuilder(OrderBy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderBy->expects(static::any())
            ->method('getColumns')
            ->will(
                static::returnValue([$orderingColumn1, $orderingColumn2])
            );

        /** @var NQLQuery|Mock $nqlQuery */
        $nqlQuery = $this->getMockBuilder(NQLQuery::class)
            ->disableOriginalConstructor()
            ->getMock();

        $nqlQuery->expects(static::any())
            ->method('getFrom')
            ->will(
                static::returnValue($from)
            );

        $nqlQuery->expects(static::any())
            ->method('getSelect')
            ->will(
                static::returnValue($select)
            );

        $nqlQuery->expects(static::any())
            ->method('getWhere')
            ->will(
                static::returnValue($where)
            );

        $nqlQuery->expects(static::any())
            ->method('getOrderBy')
            ->will(
                static::returnValue($orderBy)
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
                ElasticEntityProcessor::METADATA_DATETIME_FIELDS => [],
                ElasticEntityProcessor::METADATA_ENTITIES_TO_DECODE_FIELDS => [
                  //  'System',
                    'ChangedEntityId',
                ],
                ElasticEntityProcessor::METADATA_SOURCE_ENTITY_CLASS_FIELD => $entity,
               // 'System' => EntityActionLog::class,
                'ChangedEntityId' => EntityActionLog::class
            ]
        ];

        $client->expects(static::any())
            ->method('get')
            ->will(
                static::returnValue($response)
            );

        $client->expects(static::any())
            ->method('count')
            ->will(
                static::returnValue(['count' => 34])
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
                            ElasticEntityProcessor::METADATA_DATETIME_FIELDS => [],
                            ElasticEntityProcessor::METADATA_ENTITIES_TO_DECODE_FIELDS => [
                                //'System',
                                'ChangedEntityId'
                            ],
                            ElasticEntityProcessor::METADATA_SOURCE_ENTITY_CLASS_FIELD => $entity,
                           // 'System' => EntityActionLog::class,
                            'ChangedEntityId' => EntityActionLog::class,

                        ],
                        '_ttl' => 34,
                        '_id' => 'test',
                        '_score' => 34,
                        '_index' => 2,
                    ]
                ],
                'max_score' => 43,
                'total' => 34,
            ]
        ];

        $indicesNamespace->expects(static::any())
            ->method('refresh')
            ->will(
                static::returnValue(true)
            );

        $client->expects(static::any())
            ->method('indices')
            ->will(
                static::returnValue($indicesNamespace)
            );

        $client->expects(static::any())
            ->method('search')
            ->will(
                static::returnValue($result)
            );

        $clientBuilder->expects(static::any())
            ->method('setHosts')
            ->will(
                static::returnValue($clientBuilder)
            );

        $clientBuilder->expects(static::any())
            ->method('build')
            ->will(
                static::returnValue($client)
            );

        /** @var ElasticEntityProcessor|Mock $processor */
        $processor = $this->getMockBuilder(ElasticEntityProcessor::class)->getMock();
        $processor
            ->expects($this->exactly(5))
            ->method('decodeArrayFormat')
            ->willReturn(new EntityActionLog());

        $elasticReadLogService = new ElasticReadLogService(
            $processor,
            '111.222.33.4:9200',
            'test',
            $entityManager,
            $clientBuilder
        );

        static::assertInstanceOf(
            EntityActionLog::class,
            $elasticReadLogService->setIndex('test123')->getById('test', 'identification#index')
        );

        $query = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        static::assertEquals(34, $elasticReadLogService->getCount('test', $query));

        $searchParams = [
            'ttl',
            'test2',
        ];

        $select = [
            'select1',
            'select2',
        ];

        static::assertInstanceOf(
            EntityActionLog::class,
            $elasticReadLogService->getMatchingEntities('test', $searchParams, 4, $select)[0]
        );

        $configuration = [
            'columns' => 'test',
        ];

        static::assertInstanceOf(
            EntityActionLog::class,
            $elasticReadLogService->getByQuery($nqlQuery, 'test', $configuration)[0][0]
        );

        static::assertEquals(34, $elasticReadLogService->getByQuery($nqlQuery, 'test', $configuration)[1]);
        static::assertEquals(
            0.7906976744186,
            $elasticReadLogService->getByQuery($nqlQuery, 'test', $configuration)[2][0]
        );
    }


    /**
     * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testGeyByIdException(): void
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

        $client->expects(static::any())
            ->method('get')
            ->will(
                static::throwException(new NFException())
            );

        $clientBuilder->expects(static::any())
            ->method('setHosts')
            ->will(
                static::returnValue($clientBuilder)
            );

        $clientBuilder->expects(static::any())
            ->method('build')
            ->will(
                static::returnValue($client)
            );

        /** @var ElasticEntityProcessor|Mock $processor */
        $processor = $this->getMockBuilder(ElasticEntityProcessor::class)->getMock();

        $elasticReadLogServiceNoBuilder = new ElasticReadLogService(
            $processor,
            '111.222.33.4:9200',
            'test',
            $entityManager
        );

        static::assertInstanceOf(ElasticReadLogService::class, $elasticReadLogServiceNoBuilder);

        $elasticReadLogService = new ElasticReadLogService(
            $processor,
            '111.222.33.4:9200',
            'test',
            $entityManager,
            $clientBuilder
        );



        static::assertInstanceOf(
            EntityActionLog::class,
            $elasticReadLogService->setIndex('test123')->getById('test', 'id#index')
        );
    }


    public function testGeyCountException(): void
    {
        /** @var EntityManager|Mock $entityManager */
        $entityManager = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();

        /** @var ClientBuilder|Mock $clientBuilder */
        $clientBuilder = $this->getMockBuilder(ClientBuilder::class)->disableOriginalConstructor()->getMock();

        /** @var Client|Mock $client */
        $client = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();
        $client->expects(static::any())->method('count')->will($this->throwException(new NFException()));

        $clientBuilder->expects(static::any())->method('setHosts')->will(static::returnValue($clientBuilder));
        $clientBuilder->expects(static::any())->method('build')->will(static::returnValue($client));

        $query = [
            'key1' => 'value1',
            'key2' => 'value2'
        ];

        /** @var ElasticEntityProcessor|Mock $processor */
        $processor = $this->getMockBuilder(ElasticEntityProcessor::class)->getMock();

        $elasticReadLogService = new ElasticReadLogService(
            $processor,
            '111.222.33.4:9200',
            'test',
            $entityManager,
            $clientBuilder
        );

        static::assertEquals(0, $elasticReadLogService->getCount('test', $query));
    }


    public function testGetMatchingEntities(): void
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
                ElasticEntityProcessor::METADATA_ENTITIES_TO_DECODE_FIELDS => [
                    'System',
                    'ChangedEntityId'
                ],
                ElasticEntityProcessor::METADATA_SOURCE_ENTITY_CLASS_FIELD => $entity,
                'System' => EntityActionLog::class,
                'ChangedEntityId' => EntityActionLog::class,
            ]
        ];
        $client->expects(static::any())
            ->method('get')
            ->will(
                static::returnValue($response)
            );

        $client->expects(static::any())
            ->method('count')
            ->will(
                static::returnValue(['count' => 34])
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
                            ElasticEntityProcessor::METADATA_DATETIME_FIELDS => [],
                            ElasticEntityProcessor::METADATA_ENTITIES_TO_DECODE_FIELDS => [
                                'ChangedEntityId'
                            ],
                            ElasticEntityProcessor::METADATA_SOURCE_ENTITY_CLASS_FIELD => $entity,
                            'ChangedEntityId' => EntityActionLog::class
                        ],
                        '_ttl' => 34,
                        '_id' => '',
                        '_score' => 34,
                        '_index' => 2,
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
                            ElasticEntityProcessor::METADATA_ENTITIES_TO_DECODE_FIELDS => [
                                'ChangedEntityId'
                            ],
                            ElasticEntityProcessor::METADATA_SOURCE_ENTITY_CLASS_FIELD => $entity,
                            'ChangedEntityId' => EntityActionLog::class
                        ],
                        '_ttl' => 34,
                        '_id' => '',
                        '_score' => 34,
                        '_index' => 2,
                    ]
                ]
            ],
            'aggregations' => 'test',
        ];

        $indicesNamespace->expects(static::any())
            ->method('refresh')
            ->will(
                static::returnValue(true)
            );

        $client->expects(static::any())
            ->method('indices')
            ->will(
                static::returnValue($indicesNamespace)
            );

        $client->expects($this->at(1))
            ->method('search')
            ->will(
                static::returnValue($result1)
            );

        $client->expects(static::any())
            ->method('search')
            ->will(
                static::returnValue($result2)
            );

        $clientBuilder->expects(static::any())
            ->method('setHosts')
            ->will(
                static::returnValue($clientBuilder)
            );

        $clientBuilder->expects(static::any())
            ->method('build')
            ->will(
                static::returnValue($client)
            );

        /** @var ElasticEntityProcessor|Mock $processor */
        $processor = $this->getMockBuilder(ElasticEntityProcessor::class)->getMock();

        $elasticReadLogService = new ElasticReadLogService(
            $processor,
            '111.222.33.4:9200',
            'test',
            $entityManager,
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

        $processor
            ->expects($this->exactly(1))
            ->method('decodeArrayFormat')
            ->willReturn(new EntityActionLog());

        static::assertInstanceOf(
            EntityActionLog::class,
            $elasticReadLogService->getMatchingEntities('test', $searchParams, 4, $select)[0]
        );

        static::assertEquals(
            'test',
            $elasticReadLogService->getMatchingEntities('test', $searchParams, 4, $select)['aggregations']
        );
    }


    public function testGetMatchingEntitiesException(): void
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
                ElasticEntityProcessor::METADATA_ENTITIES_TO_DECODE_FIELDS => [
                    'System',
                    'ChangedEntityId'
                ],
                ElasticEntityProcessor::METADATA_SOURCE_ENTITY_CLASS_FIELD => $entity,
                'System' => EntityActionLog::class,
                'ChangedEntityId' => EntityActionLog::class,
            ]
        ];
        $client->expects(static::any())
            ->method('get')
            ->will(
                static::returnValue($response)
            );

        $client->expects(static::any())
            ->method('count')
            ->will(
                static::returnValue(['count' => 34])
            );

        /** @var IndicesNamespace|Mock $indicesNamespace */
        $indicesNamespace = $this->getMockBuilder(IndicesNamespace::class)
            ->disableOriginalConstructor()
            ->getMock();

        $indicesNamespace->expects(static::any())
            ->method('refresh')
            ->will(
                static::returnValue(true)
            );

        $client->expects(static::any())
            ->method('indices')
            ->will(
                static::returnValue($indicesNamespace)
            );

        $client->expects(static::any())
            ->method('search')
            ->will(
                static::throwException(new NFException())
            );

        $clientBuilder->expects(static::any())
            ->method('setHosts')
            ->will(
                static::returnValue($clientBuilder)
            );

        $clientBuilder->expects(static::any())
            ->method('build')
            ->will(
                static::returnValue($client)
            );
        /** @var ElasticEntityProcessor|Mock $processor */
        $processor = $this->getMockBuilder(ElasticEntityProcessor::class)->getMock();

        $elasticReadLogService = new ElasticReadLogService(
            $processor,
            '111.222.33.4:9200',
            'test',
            $entityManager,
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

        static::assertEmpty($elasticReadLogService->getMatchingEntities('test', $searchParams, 4, $select));
    }


    /**
     * @expectedException \RuntimeException
     */
    public function testGetByQuery(): void
    {
        /** @var Table|Mock $table */
        $table = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();

        $table->expects(static::any())
            ->method('getName')
            ->will(
                static::returnValue('NotificationLog')
            );

        /** @var From|Mock $from */
        $from = $this->getMockBuilder(From::class)
            ->disableOriginalConstructor()
            ->getMock();

        $from->expects(static::any())
            ->method('getTables')
            ->will(
                static::returnValue([0 => $table])
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

        $select->expects(static::any())
            ->method('getColumns')
            ->will(
                static::returnValue([$column1, $column2])
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

        $where->expects(static::any())
            ->method('getConditions')
            ->will(
                static::returnValue([$wherePart1, $wherePart2, $wherePart3])
            );

        /** @var OrderBy|Mock $orderBy */
        $orderBy = $this->getMockBuilder(OrderBy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderBy->expects(static::any())
            ->method('getColumns')
            ->will(
                static::returnValue([$orderingColumn1, $orderingColumn2, $orderingColumn3, $orderingColumn4])
            );

        /** @var NQLQuery|Mock $nqlQuery */
        $nqlQuery = $this->getMockBuilder(NQLQuery::class)
            ->disableOriginalConstructor()
            ->getMock();

        $nqlQuery->expects(static::any())
            ->method('getFrom')
            ->will(
                static::returnValue($from)
            );

        $nqlQuery->expects(static::any())
            ->method('getSelect')
            ->will(
                static::returnValue($select)
            );

        $nqlQuery->expects(static::any())
            ->method('getOffset')
            ->will(
                static::returnValue(3)
            );

        $nqlQuery->expects(static::any())
            ->method('getLimit')
            ->will(
                static::returnValue(4)
            );

        $nqlQuery->expects(static::any())
            ->method('getWhere')
            ->will(
                static::returnValue($where)
            );

        $nqlQuery->expects(static::any())
            ->method('getOrderBy')
            ->will(
                static::returnValue($orderBy)
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
                ElasticEntityProcessor::METADATA_ENTITIES_TO_DECODE_FIELDS => [
                    'System',
                    'ChangedEntityId'
                ],
                ElasticEntityProcessor::METADATA_SOURCE_ENTITY_CLASS_FIELD => $entity,
                'System' => EntityActionLog::class,
                'ChangedEntityId' => EntityActionLog::class,
            ]
        ];

        $client->expects(static::any())
            ->method('get')
            ->will(
                static::returnValue($response)
            );

        $client->expects(static::any())
            ->method('count')
            ->will(
                static::returnValue(['count' => 34])
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
                            ElasticEntityProcessor::METADATA_ENTITIES_TO_DECODE_FIELDS => [
                                'System',
                                'ChangedEntityId'
                            ],
                            ElasticEntityProcessor::METADATA_SOURCE_ENTITY_CLASS_FIELD => $entity,
                            'System' => EntityActionLog::class,
                            'ChangedEntityId' => EntityActionLog::class,
                        ],
                        '_ttl' => 34,
                        '_score' => 34,
                    ]
                ],
                'max_score' => 43,
                'total' => 34,
            ]
        ];

        $indicesNamespace->expects(static::any())
            ->method('refresh')
            ->will(
                static::returnValue(true)
            );

        $client->expects(static::any())
            ->method('indices')
            ->will(
                static::returnValue($indicesNamespace)
            );

        $client->expects(static::any())
            ->method('search')
            ->will(
                static::returnValue($result)
            );

        $clientBuilder->expects(static::any())
            ->method('setHosts')
            ->will(
                static::returnValue($clientBuilder)
            );

        $clientBuilder->expects(static::any())
            ->method('build')
            ->will(
                static::returnValue($client)
            );

        $processor = $this->getMockBuilder(ElasticEntityProcessor::class)->getMock();

        $elasticReadLogService = new ElasticReadLogService(
            $processor,
            '111.222.33.4:9200',
            'test',
            $entityManager,
            $clientBuilder
        );

        static::assertInstanceOf(
            EntityActionLog::class,
            $elasticReadLogService->getByQuery($nqlQuery, 'test')[0][0]
        );
    }


    public function testGetStatusByEntity(): void
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
                ElasticEntityProcessor::METADATA_ENTITIES_TO_DECODE_FIELDS => [
                    'System',
                    'ChangedEntityId'
                ],
                ElasticEntityProcessor::METADATA_SOURCE_ENTITY_CLASS_FIELD => $entity,
                'System' => EntityActionLog::class,
                'ChangedEntityId' => EntityActionLog::class,
            ]
        ];

        $client->expects(static::any())
            ->method('get')
            ->will(
                static::returnValue($response)
            );

        $client->expects(static::any())
            ->method('count')
            ->will(
                static::returnValue(['count' => 34])
            );

        $result1 = [
            'hits' => [
                'hits' => [
                    'first' => [
                        '_source' => [
                            'changeSet' => '{"info":1,"b":2,"c":3,"d":4,"e":5}',
                            'ttl' => 76,
                            ElasticEntityProcessor::METADATA_ENTITIES_TO_DECODE_FIELDS => [
                                'System',
                                'ChangedEntityId'
                            ],
                            ElasticEntityProcessor::METADATA_SOURCE_ENTITY_CLASS_FIELD => $entity,
                            'System' => EntityActionLog::class,
                            'ChangedEntityId' => EntityActionLog::class,
                            'user' => MockUser::class,
                        ],
                        '_ttl' => 34,
                        '_id' => 'test',
                        '_score' => 34,
                    ]
                ]
            ]
        ];

        $result2 = [
            'hits' => [
                'hits' => [
                    'first' => [
                        '_source' => [
                            'changeSet' => '{"info":1,"b":2,"c":3,"d":4,"e":5}',
                            'ttl' => 76,
                            ElasticEntityProcessor::METADATA_ENTITIES_TO_DECODE_FIELDS => [
                                'System',
                                'ChangedEntityId'
                            ],
                            ElasticEntityProcessor::METADATA_SOURCE_ENTITY_CLASS_FIELD => $entity,
                            'System' => EntityActionLog::class,
                            'ChangedEntityId' => EntityActionLog::class,
                            'user' => MockUser::class,
                        ],
                        '_ttl' => 34,
                        '_id' => 'test',
                        '_score' => 34,
                    ]
                ]
            ]
        ];

        /** @var IndicesNamespace|Mock $indicesNamespace */
        $indicesNamespace = $this->getMockBuilder(IndicesNamespace::class)
            ->disableOriginalConstructor()
            ->getMock();

        $indicesNamespace->expects(static::any())
            ->method('refresh')
            ->will(
                static::returnValue(true)
            );

        $client->expects(static::any())
            ->method('indices')
            ->will(
                static::returnValue($indicesNamespace)
            );

        $client->expects(static::at(1))
            ->method('search')
            ->will(
                static::returnValue($result1)
            );

        $client->expects(static::at(2))
            ->method('search')
            ->will(
                static::returnValue($result2)
            );

        $client->expects(static::at(3))
            ->method('search')
            ->will(
                static::returnValue($result2)
            );

        $client->expects(static::at(4))
            ->method('search')
            ->will(
                static::returnValue($result2)
            );

        $client->expects(static::at(5))
            ->method('search')
            ->will(
                static::throwException(new NFException())
            );

        $clientBuilder->expects(static::any())
            ->method('setHosts')
            ->will(
                static::returnValue($clientBuilder)
            );

        $clientBuilder->expects(static::any())
            ->method('build')
            ->will(
                static::returnValue($client)
            );

        /** @var ElasticEntityProcessor|Mock $processor */
        $processor = $this->getMockBuilder(ElasticEntityProcessor::class)->getMock();

        $elasticReadLogService = new ElasticReadLogService(
            $processor,
            '111.222.33.4:9200',
            'test',
            $entityManager,
            $clientBuilder
        );

        $entity = new EntityActionLog();

        static::assertEquals(76, $elasticReadLogService->getStatusByEntity($entity)[0]['ttl']);

        static::assertEquals(76, $elasticReadLogService->getStatusByEntity($entity)[0]['ttl']);

        static::assertEmpty($elasticReadLogService->getStatusByEntity($entity));
    }
}
