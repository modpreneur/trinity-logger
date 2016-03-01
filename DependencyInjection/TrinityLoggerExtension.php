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
 * Class TrinityLoggerExtension
 * @package Trinity\Bundle\LoggerBundle\DependencyInjection
 */
class TrinityLoggerExtension extends Extension
{

    /**
     * Loads a specific configuration.
     *
     * @param array $configs An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (array_key_exists('dynamo_logs', $config) && isset($config['dynamo_logs'])) {
            $container->setParameter('trinity.logger.dynamo_logs', true);
            $container->setParameter('trinity.logger.dynamo_host', $config['dynamo_logs']['dynamo_host']);
            $container->setParameter('trinity.logger.dynamo_port', $config['dynamo_logs']['dynamo_port']);
            $container->setParameter('trinity.logger.aws_key', $config['dynamo_logs']['aws_key']);
            $container->setParameter('trinity.logger.aws_secret', $config['dynamo_logs']['aws_secret']);
            $container->setParameter('trinity.logger.aws_region', $config['dynamo_logs']['aws_region']);
        }else{
            $container->setParameter('trinity.logger.dynamo_logs', false);
            $container->setParameter('trinity.logger.dynamo_host', null);
            $container->setParameter('trinity.logger.dynamo_port', null);
            $container->setParameter('trinity.logger.aws_key', null);
            $container->setParameter('trinity.logger.aws_secret', null);
            $container->setParameter('trinity.logger.aws_region', null);
        }

        if (array_key_exists('elastic_logs', $config) && isset($config['elastic_logs'])) {
            $container->setParameter('trinity.logger.elastic_logs', true);
            $container->setParameter('trinity.logger.elastic_host', $config['elastic_logs']['elastic_host']);
            $container->setParameter('trinity.logger.entity_manager', $config['elastic_logs']['entity_manager']);

        }else{
            $container->setParameter('trinity.logger.elastic_logs', false);
            $container->setParameter('trinity.logger.elastic_host', null);
            $container->setParameter('trinity.logger.entity_manager', null);

        }

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');
    }
}