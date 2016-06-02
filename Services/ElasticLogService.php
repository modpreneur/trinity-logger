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

/**
 * Class ElasticLogService
 * @package Trinity\Bundle\LoggerBundle\Services
 */
class ElasticLogService
{

    /*
     * In Elastic search is table called type, id is called id and index is something that may be
     * similar to database. There may be some miss writing as when i write table instead of type..
     */

    /**
     * @var Client;
     */
    private $ESClient;
    /**
     * @var index
     */
    private $index = 'necktie';

    /**
     * ElasticLogService constructor.
     *
     * @param $clientHost // IP:port, default port is 9200
     * @param $index // name of DB
     *
     * @throws \RuntimeException
     */
    public function __construct($clientHost, $index)
    {
        $this->index = $index ?: 'necktie';

        $params = explode(':', $clientHost);
        $port = $params[1] ?? 9200;

        //Gabi-TODO: in settings?
        $handlerParams = [
            'max_handles' => 50
        ];

        $defaultHandler = ClientBuilder::defaultHandler($handlerParams);

        $this->ESClient = ClientBuilder::create()// Instantiate a new ClientBuilder
        ->setHosts(["${params[0]}:${port}"])// Set the hosts
        ->setHandler($defaultHandler)
        ->build();
    }


    /**
     * @param $index
     * @return $this
     */
    public function setIndex($index)
    {
        $this->index = $index;
        return $this;
    }

    /**
     * @param $typeName //log name
     * @param $entity //entity
     * @return int      //ID of the logged
     */
    public function writeIntoAsync($typeName, $entity)
    {
        /*
         * Transform entity into array. Elastic can do it for you, but result is not in your hands.
         */

        $entityArray = $this->getElasticArray($entity);

        $params = [
            'index' => $this->index,
            'type' => $typeName,
            'body' => $entityArray,
            'client' => ['future' => 'lazy']
        ];
        $response = $this->ESClient->index($params);


//        $this->ESClient->indices()->refresh(['index' => $this->index]);

        return $response['_id'];
    }

    /**
     * @param string $typeName //log name
     * @param object $entity //entity
     * @return int      //ID of the logged
     */
    public function writeInto(string $typeName, $entity)
    {
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

        $this->ESClient->indices()->refresh(['index' => $this->index]);

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
    private function getElasticArray($entity)
    {
        $entityArray['EntitiesToDecode'] = [];
        $entityArray['SourceEntityClass'] = get_class($entity);

        foreach ((array)$entity as $key => $value) {
            $keyParts = explode("\x00", $key);
            $key = array_pop($keyParts);
            /*
             * Elastic can manage just few objects when passed. Here we preprocess them
             * so elastic doesn't have problems
             */
            if (is_object($value)) {
                //elastic can work with DateTime, not with ours entities
                if (get_class($value) === 'DateTime') {
                    continue;
                }
                if (get_class($value) === 'Symfony\Component\HttpFoundation\Request') {
                    $entityArray[$key] = (string) $value;
                }

                if (method_exists($value, 'getId')) {
                    // @todo @GabrielBordovsky if you need this you have to add Doctrine to composer
                    $class = \Doctrine\Common\Util\ClassUtils::getClass($value);

                    $id = $value->getId();
                    if ($id) {
                        $entityArray[$key] = "$class\x00$id";
                        $entityArray['EntitiesToDecode'][] = $key;
                    } else {
                        unset($entityArray[$key]);
                    }
                }
            } else {
                $entityArray[$key] = $value;
            }
        }

        return $entityArray;
    }


    /**
     * @param string $typeName
     * @param string $id
     * @param array $types
     * @param array $values
     */
    public function update(string $typeName, string $id, array $types, array $values)
    {
        $body = array_combine($types, $values);
        $params = [
            'index' => $this->index,
            'type'  => $typeName,
            'id'    => $id,
            'body' => ['doc' => $body]
        ];
        $this->ESClient->update($params);
    }
}
