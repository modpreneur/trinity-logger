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
     * Gabi-TODO: split into two entities? So there is no EM for sending???
     */

    /**
     * ElasticLogService constructor.
     * @param $clientHost // IP:port, default port is 9200
     */
    public function __construct($clientHost,$em)
    {
        $this->em = $em;

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
        $entityArray = $entity;

        $params = [
            'index' => $this->index,
            'type' => $typeName,
            'id'    => 'serialTest',
            'body' => $entityArray
        ];

        dump( $this->ESClient->index($params));


        return 0;
    }


    public function getById($typeName,$id){
        $params = [
            'index' => $this->index,
            'type' => $typeName,
            'id'    => $id,
        ];
        $response = $this->ESClient->get($params);

        return $this->decodeArrayFormat($response['_source']);

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


    /**
     * @param $entity
     * @return array
     */

    public function toArray($entity){
        $methods = get_class_methods(get_class($entity));
        $array = [];
        foreach($methods as $method){
            if(strpos($method,'get')===0) {
                $key =substr($method, 3);

                $value = $entity->$method();
                if(!$value) continue;


                if(is_object($value)&& method_exists($value,'getId')){
                    $value=$value->getId();
                }

                if($value){

                    $array[lcfirst($key)] = is_string($value)?"${value}" : $value;
                }
            }
        }
        return $array;
    }


    /**
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
            }

            $entity->$setter($value);
        }


        return $entity;
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