<?php

namespace Trinity\Bundle\LoggerBundle\Tests\EventListener;

/**
 * Class CustomEntity
 * @package Trinity\Bundle\LoggerBundle\Tests\EventListener
 */
class CustomEntity
{

    /** @var  string */
    private $updatedBy;

    /** @var  int */
    private $deletedBy;

    /** @var  int */
    private $createdBy;


    /**
     * @return string
     */
    public function getUpdatedBy(): string
    {
        return $this->updatedBy;
    }


    /**
     * @param string $updatedBy
     */
    public function setUpdatedBy(string $updatedBy)
    {
        $this->updatedBy = $updatedBy;
    }


    /**
     * @return int
     */
    public function getListId(): int
    {
        return 1;
    }


    /**
     * @return int
     */
    public function getDeletedBy(): int
    {
        return $this->deletedBy;
    }


    /**
     * @param int $deletedBy
     */
    public function setDeletedBy(?int $deletedBy)
    {
        $this->deletedBy = $deletedBy;
    }


    /**
     * @return int
     */
    public function getCreatedBy(): int
    {
        return $this->createdBy;
    }


    /**
     * @param int|null $createdBy
     */
    public function setCreatedBy(?int $createdBy)
    {
        $this->createdBy = $createdBy;
    }
}
