<?php
/**
 * Created by PhpStorm.
 * User: gabriel
 * Date: 14.6.16
 * Time: 11:29
 */

namespace Trinity\Bundle\LoggerBundle\Entity;

/**
 * Class BaseElasticLog
 * @package Trinity\LoggerBundle\Entity
 */
class BaseElasticLog
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var int
     */
    protected $createdAt;
    
    /**
     * @var int
     */
    protected $ttl;

    /**
     * BaseElasticLog constructor.
     * @param string $id
     */
    public function __construct($id = '')
    {
        $this->id = $id;
    }
    
    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param int $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * @param int $ttl
     */
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;
    }
}
