<?php

namespace Trinity\Bundle\LoggerBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Trinity\Bundle\LoggerBundle\DependencyInjection\TrinityLoggerExtension;

/**
 * Class ConfigurationTest
 * @package Trinity\Bundle\LoggerBundle\Tests\DependencyInjection
 */
class ConfigurationTest extends TestCase
{
    /**
     * @dataProvider configurationDataProvider
     */
    public function testConfigurationStrictSetting($configs)
    {
        /** @var TrinityLoggerExtension $loader */
        $loader = new TrinityLoggerExtension();

        /** @var ContainerBuilder $container */
        $container = new ContainerBuilder();

        $loader->load($configs, $container);

        if ($container->hasParameter('trinity.logger.elastic_host')) {
            if (array_key_exists('elastic_logs', $configs[0]) &&
                array_key_exists('elastic_host', $configs[0]['elastic_logs'])) {
                $this->assertEquals(
                    $configs[0]['elastic_logs']['elastic_host'],
                    $container->getParameter('trinity.logger.elastic_host')
                );
            } else {
                $this->assertNull($container->getParameter('trinity.logger.elastic_host'));
            }
        }

        if ($container->hasParameter('trinity.logger.elastic_managed_index')) {
            if (array_key_exists('elastic_logs', $configs[0]) &&
                array_key_exists('managed_index', $configs[0]['elastic_logs'])) {
                $this->assertEquals(
                    $configs[0]['elastic_logs']['managed_index'],
                    $container->getParameter('trinity.logger.elastic_managed_index')
                );
            }
        }
        if ($container->hasParameter('trinity.logger.base.entities.path')) {
            if (array_key_exists('elastic_logs', $configs[0]) &&
                array_key_exists('entities_path', $configs[0]['elastic_logs'])) {
                $this->assertEquals(
                    $configs[0]['elastic_logs']['entities_path'],
                    $container->getParameter('trinity.logger.base.entities.path')
                );
            }
        }
    }


    public function configurationDataProvider()
    {
        return [
            [
                [
                    [
                        'elastic_logs' => [
                            'elastic_host' => '127.0.0.1:9200',
                            'managed_index' => 'necktie',
                            'entities_path' => 'Necktie\\AppBundle\\Entity',
                        ],
                        'logger_user_provider' => '@test1',
                        'logger_ttl_provider' => '@test2',
                    ]
                ]
            ],
            [
                [
                    [
                        'logger_user_provider' => '@test3',
                    ]
                ]
            ],
            [
                [
                    [
                        'elastic_logs' => [
                            'elastic_host' => '127.0.0.1:9200',
                        ],
                        'logger_user_provider' => '@test1',
                        'logger_ttl_provider' => '@test2',
                    ]
                ]
            ]
        ];
    }
}
