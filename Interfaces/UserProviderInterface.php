<?php

namespace Trinity\Bundle\LoggerBundle\Interfaces;

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
     * @return \Trinity\Component\Core\Interfaces\UserInterface
     */
    public function getUserById(int $userId);
}
