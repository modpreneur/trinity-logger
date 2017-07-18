<?php
/**
 * This file is part of Trinity package.
 */

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     *
     * @throws \RuntimeException
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('trinity_logger');

        $rootNode
            ->children()
            ->scalarNode('use_async')->defaultValue(true)->end()
            ->scalarNode('elastic_host')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('managed_index')->cannotBeEmpty()->end()
            ->scalarNode('entities_path')->cannotBeEmpty()->end()
            ->scalarNode('async_queue_length')->defaultValue(50)->end()
            ->arrayNode('log_classes')->defaultValue([])
                ->prototype('scalar')
            ->end()
        ;

        //reference to a service - starting with '@'
        $rootNode->children()->scalarNode('logger_user_provider')->cannotBeEmpty()->isRequired()->beforeNormalization()
            //if the string starts with @, e.g. @service.name
            ->ifTrue(
                function ($v) {
                    return \is_string($v) && 0 === \strpos($v, '@');
                }
            )
            //return it's name without '@', e.g. service.name
            ->then(function ($v) {
                return \substr($v, 1);
            });

        //reference to a service - starting with '@'
        $rootNode->children()->scalarNode('logger_ttl_provider')->cannotBeEmpty()->beforeNormalization()
            //if the string starts with @, e.g. @service.name
            ->ifTrue(
                function ($v) {
                    return \is_string($v) && 0 === \strpos($v, '@');
                }
            )
            //return it's name without '@', e.g. service.name
            ->then(function ($v) {
                return \substr($v, 1);
            });

        return $treeBuilder;
    }
}
