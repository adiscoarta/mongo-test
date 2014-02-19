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
    
    
    /**
     * insert a new doc in the collection
     * @param $data array
     * @return $this
     */
    public function insert($data, $opts = array()){
        $data['_id'] = new \MongoId();
        $this->collection->insert($data, array_merge($opts, array("w" => 1)));//use w to avoid identical inserts
        $this->last_insert = $data['_id'];
        return $this;
    }
    
    /**
     * returns the last inserted document's id
     * @return MongoId() object
     */
    public function last_id(){
        return $this->last_insert;
    }
    
    /**
     * search a collection
     * @param $params array fields to search by
     * @param $fields array, the fields to return
     * @return $this
     */
    public function find($params=array(), $fields=null){
        if($fields == null)
            $this->result = $this->collection->find($params);
        else
            $this->result = $this->collection->find($params, $fields);
        return $this;
    }
    /**
     * returns the last collection search num docs found
     * @return int
     */
    public function count(){
        return $this->collection->count();
    }
    
    
    /**
     * sort a result set by something
     * @param $sort array to sort by
     * @return $this
     */
    public function sort($sort = array()){
        $this->result = $this->result->sort($sort);
        return $this;
    }
    
    /**
     * limit the query subset
     * @param $skip int default 0
     * @param $set int default 30
     * @return $this
     */
    public function limit($skip = 0, $set = 30){
        if($skip > 0)
            $this->result = $this->result->limit($set)->skip($skip);
        else{
            $this->result = $this->result->limit($set);
        }
        return $this;
    }
    
    /**
     * update a subset based on a rule
     * @param $select array to select by
     * @param $set array fields to set
     * @return $this
     */
     
    public function update($select = array(), $set = array()){
        $this->result = $this->collection->update($select, array('$set'=>$set));
        return $this;
    }
    
    /**
     * performs a collection select
     * @param $collection string
     * @return $this
     */
    public function select($collection){
        $this->collection = $this->db->$collection;
        return $this;
    }
    
    /**
     * 
     * performs a subcollection select
     * @param $collection
     * @return $this
     */
    public function subselect($collection){
        $this->collection = $this->collection->$collection;
        return $this;
    }
    
    /**
     * search a collection
     * @param $params array fields to search by
     * @return $this
     */
    public function findOne($params = array()){
        $this->result =  $this->collection->findOne($params);
        return $this;
    }
    
    /**
     * find docs in a collection and modify them by criteria
     * @param $query array to search by
     * @param $update array fields to update
     * @param $fields array fields to return after update
     * @return array result set
     */
    public function findAndModify($query, $update, $fields=array()){
        return $this->collection->findAndModify($query, $update, $fields);
    }
    
    /**
     * return the result set after a find or findOne
     * @return array
     */
    public function result(){
        $list = array();
        while($this->result->hasNext()){
            $list[] = $this->result->getNext();
        }
        return $list;
    }
    
    
    /**
     * removes documents based on $params from a collection
     * @param $params array with the criteria to remove by
     * @return $this
     */    
    public function remove($params=array()){
        $this->collection->remove($params);
        return $this;
    }
 }
