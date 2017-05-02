<?php

namespace Trinity\Bundle\LoggerBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Trinity\Bundle\LoggerBundle\Entity\EntityActionLog;
use Trinity\Component\Core\Interfaces\UserInterface;

/**
 * Class EntityActionLogTest
 * @package Trinity\Bundle\LoggerBundle\Tests\Entity
 */
class EntityActionLogTest extends TestCase
{
    public function testEntity()
    {
        $entityActionLog = new EntityActionLog();

        $mockUser = new MockUser();

        $entityActionLog->setUser($mockUser);

        $this->assertInstanceOf(UserInterface::class, $entityActionLog->getUser());

        $entityActionLog->setSystem('test123');

        $this->assertEquals('test123', $entityActionLog->getSystem());

        $entityActionLog->setChangedEntityClass('entity + namespace');

        $this->assertEquals('entity + namespace', $entityActionLog->getChangedEntityClass());

        $entityActionLog->setChangedEntity('{"test" => "test"}');

        $this->assertEquals('{"test" => "test"}', $entityActionLog->getChangedEntity());

        $entityActionLog->setChangedEntityId(99999999999999999999999999);

        $this->assertEquals(99999999999999999999999999, $entityActionLog->getChangedEntityId());

        $entityActionLog->setActionType('update');

        $this->assertEquals('update', $entityActionLog->getActionType());

        $changeSetJson= '{"name":"John","age":30,"cars":[ "Ford", "BMW", "Fiat" ]}';

        $changeSetArray = [
            "name" =>"John",
            "age" => 30,
            "cars" => [
                "Ford",
                "BMW",
                "Fiat",
            ]
        ];

        $entityActionLog->setChangeSet($changeSetJson, 'read');

        $this->assertArrayHasKey('name', $entityActionLog->getChangeSet());

        $this->assertEquals($changeSetArray, $entityActionLog->getChangeSet());

        $entityActionLog->setChangeSet($changeSetArray, 'write');

        $this->assertJsonStringEqualsJsonString($changeSetJson, $entityActionLog->getChangeSet());

        $this->assertEquals($entityActionLog->getId(), $entityActionLog->__toString());
    }
}