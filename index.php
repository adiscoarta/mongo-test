<?php

include("config.php");
require_once("objects/traits.php");
require_once("objects/schemes.php");
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
                            

echo new MongoId(1017);
sleep(1);
echo "<br/>";
echo new MongoId(1017);
die();
                            
//DO A LITTLE MONGO TEST
//clean the test collection first
//$db->select('coaches')->remove();

if(isset($_GET['limit'])){
//get coaches from mysql
    $limit = intval($_GET['limit']);
    
    $res = $mdb->query("SELECT * FROM coaches WHERE Active='Y' ORDER BY CoachID ASC LIMIT {$limit}, 10")->fetch();
    
    //export to mongo
    
    $fields = array("CoachID", "FirstName", "LastName", "BusinessName", "Occupation", "SitePrefix", "Logo", "Photo", "Graphic");
    $address = array("Address1", "Address2", "City", "State", "PostalCode", "Country", "Phone");
    $contact = array("WebSiteURL", "fb", "tw", "ln");
    $website = array("CallStrategyNote", "ShowAddress", "template_id", "IncludeCallStrategy", "EnableRegister");
    $sync = array("GoogleToken", "GLastUpdated");
    
    $coaches = array();
    $coach_ids = array();
    
    foreach($res as $coach){
        $data = array();
       
        $data['_id'] = new MongoId($coach->CoachID);
        foreach($fields as $field){
            $data[$field] = $coach->$field;
        }
        
        foreach($address as $a){
            $data['address'][$a] = $coach->$a;  
        }
        
        foreach($contact as $c){
            $data['contact'][$c] = $coach->$c;
        }
        
        foreach($website as $w){
            $data['website'][$w] = $coach->$w;
        }
        
        foreach($sync as $w){
            $data['sync'][$w] = $w == "GLastUpdated" ? new MongoDate(strtotime($coach->$w)) : $coach->$w;
        }
        
        //insert the coach
        $coaches[$coach->CoachID] = $db->select("coaches")->insert($data)->last_id();
        $coach_ids[] = $coach->CoachID;
        
    }
    
    
    //get the apps for inserted coaches
    $ids = implode(",", $coach_ids);
    $apps = $mdb->query("SELECT * FROM appointments WHERE CoachID IN({$ids})")->fetch();
    echo count($apps)."<br/>";
    
    //all apps implement this properties
    $prop = "appointmentID,start,duration,stop,title,created,lastUpdated,appType";
    $prop = explode(",", $prop);
    
    foreach($apps as $a){
        
        $fields = array();
        $fields['appointmentID'] = new MongoId($a->AppointmentID);
        
        $fields['appType'] = $a->AppointmentType;
        $start = new MongoDate(strtotime($a->Start));
        $duration = $a->Duration;
        $fields['title'] = $a->Title;
        $fields['created'] = new MongoDate(strtotime($a->CreatedDate));
        $fields['lastUpdated'] = new MongoDate(strtotime($a->LastUpdated));
        $fields['status'] = $a->Status;
        $fields['calendarID'] = $a->CalendarID;
        
        if($a->ClientID > 0){
            $app = new AppointmentClient($fields);
            $app->setClientID(new MongoId($a->ClientID)); 
        }else if($a->GroupID > 0){
            $app = new AppointmentGroup($fields);    
            $app->setGroupID(new MongoId($a->GroupID));
        }else if($a->AppointmentType == 1){
            $app = new AppointmentNeither($fields);
        }else{
            $app = new AppointmentAvailable($fields);
        }
        echo $a->CoachID;
        $app->setCoachID(new MongoId($coaches[$a->CoachID]));
        $app->setInterval($start, $duration);
        $db->commitObject($app->save());
        
    }

    die();
}

$apps = $db->select('coaches')->findOne(array('_id'=>new MongoId('530708b3f3596fd83a000243'), 'apps.appType'=>2), array("apps"=>true));
echo count($apps);
echo "<pre>";
print_r($apps);
echo "</pre>";


die();
//create a new Available Appointment
$app = new AppointmentAvailable();
//set some fields
$app->setInterval(new MongoDate(), 60); //app is from now, duration 60 minutes
$app->setCoachID($coachID);
//we're happy, save the state of our appointment
//try to insert / update in the database 
$id = $db->commitObject($app->save())->last_id();

echo $id;
die();

//create a new Available Appointment
$app = new AppointmentAvailable();
//set some fields
$app->setAppointmentID(new MongoId(100));
$app->setTitle("Hi!");
$app->sayAppType();
$app->setInterval(new MongoDate(), 60);
$app->setCoachID(1);
//will throw an error since we have defined the app with a group, but group is not defined and will not validate against the scheme.
$app->setAvailability(3);
//we're happy, save the state of our appointment
$app->save();
//try to insert / update in the database

$db->commitObject($app);

$app1 = new AppointmentClient();
$app1->setDescription('Test Appointment With Client');
//$app1->setClientID(111); if we don't specify the ClientID, the schema will not validate this appointment object
$app1->setAppointmentID(100);
$app1->setTitle("Hi!");
$app1->sayAppType();
$app1->save();

echo "<br/>";

$app2 = new AppointmentNeither();
$app2->setDescription('Test Appointment With Neither');
$app2->sayAppType();



