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


class ElasticReadLogService
{

    /*
     * In Elastic search is table called type, id is called id and index is something that may be
     * similar to database. There may be some miss writing as when i write table instead of type..
     */


    /**
     * @var entity to table translation
     */
    private $translation = [
        'Ipn' => 'IpnLog'
    ];


    /**
     * @var /Elasticsearch\Client;
     */
    private $ESClient;

    /**
     * @var index
     */
    private $index = "necktie";

    /**
     * @var em entity manager
     */
    private $em;


    /**
     * @var string entityPath
     *
     */
    private $entityPath;


    /**
     * ElasticReadLogService constructor.
     * @param $clientHost // IP:port, default port is 9200
     * @param $em
     * @param $index
     * @param $baseEntityPath
     */
    public function __construct($clientHost, $em, $index, $baseEntityPath)
    {
        $this->em = $em;

        $this->index = $index? $index : 'necktie';

        $this->entityPath = $baseEntityPath? $baseEntityPath : "Necktie\\AppBundle\\Entity";

        $params = explode(':',$clientHost);
        $port = isset($params[1]) ? $params[1] : 9200;

        $this->ESClient = ClientBuilder::create()           // Instantiate a new ClientBuilder
        ->setHosts(["${params[0]}:${port}"])          // Set the hosts
        ->build();
    }


    /**
     * @param $typeName
     * @param $id
     * @return $entity matching ID
     */
    public function getById($typeName,$id){
        $params = [
            'index' => $this->index,
            'type' => $typeName,
            'id'    => $id,
        ];
        $response = $this->ESClient->get($params);

        $entity =$this->decodeArrayFormat($response['_source']);

        if(!$entity->getId()){
            $entity->setId($response['_id']);
        }

        return $entity;
    }


    /**
     * Get number of documents with same type,
     * understand as table count
     *
     * @param $typeName
     * @return mixed
     */
    public function getCount($typeName){
        $params = [
            'index' => $this->index,
            'type' => $typeName,
        ];

        return $this->ESClient->count($params)['count'];
    }


    /**
     * Take $nqlQuery and turns it into elasticSearch parameters,
     * get matching documents from ES and transform them into array
     * of entities.
     *
     * @param $nqLQuery
     * @return array of entities
     */
    public function getByQuery($nqLQuery)
    {

            /*
             * TODO: SQL where part
             */
        $entityName = $nqLQuery->getFrom()->getTables()[0]->getName();
        $typeName = $this->translation[$entityName];

        $params = [
            'index' => $this->index,
            'type' => $typeName,

            'body' => [

            ]
        ];

            // \u0000Necktie\\AppBundle\\Entity\\Ipn\u0000invoice";
        $keyPrefix = $this->container->getParameter('trinity.search.namespace') . "\\" . $entityName;

        if ($offset = $nqLQuery->getOffset()) {
            $params['body']['from'] = $offset;
        }

        if ($nqLQuery->getLimit()) {
            $params['body']['size'] = $nqLQuery->getLimit();
        }

        $fields = [];
        foreach ($nqLQuery->getSelect()->getColumns() as $column) {
            if ($column->getName() === '_id') continue;
            if ($column->getName() === 'id') continue;
            $attributeName = ($column->getName());

            $fields[] = "\x00$keyPrefix\x00$attributeName";
        }


        if ($fields) {
            $fields[] = 'EntitiesToDecode';
            $params['body']['_source'] = $fields;
        }


        $fields = [];
        foreach ($nqLQuery->getOrderBy()->getColumns() as $column) {
                //For grid use, is not stored in elasticSearch
            if ($column->getName() === '_id') continue;

            $attributeName = ($column->getName());

                //created is object, for sort has to be attribute
            if($attributeName === 'created')
                $fields["\x00$keyPrefix\x00$attributeName.date"] = ['order' => $column->getOrdering()];
            else
                $fields["\x00$keyPrefix\x00$attributeName"] = ['order' => $column->getOrdering()];
        }

        if ($fields) {
            $params['body']['sort'] = [$fields];
        }

        $entities = [];
        $result = $this->ESClient->search($params);

            //Hits contains hits. It is not typ-o...
        foreach($result['hits']['hits'] as $arrayEntity){
            $entity = $this->decodeArrayFormat($arrayEntity['_source']);
            $entity->setId($arrayEntity['_id']);
            $entities[] = $entity;
        }
        return $entities;
    }


    /**
     * Transform document from ElasticSearch obtained as array into entity matching
     * original entity. The relations 1:1 are recreated.     *
     *
     * @var $responseArray
     * @return $entity
     */
    public function decodeArrayFormat( $responseArray){

        $entity=null;
        $relatedEntities =  $responseArray['EntitiesToDecode'];
        unset( $responseArray['EntitiesToDecode']);

        foreach( $responseArray as $key => $value){

            $attribute = explode("\x00",$key);

            if(!$entity){

                $entityPath= $attribute[1];
                $entity = new $entityPath();
            }

            $setter ="set${attribute[2]}" ;

            if(in_array($attribute[2],$relatedEntities)){
                $subEntity = explode("\x00",$value);
                $value = ($this->em->getRepository($subEntity[0])->find($subEntity[1]));
                if(!$value){
                    $value = new $subEntity[0]();
                }

            }
            $entity->$setter($value);
        }

        return $entity;
    }


}