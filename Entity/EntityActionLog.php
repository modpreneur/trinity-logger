<?php

namespace Trinity\Bundle\LoggerBundle\Entity;

use Trinity\Component\Core\Interfaces\EntityInterface;
use Trinity\Component\Core\Interfaces\UserInterface;

/**
 * Entity Actions Log. In elasticSearch.
 */
class EntityActionLog extends BaseElasticLog implements EntityInterface
{
    /**
     * @var UserInterface admin
     */
    private $user;

    /**
     * @var string
     */
    private $system;

    /**
     * @var string entity+namespace
     */
    private $changedEntityClass;

    /**
     * @var string JSON entity
     */
    private $changedEntity;

    /**
     * @var int id
     */
    private $changedEntityId;

    /**
     * @var string Created|Updated|Deleted
     */
    private $actionType;

    /**
     * @var mixed[]
     *             Analyzed by elasticSearch
     */
    private $changeSet;

    /**
     * @return UserInterface
     */
    public function getUser()
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
    public function setSystem(string $system)
    {
        $this->system = $system;
    }

    /**
     * @return string
     */
    public function getChangedEntityClass()
    {
        return $this->changedEntityClass;
    }

    /**
     * @param string $changedEntityClass
     */
    public function setChangedEntityClass($changedEntityClass)
    {
        $this->changedEntityClass = $changedEntityClass;
    }

    /**
     * @param UserInterface $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getChangedEntity()
    {
        return $this->changedEntity;
    }

    /**
     * @param string $changedEntity
     */
    public function setChangedEntity($changedEntity)
    {
        $this->changedEntity = $changedEntity;
    }

    /**
     * @return int
     */
    public function getChangedEntityId()
    {
        return $this->changedEntityId;
    }

    /**
     * @param int $changedEntityId
     */
    public function setChangedEntityId($changedEntityId)
    {
        $this->changedEntityId = $changedEntityId;
    }

    /**
     * @return string
     */
    public function getActionType()
    {
        return $this->actionType;
    }

    /**
     * @param string $actionType
     */
    public function setActionType($actionType)
    {
        $this->actionType = $actionType;
    }

    /**
     * @return array|string
     */
    public function getChangeSet()
    {
        return $this->changeSet;
    }

    /**
     * @param array|string  $changeSet
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
            $this->changeSet = (array) json_decode($changeSet);
        } else {
            $this->changeSet = json_encode($changeSet);
        }
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return $this->id;
    }
}
