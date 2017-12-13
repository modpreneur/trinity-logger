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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Trinity\Bundle\LoggerBundle\Annotation\EntityMapper;

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

    private const PATH = __DIR__ . '/../Resources/MappingData/base';

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
            );
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
        $test = false;

        try {
            $this->createClient();

            $index = '_all';
            if ($input->getOption('test-only') || $input->getOption('env') === 'test') {
                $index = 'test*';
                $test = true;
            }

            $client = new Client();

            $status = $this->getStatus();

            if (!$status || $input->getOption('clean-all')) {
                $output->write('Delete in process....');
                $client->request('DELETE', $this->elasticHost . "/$index");
                $output->writeln('Deleted');

                $this->processMapper($input, $output, $test);
            }

            if (!$status) {
                $output->writeln('ElasticSearch not initialized');
                $fileContent = \file_get_contents(self::PATH);
                if (!$fileContent) {
                    throw new \UnexpectedValueException(
                        'Migration file not found on ' . self::PATH
                    );
                }

                $eolPos = 0;

                $equalPos = \strpos($fileContent, '=', $eolPos) + 2;

                $quotePos = \strrpos($fileContent, "'");

                $data = \substr($fileContent, $equalPos, $quotePos - $equalPos);
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

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param bool $test
     *
     * @throws \ReflectionException
     * @throws \InvalidArgumentException
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws \LogicException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    private function processMapper(InputInterface $input, OutputInterface $output, bool $test = false): void
    {
        $logService = $this->getContainer()
            ->get('trinity.logger.elastic_log_service');

        $annotationReader = $this
            ->getContainer()
            ->get('annotation_reader');

        $path = $this
            ->getContainer()
            ->getParameter('kernel.project_dir');

        $finder = new Finder();
        $finder->files()->in($path . '/vendor')->name('*.php');

        $ref = new \ReflectionClass(EntityMapper::class);
        $entityMapperName = $ref->getShortName();

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $classes = $this
                ->fileGetPhpClassesWithEntityMapperAnnotation($file->getPathname(), $entityMapperName);

            /** @var array $classNames */
            foreach ($classes as $namespace => $classNames) {
                foreach ($classNames as $className) {
                    $class = $namespace . '\\' . $className;
                    $annotation = $annotationReader
                        ->getClassAnnotation(
                            new \ReflectionClass($class),
                            EntityMapper::class
                        );

                    if (!$annotation) {
                        continue;
                    }

                    $logService->putMapping($className, $annotation->getDisabled(), $test);
                }
            }
        }
    }


    /**
     * Projde třídy a najde všechny, které mají EntityMapper.
     *
     *
     * @param string $filePath
     * @param string $entityMapperName
     * @return array
     */
    private function fileGetPhpClassesWithEntityMapperAnnotation(string $filePath, string $entityMapperName): array
    {
        $phpCode = \file_get_contents($filePath);
        $classes = [];
        $namespace = 0;
        $tokens = \token_get_all($phpCode);
        $count = \count($tokens);

        $dlm = false;

        $hasAnnotation = false;
        for ($i = 2; $i < $count; $i++) {
            if (($dlm && $tokens[$i - 1][0] === \T_NS_SEPARATOR && $tokens[$i][0] === \T_STRING)
                || (
                    isset($tokens[$i - 2][1])
                    && ($tokens[$i - 2][1] === 'phpnamespace' || $tokens[$i - 2][1] === 'namespace')
                )
            ) {
                if (!$dlm) {
                    $namespace = 0;
                }

                if (isset($tokens[$i][1])) {
                    $namespace = $namespace ? $namespace . "\\" . $tokens[$i][1] : $tokens[$i][1];
                    $dlm = true;
                }
            } elseif ($dlm && ($tokens[$i][0] !== \T_NS_SEPARATOR) && ($tokens[$i][0] !== \T_STRING)) {
                $dlm = false;
            }

            if ($tokens[$i - 1][0] === \T_WHITESPACE
                && $tokens[$i][0] === \T_STRING
                && ($tokens[$i - 2][0] === \T_CLASS || (isset($tokens[$i - 2][1]) && $tokens[$i - 2][1] === 'phpclass'))
            ) {
                $className = $tokens[$i][1];

                if (!isset($classes[$namespace])) {
                    $classes[$namespace] = [];
                }

                $classes[$namespace][] = $className;
            }

            if (isset($tokens[$i - 2][1]) && ($tokens[$i - 2][1] === $entityMapperName)) {
                $hasAnnotation = true;
            }
        }

        if (!$hasAnnotation) {
            $classes = [];
        }

        return $classes;
    }
}
