<?php
/**
 * Created by PhpStorm.
 * User: gabriel
 * Date: 13.2.16
 * Time: 12:13
 */

namespace Trinity\LoggerBundle\Entity;


class BaseDynamoLog
{

    protected $dynamoKey;

    protected $created;

    /**
     * @return mixed
     */
    public function getDynamoKey()
    {
        return $this->dynamoKey;
    }

    /**
     * @param mixed $dynamoKey
     */
    public function setDynamoKey($dynamoKey)
    {
        $this->dynamoKey = $dynamoKey;
    }

    /**
     * @return mixed
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @param mixed $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }


    /**
     * @return array
     */

    public function getDynamoArray(){

        $methods = get_class_methods(get_class($this));
        $array = [];
        foreach($methods as $method){
            if(strpos($method,'get')===0) {
                $key =substr($method, 3);
                if(strpos($key,'DynamoArray')===0){
                    //this would result in infinite recursion
                    continue;
                }

                    //date Time problem
                if(strpos($key,'ReceiveAt')===0){
                    continue;
                }

                $value = $this->$method();
                if(!$value) continue;


                if(is_object($value)&& method_exists($value,'getId')){
                    $value=$value->getId();
                }

                if($value){

                    $array[$key] = is_numeric($value)? ['N'=>$value ]: ['S' => "${value}"] ;
                }
            }
        }
        return $array;


    }


}