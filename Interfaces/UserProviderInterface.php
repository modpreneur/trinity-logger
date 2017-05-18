<?php

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\Interfaces;

use Trinity\Component\Core\Interfaces\UserInterface;

/**
 * Interface UserProviderInterface.
 */
interface UserProviderInterface
{
    /**
     * Get user by id.
     *
     * @param int $userId
     *
     * @return UserInterface
     */
    public function getUserById(int $userId): UserInterface;
}
