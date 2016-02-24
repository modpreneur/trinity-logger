<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\LoggerBundle;


use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Trinity\Bundle\SettingsBundle\DependencyInjection\TrinityLoggerExtension;


/**
 * Class SettingsBundle
 * @package Trinity\Bundle\LoggerBundle
 */
class LoggerBundle extends Bundle
{

    public function build(ContainerBuilder $container){
        parent::build($container);
        $container->registerExtension(new TrinityLoggerExtension() );
    }
}
