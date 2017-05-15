<?php

namespace Trinity\Bundle\LoggerBundle\Tests\EventListener;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Class ObjectManagerWithout
 * @package Trinity\Bundle\LoggerBundle\Tests\EventListener
 */
class ObjectManagerWithout implements ObjectManager
{
    /**
     * @param string $className
     * @param mixed $id
     */
    public function find($className, $id)
    {
    }

    /**
     * @param object $object
     */
    public function persist($object)
    {
    }

    /**
     * @param object $object
     */
    public function remove($object)
    {
    }

    /**
     * @param object $object
     */
    public function merge($object)
    {
    }

    /**
     * @param null $objectName
     */
    public function clear($objectName = null)
    {
    }

    /**
     * @param object $object
     */
    public function detach($object)
    {
    }

    /**
     * @param object $object
     */
    public function refresh($object)
    {
    }

    public function flush()
    {
    }

    /**
     * @param string $className
     */
    public function getRepository($className)
    {
    }

    /**
     * @param string $className
     */
    public function getClassMetadata($className)
    {
    }


    public function getMetadataFactory()
    {
    }

    /**
     * @param object $obj
     */
    public function initializeObject($obj)
    {
    }

    /**
     * @param object $object
     */
    public function contains($object)
    {
    }
}
