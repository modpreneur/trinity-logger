<?php

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Trinity\Bundle\LoggerBundle\DependencyInjection\TrinityLoggerExtension;
use Trinity\Bundle\LoggerBundle\Tests\Entity\DatetimeTestLog;

/**
 * Class ConfigurationTest
 * @package Trinity\Bundle\LoggerBundle\Tests\DependencyInjection
 */
class ConfigurationTest extends TestCase
{
    /**
     * @dataProvider configurationDataProvider
     */
    public function testConfigurationStrictSetting($configs): void
    {
        /** @var TrinityLoggerExtension $loader */
        $loader = new TrinityLoggerExtension();

        /** @var ContainerBuilder $container */
        $container = new ContainerBuilder();

        $loader->load($configs, $container);

        if ($container->hasParameter('trinity.logger.elastic_host')) {
            if (\array_key_exists('elastic_host', $configs[0])) {
                static::assertEquals(
                    $configs[0]['elastic_host'],
                    $container->getParameter('trinity.logger.elastic_host')
                );
            } else {
                static::assertNull($container->getParameter('trinity.logger.elastic_host'));
            }
        }

        if ($container->hasParameter('trinity.logger.base.entities.path')) {
            if (\array_key_exists('entities_path', $configs[0])) {
                static::assertEquals(
                    $configs[0]['entities_path'],
                    $container->getParameter('trinity.logger.base.entities.path')
                );
            }
        }

        if ($container->hasParameter('trinity.logger.log_classes')) {
            if (\array_key_exists('log_classes', $configs[0])) {
                static::assertEquals(
                    $configs[0]['log_classes'],
                    $container->getParameter('trinity.logger.log_classes')
                );
            }
        }

        static::assertFalse($configs[0]['use_async']);
    }


    /**
     * @return array
     */
    public function configurationDataProvider(): array
    {
        return [
            [
                [
                    [
                        'elastic_host' => '127.0.0.1:9200',
                        'entities_path' => 'Necktie\\AppBundle\\Entity',
                        'logger_user_provider' => '@test1',
                        'logger_ttl_provider' => '@test2',
                        'use_async' => false,
                        'log_classes' => [
                            DatetimeTestLog::class
                        ]
                    ]
                ]
            ],
            [
                [
                    [
                        'logger_user_provider' => '@test3',
                        'use_async' => false,
                        'elastic_host' => '127.0.0.1:9200',
                        'log_classes' => [
                            DatetimeTestLog::class
                        ]

                    ]
                ]
            ],
            [
                [
                    [
                        'elastic_host' => '127.0.0.1:9200',
                        'logger_user_provider' => '@test1',
                        'logger_ttl_provider' => '@test2',
                        'use_async' => false,
                        'log_classes' => [
                            DatetimeTestLog::class
                        ]
                    ]
                ]
            ]
        ];
    }
}
