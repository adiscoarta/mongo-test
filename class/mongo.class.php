<?php

/**
 * MONGO DB WRAPPER
 */
namespace Database;

class Mongo{
     
    private $db;
    private $collection;
    private $query;
    private $result;
    private $last_insert;
    
    use \Singleton;
    
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

    /**
     * insert a new doc in the collection
     * @param $data array
     * @return $this
     */
    public function insert($data, $opts = array()){
            
        $data['_id'] = (!isset($data['_id']))? new \MongoId() : $data['_id'];
        try{
            $this->collection->insert($data, array_merge($opts, array("w" => 1)));//use w to avoid identical inserts
            $this->last_insert = $data['_id'];
            return $this;
        }catch(\Exception $e){
            echo "INSERT ERROR". $e->getMessage()." ".$e->getCode();
        }
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
        return $this->result->count();
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
    
    private function update_array($select = array(), $opts = array()){
        $this->result = $this->collection->update($select, $opts);
        return $this;
    }
    
    public function addToSet($select = array(), $addToSet){
        return $this->update_array($select, array('$addToSet'=>$addToSet));
    }
    
    public function push($select = array(), $push){
        return $this->update_array($select, array('$push'=>$push));
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
     * @return row
     */
    public function findOne($params = array(), $fields = array()){
        return count($fields)? $this->collection->findOne($params, $fields) : $this->collection->findOne($params);
        
        
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
    
    /**
     * insert or update object. The object itself must be save()-d before use
     * @param $obj the object to insert
     */
    public function commitObject($obj, $select_rule = null){
            
        $reflection = new \ReflectionClass($obj);
        
        if(!$reflection->hasProperty('elements') || !$reflection->hasMethod('setValidationError') || !$reflection->hasMethod('getValidationError')){
            trigger_error("Object does not implement the Validation Scheme or it has not been saved!", E_USER_WARNING);
            return $this;
        }
        
        if(!count($obj->elements)){
            trigger_error("Object has not been saved or is empty.", E_USER_WARNING);
            return $this;
        }
        
        
        if($obj->hasValidationErrors()){
            $e = '';
            while($error = $obj->getValidationError()){
                $e .= $obj->validationScheme."::".$error["scheme"]."::".$error["error"]."<br/>";
            }
            trigger_error("Object did not validate against the schema: <br/>".$e, E_USER_WARNING);
            echo "<pre>";
            print_r($obj->elements);
            echo "</pre>";
            return $this;
            
        }
        
        if(isset($obj->memberOf) && !isset($obj->parentID)){
            trigger_error("Object should be an array member of {$obj->memberOf} but you haven't declared it's parentID: <br/>", E_USER_WARNING);
            return $this;
        }
        
        $selector = false;
        
        if((isset($obj->parentID) || $select_rule !== null)){
            $selector = $select_rule !== null ? $select_rule : array('_id'=>$obj->parentID);
        }
        
        if(isset($obj->collection)){
            $this->select($obj->collection);
        }
        
        if($selector != false && isset($obj->memberOf)){
            //we need to insert this item in an array that is part of a collection
            $addToSet = array($obj->memberOf=>$obj->elements);
            return $this->addToSet($selector, $addToSet);
        
        }else if($selector != false){
            //we need to update the object in a collection
            return $this->update($selector, $obj->elements);
        
        }else{
            // just insert in whatever collection was previously selected
            return $this->insert($obj->elements);    
        }
        
    }
 }
