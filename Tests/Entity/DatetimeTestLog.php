<?php

namespace Trinity\Bundle\LoggerBundle\Tests\Entity;

use Trinity\Bundle\LoggerBundle\Entity\BaseElasticLog;

/**
 * Class DatetimeTestLog
 */
class DatetimeTestLog extends BaseElasticLog
{
    const LOG_NAME = 'DatetimeTestLog';
    const DEFAULT_TTL = 5;

    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * @var string
     */
    protected $string;

    /**
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * @return string
     */
    public function getString(): string
    {
        return $this->string;
    }

    /**
     * @param string $string
     */
    public function setString(string $string)
    {
        $this->string = $string;
    }


    /**
     * Return a human readable string containing only characters.
     * For example: ExceptionLog, IpnLog
     *
     * @return string
     */
    public static function getLogName(): string
    {
        return self::LOG_NAME;
    }

    /**
     * Return a default tll in days.
     *
     * @return int
     */
    public static function getDefaultTtl(): int
    {
        return self::DEFAULT_TTL;
    }
}

