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
     * {@inheritDoc}
     */
    public function find($className, $id): void
    {
    }


    /**
     * {@inheritDoc}
     */
    public function persist($object): void
    {
    }


    /**
     * {@inheritDoc}
     */
    public function remove($object): void
    {
    }


    /**
     * {@inheritDoc}
     */
    public function merge($object): void
    {
    }


    /**
     * {@inheritDoc}
     */
    public function clear($objectName = null): void
    {
    }


    /**
     * {@inheritDoc}
     */
    public function detach($object): void
    {
    }


    /**
     * {@inheritDoc}
     */
    public function refresh($object): void
    {
    }


    /**
     * {@inheritDoc}
     */
    public function flush(): void
    {
    }


    /**
     * {@inheritDoc}
     */
    public function getRepository($className): void
    {
    }


    /**
     * {@inheritDoc}
     */
    public function getClassMetadata($className): void
    {
    }


    /**
     * {@inheritDoc}
     */
    public function getMetadataFactory(): void
    {
    }


    /**
     * {@inheritDoc}
     */
    public function initializeObject($obj): void
    {
    }


    /**
     * {@inheritDoc}
     */
    public function contains($object): void
    {
    }
}
