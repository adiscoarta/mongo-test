<?php
set_time_limit(0);
include("config.php");
//traits and schemes
require_once("objects/traits.php");
require_once("objects/schemes.php");
//base classes
require_once("class/mongo.class.php");
require_once("class/mysqli.class.php");
//objects
require_once("objects/appointment.object.php");
require_once("objects/client.object.php");
require_once("objects/group.object.php");

//instantiate mongo and mysql wrappers

$db = new \Database\Mongo(  $config['mongo']['hostbase'], 
                            $config['mongo']['username'], 
                            $config['mongo']['password'], 
                            $config['mongo']['database']);

$mdb = new \Database\MySqli($config['mysql']['hostbase'], 
                            $config['mysql']['username'], 
                            $config['mysql']['password'], 
                            $config['mysql']['database']);
                            


//clean the test collection first
// $db->select('coaches')->remove();
// $db->select('clients')->remove();
// $db->select('appointments')->remove();

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
       
        $data['_id'] = new MongoId();
        foreach($fields as $field){
            $data[$field] = utf8_encode($coach->$field);
        }
        
        foreach($address as $a){
            $data['address'][$a] = utf8_encode($coach->$a);  
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
    
    
    $ids = implode(",", $coach_ids);
    echo $ids;
    //get the clients
    $clients_arr = array();
    $clients = $mdb->query("SELECT * FROM clients WHERE CoachID IN({$ids}) AND IsDeleted='N'")->fetch();
    
    foreach($clients as $client){
        $data = array();
        $data['clientID'] = $client->ClientID;
        $data['Created'] = new MongoDate(strtotime($client->Created));
        $data['FirstName'] = utf8_encode($client->FirstName);
        $data['MiddleName'] = utf8_encode($client->MiddleName);
        $data['LastName'] = utf8_encode($client->LastName);
        $data['address'] = array(
            "Address"=>utf8_encode($client->Address),
            "AddressExtended"=>utf8_encode($client->AddressExtended),
            "City"=>utf8_encode($client->City),
            "State"=>utf8_encode($client->State),
            "PostalCode"=>$client->PostalCode,
            "Country"=>utf8_encode($client->Country)
        );
        $data['Active'] = $client->Active;
        $data['coachID'] = $coaches[$client->CoachID];
        $cli = new Client($data);
        $clients_arr[$client->ClientID] = $db->select('clients')->insert($data)->last_id();
    }
    
    //get the groups
    $groups = $mdb->query("SELECT * FROM groups WHERE CoachID IN({$ids})")->fetch();
    
    $groups_arr = array();
    if(count($groups)){
        
        foreach($groups as $gr){
            $data = array();    
            $data['GroupName'] = utf8_encode($gr->GroupName);
            $data['_id'] = new MongoId();
            $data['groupID'] = $gr->GroupID;
            $push = array('groups'=>$data);
            $db->select('coaches')->push(array('_id'=>$coaches[$gr->CoachID]), $push);
            $groups_arr[$gr->GroupID] = $data['_id'];
            
            //assign the clients to this group
            $assigned = $mdb->query("SELECT * FROM client_groups WHERE GroupID=".$gr->GroupID)->fetch();
            if(count($assigned)){
                foreach($assigned as $a){
                    $push = array('groups'=>$groups_arr[$a->GroupID]);
                    if(isset($clients_arr[$a->ClientID])){
                        $db->select('clients')->push(array('_id'=>$clients_arr[$a->ClientID]), $push);
                    }
                }
            }
        }
    }
    
    
    //get the apps for inserted coaches
    $apps = $mdb->query("SELECT * FROM appointments WHERE CoachID IN({$ids})")->fetch();
    echo "<br/>".count($apps)."<br/>";
    
    //all apps implement this properties
    $prop = "appointmentID,start,duration,stop,title,created,lastUpdated,appType";
    $prop = explode(",", $prop);
    
    $clients = array();
    $groups = array();
    
    foreach($apps as $a){
        
        $fields = array();
        $app = null;
        
        $fields['appointmentID'] = $a->AppointmentID;
        
        $fields['appType'] = $a->AppointmentType;
        $start = new MongoDate(strtotime($a->Start));
        $duration = $a->Duration;
        $fields['title'] = $a->Title;
        $fields['created'] = new MongoDate(strtotime($a->CreatedDate));
        $fields['lastUpdated'] = new MongoDate(strtotime($a->LastUpdated));
        $fields['status'] = $a->Status;
        $fields['calendarID'] = $a->CalendarID;
        $fields['title'] = utf8_encode($a->Title);
        
        if($a->ClientID > 0 && isset($clients_arr[$a->ClientID])){
            $app = new AppointmentClient($fields);
            $app->setClientID($clients_arr[$a->ClientID]); 
        }else if($a->GroupID > 0 && isset($groups_arr[$a->GroupID])){
            $app = new AppointmentGroup($fields);    
            $app->setGroupID($groups_arr[$a->GroupID]);
        }else if($a->AppointmentType == 1){
            $app = new AppointmentNeither($fields);
        }else{
            $app = new AppointmentAvailable($fields);
            if($app->appFor == AppointmentAvailable::APP_GROUP){
                echo $a->AppointmentID." ". $a->AppFor." ". $groups_arr[$a->GroupFor];
                $app->setAvailability($a->AppFor, $groups_arr[$a->GroupFor]);
            }else{
                $app->setAvailability($a->AppFor);
            }
        }
        if($app !== null){
            $app->setCoachID($coaches[$a->CoachID]);
            $app->setInterval($start, $duration);
            $db->commitObject($app->save());
        }
        
    }

}

$res = $db->select("clients")->find(
                                array(
                                    'groups'=>
                                        array(
                                            '$all'=>
                                                array(new MongoId("530743d5f3596fa43e002b56"))
                                                )
                                        )
                                 )->result();
echo "<pre>";
var_dump($res);
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



