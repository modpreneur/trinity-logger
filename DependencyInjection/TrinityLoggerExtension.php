<?php
/**
 * This file is part of Trinity package.
 */
namespace Trinity\Bundle\LoggerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class TrinityLoggerExtension.
 */
class TrinityLoggerExtension extends Extension
{
    /**
     * Loads a specific configuration.
     *
     * @param array            $configs   An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (array_key_exists('elastic_logs', $config) && isset($config['elastic_logs'])) {
            $container->setParameter('trinity.logger.elastic_logs', true);
            $container->setParameter('trinity.logger.elastic_host', $config['elastic_logs']['elastic_host']);
            $container->setParameter(
                'trinity.logger.async_queue_length',
                $config['elastic_logs']['async_queue_length']
            );

            if (array_key_exists('managed_index', $config['elastic_logs'])
                && isset($config['elastic_logs']['managed_index'])
            ) {
                $container->setParameter(
                    'trinity.logger.elastic_managed_index',
                    $config['elastic_logs']['managed_index']
                );
            } else {
                $container->setParameter('trinity.logger.elastic_managed_index', null);
            }

            if (array_key_exists('entities_path', $config['elastic_logs'])
                && isset($config['elastic_logs']['entities_path'])
            ) {
                $container->setParameter('trinity.logger.base.entities.path', $config['elastic_logs']['entities_path']);
            } else {
                $container->setParameter('trinity.logger.base.entities.path', null);
            }
        } else {
            $container->setParameter('trinity.logger.elastic_logs', false);
            $container->setParameter('trinity.logger.elastic_host', null);
        }

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        if (array_key_exists('logger_ttl_provider', $config) && $config['logger_ttl_provider'] !== null) {
            $container->setAlias('trinity.logger.ttl_provider', $config['logger_ttl_provider']);
        } else {
            $container->setAlias('trinity.logger.ttl_provider', 'trinity.logger.default_ttl_provider');
        }

        $container->setAlias('trinity.logger.user_provider', $config['logger_user_provider']);
    }
}
