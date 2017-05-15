<?php

namespace Trinity\Bundle\LoggerBundle\Tests\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

/**
 * Class ObjectManagerChild
 * @package Trinity\Bundle\LoggerBundle\Tests\EventListener
 */
class ObjectManagerChild extends EntityManager implements ObjectManager
{

}
