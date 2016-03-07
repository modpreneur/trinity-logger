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


class ElasticLogService
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
     * @var container
     *
     */
    private $container;

    /**
     * Gabi-TODO: split into two entities? So there is no EM for sending???
     */

    /**
     * ElasticLogService constructor.
     * @param $clientHost // IP:port, default port is 9200
     */
    public function __construct($clientHost,$em,$container)
    {
        $this->em = $em;

        $this->container = $container;

        $params = explode(':',$clientHost);
        $port = isset($params[1]) ? $params[1] : 9200;

        $this->ESClient = ClientBuilder::create()           // Instantiate a new ClientBuilder
        ->setHosts(["${params[0]}:${port}"])          // Set the hosts
        ->build();
    }


    /**
     * @param $typeName //log name
     * @param $entity   //entity
     * @return int      //ID of the logged
     */

    public function writeInto($typeName, $entity){

        ($entityArray = $this->getElasticArray($entity));
        //($entityArray = $entity->getElasticArray());



        $params = [
            'index' => $this->index,
            'type' => $typeName,
//            'id'    => 'serialTest',
            'body' => $entityArray
        ];

        $response = $this->ESClient->index($params);

        return $response['_id'];
    }


//    /**
//     * @param $typeName
//     * @param $itemsOnPage
//     * @param int $pageNumber
//     * @param string $orderAttribute
//     * @param string $order direction
//     */
//
//    public function getGridPage(
//        $typeName, $itemsOnPage ,$pageNumber=1, $orderAttribute = 'created', $order = 'desc' ){
//
//        $params = [
//            'index' => $this->index,
//            'type' =>  $typeName,
//            'body' => [
//                'query' => [
//                    'match' => [
//                        'testField' => 'abc'
//                    ]
//                ]
//            ]
//        ];
//
//        $results = $client->search($params);
//
//    }

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



    public function getByQuery($nqLQuery)
    {
//        private $select; -
//        private $from;  -
//        private $where;
//        private $limit; -
//        private $offset; -
//        private $orderBy; -


        $entityName = $nqLQuery->getFrom()->getTables()[0]->getName();
        $typeName = $this->translation[$entityName];

        $params = [
            'index' => $this->index,
            'type' => $typeName,

            'body' => [

            ]
        ];

//
        // \u0000Necktie\\AppBundle\\Entity\\Ipn\u0000invoice";
        $keyPrefix = $this->container->getParameter('trinity.search.namespace') . "\\" . $entityName;

        if ($offset = $nqLQuery->getOffset()) {
            $params['body']['from'] = $offset;
        }

        if ($nqLQuery->getLimit()) {
            $params['body']['size'] = $nqLQuery->getLimit();
        }
//
////        private $select;
//
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
//
//        dump($nqLQuery->getWhere());
//
        $fields = [];
        foreach ($nqLQuery->getOrderBy()->getColumns() as $column) {
            if ($column->getName() === '_id') continue;
            $attributeName = ($column->getName());
            if($attributeName === 'created')
                $fields["\x00$keyPrefix\x00$attributeName.date"] = ['order' => $column->getOrdering()];
            else
                $fields["\x00$keyPrefix\x00$attributeName"] = ['order' => $column->getOrdering()];

        }
        if ($fields) {
            $params['body']['sort'] = [$fields];
        }

        dump(json_encode($params));
        dump($result = $this->ESClient->search($params));

        $entities = [];
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


    /**
     * Function transforms entity into array, the array stores type of entity
     * for recreation when obtain from elastic search and type and id of related
     * entities (FK) so they can be linked in decoding process.
     *
     * Gabi-TODO:Was not tested on M:N , N:1 or 1:N relations !!!
     *
     * @param $entity
     * @return array
     */
    private function getElasticArray($entity){
        $entityArray = (array) $entity;
        $entityArray['EntitiesToDecode'] = [];

        foreach($entityArray as $key => $value){
            if(is_object($value)){

                    //elastic can work with DateTime, not with ours entities
                if(get_class($value) === 'DateTime'){
                    continue;
                }
                if(get_class($value) === 'Symfony\Component\HttpFoundation\Request'){
                    $entityArray[$key] = $value->__toString();
                }


                if(method_exists($value,'getId')) {
                    dump($Id = $value->getId());
                    $class = (\Doctrine\Common\Util\ClassUtils::getClass($value));

                    $entityArray[$key] = "${class}\x00${Id}";

                    //explodeded are 0-null,1-entityClass,2-attributeName
                    $attributeName = explode("\x00", $key)[2];

                    array_push($entityArray['EntitiesToDecode'], $attributeName);
                }

            }
        }

       return $entityArray;
    }



    public function test(){
        $params = [
            'index' => 'test',
            'type' => 'test',
            'id' => 'my_id'
        ];

        $response = $this->ESClient->get($params);
        dump($response);
        dump('Super');
    }
}