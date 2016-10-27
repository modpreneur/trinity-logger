<?php

namespace Trinity\Bundle\LoggerBundle\Annotation;

/**
 * Class EntityActionLoggable.
 *
 * when used :
 *      @EntityActionLoggable()
 *          all attributes will be logged
 *
 *      @EntityActionLoggable('name','ip','price')
 *          only changes in name, ip or price attributes will be logged
 *
 *
 * @Annotation
 */
class EntityActionLoggable
{
    /** @var array  */
    private $attributes = [];

    /** @var array  */
    private $emptyAttributes = [];

    /**
     * EntityActionLoggable constructor.
     *
     * @param $options
     */
    public function __construct($options)
    {
        if (array_key_exists('value', $options)) {
            $this->attributes = $options['value'];
        }

        if (array_key_exists('empty', $options)) {
            $this->emptyAttributes = $options['empty'];
            if ($this->attributes) {
                $this->attributes = array_merge($this->attributes, $this->emptyAttributes);
            }
        }
    }

    /**
     * @return array
     */
    public function getAttributeList() : array
    {
        return $this->attributes;
    }

    /**
     * @return array
     */
    public function getEmptyAttributes() : array
    {
        return $this->emptyAttributes;
    }
}
