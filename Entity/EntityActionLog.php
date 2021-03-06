<?php

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\Entity;

use Trinity\Component\Core\Interfaces\EntityInterface;
use Trinity\Component\Core\Interfaces\UserInterface;

/**
 * Entity Actions Log. In elasticSearch.
 */
class EntityActionLog extends BaseElasticLog implements EntityInterface
{
    const LOG_NAME = 'EntityActionLog';
    const DEFAULT_TTL = 30;

    /** @var UserInterface admin */
    private $user;

    /** @var string */
    private $system;

    /** @var string entity+namespace */
    private $changedEntityClass;

    /** @var string|null JSON entity */
    private $changedEntity;

    /** @var int|null id */
    private $changedEntityId;

    /** @var string Created|Updated|Deleted */
    private $actionType;

    /**
     * @var array|string
     * Analyzed by elasticSearch
     */
    private $changeSet;


    /**
     * @return UserInterface|null
     */
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }


    /**
     * @return string
     */
    public function getSystem(): string
    {
        return $this->system ?? '';
    }


    /**
     * @param string $system
     */
    public function setSystem(string $system): void
    {
        $this->system = $system;
    }


    /**
     * @return string
     */
    public function getChangedEntityClass(): string
    {
        return $this->changedEntityClass;
    }


    /**
     * @param string $changedEntityClass
     */
    public function setChangedEntityClass($changedEntityClass): void
    {
        $this->changedEntityClass = $changedEntityClass;
    }


    /**
     * @param UserInterface $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }


    /**
     * @return string|null
     */
    public function getChangedEntity(): ?string
    {
        return $this->changedEntity;
    }


    /**
     * @param string $changedEntity
     */
    public function setChangedEntity($changedEntity): void
    {
        $this->changedEntity = $changedEntity;
    }


    /**
     * @return int|string|null
     *
     * Don't insert the data type after the method. Int is for SQL entities and String for NoSQL entities.
     */
    public function getChangedEntityId()
    {
        return $this->changedEntityId;
    }


    /**
     * @param int|string $changedEntityId
     */
    public function setChangedEntityId($changedEntityId): void
    {
        $this->changedEntityId = (string) $changedEntityId;
    }


    /**
     * @return string
     */
    public function getActionType(): string
    {
        return $this->actionType;
    }


    /**
     * @param string $actionType
     */
    public function setActionType($actionType): void
    {
        $this->actionType = $actionType;

        /* Backward compatibility with old logger */
        if (\strpos($actionType, 'admin_') === 0) {
            $this->actionType = \explode('_', $actionType)[1];
        }
    }


    /**
     * @return array|string
     */
    public function getChangeSet()
    {
        return $this->changeSet;
    }


    /**
     * @param array|string $changeSet
     * @param string $param
     *
     * $param can be 'read'/'write'
     *
     * Read from elastic /Write into elastic
     *
     * This is due elastic-search way of storing data, when same attribute
     * has to have same data type. But one client can be entity or just ID
     * so this goes around it.
     */
    public function setChangeSet($changeSet, $param = 'read')
    {
        if ($param === 'read') {
            $this->changeSet = (array)\json_decode($changeSet);
        } else {
            $this->changeSet = \json_encode($changeSet);
        }
    }


    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->id;
    }

    /**
     * Return a human readable string containing only characters.
     * For example: ExceptionLog, IpnLog
     *
     * @return string
     */
    public static function getLogName(): string
    {
        return self::LOG_NAME;
    }

    /**
     * Return a default tll in days.
     *
     * @return int
     */
    public static function getDefaultTtl(): int
    {
        return self::DEFAULT_TTL;
    }
}
