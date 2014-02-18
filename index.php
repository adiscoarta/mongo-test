<?php

include("config.php");
require_once("class/mongo.class.php");
require_once("class/mysqli.class.php");

$db = new \Database\Mongo(  $config['mongo']['hostbase'], 
                            $config['mongo']['username'], 
                            $config['mongo']['password'], 
                            $config['mongo']['database']);

$mdb = new \Database\MySqli($config['mysql']['hostbase'], 
                            $config['mysql']['username'], 
                            $config['mysql']['password'], 
                            $config['mysql']['database']);
                            
//DO A LITTLE MONGO TEST

//select a collection, insert some data, retrieve the inserted id
$id = $db->select("test")->insert(array("title"=>"Test Insert", "data"=>12345))->last_id();

echo "INSERT ID:" .$id->__toString();
//select all results in the last selected collection
$result = $db->find()->result();
echo "<br/>RESULT:<br/>";
echo "<pre>";
var_dump($result);
echo "</pre>";

//modify our little record
$db->findAndModify(array('_id'=>$id), array("title"=>"New Title"));

//do we have what we expected?
$result = $db->find(array('_id'=>$id))->result();
echo "<br/>MODIFIED RESULT:<br/>";
echo "<pre>";
var_dump($result);
echo "</pre>";


//remove all from the last selected collection
$db->remove();
echo "REMOVED?". $db->count();


//IS THE MYSQL OK?

//select an appointment from the appointments table, single result
$app = $mdb->query("SELECT * FROM appointments LIMIT 0,1")->fetch(true);

echo "<pre>";
var_dump($app);
echo "</pre>";





