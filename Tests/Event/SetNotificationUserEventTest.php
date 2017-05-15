<?php

namespace Trinity\Bundle\LoggerBundle\Tests\Event;

use PHPUnit\Framework\TestCase;
use Trinity\Bundle\LoggerBundle\Event\SetNotificationUserEvent;

/**
 * Class SetNotificationUserEventTest
 * @package Trinity\Bundle\LoggerBundle\Tests\Event
 */
class SetNotificationUserEventTest extends TestCase
{
    public function testConstructGetsAndSets()
    {
        /** @var SetNotificationUserEvent $setNotificationUserEvent */
        $setNotificationUserEvent = new SetNotificationUserEvent('userIdentification', 'clientId');

        $this->assertEquals('userIdentification', $setNotificationUserEvent->getUserIdentification());

        $this->assertEquals('clientId', $setNotificationUserEvent->getClientId());

        $setNotificationUserEvent->setUserIdentification('identificationTest');
        $setNotificationUserEvent->setClientId('clientIdTest');

        $this->assertEquals('identificationTest', $setNotificationUserEvent->getUserIdentification());

        $this->assertEquals('clientIdTest', $setNotificationUserEvent->getClientId());
    }
}
