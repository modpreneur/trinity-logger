<?php

namespace Trinity\Bundle\LoggerBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class SetNotificationUserEvent
 */
class SetNotificationUserEvent extends Event
{
    const NAME = 'trinity.logger.setNotificationUser';

    /** @var  string Integer representing user's id or string representing system(e.g. client_3, necktie) */
    protected $userIdentification;

    /** @var  string Client id */
    protected $clientId;

    /**
     * BeforeNotificationBatchProcessEvent constructor.
     *
     * @param string $userIdentification
     * @param string $clientId
     */
    public function __construct(string $userIdentification, string $clientId)
    {
        $this->userIdentification = $userIdentification;
        $this->clientId = $clientId;
    }

    /**
     * @return string
     */
    public function getUserIdentification()
    {
        return $this->userIdentification;
    }

    /**
     * @param string $userIdentification
     */
    public function setUserIdentification($userIdentification)
    {
        $this->userIdentification = $userIdentification;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }
}