<?php

namespace Trinity\Bundle\LoggerBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Trinity\Bundle\LoggerBundle\DependencyInjection\TrinityLoggerExtension;
use Trinity\Bundle\LoggerBundle\LoggerBundle;

/**
 * Class TrinityLoggerExtensionTest
 * @package Trinity\Bundle\LoggerBundle\Tests\DependencyInjection
 */
class TrinityLoggerExtensionTest extends TestCase
{
    public function testLoggerBundle(): void
    {
        /** @var ContainerBuilder $container */
        $container = new ContainerBuilder();

        /** @var LoggerBundle $settingBundle */
        $settingBundle = new LoggerBundle();
        $settingBundle->build($container);

        /** @var ExtensionInterface[] $extensions */
        $extensions = $container->getExtensions();

        static::assertEmpty($extensions);

        static::assertInstanceOf(TrinityLoggerExtension::class, $settingBundle->getContainerExtension());
    }
}
