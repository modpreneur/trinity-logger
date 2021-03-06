<?php

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\EventListener;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Common\Annotations\Reader;
use Monolog\Logger;
use Trinity\Bundle\LoggerBundle\Annotation\EntityActionLoggable;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Trinity\Bundle\LoggerBundle\Event\ElasticLoggerEvent;
use Trinity\Bundle\LoggerBundle\Event\RemoveNotificationUserEvent;
use Trinity\Bundle\LoggerBundle\Event\SetNotificationUserEvent;
use Trinity\Bundle\LoggerBundle\Interfaces\UserProviderInterface;
use Trinity\Bundle\LoggerBundle\Services\ElasticLogService;
use Trinity\Bundle\LoggerBundle\Entity\EntityActionLog;
use JMS\Serializer\Serializer as JMS;
use Trinity\Component\Core\Interfaces\UserInterface;

/**
 * Class EntityActionListener.
 *
 * This class has an invisible dependency on trinity/notifications.
 */
class EntityActionListener
{
    const NEW_VALUE = 1;
    const OLD_VALUE = 0;
    const UPDATE = 'update';
    const DELETE = 'delete';
    const CREATE = 'create';
    const PROXY_FLAG = 'Proxies\\__CG__\\';
    const NECKTIE_SYSTEM_NAME = 'Necktie';

    /** @var int */
    private $deletedId = 0;

    /** @var mixed */
    private $deleteEntity = 0;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /** @var ElasticLogService */
    protected $esLogger;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var JMS */
    protected $serializer;

    /** @var Reader */
    protected $reader;

    /** @var string */
    protected $kernelEnvironment;

    /** @var Logger */
    protected $moLogger;

    /** @var bool */
    protected $notificationsInProgress = false;

    /** @var int */
    protected $notificationUser;

    /** @var int|string */
    protected $notificationClient = '';

    /** @var UserProviderInterface */
    protected $userProvider;


    /**
     * EntityActionListener constructor.
     *
     * @param TokenStorageInterface $ts
     * @param EventDispatcherInterface $eventDispatcher
     * @param JMS $serializer
     * @param Reader $reader
     * @param Logger $mo
     * @param UserProviderInterface $userProvider
     * @param string $kernelEnvironment
     */
    public function __construct(
        TokenStorageInterface $ts,
        EventDispatcherInterface $eventDispatcher,
        JMS $serializer,
        Reader $reader,
        Logger $mo,
        UserProviderInterface $userProvider,
        string $kernelEnvironment = ''
    ) {
        $this->serializer = $serializer;
        $this->tokenStorage = $ts;
        $this->eventDispatcher = $eventDispatcher;
        $this->reader = $reader;
        $this->kernelEnvironment = $kernelEnvironment;
        $this->moLogger = $mo;
        $this->userProvider = $userProvider;
    }


    /**
     * @param SetNotificationUserEvent $event
     */
    public function setUserFromNotification(SetNotificationUserEvent $event): void
    {
        $this->notificationUser = 0;

        if ($event->getUserIdentification() && \is_numeric($event->getUserIdentification())) {
            $this->notificationUser = (int)$event->getUserIdentification();
        }

        $this->notificationClient = $event->getClientId();
        $this->notificationsInProgress = true;
    }


    /**
     * @param RemoveNotificationUserEvent $event
     *
     * @throws \InvalidArgumentException
     */
    public function clearUserFromNotification(RemoveNotificationUserEvent $event): void
    {
        $user = $event->getUserIdentification();
        $clientId = $event->getClientId();
        /*
         * There should never be clear for A when B is active. If this happens on prod we log it as error and clear
         * it ,when it happen in dev, we throw the error.
         */

        try {
            if ("$user" !== "$this->notificationUser") {
                throw new \InvalidArgumentException(
                    'Received end of event for ' . $user . ' but ' . $this->notificationUser . 'is still active.'
                );
            }
            if ("$clientId" !== "$this->notificationClient") {
                throw new \InvalidArgumentException(
                    'Received end of event for ' . $clientId . ' but ' . $this->notificationClient . 'is still active.'
                );
            }
        } catch (\InvalidArgumentException $e) {
            if ($this->kernelEnvironment !== 'dev') {
                $this->moLogger->addError($e);
            } else {
                throw $e;
            }
        }
        $this->notificationClient = 0;
        $this->notificationUser = 0;
        $this->notificationsInProgress = false;
    }


    /**
     * @param LifecycleEventArgs $args
     *
     * @throws Exception
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     * @throws \Exception
     */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        try {
            $this->process($args, self::UPDATE);
        } catch (\Exception $e) {
            if ($this->kernelEnvironment !== 'dev') {
                $this->moLogger->addError($e);
            } else {
                throw $e;
            }
        }
    }


    /**
     * @param LifecycleEventArgs $args
     *
     * @throws \Exception
     */
    public function postRemove(LifecycleEventArgs $args): void
    {
        try {
            $this->process($args, self::DELETE);
        } catch (\Exception $e) {
            if ($this->kernelEnvironment !== 'dev') {
                $this->moLogger->addError($e);
            } else {
                throw $e;
            }
        }
    }


    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        $this->deletedId = $args->getObject()->getId();
        $this->deleteEntity = $this->serializer->serialize($args->getObject(), 'json');
    }


    /**
     * @param LifecycleEventArgs $args
     *
     * @throws \Exception
     */
    public function postPersist(LifecycleEventArgs $args): void
    {
        try {
            $this->process($args, self::CREATE);
        } catch (\Exception $e) {
            if ($this->kernelEnvironment !== 'dev') {
                $this->moLogger->addError($e);
            } else {
                throw $e;
            }
        }
    }


    /**
     * @param LifecycleEventArgs $args
     * @param $operationType
     *
     * @throws Exception
     * @throws \RuntimeException
     * @throws \UnexpectedValueException
     * @throws \ReflectionException
     */
    private function process(LifecycleEventArgs $args, $operationType): void
    {
        $log = new EntityActionLog();
        $log->setSystem(self::NECKTIE_SYSTEM_NAME);

        $entity = $args->getObject();

        $reflect = new \ReflectionClass($entity);
        $className = \get_class($entity);

        $annotation = $this->reader->getClassAnnotation(
            $reflect,
            EntityActionLoggable::class
        );
        if (!$annotation && \strpos($className, self::PROXY_FLAG) === 0) {
            $className = \substr($className, \strlen(self::PROXY_FLAG));
            $emptyEntity = new $className();
            $reflect = new \ReflectionClass($emptyEntity);
            /** @var EntityActionLoggable $annotation */
            $annotation = $this->reader->getClassAnnotation(
                $reflect,
                EntityActionLoggable::class
            );
        }
        if (!$annotation) {
            return;
        }

        $log->setActionType($operationType);
        $log->setCreatedAt(new \DateTime());

        switch ($operationType) {
            case self::DELETE:
                $this->setDeleteLog($log, $entity);
                break;
            // function setCreateLog is no more used, serializer was not easily configurable and provided too big logs.
            case self::CREATE:
            case self::UPDATE:
                $this->setUpdateLog($log, $annotation, $args->getObjectManager(), $entity);
                if (!$log->getChangeSet() && !$log->getChangedEntity()) {
                    return;
                }
                break;
            default:
                break;
        }

        $this->checkUser($log, $args->getObjectManager());

        $this->eventDispatcher->dispatch(
            ElasticLoggerEvent::EVENT_NAME,
            new ElasticLoggerEvent('EntityActionLog', $log)
        );
    }


    /**
     * @param EntityActionLog $log
     * @param ObjectManager $manager
     *
     * @throws \UnexpectedValueException
     * @throws \RuntimeException
     */
    private function checkUser(EntityActionLog $log, ObjectManager $manager): void
    {
        //Notification source user overrides what come naturally
        if ($this->notificationsInProgress) {
            if (!$manager instanceof EntityManager) {
                throw new \RuntimeException('Entity manager expected');
            }
            /* @var EntityManager $manager */
            if ($this->notificationUser !== 0) {
                $log->setUser($this->userProvider->getUserById($this->notificationUser));
            }
            $log->setSystem($this->notificationClient);
        } elseif (!$log->getUser()) {
            if ($this->tokenStorage->getToken()
                && $this->tokenStorage->getToken()->getUser() instanceof UserInterface
            ) {
                $log->setUser($this->tokenStorage->getToken()->getUser());
            } else {
                if ($this->kernelEnvironment === 'dev') {
                    \dump('Add user to create/update/delete actions' .
                        'or use PROD environment to ignore following exception:');
                }

                $exception = new \UnexpectedValueException(
                    'Could not identify user making this entity '. $log->getActionType() .
                    ' action on ' . $log->getChangedEntityClass() . '.'
                );
                $this->moLogger->addError($exception);
            }
        }
    }


    /**
     * @param EntityActionLog $log
     * @param EntityActionLoggable $annotation
     * @param ObjectManager $objectManager
     * @param Object $entity
     *
     * @throws \RuntimeException
     */
    private function setUpdateLog(
        EntityActionLog $log,
        EntityActionLoggable $annotation,
        ObjectManager $objectManager,
        $entity
    ): void {
        if (!$objectManager instanceof EntityManager) {
            throw new \RuntimeException('Entity manager expected');
        }
        /* @var EntityManager $objectManager */
        $uow = $objectManager->getUnitOfWork();
        $loggedFields = $annotation->getAttributeList();
        $ignoreValue = $annotation->getEmptyAttributes();

        $changeSet = $uow->getEntityChangeSet($entity);

        if (\array_key_exists('updatedBy', $changeSet) && $changeSet['updatedBy']) {
            $log->setUser($changeSet['updatedBy'][self::NEW_VALUE]);
        } elseif (\method_exists($entity, 'getUpdatedBy') && $entity->getUpdatedBy()) {
            $log->setUser($entity->getUpdatedBy());
        }

        unset($changeSet['updatedBy'], $changeSet['updatedAt'], $changeSet['createdBy'], $changeSet['createdAt']);

        $changeSet = $this->setRelationChanges(
            $changeSet,
            $uow->getScheduledCollectionUpdates(),
            $loggedFields
        );

        $changeSet = $this->filterIgnored($changeSet, $loggedFields, $ignoreValue);
        $changeSet = $this->manageObjects($changeSet);

        if (!$changeSet) {
            return;
        }

        $this->setClass($log, $entity);

        if ($log->getActionType() === 'create') {
            $createChangeSet = [];
            foreach ($changeSet as $item => $value) {
                if ($this->isAssociativeArray($value)) {
                    $createChangeSet[$item] = $value;
                } else {
                    $createChangeSet[$item] = $value[1];
                }
            }
            $log->setChangedEntity(\json_encode($createChangeSet));
        } else {
            $log->setChangeSet($changeSet, 'write');
        }
    }


    /**
     * @param array $array
     *
     * @return bool
     */
    private function isAssociativeArray(array $array): bool
    {
        foreach (array_keys($array) as $key) {
            if (!is_int($key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array $changeSet
     *
     * @return array
     */
    private function manageObjects(array $changeSet): array
    {
        foreach ($changeSet as $key => $value) {
            //is not M:N relation
            if (\array_key_exists(self::OLD_VALUE, $value)) {
                //doctrine store numeric as string and it results in false changes
                if (\is_numeric($value[self::OLD_VALUE]) &&
                    (double)$value[self::OLD_VALUE] === (double)$value[self::NEW_VALUE]
                ) {
                    unset($changeSet[$key]);
                    continue;
                }
                if (\is_object($value[self::OLD_VALUE]) && \method_exists($value[self::OLD_VALUE], 'getId')) {
                    $changeSet[$key][self::OLD_VALUE] = $value[self::OLD_VALUE]->getId();
                }
                if (\is_object($value[self::NEW_VALUE]) && \method_exists($value[self::NEW_VALUE], 'getId')) {
                    $changeSet[$key][self::NEW_VALUE] = $value[self::NEW_VALUE]->getId();
                }
            }
        }

        return $changeSet;
    }


    /**
     * @param array $changeSet
     * @param array $loggedFields
     * @param array $ignoreValue
     *
     * @return array
     */
    private function filterIgnored(array $changeSet, array $loggedFields, array $ignoreValue): array
    {
        foreach ($changeSet as $key => $value) {
            if ($loggedFields && !\in_array($key, $loggedFields, false)) {
                unset($changeSet[$key]);
                continue;
            }

            if ($ignoreValue && \array_key_exists($key, $ignoreValue)) {
                //code sniffer/inspect lies
                $changeSet[$key] = '';
                continue;
            }
        }

        return $changeSet;
    }


    /**
     * @param array $changeSet
     * @param array $updates
     * @param array $loggedFields
     *
     * @return array
     */
    private function setRelationChanges(array $changeSet, array $updates, array $loggedFields): array
    {
        /**
         * @var PersistentCollection $update
         */
        foreach ($updates as $update) {
            $fieldName = $update->getMapping()['fieldName'];
            if (null !== $loggedFields && !\in_array($fieldName, $loggedFields, false)) {
                continue;
            }

            $inserted = [];
            //I am not sure if key is ID, should be but ..
            foreach ($update->getInsertDiff() as $id => $subEntity) {
                if (\method_exists($subEntity, 'getId')) {
                    $inserted[] = $subEntity->getId();
                } else {
                    $inserted[] = $id;
                }
            }

            $removed = [];
            foreach ($update->getDeleteDiff() as $id => $subEntity) {
                if (\method_exists($subEntity, 'getId')) {
                    $removed[] = $subEntity->getId();
                } else {
                    $removed[] = $id;
                }
            }

            $changeSet[$fieldName] = [
                'inserted' => $inserted,
                'removed' => $removed,
            ];
        }

        return $changeSet;
    }


    /**
     * @param EntityActionLog $log
     * @param $entity
     */
    private function setDeleteLog(EntityActionLog $log, $entity): void
    {
        $entity = clone $entity;
        if (\method_exists($entity, 'getDeletedBy') && $entity->getDeletedBy()) {
            $log->setUser($entity->getDeletedBy());
        }
        //we don't want to serialize user info, user is in own attribute
        if (\method_exists($entity, 'setDeletedBy')) {
            $entity->setDeletedBy(null);
        }
        $this->setClass($log, $entity);
        $log->setChangedEntityId($this->deletedId);
        $log->setChangedEntity($this->deleteEntity);
    }


    /**
     * @param EntityActionLog $log
     * @param Object $entity
     */
    private function setCreateLog(EntityActionLog $log, $entity): void
    {
        $entity = clone $entity;
        if (\method_exists($entity, 'getCreatedBy') && $entity->getCreatedBy()) {
            $log->setUser($entity->getCreatedBy());
        }
        //we don't want to serialize user info, user is in own attribute
        if (\method_exists($entity, 'setCreatedBy')) {
            $entity->setCreatedBy(null);
        }
        $this->setClass($log, $entity);

        $serialized = $this->serializer->serialize($entity, 'json');

        $log->setChangedEntity($serialized);
    }


    /**
     * @param EntityActionLog $log
     * @param Object $entity
     */
    private function setClass(EntityActionLog $log, $entity): void
    {
        $className = \get_class($entity);
        if (\strpos($className, self::PROXY_FLAG) === 0) {
            $className = \substr($className, \strlen(self::PROXY_FLAG));
        }

        if (\method_exists($entity, 'getId')) {
            $log->setChangedEntityId($entity->getId());
            $log->setChangedEntityClass($className);
        } else {
            //newsletter filter
            $id = $entity->getListId();
            $className = "$className $id";
            $log->setChangedEntityClass($className);
        }
    }
}
