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
     * ElasticLogService constructor.
     * @param $clientHost // IP:port, default port is 9200
     * @param $index // name of DB
     */
    public function __construct($clientHost,$index)
    {
        $this->index = $index? $index : 'necktie';

        $params = explode(':',$clientHost);
        $port = isset($params[1]) ? $params[1] : 9200;

        $this->ESClient = ClientBuilder::create()           // Instantiate a new ClientBuilder
        ->setHosts(["${params[0]}:${port}"])          // Set the hosts
        ->build();
    }


    /**
     * @param $index
     * @return $this
     */
    public function setIndex($index){
        $this->index = $index;
        return $this;
    }

    /**
     * @param $typeName //log name
     * @param $entity   //entity
     * @return int      //ID of the logged
     */
    public function writeInto($typeName, $entity){
            /*
             * Transform entity into array. Elastic can do it for you, but result is not in your hands.
             */

        $entityArray = $this->getElasticArray($entity);

        $params = [
            'index' => $this->index,
            'type' => $typeName,
            'body' => $entityArray
        ];
        $response = $this->ESClient->index($params);

        return $response['_id'];
    }



    /**
     * Function transforms entity into array, the array stores type of entity
     * for recreation when obtain from elastic search and type and id of related
     * entities (FK) so they can be linked in decoding process.
     *
     * Gabi-TODO:Was not tested on M:N , N:1 or 1:N relations !!!
     * Gabi-TODO-2: it is as simple as it could be. N part is usually mapped, on elastic site should not FK
     *
     *
     *
     * @param $entity
     * @return array
     */
    private function getElasticArray($entity){
        $entityArray = (array) $entity;
        $entityArray['EntitiesToDecode'] = [];

        foreach($entityArray as $key => $value){
            /*
             * Elastic can manage just few objects when passed. Here we preprocess them
             * so elastic doesn't have problems
             */
            if(is_object($value)){

                    //elastic can work with DateTime, not with ours entities
                if(get_class($value) === 'DateTime'){
                    continue;
                }
                if(get_class($value) === 'Symfony\Component\HttpFoundation\Request'){
                    $entityArray[$key] = $value->__toString();
                }

                if(method_exists($value,'getId')) {
                    $class = (\Doctrine\Common\Util\ClassUtils::getClass($value));

                    $Id = $value->getId();
                    if($Id) {
                        $entityArray[$key] = "${class}\x00${Id}";

                        //explodeded are 0-null,1-entityClass,2-attributeName
                        $attributeName = explode("\x00", $key)[2];

                        array_push($entityArray['EntitiesToDecode'], $attributeName);
                    }else{
                        unset($entityArray[$key]);
                    }
                }

            }
        }

       return $entityArray;
    }

}