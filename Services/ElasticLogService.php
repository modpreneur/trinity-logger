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
    private $index = "Necktie";


    /**
     * ElasticLogService constructor.
     * @param $clientHost // IP:port, default port is 9200
     */
    public function __construct($clientHost)
    {
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
        $entityArray = $this->toArray($entity);
        return 0;
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

    private function toArray($entity){
        $methods = get_class_methods(get_class($entity));
        $array = [];
        foreach($methods as $method){
            if(strpos($method,'get')===0) {
                $key =substr($method, 3);
//                if(strpos($key,'DynamoArray')===0){
//                    //this would result in infinite recursion
//                    continue;
//                }

                //date Time problem
//                if(strpos($key,'ReceiveAt')===0){
//                    continue;
//                }

                $value = $entity->$method();
                if(!$value) continue;


                if(is_object($value)&& method_exists($value,'getId')){
                    $value=$value->getId();
                }

                if($value){

                    $array[$key] = $value;
                }
            }
        }
        return $array;
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