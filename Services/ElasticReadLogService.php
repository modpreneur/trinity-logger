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
use Elasticsearch\Common\Exceptions\BadRequest400Exception;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException as Exception404;
use Elasticsearch\Common\Exceptions\Missing404Exception as NFException;
use Doctrine\ORM\EntityManager;
use Trinity\Bundle\SearchBundle\NQL\NQLQuery;
//use Trinity\FrameworkBundle\Entity\EntityInterface;

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
    private $ESClient;

    /**
     * @var string index
     */
    private $index = 'necktie';

    /**
     * @var EntityManager entity manager
     */
    private $em;


    /**
     * ElasticReadLogService constructor.
     * @param string $clientHost // IP:port, default port is 9200
     * @param EntityManager $em
     * @param string $index
     */
    public function __construct($clientHost, $em, $index)
    {
        $this->em = $em;

        $this->index = $index ?: 'necktie';

        $params = explode(':', $clientHost);
        $portNumber = array_key_exists(1, $params) ? $params[1] : 9200;

        $this->ESClient = ClientBuilder::create()// Instantiate a new ClientBuilder
        ->setHosts([$params[0].':'.$portNumber])// Set the hosts
        ->build();
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
            'type' => $typeName,
            'id' => $id,
        ];
        try {
            $response = $this->ESClient->get($params);
        } catch (NFException $e) {
            throw new Exception404();
        }
        if (array_key_exists('_ttl', $response)) {
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
            return $this->ESClient->count($params)['count'];
        } catch (NFException $e) {
            return 0;
        }

    }


    /**
     * @param string $index
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

        if ($limit) {
            $params['body']['size'] = $limit;
        }

        $params['body']['sort'] = $order;

        if ($select) {
            $select[] = 'EntitiesToDecode';
            $select[] = 'SourceEntityClass';
            $params['body']['_source'] = $select;
        }

        if ($searchParams) {
            $params['body'] = $searchParams;
        }
        try {
            $this->ESClient->indices()->refresh(['index' => $this->index]);
            $result = $this->ESClient->search($params);
        } catch (NFException $e) {
            return [];
        } catch (BadRequest400Exception $e) {
            return [];
        }
        
        if (array_key_exists('aggregations', $result)) {
            return $result;
        }
        
        $entities = [];
        foreach ($result['hits']['hits'] as $arrayEntity) {
            if (array_key_exists('_ttl', $arrayEntity)) {
                $arrayEntity['_source']['ttl'] = intdiv($arrayEntity['_ttl'], 1000);
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
     * @return array of entities
     */
    public function getByQuery($nqLQuery)
    {

        /*
         * TODO: SQL where part
         */
        $entityName = $nqLQuery->getFrom()->getTables()[0]->getName();
        $typeName = array_key_exists($entityName, $this->translation) ? $this->translation[$entityName] : $entityName;

        $params = [
            'index' => $this->index,
            'type' => $typeName,
            'body' => [
            ]
        ];

//        "changedEntityClass" => "Logger TTL settings"
//
//        $query['bool']['must'][] = [
//            'match' => [
//                'changedEntityClass' => 'Logger TTL settings'
//            ]
//        ];
//        $params['body']['query'] = $query;

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


        $fields = [];
        foreach ($nqLQuery->getOrderBy()->getColumns() as $column) {
                //For grid use, is not stored in elasticSearch
            if ($column->getName() === '_id') {
                continue;
            }

            $attributeName = $column->getName();
            $fields[$attributeName] = ['order' => strtolower($column->getOrdering())];
            if ($attributeName === 'changedEntityClass') {
                $fields['changedEntityId'] = ['order' => strtolower($column->getOrdering())];
            }
        }

        if ($fields) {
            $params['body']['sort'] = [$fields];
        }

        $entities = [];
        try {
            $this->ESClient->indices()->refresh(['index' => $this->index]);

            $result = $this->ESClient->search($params);
        } catch (NFException $e) {
            return [];
        } catch (BadRequest400Exception $e) {
            return [];
        }

            //Hits contains hits. It is not typ-o...
        foreach ($result['hits']['hits'] as $arrayEntity) {
            if (array_key_exists('_ttl', $arrayEntity)) {
                $arrayEntity['_source']['ttl'] = intdiv($arrayEntity['_ttl'], 1000);
            }
            $entity = $this->decodeArrayFormat($arrayEntity['_source'], $arrayEntity['_id']);
            $entities[] = $entity;
        }
        return $entities;
    }

    
    /**
     * Takes entity and try to search EntityActionLog for matching nodes.
     * @param $entity
     * @return array
     */
    public function getStatusByEntity($entity)
    {
        $params = [
            'index' => $this->index,
            'type' => 'EntityActionLog',
            'body' => [
            ]
        ];

        $fields[] = 'changeSet';
        $fields[] = 'createdAt';
        $fields[] = 'actionType';
        $fields[] = 'user';
        $temp = [];
        $params['body']['_source'] = $fields;
        $params['body']['sort']['createdAt']['order'] = 'desc';

        $params['body']['query']['bool']['filter']['term']['changedEntityClass'] = get_class($entity);
        if (method_exists($entity, 'getId')) {
            $temp['term']['changedEntityId'] = $entity->getId();
            $params['body']['query']['bool']['filter'][] = $temp;
        }

        try {
            $this->ESClient->indices()->refresh(['index' => $this->index]);
            $result = $this->ESClient->search($params);
        } catch (NFException $e) {
            return [];
        } catch (BadRequest400Exception $e) {
            return [];
        }

        $entities = [];
        foreach ($result['hits']['hits'] as $arrayEntity) {
            $source = $arrayEntity['_source'];
            $source['_id'] = $arrayEntity['_id'];
            $source['user'] = $this->getEntity($source['user']);
            $changeSet = (array) json_decode($source['changeSet']);
            if (array_key_exists('info', $changeSet)) {
                $source['changeSet'] = $changeSet;
            } else {
                $source['changeSet'] = array_keys($changeSet);
            }
            $entities[] = $source;//[$entity['createdAt'], $entity['changeSet'], $entity['actionType'], ];
        }
        return $entities;

//        return $result['hits']['hits'];
    }


    /**
     * Transform document from ElasticSearch obtained as array into entity matching
     * original entity. The relations 1:1 are recreated.     *
     *
     * @param array $responseArray
     * @param string $id
     * @return $entity
     */
    public function decodeArrayFormat($responseArray, $id)
    {
        $entity = null;
        $relatedEntities = $responseArray['EntitiesToDecode'];
        unset($responseArray['EntitiesToDecode']);
        $entityClass = $responseArray['SourceEntityClass'];
        unset($responseArray['SourceEntityClass']);

        $entity = new $entityClass($id);

        foreach ($responseArray as $key => $value) {
            $setter = "set${key}";

            if (in_array($key, $relatedEntities, true)) {
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
        $subEntity = explode("\x00", $identification);
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
