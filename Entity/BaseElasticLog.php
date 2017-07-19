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
abstract class BaseElasticLog
{
    /** @var string $id*/
    protected $id;

    /** @var int $createdAt*/
    protected $createdAt;

    /**
     * Return a human readable string containing only characters.
     * For example: ExceptionLog, IpnLog
     *
     * @return string
     */
    abstract public static function getLogName(): string;

    /**
     * Return a default tll in days.
     *
     * @return int
     */
    abstract public static function getDefaultTtl(): int;

    /**
     * BaseElasticLog constructor.
     *
     * @param string $id
     */
    public function __construct($id = '')
    {
        $this->id = $id;
        $this->createdAt = new \DateTime();
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
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }


    /**
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
