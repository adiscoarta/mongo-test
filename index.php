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

// $cid = $db->select("apps")->insert(array("CoachID"=>1017, "Name"=>"Coach Name"))->last_id();
// $id = $db->select("apps")->insert(
                                // array(  "time"=>new MongoDate(), 
                                        // "Description"=>"App Now",
                                        // 'parent'=>$cid 
                                        // )
                                 // )
                                // ->last_id();
// $id = $db->select("apps")->insert(
                                // array(  "time"=>new MongoDate(), 
                                        // "Description"=>"App Now Again",
                                        // 'parent'=>$cid 
                                        // )
                                 // )
                                // ->last_id();
                                
$res = $db->select('apps')->find(array('parent'=>new MongoId('53049b63f3596fb811000004')))->result();

echo "<pre>";
var_dump($res);
echo "</pre>";
die();
//modify our little record
$db->findAndModify(array('_id'=>$id), array("title"=>"New Title"));

//do we have what we expected?
$result = $db->find(array('_id'=>$id))->result();
echo "<br/>MODIFIED RESULT:<br/>";
echo "<pre>";
var_dump($result);
echo "</pre>";


//remove all from the last selected collection
//$db->remove();
echo "REMOVED?". $db->count();


//IS THE MYSQL OK?

//select an appointment from the appointments table, single result
$app = $mdb->query("SELECT * FROM appointments LIMIT 0,1")->fetch(true);

echo "<pre>";
var_dump($app);
echo "</pre>";





