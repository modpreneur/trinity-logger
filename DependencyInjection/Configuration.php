<?php
/**
 * This file is part of Trinity package.
 */
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
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('trinity_logger');

        $rootNode
            ->children()
                ->arrayNode('dynamo_logs')
                    ->children()
                        ->scalarNode('dynamo_host')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('dynamo_port')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('aws_key')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('aws_secret')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('aws_region')->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()
            ->end()
            ->children()
                ->arrayNode('elastic_logs')
                    ->children()
                        ->scalarNode('elastic_host')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('managed_index')->cannotBeEmpty()->end()
                        ->scalarNode('entities_path')->cannotBeEmpty()->end()
        ;

        //reference to a service - starting with '@'
        $rootNode->children()->scalarNode('logger_user_provider')->cannotBeEmpty()->isRequired()->beforeNormalization()
            //if the string starts with @, e.g. @service.name
            ->ifTrue(
                function ($v) {
                    return is_string($v) && 0 === strpos($v, '@');
                }
            )
            //return it's name without '@', e.g. service.name
            ->then(function ($v) {
                return substr($v, 1);
            });

        //reference to a service - starting with '@'
        $rootNode->children()->scalarNode('logger_ttl_provider')->cannotBeEmpty()->isRequired()->beforeNormalization()
            //if the string starts with @, e.g. @service.name
            ->ifTrue(
                function ($v) {
                    return is_string($v) && 0 === strpos($v, '@');
                }
            )
            //return it's name without '@', e.g. service.name
            ->then(function ($v) {
                return substr($v, 1);
            });

        return $treeBuilder;
    }
}
