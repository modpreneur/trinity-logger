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

    /**
     * @var /Elasticsearch\Client;
     */
    private $ESClient;


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