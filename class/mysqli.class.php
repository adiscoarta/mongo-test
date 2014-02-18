<?php

namespace Database;

class MySqli{
    
    private static $instance;
    private $db;
    private $query;
    
    public function __construct($host, $user, $pwd, $db){
        $this->db = new \mysqli($host, $user, $pwd, $db) or die('Couldn\'t connect');
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
    
    public function query($sql){
        $this->query = $this->db->query($sql);
        return $this;
    }
    
    /**
     * return the num rows in the last query selection
     * @return int
     */
    public function num_rows(){
        return $this->query->num_rows;
    }
    
    /**
     * returns the rows in the last query selection.
     * @param $single bool, default false single result or all results;
     * @return mixed $single = true may return the result object or FALSE if empty result set, $single = false returns an array
     */
    public function fetch($single = false){
        
        if($this->num_rows() == 0)
            return $single? false : array();
        
        if($single)
            return $this->query->fetch_object();
        
        $result_set = array();    
        while($row = $this->query->fetch_object()){
            $result_set[] = $row; 
        }
        return $result_set;
    }
    
    /**
     * returns the last inserted id
     * @return int
     */
    public function insert_id(){
        return $this->db->insert_id;
    }
    
    /**
     * returns an escaped string
     * @param $string string
     * @return $string escaped string
     */
    public function escape($string){
        return $this->db->real_escape_string($string);
    }

}