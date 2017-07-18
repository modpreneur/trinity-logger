<?php
/**
 * This file is part of Trinity package.
 */

declare(strict_types=1);

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
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('trinity.logger.elastic_host', $config['elastic_host']);
        $container->setParameter(
            'trinity.logger.async_queue_length',
            (int) $config['async_queue_length']
        );

        if (\array_key_exists('managed_index', $config) && isset($config['managed_index'])
        ) {
            $container->setParameter(
                'trinity.logger.elastic_managed_index',
                $config['managed_index']
            );
        } else {
            $container->setParameter('trinity.logger.elastic_managed_index', null);
        }

        if (\array_key_exists('entities_path', $config) && isset($config['entities_path'])
        ) {
            $container->setParameter('trinity.logger.base.entities.path', $config['entities_path']);
        } else {
            $container->setParameter('trinity.logger.base.entities.path', null);
        }

        $container->setParameter('trinity.logger.log_classes', $config['log_classes']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('trinity.logger.use_async', $config['use_async']);

        if (\array_key_exists('logger_ttl_provider', $config) && $config['logger_ttl_provider'] !== null) {
            $container->setAlias('trinity.logger.ttl_provider', $config['logger_ttl_provider']);
        } else {
            $container->setAlias('trinity.logger.ttl_provider', 'trinity.logger.default_ttl_provider');
        }

        $container->setAlias('trinity.logger.user_provider', $config['logger_user_provider']);
    }
}
