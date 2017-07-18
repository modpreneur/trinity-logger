<?php

namespace Trinity\Bundle\LoggerBundle\Tests\Entity;

use Trinity\Bundle\LoggerBundle\Entity\BaseElasticLog;

/**
 * Class DatetimeTestLog
 */
class DatetimeTestLog extends BaseElasticLog
{
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
}

