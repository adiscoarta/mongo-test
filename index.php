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
//clean the test collection first
$db->select('apps')->remove();


//create a dummy coach
$id = $db->select('apps')->insert(array("Coach"=>"Le Coach",  "apps"=>array()))->last_id();


//add some refs into an array
for($x = 0; $x<10; $x++){
    //push no matter what
    $update =  array('apps'=>array(
                                    "title" => 'APP '.$x, 
                                    "le_id"=>$x)
                        );
              
    $db->select('apps')->push(array('_id'=>$id), $update);
    //add to set if it doesn't exists
    if($x>4){
        $update =  array('apps'=>array(
                                        "title" => 'APP with add to set '.$x, 
                                        "le_id"=>$x)
                        );
                        
        
        $db->select('apps')->addToSet(array('_id'=>$id), $update);
    }

}

//do we have what we want?
$res = $db->select('apps')->find()->result();

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





