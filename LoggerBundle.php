<?php
/**
 * This file is part of Trinity package.
 */

namespace Trinity\Bundle\LoggerBundle;


use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Trinity\Bundle\LoggerBundle\DependencyInjection\TrinityLoggerExtension;


/**
 * Class LoggerBundle
 * @package Trinity\Bundle\LoggerBundle
 */
class LoggerBundle extends Bundle
{

    public function build(ContainerBuilder $container){
        parent::build($container);
    }


    /**
     * @return TrinityLoggerExtension
     */
    public function getContainerExtension()
    {
        return new TrinityLoggerExtension();
    }

}
