<?php

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\Tests\Entity;

use Trinity\Component\Core\Interfaces\UserInterface;

/**
 * Class MockUser
 * @package Trinity\Bundle\LoggerBundle\Tests\Entity
 */
class MockUser implements UserInterface
{
    /** @var  int */
    private $id;
    /** @var  string */
    private $firstName;
    /** @var  string */
    private $lastName;
    /** @var  string */
    private $fullName;
    /** @var  string */
    private $phoneNumber;
    /** @var  string */
    private $website;
    /** @var  string */
    private $avatar;
    /** @var  bool */
    private $public;
    /** @var  int */
    private $settingIdentifier;


    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }


    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }


    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }


    /**
     * @return string
     */
    public function getFullName(): string
    {
        return $this->fullName;
    }


    /**
     * @return string
     */
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }


    /**
     * @return string
     */
    public function getWebsite(): string
    {
        return $this->website;
    }


    /**
     * @return string
     */
    public function getAvatar(): string
    {
        return $this->avatar;
    }


    /**
     * @return bool
     */
    public function getPublic(): bool
    {
        return $this->public;
    }


    /**
     * @return int
     */
    public function getSettingIdentifier(): int
    {
        return $this->settingIdentifier;
    }


    /**
     * @return string
     */
    public function __toString(): string
    {
        return '';
    }
}
