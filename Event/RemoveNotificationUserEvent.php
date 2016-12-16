<?php

namespace Trinity\Bundle\LoggerBundle\Event;

/**
 * Class RemoveNotificationUserEvent
 */
class RemoveNotificationUserEvent extends SetNotificationUserEvent
{
    const NAME = 'trinity.logger.removeNotificationUser';
}