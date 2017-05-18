<?php

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\Event;

/**
 * Class RemoveNotificationUserEvent
 */
class RemoveNotificationUserEvent extends SetNotificationUserEvent
{
    const NAME = 'trinity.logger.removeNotificationUser';
}
