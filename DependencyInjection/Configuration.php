<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\LoggerBundle\DependencyInjection;



use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;


/**
 * Class Configuration
 * @package Trinity\DependencyInjection
 */
class Configuration implements ConfigurationInterface
{

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
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

        ;

        return $treeBuilder;
    }
}