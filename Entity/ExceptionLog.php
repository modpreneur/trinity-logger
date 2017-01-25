<?php

/*
 * This file is part of the Trinity project.
 */

namespace Trinity\Bundle\LoggerBundle\Entity;

use Trinity\Component\Core\Interfaces\EntityInterface;
use Trinity\Component\Core\Interfaces\UserInterface;

/**
 * Class ExceptionLog.
 */
class ExceptionLog extends BaseElasticLog implements EntityInterface
{
    const NAME = 'ExceptionLog';

    /**
     * @var string Analyzed by elasticSearch
     */
    private $log;

    /**
     * @var string Analyzed by elasticSearch
     */
    private $readable;

    /**
     * @var string Analyzed by elasticSearch
     */
    private $serverData;

    /**
     * @var int This is mono-log exception level used in mono-log bundle, not http response!
     */
    private $level;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $ip;

    /**
     * Get log.
     *
     * @return string
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * Set log.
     *
     * @param string $log
     *
     * @return ExceptionLog
     */
    public function setLog($log)
    {
        $this->log = $log;

        return $this;
    }

    /**
     * @return string
     */
    public function getReadable()
    {
        return $this->readable;
    }

    /**
     * @param string $readable
     */
    public function setReadable($readable)
    {
        $this->readable = $readable;
    }

    /**
     * Get serverData.
     *
     * @return string
     */
    public function getServerData()
    {
        return $this->serverData;
    }

    /**
     * Set serverData.
     *
     * @param string $serverData
     *
     * @return ExceptionLog
     */
    public function setServerData($serverData)
    {
        $this->serverData = $serverData;

        return $this;
    }

    /**
     * Get level.
     *
     * @return string
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Set level.
     *
     * @param string $level
     *
     * @return ExceptionLog
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @var UserInterface
     */
    private $user;

    /**
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param UserInterface $user
     */
    public function setUser(UserInterface $user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return (string) $this->id;
    }

    /**
     * @return array
     *
     * 400: Runtime errors that do not require immediate action but should typically be logged and monitored.
     * 500: Critical conditions. Example: Application component unavailable, unexpected exception.
     * 550: Action must be taken immediately. Example: Entire website down, database unavailable, etc.
     *      This should trigger the SMS alerts and wake you up.
     * 600: System is unusable
     */
    public static function getPossibleLevels() :array
    {
        return [
            400 => 'Error',
            500 => 'Critical',
            550 => 'Alert',
            600 => 'Emergency',
        ];
    }
}
