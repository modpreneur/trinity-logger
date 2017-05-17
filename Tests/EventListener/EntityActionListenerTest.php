<?php

namespace Trinity\Bundle\LoggerBundle\Tests\EventListener;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use JMS\Serializer\Serializer as JMS;
use Trinity\Bundle\LoggerBundle\Annotation\EntityActionLoggable;
use Trinity\Bundle\LoggerBundle\Entity\BaseElasticLog;
use Trinity\Bundle\LoggerBundle\Entity\EntityActionLog;
use Trinity\Bundle\LoggerBundle\Event\RemoveNotificationUserEvent;
use Trinity\Bundle\LoggerBundle\Event\SetNotificationUserEvent;
use Trinity\Bundle\LoggerBundle\EventListener\EntityActionListener;
use Trinity\Bundle\LoggerBundle\Interfaces\UserProviderInterface;
use Trinity\Component\Core\Interfaces\UserInterface;

/**
 * Class EntityActionListenerTest
 * @package Trinity\Bundle\LoggerBundle\Tests\EntityActionListenerTest
 */
class EntityActionListenerTest extends TestCase
{

    /** @var TokenStorageInterface|Mock $tokenStorage */
    private $tokenStorage;

    /** @var EventDispatcherInterface|Mock $eventDispatcherInterface */
    private $eventDispatcherInterface;

    /** @var JMS|Mock $jms */
    private $jms;

    /** @var Reader|Mock $reader */
    private $reader;

    /** @var Logger|Mock $logger */
    private $logger;

    /** @var UserProviderInterface|Mock $userProviderInterface */
    private $userProviderInterface;

    /** @var LifecycleEventArgs|Mock $lifecycleEventArgs */
    private $lifecycleEventArgs;

    /** @var UnitOfWork|Mock $unitOfWork */
    private $unitOfWork;

    /** @var ObjectManagerChild|Mock $objectManagerChild */
    private $objectManagerChild;

    /** @var UserInterface|Mock $userInterface */
    private $userInterface;

    /** @var EntityActionLog|Mock $entityActionLog */
    private $entityActionLog;

    /** @var  EntityActionLog|Mock $object */
    private $object;

    /** @var  ObjectManagerWithout|Mock $objectManagerWithout */
    private $objectManagerWithout;

    /** @var EntityActionLoggable|Mock $entityActionLoggable */
    private $entityActionLoggable;

    /** @var $objectManager */
    private $objectManager;


    public function testNoExceptions()
    {
        $this->mockProvider();

        $setNotificationUserEvent = new SetNotificationUserEvent('34', '54');
        $removeNotificationUserEvent = new RemoveNotificationUserEvent('43', '45');

        $object = new EntityActionLog();

        $lifecycleEventArgs = new LifecycleEventArgs($object, $this->objectManager);

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'test'
        );

        $dispatcher = new EventDispatcher();

        $dispatcher->addListener(
            'trinity.logger.entity_action_listener',
            [
                $entityActionListener,
                'setUserFromNotification'
            ]
        );

        $entityActionListener->setUserFromNotification($setNotificationUserEvent);

        static::assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));

        $dispatcher->addListener('trinity.logger.entity_action_listener', [$entityActionListener, 'postUpdate']);

        $entityActionListener->postUpdate($lifecycleEventArgs);

        static::assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));


        $dispatcher->addListener('trinity.logger.entity_action_listener', [$entityActionListener, 'postRemove']);

        $entityActionListener->postRemove($lifecycleEventArgs);

        static::assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));

        $dispatcher->addListener('trinity.logger.entity_action_listener', [$entityActionListener, 'preRemove']);

        $entityActionListener->preRemove($lifecycleEventArgs);

        static::assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));

        $dispatcher->addListener('trinity.logger.entity_action_listener', [$entityActionListener, 'postPersist']);

        $entityActionListener->postPersist($lifecycleEventArgs);

        static::assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));

        $dispatcher->addListener(
            'trinity.logger.entity_action_listener',
                [
                    $entityActionListener,
                    'clearUserFromNotification'
                ]
        );

        $entityActionListener->clearUserFromNotification($removeNotificationUserEvent);

        static::assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));

        $dispatcherArray = $dispatcher->getListeners('trinity.logger.entity_action_listener');

        static::assertInstanceOf(EntityActionListener::class, $dispatcherArray[0][0]);
    }


    /**
     * @expectedException \InvalidArgumentException
     */
    public function testClearUserFromNotification()
    {

        $this->mockProvider();

        $setNotificationUserEvent = new SetNotificationUserEvent('34', '34');

        $removeNotificationUserEvent = new RemoveNotificationUserEvent('34', '34');

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'dev'
        );

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            'trinity.logger.entity_action_listener',
            [
                $entityActionListener,
                'setUserFromNotification'
            ]
        );

        $entityActionListener->setUserFromNotification($setNotificationUserEvent);

        $entityActionListener->clearUserFromNotification($removeNotificationUserEvent);

        static::assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));

        $dispatcherArray = $dispatcher->getListeners('trinity.logger.entity_action_listener');

        static::assertInstanceOf(EntityActionListener::class, $dispatcherArray[0][0]);

        $removeNotificationUserEvent = new RemoveNotificationUserEvent('34', '43');

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'dev'
        );

        $dispatcher = new EventDispatcher();

        $dispatcher->addListener(
            'trinity.logger.entity_action_listener',
            [
                $entityActionListener,
                'setUserFromNotification'
            ]
        );

        $entityActionListener->setUserFromNotification($setNotificationUserEvent);

        $entityActionListener->clearUserFromNotification($removeNotificationUserEvent);

        static::assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));

        $dispatcherArray = $dispatcher->getListeners('trinity.logger.entity_action_listener');

        static::assertInstanceOf(EntityActionListener::class, $dispatcherArray[0][0]);
    }


    /**
     * @expectedException \Exception
     */
    public function testPostUpdate()
    {
        $this->mockProvider();

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'test'
        );

        static::assertNull($entityActionListener->postUpdate($this->lifecycleEventArgs));

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'dev'
        );

        $entityActionListener->postUpdate($this->lifecycleEventArgs);
    }


    /**
     * @expectedException \Exception
     */
    public function testPostRemove()
    {
        $this->mockProvider(1);

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'test'
        );

        static::assertNull($entityActionListener->postRemove($this->lifecycleEventArgs));

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'dev'
        );

        $entityActionListener->postRemove($this->lifecycleEventArgs);
    }


    /**
     * @expectedException \Exception
     */
    public function testPostPersist()
    {
        $this->mockProvider(1);

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'test'
        );

        static::assertNull($entityActionListener->postPersist($this->lifecycleEventArgs));

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'dev'
        );

        $entityActionListener->postPersist($this->lifecycleEventArgs);
    }


    public function testProcess()
    {
        $this->mockProvider(2);

        $setNotificationUserEvent = new SetNotificationUserEvent('34', '54');

        $object = new EntityActionLog();

        $lifecycleEventArgs = new LifecycleEventArgs($object, $this->objectManagerChild);

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'test'
        );

        $entityActionListener->setUserFromNotification($setNotificationUserEvent);

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('trinity.logger.entity_action_listener', [$entityActionListener, 'postUpdate']);

        $entityActionListener->postUpdate($lifecycleEventArgs);

        static::assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));

        $dispatcher->addListener('trinity.logger.entity_action_listener', [$entityActionListener, 'postRemove']);

        $entityActionListener->postRemove($lifecycleEventArgs);

        static::assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));

        $dispatcher->addListener('trinity.logger.entity_action_listener', [$entityActionListener, 'postPersist']);

        $entityActionListener->postPersist($lifecycleEventArgs);

        static::assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));
    }


    public function testManageObjects()
    {
        $this->mockProvider();

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'test'
        );

        $baseElasticLog = new BaseElasticLog();

        $changeSet = [
            0 => [
                0 => 23,
                1 => 54,
            ],
            1 => [
                0 => 24,
                1 => 24,
            ],
            2 => [
                0 => $baseElasticLog,
                1 => $baseElasticLog,
            ]
        ];

        $expected = [
            0 => [
                0 => 23,
                1 => 54,
            ],
            2 => [
                0 => "",
                1 => "",
            ]
        ];

        static::assertEquals($expected, $this->invokeMethod($entityActionListener, 'manageObjects', [$changeSet]));
    }


    public function testCheckUser()
    {
        $this->mockProvider(3);

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'dev'
        );

        $this->invokeMethod($entityActionListener, 'checkUser', [$this->object, $this->objectManagerChild]);
    }


    public function testCheckUserNoUserInterface()
    {
        $this->mockProvider(3);

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'dev'
        );

        $this->invokeMethod($entityActionListener, 'checkUser', [$this->object, $this->objectManagerChild]);
    }


    /**
     * @expectedException \RuntimeException
     */
    public function testCheckUserException()
    {
        $this->mockProvider(4);

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'dev'
        );

        $setNotificationUserEvent = new SetNotificationUserEvent('34', '54');
        $entityActionListener->setUserFromNotification($setNotificationUserEvent);

        $this->invokeMethod(
            $entityActionListener,
            'checkUser',
            [
                $this->object,
                $this->objectManagerWithout,
            ]
        );
    }


    /**
     * @expectedException \RuntimeException
     */
    public function testSetUpdateLogRuntimeException()
    {
        $this->mockProvider(4);

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'dev'
        );

        $entity = new EntityActionLog();

        $setNotificationUserEvent = new SetNotificationUserEvent('34', '54');
        $entityActionListener->setUserFromNotification($setNotificationUserEvent);

        $this->invokeMethod(
            $entityActionListener,
            'setUpdateLog',
            [
                $this->object,
                $this->entityActionLoggable,
                $this->objectManagerWithout, $entity]
        );
    }


    public function testSetUpdateLogChangedWithUpdatedBy()
    {
        $this->mockProvider(5);

        $entity = new EntityActionLog();

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'dev'
        );

        $setNotificationUserEvent = new SetNotificationUserEvent('34', '54');
        $entityActionListener->setUserFromNotification($setNotificationUserEvent);

        $this->invokeMethod(
            $entityActionListener,
            'setUpdateLog',
            [
                $this->object,
                $this->entityActionLoggable,
                $this->objectManagerChild,
                $entity
            ]
        );
    }


    public function testSetUpdateLogChangedWithoutUpdatedBy()
    {
        $this->mockProvider(6);

        $entity = new CustomEntity();
        $entity->setUpdatedBy('test');

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'dev'
        );

        $setNotificationUserEvent = new SetNotificationUserEvent('34', '54');
        $entityActionListener->setUserFromNotification($setNotificationUserEvent);

        $this->invokeMethod(
            $entityActionListener,
            'setUpdateLog',
            [
                $this->object,
                $this->entityActionLoggable,
                $this->objectManagerChild,
                $entity
            ]
        );
    }


    public function testSetFilterIgnored()
    {
        $this->mockProvider(7);

        $entity = new CustomEntity();
        $entity->setUpdatedBy('test');

        $baseElasticLog = new BaseElasticLog();

        $changeSet = [
            0 => [
                0 => 23,
                1 => 54,
            ],
            1 => [
                0 => 24,
                1 => 24,
            ],
            2 => [
                0 => $baseElasticLog,
                1 => $baseElasticLog,
            ],
            3 => [
                0 => 'ffd',
                1 => $baseElasticLog,
            ],
        ];

        $loggedFields = [
            23,
            54,
            0,
        ];
        $ignoreValue = [
            23,
            'ffd',
        ];

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'dev'
        );

        $setNotificationUserEvent = new SetNotificationUserEvent('34', '54');
        $entityActionListener->setUserFromNotification($setNotificationUserEvent);

        $expected = [
            0 => ''
        ];

        static::assertEquals(
            $expected,
            $this->invokeMethod(
                $entityActionListener,
                'filterIgnored',
                [
                    $changeSet,
                    $loggedFields,
                    $ignoreValue
                ]
            )
        );
    }


    public function testSetRelationChanges()
    {
        $this->mockProvider(8);

        $entity = new CustomEntity();
        $entity->setUpdatedBy('test');

        $baseElasticLog = new BaseElasticLog();

        $changeSet = [
            0 => [
                0 => 23,
                1 => 54,
            ],
            1 => [
                0 => 24,
                1 => 24,
            ],
            2 => [
                0 => $baseElasticLog,
                1 => $baseElasticLog
            ],
            3 => [
                0 => 'ffd',
                1 => $baseElasticLog
            ],
        ];

        $loggedFields = [58];

        /** @var EntityManagerInterface|Mock $em */
        $em = $this->getMockBuilder(EntityManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ClassMetadata|Mock $class */
        $class = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Collection|Mock $collection */
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $collection->expects(static::at(0))
            ->method('toArray')
            ->will(static::returnValue(
                [
                    'first' => $class,
                    'third' => 'text',
                    '4242' => $class,
                    '34fds' => 'fddsfsdfsdf'
                ]));

        $collection->expects(static::any())
            ->method('toArray')
            ->will(static::returnValue(
                [
                    'first' => $class,
                    'second' => $class,
                    'third' => 'text'
                ]));

        $persistentCollection1 = new PersistentCollection($em, $class, $collection);

        $persistentCollection2 = new PersistentCollection($em, $class, $collection);

        $persistentCollection3 = new PersistentCollection($em, $class, $collection);

        $assoc = [
            'fieldName' => 58,
            'inversedBy' => 'test',
        ];

        $persistentCollection3->setOwner($baseElasticLog, $assoc);
        $persistentCollection3->takeSnapshot();

        $persistentCollection2->setOwner($baseElasticLog, $assoc);

        $persistentCollection3->setOwner($baseElasticLog, $assoc);

        $updates = [
            $persistentCollection1,
            $persistentCollection2,
            $persistentCollection3,
        ];

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'dev'
        );

        $setNotificationUserEvent = new SetNotificationUserEvent('34', '54');
        $entityActionListener->setUserFromNotification($setNotificationUserEvent);

        $expected = [
            0 => [
                0 => 23,
                1 => 54,
            ],
            1 => [
                0 => 24,
                1 => 24,
            ],
            2 => [
                0 => $baseElasticLog,
                1 => $baseElasticLog,
            ],
            3 => [
                0 => 'ffd',
                1 => $baseElasticLog,
            ],
            58 => [
                'inserted' => [
                    0 => 'second',
                ],
                'removed' => [
                    0 => 4242,
                    1 => '34fds',
                ]
            ],
        ];

        static::assertInstanceOf(
            BaseElasticLog::class,
            $this->invokeMethod(
                $entityActionListener,
                'setRelationChanges',
                [
                    $changeSet,
                    $updates,
                    $loggedFields]
            )[2][0]
        );

        static::assertEquals(
            $expected,
            $this->invokeMethod(
                $entityActionListener,
                'setRelationChanges',
                [
                    $changeSet,
                    $updates,
                    $loggedFields
                ]
            )
        );
    }


    public function testSetDeleteLog()
    {
        $this->mockProvider(9);

        $entity = new CustomEntity();
        $entity->setDeletedBy(4);

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'prod'
        );

        $this->invokeMethod($entityActionListener, 'setDeleteLog', [$this->entityActionLog, $entity]);
    }


    public function testSetCreateLog()
    {
        $this->mockProvider(10);

        $entity = new CustomEntity();
        $entity->setCreatedBy(1);

        $entityActionListener = new EntityActionListener(
            $this->tokenStorage,
            $this->eventDispatcherInterface,
            $this->jms,
            $this->reader,
            $this->logger,
            $this->userProviderInterface,
            'prod'
        );

        $this->invokeMethod($entityActionListener, 'setCreateLog', [$this->entityActionLog, $entity]);
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


    /**
     * @param int|null $settings
     */
    private function mockProvider(int $settings = null)
    {
        $this->tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcherInterface = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jms = $this->getMockBuilder(JMS::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->reader = $this->getMockBuilder(Reader::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userProviderInterface = $this->getMockBuilder(UserProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = $this->getMockBuilder(ObjectManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->lifecycleEventArgs = $this->getMockBuilder(LifecycleEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->lifecycleEventArgs->expects(static::any())
            ->method('getObject')
            ->will(
                static::throwException(new \Exception)
            );

        switch ($settings) {
            case 1:
                $this->lifecycleEventArgs->expects(static::exactly(2))
                    ->method('getObject')
                    ->will(
                        static::throwException(new \Exception)
                    );
                break;
            case 2:
                $entityActionLoggable = $this->getMockBuilder(EntityActionLoggable::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->reader->expects(static::any())
                    ->method('getClassAnnotation')
                    ->will(
                        static::returnValue($entityActionLoggable)
                    );

                $this->unitOfWork = $this->getMockBuilder(UnitOfWork::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->unitOfWork->expects(static::any())
                    ->method('getEntityChangeSet')
                    ->will(
                        static::returnValue([])
                    );

                $this->unitOfWork->expects(static::any())
                    ->method('getScheduledCollectionUpdates')
                    ->will(
                        static::returnValue([])
                    );

                $this->objectManagerChild = $this->getMockBuilder(ObjectManagerChild::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->objectManagerChild->expects(static::any())
                    ->method('getUnitOfWork')
                    ->will(
                        static::returnValue($this->unitOfWork)
                    );
                break;
            case 3:
                $this->userInterface  = $this->getMockBuilder(UserInterface::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->entityActionLog =  $this->getMockBuilder(EntityActionLog::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->entityActionLog->expects(static::any())
                    ->method('getUser')
                    ->will(
                        static::returnValue($this->userInterface)
                    );

                $this->objectManagerChild = $this->getMockBuilder(ObjectManagerChild::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->object = $this->getMockBuilder(EntityActionLog::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->object->expects(static::once())
                    ->method('getUser')
                    ->will(
                        static::returnValue(false)
                    );

                break;
            case 4:
                $this->userInterface  = $this->getMockBuilder(UserInterface::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->entityActionLog =  $this->getMockBuilder(EntityActionLog::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->entityActionLog->expects(static::any())
                    ->method('getUser')
                    ->will(
                        static::returnValue($this->userInterface)
                    );

                $this->objectManagerWithout = $this->getMockBuilder(ObjectManagerWithout::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->object = $this->getMockBuilder(EntityActionLog::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->object->expects(static::any())
                    ->method('getUser')
                    ->will(
                        static::returnValue(false)
                    );
                $this->entityActionLoggable = $this->getMockBuilder(EntityActionLoggable::class)
                    ->disableOriginalConstructor()
                    ->getMock();
                break;
            case 5:
                $this->entityActionLog =  $this->getMockBuilder(EntityActionLog::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->entityActionLog->expects(static::any())
                    ->method('getUser')
                    ->will(
                        static::returnValue('foo')
                    );

                $this->tokenStorage->expects(static::any())
                    ->method('getToken')
                    ->will(
                        static::returnValue($this->entityActionLog)
                    );

                $this->objectManagerChild = $this->getMockBuilder(ObjectManagerChild::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->unitOfWork = $this->getMockBuilder(UnitOfWork::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $entity = new EntityActionLog();

                $this->unitOfWork->expects(static::any())->method('getEntityChangeSet')
                    ->with($entity)
                    ->will(
                        static::returnValue(['updatedBy' => true])
                    );

                $this->unitOfWork->expects(static::once())
                    ->method('getScheduledCollectionUpdates')
                    ->will(
                        static::returnValue([])
                    );

                $this->objectManagerChild->expects(static::any())
                    ->method('getUnitOfWork')
                    ->will(
                        static::returnValue($this->unitOfWork)
                    );

                $this->object = $this->getMockBuilder(EntityActionLog::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->entityActionLoggable = $this->getMockBuilder(EntityActionLoggable::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                break;
            case 6:
                $this->entityActionLog =  $this->getMockBuilder(EntityActionLog::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->entityActionLog->expects(static::any())
                    ->method('getUser')
                    ->will(
                        static::returnValue('foo')
                    );

                $this->tokenStorage->expects(static::any())
                    ->method('getToken')
                    ->will(
                        static::returnValue($this->entityActionLog)
                    );


                $this->objectManagerChild = $this->getMockBuilder(ObjectManagerChild::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->unitOfWork = $this->getMockBuilder(UnitOfWork::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $baseElasticLog = new BaseElasticLog();

                $entity = new CustomEntity();
                $entity->setUpdatedBy('test');

                $changeSet = [
                    0 => [
                        0 => 23,
                        1 => 54,
                    ],
                    1 => [
                        0 => 24,
                        1 => 24,
                    ],
                    2 => [
                        0 => $baseElasticLog,
                        1 => $baseElasticLog,
                    ]
                ];

                $this->unitOfWork->expects(static::any())->method('getEntityChangeSet')->with($entity)
                    ->will(static::returnValue($changeSet));

                $this->unitOfWork->expects(static::once())
                    ->method('getScheduledCollectionUpdates')
                    ->will(
                        static::returnValue([])
                    );

                $this->objectManagerChild->expects(static::any())
                    ->method('getUnitOfWork')
                    ->will(
                        static::returnValue($this->unitOfWork)
                    );

                $this->object = $this->getMockBuilder(EntityActionLog::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->entityActionLoggable = $this->getMockBuilder(EntityActionLoggable::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                break;
            case 7:
                $this->entityActionLog =  $this->getMockBuilder(EntityActionLog::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->entityActionLog->expects(static::any())
                    ->method('getUser')
                    ->will(
                        static::returnValue('foo')
                    );

                $this->tokenStorage->expects(static::any())
                    ->method('getToken')
                    ->will(
                        static::returnValue($this->entityActionLog)
                    );

                $this->objectManagerChild = $this->getMockBuilder(ObjectManagerChild::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->unitOfWork = $this->getMockBuilder(UnitOfWork::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $entity = new EntityActionLog();

                $this->unitOfWork->expects(static::any())->method('getEntityChangeSet')
                    ->with($entity)
                    ->will(
                        static::returnValue(['updatedBy' => true])
                    );

                $this->unitOfWork->expects(static::any())
                    ->method('getScheduledCollectionUpdates')
                    ->will(
                        static::returnValue([])
                    );

                $this->objectManagerChild->expects(static::any())
                    ->method('getUnitOfWork')
                    ->will(
                        static::returnValue($this->unitOfWork)
                    );

                $this->object = $this->getMockBuilder(EntityActionLog::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->entityActionLoggable = $this->getMockBuilder(EntityActionLoggable::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                break;
            case 8:
                $this->entityActionLog =  $this->getMockBuilder(EntityActionLog::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->entityActionLog->expects(static::any())
                    ->method('getUser')
                    ->will(
                        static::returnValue('foo')
                    );

                break;
            case 9:
                $this->entityActionLog =  $this->getMockBuilder(EntityActionLog::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->entityActionLog->expects(static::any())
                    ->method('getUser')
                    ->will(
                        static::returnValue('foo')
                    );

                $this->entityActionLog->expects(static::once())
                    ->method('setUser');

                $this->entityActionLog->expects(static::once())
                    ->method('setChangedEntityId');

                $this->entityActionLog->expects(static::once())
                    ->method('setChangedEntity');

                $this->tokenStorage->expects(static::any())
                    ->method('getToken')
                    ->will(
                        static::returnValue($this->entityActionLog)
                    );

                break;
            case 10:
                $this->entityActionLog =  $this->getMockBuilder(EntityActionLog::class)
                    ->disableOriginalConstructor()
                    ->getMock();

                $this->entityActionLog->expects(static::any())
                    ->method('getUser')
                    ->will(
                        static::returnValue('foo')
                    );

                $this->entityActionLog->expects(static::once())
                    ->method('setChangedEntity');

                $this->tokenStorage->expects(static::any())
                    ->method('getToken')
                    ->will(
                        static::returnValue($this->entityActionLog)
                    );

                break;
        }
    }
}
