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
    public function testEntity(): void
    {
        /** @var EntityActionLog $entityActionLog */
        $entityActionLog = new EntityActionLog();

        /** @var MockUser $mockUser */
        $mockUser = new MockUser();

        $entityActionLog->setUser($mockUser);
        static::assertInstanceOf(UserInterface::class, $entityActionLog->getUser());

        $entityActionLog->setSystem('test123');
        static::assertEquals('test123', $entityActionLog->getSystem());

        $entityActionLog->setChangedEntityClass('entity + namespace');
        static::assertEquals('entity + namespace', $entityActionLog->getChangedEntityClass());

        $entityActionLog->setChangedEntity('{"test" => "test"}');
        static::assertEquals('{"test" => "test"}', $entityActionLog->getChangedEntity());

        $entityActionLog->setChangedEntityId(99999999);
        static::assertEquals(99999999, $entityActionLog->getChangedEntityId());

        $entityActionLog->setActionType('update');
        static::assertEquals('update', $entityActionLog->getActionType());

        $changeSetJson = '{"name":"John","age":30,"cars":[ "Ford", "BMW", "Fiat" ]}';

        $changeSetArray = [
            "name" => "John",
            "age" => 30,
            "cars" => [
                "Ford",
                "BMW",
                "Fiat",
            ]
        ];

        $entityActionLog->setChangeSet($changeSetJson, 'read');

        static::assertArrayHasKey('name', $entityActionLog->getChangeSet());

        static::assertEquals($changeSetArray, $entityActionLog->getChangeSet());

        $entityActionLog->setChangeSet($changeSetArray, 'write');

        static::assertJsonStringEqualsJsonString($changeSetJson, $entityActionLog->getChangeSet());

        static::assertEquals($entityActionLog->getId(), $entityActionLog->__toString());
    }
}
