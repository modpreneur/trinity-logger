<?php

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ElasticsearchMappingProcessCommand
 * @package Necktie\AppBundle\Command
 */
class ExpireLogsCommand extends ContainerAwareCommand
{
    protected function configure(): void
    {
        $this->setName('trinity:logger:expire-logs')
            ->setDescription('Check setting for ttl of registered logs and deletes old ones.')
        ;
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \LogicException
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('trinity.logger.elastic_expire_log_service');
        return 0;
    }
}
