<?php
/**
 * Created by PhpStorm.
 * User: gabriel
 * Date: 12.2.16
 * Time: 10:59
 */
namespace Trinity\Bundle\LoggerBundle\Services;

use Aws\Sdk;
use Aws\DynamoDb\DynamoDbClient;
use Trinity\LoggerBundle\Entity\BaseDynamoLog;

/**
 * //Move this somewhere to necktie when done??
 *
 *
 * @method string writeIntoExceptionLog(array $msg = [])
 * @method string writeIntoIPNLog(array $msg = [])
 * @method string writeIntoAccessLog(array $msg = [])
 * @method string writeIntoUserActionLog(array $msg = [])
 * @method string writeIntoNotificationLog(array $msg = [])
 * @method string writeIntoNewsletterLog(array $msg = [])
 *
 * @method int itemCountExceptionLog(array $msg =[])
 * @method int itemCountIPNLog(array $msg =[])
 * @method int itemCountAccessLog(array $msg =[])
 * @method int itemCountUserLog(array $msg =[])
 * @method int itemCountNotificationLog(array $msg =[])
 * @method int itemCountNewsletterLog(array $msg =[])
 *
 *
    */
class DynamoDBService
{
    /**
     * @var \Aws\DynamoDb\DynamoDbClient
     */
    private $connection;

    private $inPartitionCount = 15;

    /**
     * @var array
     * Each log is represented by dynamoDB table that has MicroTime as main key.
     * For results managing class expanding BaseDynamoLog should exist for each
     */

    private $logsList = ['ExceptionLog', 'IPNLog', 'AccessLog', 'UserActionLog', 'NotificationLog',
    'NewsletterLog'];

    public function __construct($dynamoHost=null, $dynamoPort=null, $awsRegion=null, $awsKey=null, $awsSecret=null)
    {
        if(!$dynamoHost)
            return null;

        $sdk = new Sdk([
            'region' => $awsRegion,
            'version' => 'latest',
            //'endpoint' => 'http://necktie_dynamoDB_1:8000',
            'endpoint' => "http://${dynamoHost}:${dynamoPort}",
            'credentials' => array(
                'key' => $awsKey,
                'secret' => $awsSecret
            ),
        ]);
        $this->connection = $sdk->createDynamoDb();
        $this->checkLogs();
    }

    /*
     * Ensure all tables exists
     */
        private function checkLogs(){
        $result = $this->connection->listTables();

        $existingTables = $result['TableNames'];

        foreach($this->logsList as $log){
            if(!in_array($log, $existingTables)){
                $this->createDynamoLogTable($log);
            }
        }
    }


    /**
     * @return DynamoDbClient
     */
    public function getConnection(){
        return $this->connection;
    }


    public function __call($name, array $args)
    {
        if (strpos($name, 'writeInto') === 0) {

            return $this->writeInto(
                    //len('writeInto') = 9
                substr($name, 9),
                isset($args[0]) ? $args[0] : []
            );
        }

        if (strpos($name, 'itemCount') === 0) {
            return $this->itemCount(
            //len('itemCount') = 9
                substr($name, 9),
                isset($args[0]) ? $args[0] : []
            );
        }

        throw new \Exception("Error 500: Unsupported method called in DynamoDB Service");

    }


    /**
     * DynamoDB split tables into N partitions, probably discs/machines.
     * For split they use
     *
     * @param $tableName
     * @param BaseDynamoLog $data
     * @return array
     */
    private function writeInto($tableName, $data){


        /*
         * Gabi-TODO: This will always be object, when fixed in databaseHandler change remove it
         */

        if(!is_array($data)) {
            $data = $data->getDynamoArray();
        }

        //microtime is returned as string when floats are first, for ordering on server side we have to reverse it
        $microTime = explode(' ', microtime());
        $data ['created'] = ['S' => "${microTime[1]} ${microTime[0]}"];

        $data ['dynamoKey'] = ['N' => $this->itemCount($tableName) + 1];
        $data ['dynamoHash'] = ['N' => floor(($this->itemCount($tableName) + 1) / $this->inPartitionCount)];

        try {
            $this->connection->putItem([
                'TableName' => $tableName,
                'Item' => $data,
            ]);
        } catch (Exception $e) {
            dump($e);
        }
        return $data['created'];

    }


    /**
     * Get item count. Should be same as ID of last item (counted from 1)
     *
     * @param $tableName
     * @param array $data
     * @return int
     */
    private function itemCount($tableName,array $data=[]){

        try {
            $result = $this->connection->describeTable([
                'TableName' => $tableName,
            ]);
        }catch(Exception $e){
            dump($e);
        }
        return $result['Table']['ItemCount'];
    }



    private function createDynamoLogTable($name,$read=2,$write=2){
        $this->connection->createTable([
            'TableName' => $name,

                //created should be MicroTime()
            'AttributeDefinitions' => [
                [ 'AttributeName' => 'dynamoKey', 'AttributeType' => 'N' ],
                [ 'AttributeName' => 'dynamoHash', 'AttributeType' => 'N' ]
            ],
            'KeySchema' => [
                [ 'AttributeName' => 'dynamoHash', 'KeyType' => 'HASH' ],
                [ 'AttributeName' => 'dynamoKey', 'KeyType' => 'RANGE' ]

            ],
            'Projection' => [ // attributes to project into the index
                'ProjectionType' => 'ALL', // (ALL | KEYS_ONLY | INCLUDE)
            ],
            'ProvisionedThroughput' => [
                'ReadCapacityUnits'    => $read,
                'WriteCapacityUnits' => $write
            ]

        ]);
    }

    /**
     *
     * @param $entity
     * @param $item
     * @return array
     */
    public function decodeDynamoFormat(BaseDynamoLog $entity,$item){


        foreach($item as $key => $value)
        {
            if(strpos($key, 'dynamoHash') === 0)continue;

            /*
             * gabi-TODO: dynamologs will not have IDs (have dynamoKey it is same) this is for compatibility with old flow
             */
            if(strpos($key, 'Id') === 0)continue;
            $method = sprintf('set%s', ucwords($key)); // or you can cheat and omit ucwords() because PHP method calls are case insensitive
            // use the method as a variable variable to set your value
            $entity->$method(array_pop($value));

        }

            //this ignores microseconds, they are important in key but not in view
        $entity->setCreated(explode(' ',$entity->getCreated())[0]);


        return $entity;
    }



    /**
     *
     * @param $tableName
     * @param BaseDynamoLog $entityBase
     * @return array
     */

    public function getByID($tableName, BaseDynamoLog $entityBase,$id){

        $result = $this->connection->getItem([
            'TableName' => $tableName,
            'Key'=> [
                'dynamoKey' => ['N' => $id ],
                'dynamoHash' => ['N' => floor($id/$this->inPartitionCount) ],
            ]
        ]);
        if($result['Item']) {
            $entityBase = $this->decodeDynamoFormat($entityBase, $result['Item']);
            return $entityBase;
        }
        return null;

    }


    /**
     * @param $tableName
     * @param BaseDynamoLog $entityBase
     * @param $itemsOnPage
     * @param $pageNumber
     * @return array|null|BaseDynamoLog
     */

    public function getGridPage($tableName, BaseDynamoLog $entityBase, $itemsOnPage ,$pageNumber=1){
            //top of logs
        $firstItem = $this->itemCount($tableName);

            //move to page
        $firstItem -= ($pageNumber-1)*$itemsOnPage;

        if($firstItem<1){
            return null;
        }

            //found partition where is item stored
        $partitionNum = floor($firstItem/$this->inPartitionCount);
        $return = [];
        for(;;) {
            dump($partitionNum);
            $results = $this->connection->query([
                'TableName' => $tableName,
                'KeyConditionExpression' => '#hashkey = :hk_val AND #rangekey <= :rk_val',
                'ExpressionAttributeNames' => [
                    '#hashkey' => 'dynamoHash',
                    '#rangekey' => 'dynamoKey',
                ],
                'ExpressionAttributeValues' => [
                    ':hk_val' => ['N' => $partitionNum],
                    ':rk_val' => ['N' => $firstItem],
                ],
                'ScanIndexForward' => false,        //from latest
                'Limit' => $itemsOnPage,
            ]);

            foreach($results['Items'] as $result){
                $entity = clone $entityBase;
                $entity= $this->decodeDynamoFormat($entity,$result);
                array_push($return,$entity);
            }


            if($results['Count']<$itemsOnPage && count($return)<$itemsOnPage && $partitionNum != 0){
                $partitionNum--;
                continue;
            }


            return $return;

        }
        dump($result['Count']);
//        if($result['Item']) {
//            $entityBase = $this->decodeDynamoFormat($entityBase, $result['Item']);
//            return $entityBase;
//        }
//        return null;
//        dump($entityBase);

    }



    /**
     *
     * @param $tableName
     * @param BaseDynamoLog $entityBase
     * @return array
     */

    public function scanByTableName($tableName, BaseDynamoLog $entityBase){
//        $entity = new ExceptionLog();
        $results=$this->connection->scan(['TableName' => $tableName, ]);
        $return =[];

        foreach($results['Items'] as $result){
            $entity = clone $entityBase;
            $entity= $this->decodeDynamoFormat($entity,$result);
            $return[$entity->getCreated()] = $entity;
        }

        return $return;
    }


    public function scanIndexByTableName($tableName){
            //unsorted
        //$results=$this->connection->scan(['TableName' => $tableName, ]);
        $results=$this->connection->scan(['TableName' => $tableName, 'IndexName' => 'indexByCreated' ]);

        $b = microtime(true);
        $results['Items'] = array_reverse($results['Items']);
        dump(microtime(true) - $b);

        foreach($results['Items'] as $result) {
          dump($result['created']);
        }
    }



/*
 *
// Add Global Secondary Index
var params = {
    TableName: 'ExceptionLog',
    AttributeDefinitions: [ // only required if adding new index
        {
            AttributeName: 'created',
            AttributeType: 'S' // (S | N | B) for string, number, binary
        },
        // ... more attributes ...
    ],
    GlobalSecondaryIndexUpdates: [
        {
            Create: {
                IndexName: 'CreatedIndex',
                KeySchema: [
                    {
                        AttributeName: 'created',
                        KeyType: 'HASH'
                    },
                    // optional RANGE key
                ],
                Projection: { // attributes to project into the index
                    ProjectionType: 'KEYS_ONLY', // (ALL | KEYS_ONLY | INCLUDE)
                },
                ProvisionedThroughput: {
                    ReadCapacityUnits: 2,
                    WriteCapacityUnits: 2
                }
            }
        },
        // ... more optional indexes ...
    ],
};

dynamodb.updateTable(params, function(err, data) {
    if (err) print(err); // an error occurred
    else print(data); // successful response
});
 */


}
