<?php

declare(strict_types=1);

namespace Trinity\Bundle\LoggerBundle\Command;

use Elasticsearch\Client as ESClient;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Class ElasticsearchMappingProcessCommand
 * @package Necktie\AppBundle\Command
 */
class ElasticMappingCommand extends ContainerAwareCommand
{
    protected static $params = [
        'index' => 'necktie_migration_status',
        'type' => 'migration_status',
    ];

    const PATH = __DIR__.'/../Resources/MappingData/base';

    /** @var string */
    protected $elasticHost;

    /** @var ESClient */
    protected $eSClient;

    /**
     * {@inheritDoc}
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure(): void
    {
        $this->setName('trinity:logger:initialize-elastic')
            ->setDescription('Check status and puts elastic mapping.')
            ->addOption(
                'clean-all',
                null,
                InputOption::VALUE_NONE,
                'Deletes all databases and starts clean.'
            )
            ->addOption(
                'test-only',
                null,
                InputOption::VALUE_NONE,
                'Changes are applied only on test indexes.'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \LengthException
     * @throws \RuntimeException
     * @throws \LogicException
     * @throws InvalidArgumentException
     * @throws \UnexpectedValueException
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        try {
            $this->createClient();

            $index = '_all';
            if ($input->getOption('test-only')) {
                $index = 'test*';
            }

            $client = new Client();

            $status = $this->getStatus();

            if (!$status || $input->getOption('clean-all')) {
                $output->write('Delete in process....');
                $client->request('DELETE', $this->elasticHost . "/$index");
                $output->writeln('Deleted');
            }

            if (!$status) {
                $output->writeln('ElasticSearch not initialized');
                $fileContent = \file_get_contents(self::PATH);
                if (!$fileContent) {
                    throw new \UnexpectedValueException(
                        'Migration file not found on '.self::PATH
                    );
                }

                $eolPos = 0;

                $equalPos = \strpos($fileContent, '=', $eolPos)+2;

                $quotePos = \strrpos($fileContent, "'");

                $data = \substr($fileContent, $equalPos, $quotePos-$equalPos);
                $output->write('Putting template......');
                $client->request('PUT', $this->elasticHost . '/_template/trinity-logger', [
                    'headers' => ['Content-Type' => 'application/json'],
                    'body' => $data
                ]);
                $output->writeln('Done');
                $this->setStatus();
            }
            return 0;
        } catch (\Exception $ex) {
                //Do not throw exceptions, just notify
            $output->write('ERROR');
            $output->writeln($ex->getMessage());
            return 1;
        }
    }


    /**
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws \LogicException
     */
    private function createClient(): void
    {
        $this->elasticHost = $this->getContainer()->getParameter('elasticsearch_host');
        $params = \parse_url($this->elasticHost);

        if (!\array_key_exists('port', $params)) {
            if (\array_key_exists('scheme', $params) && $params['scheme'] === 'https') {
                $this->elasticHost .= ':443';
            } else {
                $this->elasticHost .= ':9200';
            }
        }

        $this->eSClient = ClientBuilder::create()->setHosts([$this->elasticHost])->build();
    }

    /**
     *
     */
    private function setStatus(): void
    {
        $params = self::$params;
        $params['body'] = ['init' => 'done'];
        $this->eSClient->index($params);
    }


    /**
     * @return array
     */
    private function getStatus(): array
    {
        try {
            $esResponse = $this->eSClient->search(self::$params);
        } catch (Missing404Exception $exception) {
            $esResponse = [];
        }
        return $esResponse;
    }
}
