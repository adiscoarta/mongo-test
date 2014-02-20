<?php

include("config.php");
require_once("class/mongo.class.php");
require_once("class/mysqli.class.php");
require_once("objects/appointment.object.php");

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


//traits test

$app = new AppointmentAvailable();
$app->sayAppType();

echo "<br/>";

$app1 = new AppointmentClient();
$app1->sayAppType();

echo "<br/>";

$app2 = new AppointmentNeither();
$app2->sayAppType();



