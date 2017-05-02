<?php

namespace Trinity\Bundle\LoggerBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Trinity\Bundle\LoggerBundle\DependencyInjection\TrinityLoggerExtension;
use Trinity\Bundle\LoggerBundle\LoggerBundle;

/**
 * Class TrinityLoggerExtensionTest
 * @package Trinity\Bundle\LoggerBundle\Tests\DependencyInjection
 */
class TrinityLoggerExtensionTest extends TestCase
{


    public function testLoggerBundle()
    {
        $container = new ContainerBuilder();
        $settingBundle = new LoggerBundle();
        $settingBundle->build($container);
        $extensions = $container->getExtensions();

        $this->assertEmpty($extensions);

        $this->assertInstanceOf(TrinityLoggerExtension::class, $settingBundle->getContainerExtension());

    }
}
