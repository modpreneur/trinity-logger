<?php
/**
 * Created by PhpStorm.
 * User: gabriel
 * Date: 26.2.16
 * Time: 18:04
 */

namespace Trinity\Bundle\LoggerBundle\Services;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Trinity\Component\Utils\Hydrators\ColumnHydrator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException as Exception404;
use Elasticsearch\Common\Exceptions\Missing404Exception as NFException;
use Doctrine\ORM\EntityManager;
use Trinity\Bundle\SearchBundle\NQL\NQLQuery;
use Trinity\Bundle\SearchBundle\NQL\WherePart;

/**
 * Class ElasticReadLogService
 * @package Trinity\Bundle\LoggerBundle\Services
 */
class ElasticReadLogService
{

    /*
     * In Elastic search is table called type, id is called id and index is something that may be
     * similar to database. There may be some miss writing as when i write table instead of type..
     */
    protected $proxyFlag = 'Proxies\\__CG__\\';

    /**
     * @var array entity to table translation
     */
    private $translation = [
        'Ipn' => 'IpnLog',
        'Notification' => 'NotificationLog'
    ];

    /**
     * @var Client;
     */
    private $eSClient;

    /**
     * @var string index
     */
    private $index;

    /**
     * @var EntityManager entity manager
     */
    private $em;

    /**
     * @var array for extended search
     */
    private $query;
    /**
     * ElasticReadLogService constructor.
     * @param string $clientHost // IP:port, default port is 9200
     * @param EntityManager|null $em
     * @param string $index
     * @param ClientBuilder|null $clientBuilder
     */
    public function __construct(string $clientHost, ?EntityManager $em = null, string $index, ClientBuilder $clientBuilder = null)
    {
        $this->em = $em;
        $this->index = $index ?: 'necktie';

        $params = \explode(':', $clientHost);
        $portNumber = \array_key_exists(1, $params) ? $params[1] : 9200;

        if($clientBuilder){
            $this->eSClient = $clientBuilder->setHosts([$params[0].':'.$portNumber])// Set the hosts
            ->build();
        } else {
            $this->eSClient = ClientBuilder::create()// Instantiate a new ClientBuilder
            ->setHosts([$params[0].':'.$portNumber])// Set the hosts
            ->build();
        }
    }


    /**
     * @param string $typeName
     * @param string $id
     * @return $entity matching ID
     * @throws Exception404 404 exceptions when not found
     */
    public function getById(string $typeName, string $id)
    {
        $params = [
            'index' => $this->index,
            'type'  => $typeName,
            'id'    => $id,
        ];
        try {
            $response = $this->eSClient->get($params);
        } catch (NFException $e) {
            throw new Exception404();
        }

        if (\array_key_exists('_ttl', $response)) {
            $response['_source']['ttl'] = intdiv($response['_ttl'], 1000);
        }
        return $this->decodeArrayFormat($response['_source'], $response['_id']);
    }


    /**
     * Get number of documents with same type,
     * understand as table count
     *
     * @param string $typeName
     * @param array $query
     * @return mixed
     */
    public function getCount(string $typeName, array $query = [])
    {
        $params = [
            'index' => $this->index,
            'type' => $typeName,
        ];

        if ($query) {
            $params['body']['query'] = $query;
        }

        try {
            return $this->eSClient->count($params)['count'];
        } catch (NFException $e) {
            return 0;
        }
    }


    /**
     * @param string $index
     *
     * @return $this
     */
    public function setIndex($index)
    {
        $this->index = $index;

        return $this;
    }


    /**
     * WARNING: In case aggregation was selected result is returned as given by elastic-search.
     *     Parsing, such as transforming entities back from array, has to be made on result.
     *
     * @param string $typeName
     * @param array $searchParams
     * @param int $limit
     * @param array $select
     * @param array $order
     *
     * @return array
     */
    public function getMatchingEntities(
        string $typeName,
        array $searchParams = [],
        int $limit = 0,
        array $select = [],
        array $order = [['createdAt' => ['order' => 'desc']]]
    ) {
        $params = [
            'index' => $this->index,
            'type' => $typeName,
            'body' => [
            ]
        ];

        if ($searchParams) {
            $params['body'] = $searchParams;
        }

        if ($limit) {
            $params['body']['size'] = $limit;
        }

        $params['body']['sort'] = $order;

        if ($select) {
            $select[] = 'EntitiesToDecode';
            $select[] = 'SourceEntityClass';
            $params['body']['_source'] = $select;
        }

        try {
            $this->eSClient->indices()->refresh(['index' => $this->index]);
            $result = $this->eSClient->search($params);
        } catch (NFException $e) {
            return [];
        }

        if (\array_key_exists('aggregations', $result)) {
            return $result;
        }
        
        $entities = [];
        foreach ($result['hits']['hits'] as $arrayEntity) {
            if (\array_key_exists('_ttl', $arrayEntity)) {
                $arrayEntity['_source']['ttl'] = \intdiv($arrayEntity['_ttl'], 1000);
            }
            $entity = $this->decodeArrayFormat($arrayEntity['_source'], $arrayEntity['_id']);
            $entities[] = $entity;
        }

        return $entities;
    }


    /**
     * Take $nqlQuery and turns it into elasticSearch parameters,
     * get matching documents from ES and transform them into array
     * of entities.
     *
     * TODO: use function above to reduce complexity.
     *
     * @param NQLQuery $nqLQuery
     * @param string $globalSearch
     *
     * @param array $configuration
     * @return array
     * @throws \RuntimeException
     */
    public function getByQuery($nqLQuery, string $globalSearch, array $configuration = [])
    {
            /*
             * No joins accepted, so we work only with one 'table'
             */
        $entityName = $nqLQuery->getFrom()->getTables()[0]->getName();
        $typeName = \array_key_exists($entityName, $this->translation) ? $this->translation[$entityName] : $entityName;

        $params = [
            'index' => $this->index,
            'type' => $typeName,
            'body' => [
            ]
        ];

        if ($globalSearch) {
            $params['body']['query']['multi_match'] = [
                'query' => $globalSearch,
                'type' => 'phrase_prefix',
                'fields' => '*', //all fields
                'lenient' => true // ignore fields with bad type
            ];
        }
        $offset = $nqLQuery->getOffset();
        if ($offset) {
            $params['body']['from'] = $offset;
        }

        if ($nqLQuery->getLimit()) {
            $params['body']['size'] = $nqLQuery->getLimit();
        }

        $fields = [];
        foreach ($nqLQuery->getSelect()->getColumns() as $column) {
            $attributeName = $column->getName();

            if ($attributeName === '_id' || $attributeName === 'id') {
                continue;
            }

            $fields[] = "$attributeName";
        }

        if ($fields) {
            $fields[] = 'EntitiesToDecode';
            $fields[] = 'SourceEntityClass';
            $params['body']['_source'] = $fields;
        }


        if ($nqLQuery->getWhere()->getConditions()) {
            if (!$configuration) {
                throw new \RuntimeException('Configuration for searching on '. $entityName. ' was not found.');
            }
            $types = $this->getColumnTypes($configuration['columns']);
            $keyBase = 'must';
            $elem = null;

            /*
             * If there is only one element in conditions we store him into $elem
             * and process him after loop.
             *
             * If there is more,  it is expected (and currently only working way)
             * that each even element is operator AND or OR. The OR/AND tells which keyword
             * is used in filter, of element before and after. So we may process condition
             * after we have read the operator.
             */
            foreach ($nqLQuery->getWhere()->getConditions() as $condition) {
                if ($condition->type !== 'condition') {
                    $keyBase = ($condition->value === 'OR') ? 'should' : 'must';
                    $this->translateCondition($elem, $types, $keyBase);
                } else {
                    $elem = $condition;
                }
            }
            $this->translateCondition($elem, $types, $keyBase);

            if (\array_key_exists('error', $this->query)) {
                return [[], 0, 0];
            }

            $params['body']['filter'] = $this->query;
        }
        $fields = [];
        foreach ($nqLQuery->getOrderBy()->getColumns() as $column) {
                //For grid use, is not stored in elasticSearch
            if ($column->getName() === '_id') {
                continue;
            }

            $attributeName = $column->getName();
            $fields[$attributeName] = ['order' => \strtolower($column->getOrdering())];
            if ($attributeName === 'changedEntityClass') {
                $fields['changedEntityId'] = ['order' => \strtolower($column->getOrdering())];
            }
        }

        if ($fields) {
            $params['body']['sort'] = [$fields];
        }

        $entities = [];
        $score = [];
        try {
            $this->eSClient->indices()->refresh(['index' => $this->index]);

            $result = $this->eSClient->search($params);
        } catch (NFException $e) {
            return [[], 0, 0];
        }

        $totalScore = $result['hits']['max_score'];
            //Hits contains hits. It is not typ-o...
        foreach ($result['hits']['hits'] as $arrayEntity) {
            if (\array_key_exists('_ttl', $arrayEntity)) {
                $arrayEntity['_source']['ttl'] = \intdiv($arrayEntity['_ttl'], 1000);
            }
            $entity = $this->decodeArrayFormat($arrayEntity['_source'], $arrayEntity['_id']);
            $entities[] = $entity;
            if ($totalScore) {
                $score[] = $arrayEntity['_score'] / $totalScore;
            }
        }
        return [$entities, $result['hits']['total'], $score];
    }


    /**
     * @param WherePart $condition
     * @param array $types
     * @param string $key
     * @throws \RuntimeException
     */
    private function translateCondition(WherePart $condition, array $types, string $key)
    {
        $term = 'term';

        if ($types[$condition->key->getName()] === 'string') {
            $raw = '.raw';
        }

        if (\is_array($types[$condition->key->getName()]) &&
            \array_key_exists('entity', $types[$condition->key->getName()])
        ) {
            //@todo em is empty?
            $this->em->getConfiguration()->addCustomHydrationMode('COLUMN_HYDRATOR', ColumnHydrator::class);
            $values = $this->em->getRepository($types[$condition->key->getName()]['entity'])
                ->createQueryBuilder('b')
                ->select('b.id')
                ->where("LOWER(b.{$types[$condition->key->getName()]['column']}) LIKE LOWER(:value)")
                ->setParameter('value', $condition->value)
                ->getQuery()
                ->getResult('COLUMN_HYDRATOR');
            $name = $condition->key->getName() . '.raw';
            $key = 'should';

            if (!$values) {
                if ($condition->value !== '') {
                    $this->query = ['error' => 'No matching values.'];
                } else {
                    $this->query['bool'][$key][] = [$term => [$name => '']];
                }
            } else {
                foreach ($values as $id) {
                    $value = "{$types[$condition->key->getName()]['entity']}\x00$id";
                    $this->query['bool'][$key][] = [$term => [$name => $value]];
                }
            }
        } else {
            $name = $condition->key->getName() . ($raw ?? '');
            switch ($condition->operator) {
                case '=':
                    if ($condition->value === '<NULL>') {
                        $key = 'should';
                        $this->query['bool'][$key][] = [$term => [$name => '']];
                        // we don't want to continue and change value
                        return;
                    }
                    break;
                case '!=':
                    $key .= '_not';
                    break;
                case 'LIKE':
                    $value = $condition->value;
                    $term = 'wildcard';

                    if ($value[0] === '%') {
                        $value[0] = '*';
                    }

                    if ($value[\strlen($value) - 1] === '%') {
                        $value[\strlen($value) - 1] = '*';
                    }

                    break;
                case '>':
                    $term = 'range';
                    $value = ['gt' => $condition->value];
                    break;
                case '<':
                    $term = 'range';
                    $value = ['lt' => $condition->value];
                    break;
                case '>=':
                    $term = 'range';
                    $value = ['gte' => $condition->value];
                    break;
                case '<=':
                    $term = 'range';
                    $value = ['lte' => $condition->value];
                    break;
                default:
                    throw new \RuntimeException("Unexpected operator: {$condition->operator}");
            }

            $value = $value ?? (\is_int($condition->value)? (int) $condition->value: $condition->value);
            $this->query['bool'][$key][] = [$term => [$name => $value]];
        }
    }


    /*
     * @TODO: @GabrielBordovsky move to Necktie !!
     */
    /**
     * Takes entity and try to search EntityActionLog for matching nodes.
     * @param $entity
     * @return array
     */
    public function getStatusByEntity($entity): array
    {
        $params = [
            'index' => $this->index,
            'type'  => 'EntityActionLog',
            'body'  => []
        ];

        $fields[] = 'changeSet';
        $fields[] = 'createdAt';
        $fields[] = 'actionType';
        $fields[] = 'user';
        $fields[] = 'system';
        $temp = [];
        $params['body']['_source'] = $fields;
        $params['body']['sort']['createdAt']['order'] = 'desc';

        $class = \get_class($entity);
        if (\strpos($class, $this->proxyFlag) === 0) {
            $class = \substr($class, \strlen($this->proxyFlag));
        }
        $params['body']['query']['bool']['filter'][0]['term']['changedEntityClass.raw'] = $class;

        if (\method_exists($entity, 'getId')) {
            $temp['term']['changedEntityId'] = $entity->getId();
            $params['body']['query']['bool']['filter'][] = $temp;
        }

        try {
            $this->eSClient->indices()->refresh(['index' => $this->index]);
            $result = $this->eSClient->search($params);
        } catch (NFException $e) {
            return [];
        }

        $entities = [];
        foreach ($result['hits']['hits'] as $arrayEntity) {
            $source = $arrayEntity['_source'];
            $source['_id'] = $arrayEntity['_id'];
            $source['user'] = $source['user'] ? $this->getEntity($source['user']) : null;
            $changeSet = (array) \json_decode($source['changeSet']);
            if (\array_key_exists('info', $changeSet)) {
                $source['changeSet'] = $changeSet;
            } else {
                $source['changeSet'] = \array_keys($changeSet);
            }
            $entities[] = $source;//[$entity['createdAt'], $entity['changeSet'], $entity['actionType'], ];
        }
        return $entities;

//        return $result['hits']['hits'];
    }


    /**
     * @param array $columns
     * @return array
     */
    private function getColumnTypes(array $columns): array
    {
        $types = [];
        foreach ($columns as $column) {
            $types[$column['name']] = $column['elasticType']??$column['type']??null;
            if ($types[$column['name']] === 'enum') {
                $types[$column['name']] = 'string';
            }
        }

        return $types;
    }


    /**
     * Transform document from ElasticSearch obtained as array into entity matching
     * original entity. The relations 1:1 are recreated.     *
     *
     * @param array $responseArray
     * @param string $id
     * @return $entity
     */
    public function decodeArrayFormat($responseArray, $id = '')
    {
        $entity = null;
        $relatedEntities = $responseArray['EntitiesToDecode'];
        unset($responseArray['EntitiesToDecode']);
        $entityClass = $responseArray['SourceEntityClass'];
        unset($responseArray['SourceEntityClass']);

        $entity = new $entityClass($id);

        foreach ($responseArray as $key => $value) {
            $setter = "set${key}";

            if (\in_array($key, $relatedEntities, true)) {
                $value = $this->getEntity($value);
            }

            if ($value) {
                $entity->$setter($value);
            }
        }

        return $entity;
    }


    /**
     * Transform reference into doctrine entity
     * @param string $identification
     * @return mixed $value
     */
    private function getEntity(string $identification)
    {
        if (null === $this->em) {
            return null;
        }

        $subEntity = \explode("\x00", $identification);
        $value = null;

        if ($subEntity[1]) {
            $value = $this->em->getRepository($subEntity[0])->find($subEntity[1]);
        }

        if (!$value) {
            $value = new $subEntity[0]();
        }
        
        return $value;
    }
}
