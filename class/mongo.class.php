<?php

/**
 * MONGO DB WRAPPER
 */
namespace Database;

class Mongo{
     
    private static $instance;
    private $db;
    private $collection;
    private $query;
    private $result;
    private $last_insert;
    
    public function __construct($host, $user, $pwd, $db){
        try{
            $userpass = strlen($user)? $user.":".$pwd."@" : "";
            $dbcon = new \MongoClient("mongodb://{$userpass}{$host}", array("connect"=>TRUE));
            $this->db = $dbcon->$db;
        }catch(\MongoConnectionException $e){
            echo $e->getCode()." - ".$e->getMessage();
            die();
        }
    }
    
    public static function getInstance()  
    {  
        if (NULL === self::$instance)
            self::$instance = new self();
        return self::$instance;          
    }  
      
    public function __clone()  
    {  
        throw new \Exception("Cloning is not permited");  
    }

    public function insert($data){
        $data['_id'] = new \MongoId();
        $this->collection->insert($data, array("w" => 1));//use w to avoid identical inserts
        $this->last_insert = $data['_id'];
        return $this;
    }
    
    public function last_id(){
        return $this->last_insert;
    }
    
    public function find($params=array(), $fields=null){
        if($fields == null)
            $this->result = $this->collection->find($params);
        else
            $this->result = $this->collection->find($params, $fields);
        return $this;
    }
    
    public function count($params=array()){
        return $this->collection->count();
    }
    
    public function select($table){
        $this->collection = $this->db->$table;
        return $this;
    }
    
    public function findOne($params = array()){
        $this->result =  $this->collection->findOne($params);
        return $this;
    }
    
    public function findAndModify($query, $update, $fields=array()){
        return $this->collection->findAndModify($query, $update, $fields);
    }
    
    public function result(){
        $list = array();
        while($this->result->hasNext()){
            $list[] = $this->result->getNext();
        }
        return $list;
    }
    
    public function update($params=array()){
        $this->collection->update($params);
        return $this;
    }
    
    public function remove($params=array()){
        $this->collection->remove($params);
        return $this;
    }
 }
