<?php

namespace Trinity\Bundle\LoggerBundle\Tests\Annotation;

use PHPUnit\Framework\TestCase;
use Trinity\Bundle\LoggerBundle\Annotation\EntityActionLoggable;

/**
 * Class EntityActionLoggableTest
 * @package Trinity\Bundle\LoggerBundle\Tests\Annotation
 */
class EntityActionLoggableTest extends TestCase
{
    public function testAttributes()
    {
        $option = [
            'value' => [
                'name' => 'foo',
                'ip' => '123.456.78.9',
                'price' => '25.99',
            ],
        ];

        /** @var EntityActionLoggable  $entityActionLoggable */
        $entityActionLoggable = new EntityActionLoggable($option);

        $excepted = [
            'name' => 'foo',
            'ip' => '123.456.78.9',
            'price' => '25.99',
        ];

        $this->assertEquals($excepted, $entityActionLoggable->getAttributeList());

        $this->assertEmpty($entityActionLoggable->getEmptyAttributes());
    }

    public function testEmptyAttributes()
    {
        $option = [
            'empty' => [
                'name' => 'foo',
                'ip' => '123.456.78.9',
                'price' => '25.99',
            ],
        ];

        /** @var EntityActionLoggable  $entityActionLoggable */
        $entityActionLoggable = new EntityActionLoggable($option);

        $excepted = [
            'name' => 'foo',
            'ip' => '123.456.78.9',
            'price' => '25.99',
        ];

        $this->assertEquals($excepted, $entityActionLoggable->getEmptyAttributes());

        $this->assertEmpty($entityActionLoggable->getAttributeList());
    }

    public function testMergeEmptyAttributesToAtributes()
    {
        $option = [
            'value' => [
                'foo' => "blah",
            ],
            'empty' => [
                'name' => 'foo',
                'ip' => '123.456.78.9',
                'price' => '25.99',
            ],
        ];

        /** @var EntityActionLoggable  $entityActionLoggable */
        $entityActionLoggable = new EntityActionLoggable($option);

        $excepted = [
            'foo' => 'blah',
            'name' => 'foo',
            'ip' => '123.456.78.9',
            'price' => '25.99',
        ];

        $this->assertEquals($excepted, $entityActionLoggable->getAttributeList());

        $exceptedEmpty = [
            'name' => 'foo',
            'ip' => '123.456.78.9',
            'price' => '25.99',
        ];

        $this->assertEquals($exceptedEmpty, $entityActionLoggable->getEmptyAttributes());
    }
}
