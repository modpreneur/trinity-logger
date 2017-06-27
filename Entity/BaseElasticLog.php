<?php
/**
 * Created by PhpStorm.
 * User: gabriel
 * Date: 14.6.16
 * Time: 11:29
 */

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\Entity;

/**
 * Class BaseElasticLog
 * @package Trinity\LoggerBundle\Entity
 */
class BaseElasticLog
{
    /** change when class name and log name are different */
    const LOG_NAME = self::class;

    /** @var string $id*/
    protected $id;

    /** @var int $createdAt*/
    protected $createdAt;

    /** @var int|null $ttl*/
    protected $ttl;


    /**
     * BaseElasticLog constructor.
     *
     * @param string $id
     */
    public function __construct($id = '')
    {
        $this->id = $id;
        $this->createdAt = \time();
    }

    /**
     * @internal DO NOT use this to set the id before the entity is stored. The id will be ignored.
     * The setter is used ONLY INTERNALLY.
     * It is used for your convenience to set the id to the entity after it is persisted.
     *
     *
     * @param string|null $id
     */
    public function setId(?string $id)
    {
        $this->id = $id;
    }


    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }


    /**
     * @return int
     */
    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }


    /**
     * @param int $createdAt
     */
    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }


    /**
     * @return int|null
     */
    public function getTtl(): ?int
    {
        return $this->ttl;
    }


    /**
     * @param int $ttl
     */
    public function setTtl($ttl): void
    {
        $this->ttl = $ttl;
    }
}
