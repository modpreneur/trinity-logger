<?php
/**
 * Created by PhpStorm.
 * User: gabriel
 * Date: 26.2.16
 * Time: 18:04.
 */
namespace Trinity\Bundle\LoggerBundle\Services;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

/**
 * Class ElasticLogService.
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
     * @var string index
     */
    private $index = 'necktie';

    /**
     * @var string
     */
    protected $proxyFlag = 'Proxies\\__CG__\\';

    /**
     * ElasticLogService constructor.
     *
     * @param string $clientHost IP:port, default port is 9200
     * @param string $index name of DB
     * @param int $asyncQueLength
     *
     * @throws \RuntimeException
     */
    public function __construct(string $clientHost, $index, int $asyncQueLength = 50)
    {
        $this->index = $index ?: 'necktie';

        $params = explode(':', $clientHost);
        $port = $params[1] ?? 9200;

        $handlerParams = [
            'max_handles' => $asyncQueLength,
        ];

        $defaultHandler = ClientBuilder::defaultHandler($handlerParams);

        $this->ESClient = ClientBuilder::create()// Instantiate a new ClientBuilder
        ->setHosts(["${params[0]}:${port}"])// Set the hosts
        ->setHandler($defaultHandler)
        ->build();
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
     * If the ttl is not set default mapping in elastic is used (if exist).
     * The type(log) has to have enabled ttl in its mapping.
     *
     *
     * @param string $typeName //log name
     * @param $entity   //entity
     * @param int $ttl
     *
     * @return int //ID of the logged
     */
    public function writeIntoAsync(string $typeName, $entity, int $ttl = 0)
    {
        /*
         * Transform entity into array. Elastic can do it for you, but result is not in your hands.
         */

        $entityArray = $this->getElasticArray($entity);

        $params = [
            'index' => $this->index,
            'type' => $typeName,
            'body' => $entityArray,
            'client' => ['future' => 'lazy'],
        ];

        if ($ttl) {
            $params['ttl'] = "{$ttl}d";
        }

        $response = $this->ESClient->index($params);

        return $response['_id'];
    }

    /**
     * If the ttl is not set default mapping in elastic is used (if exist).
     * The type(log) has to have enabled ttl in its mapping.
     *
     * @param string $typeName //log name
     * @param $entity //entity
     * @param int $ttl // in days
     *
     * @return int //ID of the logged
     */
    public function writeInto(string $typeName, $entity, int $ttl = 0)
    {
        /*
         * Transform entity into array. Elastic can do it for you, but result is not in your hands.
         */

        $entityArray = $this->getElasticArray($entity);

        $params = [
            'index' => $this->index,
            'type' => $typeName,
            'body' => $entityArray,
        ];

        if ($ttl) {
            $params['ttl'] = "{$ttl}d";
        }

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
     * @param $entity
     *
     * @return array
     */
    private function getElasticArray($entity)
    {
        $entityArray['EntitiesToDecode'] = [];
        $entityArray['SourceEntityClass'] = get_class($entity);

        foreach ((array) $entity as $key => $value) {
            $keyParts = explode("\x00", $key);
            $key = array_pop($keyParts);

            //ttl is elastic thing, we only need place to store it in entity, not send it
            if ($key === 'ttl') {
                continue;
            }

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
                    $class = get_class($value);
                    if (strpos($class, $this->proxyFlag) === 0) {
                        $class = substr($class, strlen($this->proxyFlag));
                    }
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

        if (array_key_exists('id', $entityArray) && !$entityArray['id']) {
            unset($entityArray['id']);
        }

        return $entityArray;
    }

    /**
     * @param string $typeName
     * @param string $id
     * @param array  $types
     * @param array  $values
     * @param int    $ttl
     */
    public function update(string $typeName, string $id, array $types, array $values, int $ttl = 0)
    {
        $body = array_combine($types, $values);
        $params = [
            'index' => $this->index,
            'type' => $typeName,
            'id' => $id,
            'body' => ['doc' => $body],
        ];

        if ($ttl) {
            $params['ttl'] = "{$ttl}d";
        }

        $this->ESClient->update($params);
    }
}
