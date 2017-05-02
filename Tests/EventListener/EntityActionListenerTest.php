<?php

namespace Trinity\Bundle\LoggerBundle\Tests\EventListener;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Error;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use JMS\Serializer\Serializer as JMS;
use Trinity\Bundle\LoggerBundle\Entity\EntityActionLog;
use Trinity\Bundle\LoggerBundle\Event\ElasticLoggerEvent;
use Trinity\Bundle\LoggerBundle\Event\RemoveNotificationUserEvent;
use Trinity\Bundle\LoggerBundle\Event\SetNotificationUserEvent;
use Trinity\Bundle\LoggerBundle\EventListener\ElasticLoggerListener;
use Trinity\Bundle\LoggerBundle\EventListener\EntityActionListener;
use Trinity\Bundle\LoggerBundle\Interfaces\UserProviderInterface;
use Trinity\Bundle\LoggerBundle\Services\ElasticLogServiceWithTtl;

/**
 * Class EntityActionListenerTest
 * @package Trinity\Bundle\LoggerBundle\Tests\EntityActionListenerTest
 */
class EntityActionListenerTest extends TestCase
{
    public function testNoExceptions()
    {

        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->disableOriginalConstructor()->getMock();
        $eventDispatcherInterface = $this->getMockBuilder(EventDispatcherInterface::class)->disableOriginalConstructor()->getMock();
        $JMS = $this->getMockBuilder(JMS::class)->disableOriginalConstructor()->getMock();
        $reader = $this->getMockBuilder(Reader::class)->disableOriginalConstructor()->getMock();
        $logger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $userProviderInterface = $this->getMockBuilder(UserProviderInterface::class)->disableOriginalConstructor()->getMock();
        $objectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();



        $setNotificationUserEvent = new SetNotificationUserEvent('34', '54');
        $removeNotificationUserEvent = new RemoveNotificationUserEvent('43', '45');

        $object = new EntityActionLog();

        $lifecycleEventArgs = new LifecycleEventArgs($object, $objectManager);


        $entityActionListener = new EntityActionListener($tokenStorage, $eventDispatcherInterface, $JMS, $reader, $logger, $userProviderInterface, 'test');

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('trinity.logger.entity_action_listener', [$entityActionListener, 'setUserFromNotification']);

        $entityActionListener->setUserFromNotification($setNotificationUserEvent);

        $this->assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));



        $dispatcher->addListener('trinity.logger.entity_action_listener', [$entityActionListener, 'postUpdate']);

        $entityActionListener->postUpdate($lifecycleEventArgs);

        $this->assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));


        $dispatcher->addListener('trinity.logger.entity_action_listener', [$entityActionListener, 'postRemove']);

        $entityActionListener->postRemove($lifecycleEventArgs);

        $this->assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));


        $dispatcher->addListener('trinity.logger.entity_action_listener', [$entityActionListener, 'preRemove']);

        $entityActionListener->preRemove($lifecycleEventArgs);

        $this->assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));

        $dispatcher->addListener('trinity.logger.entity_action_listener', [$entityActionListener, 'postPersist']);

        $entityActionListener->postPersist($lifecycleEventArgs);

        $this->assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));

        $dispatcher->addListener('trinity.logger.entity_action_listener', [$entityActionListener, 'clearUserFromNotification']);

        $entityActionListener->clearUserFromNotification($removeNotificationUserEvent);

        $this->assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));


        $dispatcherArray = $dispatcher->getListeners('trinity.logger.entity_action_listener');


        $this->assertInstanceOf(EntityActionListener::class, $dispatcherArray[0][0]);
    }


    /**
     * @expectedException \InvalidArgumentException
     */
    public function testClearUserFromNotification()
    {

        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->disableOriginalConstructor()->getMock();
        $eventDispatcherInterface = $this->getMockBuilder(EventDispatcherInterface::class)->disableOriginalConstructor()->getMock();
        $JMS = $this->getMockBuilder(JMS::class)->disableOriginalConstructor()->getMock();
        $reader = $this->getMockBuilder(Reader::class)->disableOriginalConstructor()->getMock();
        $logger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $userProviderInterface = $this->getMockBuilder(UserProviderInterface::class)->disableOriginalConstructor()->getMock();
        $objectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();


        $setNotificationUserEvent = new SetNotificationUserEvent(34, 34);
        $removeNotificationUserEvent = new RemoveNotificationUserEvent(34, 34);

        $object = new EntityActionLog();

        $lifecycleEventArgs = new LifecycleEventArgs($object, $objectManager);


        $entityActionListener = new EntityActionListener($tokenStorage, $eventDispatcherInterface, $JMS, $reader, $logger, $userProviderInterface, 'dev');

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('trinity.logger.entity_action_listener', [$entityActionListener, 'setUserFromNotification']);

        $entityActionListener->setUserFromNotification($setNotificationUserEvent);

        $entityActionListener->clearUserFromNotification($removeNotificationUserEvent);

        $this->assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));


        $dispatcherArray = $dispatcher->getListeners('trinity.logger.entity_action_listener');


        $this->assertInstanceOf(EntityActionListener::class, $dispatcherArray[0][0]);



        $setNotificationUserEvent = new SetNotificationUserEvent(34, 34);
        $removeNotificationUserEvent = new RemoveNotificationUserEvent(34, 43);

        $object = new EntityActionLog();

        $lifecycleEventArgs = new LifecycleEventArgs($object, $objectManager);


        $entityActionListener = new EntityActionListener($tokenStorage, $eventDispatcherInterface, $JMS, $reader, $logger, $userProviderInterface, 'dev');

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('trinity.logger.entity_action_listener', [$entityActionListener, 'setUserFromNotification']);

        $entityActionListener->setUserFromNotification($setNotificationUserEvent);

        $entityActionListener->clearUserFromNotification($removeNotificationUserEvent);

        $this->assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));


        $dispatcherArray = $dispatcher->getListeners('trinity.logger.entity_action_listener');


        $this->assertInstanceOf(EntityActionListener::class, $dispatcherArray[0][0]);


    }


    public function testPostUpdate()
    {

        $tokenStorage = $this->getMockBuilder(TokenStorageInterface::class)->disableOriginalConstructor()->getMock();
        $eventDispatcherInterface = $this->getMockBuilder(EventDispatcherInterface::class)->disableOriginalConstructor()->getMock();
        $JMS = $this->getMockBuilder(JMS::class)->disableOriginalConstructor()->getMock();
        $reader = $this->getMockBuilder(Reader::class)->disableOriginalConstructor()->getMock();
        $logger = $this->getMockBuilder(Logger::class)->disableOriginalConstructor()->getMock();
        $userProviderInterface = $this->getMockBuilder(UserProviderInterface::class)->disableOriginalConstructor()->getMock();

        $objectManager = $this->getMockBuilder(ObjectManager::class)->disableOriginalConstructor()->getMock();

        $objectManager->expects($this->once())->method('getObject')->willThrowException(new \Exception());

        $setNotificationUserEvent = new SetNotificationUserEvent(34, 34);
        //$removeNotificationUserEvent = new RemoveNotificationUserEvent(34, 34);

        //$object = new EntityActionLog();

        $lifecycleEventArgs = $this->getMockBuilder(LifecycleEventArgs::class)->disableOriginalConstructor()->getMock();;
        $lifecycleEventArgs->expects($this->any())->method('getObject')->willThrowException(new \Exception());


        $entityActionListener = new EntityActionListener($tokenStorage, $eventDispatcherInterface, $JMS, $reader, $logger, $userProviderInterface, 'dev');

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener('trinity.logger.entity_action_listener', [$entityActionListener, 'setUserFromNotification']);

        $entityActionListener->setUserFromNotification($setNotificationUserEvent);

        $entityActionListener->postUpdate($lifecycleEventArgs);

        $this->assertTrue($dispatcher->hasListeners('trinity.logger.entity_action_listener'));


        $dispatcherArray = $dispatcher->getListeners('trinity.logger.entity_action_listener');


        $this->assertInstanceOf(EntityActionListener::class, $dispatcherArray[0][0]);

    }
}
