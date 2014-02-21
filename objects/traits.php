<?php

/**
 * Traits for each basic appointment Type
 */

trait TraitAppointmentBase{//base and available with everyone
    
    public $appointmentID, $start, $duration, $stop, $title, $created, $lastUpdated, $appType;
    public $calendarID = -1;
    
    public function appType(){
        return 2;
    }
    
    public function baseTranslate(){
        $base = array();
        $base['appointmentID'] = $this->appointmentID;
        $base['start'] = $this->start;
        $base['duration'] = $this->duration;
        $base['stop'] = $this->stop;
        $base['title'] = $this->title;
        $base['created'] = $this->created;
        $base['lastUpdated'] = $this->lastUpdated;
        $base['calendarID'] = $this->calendarID;
        $base['appType'] = $this->appType;
        return $base;
    }
    
    public function setAppointmentID($appointmentID = null){
        $this->appointmentID = $appointmentID == null? new MongoId() : $appointmentID;
    }
    
    public function getAppointmentID(){
        return $this->appointmentID;
    }
    
    public function setTitle($title){
        $this->title = $title;
    }
    
    public function setInterval($start, $duration){
        $this->start = $start;
        $this->duration = $duration;
        $this->stop = $start->sec + ($duration * 60);
    }
    
    public function setCoachID($coachID){
        $this->parentID = $coachID;
    }
    
    public function unwind($array){
        foreach($array as $key=>$value){
            $this->$key = $value;
        }
    }
    
    abstract public function translate();
}

trait TraitAppointmentAvailable{
    use TraitAppointmentBase;
    public $appFor = 0;//enum, 0 everyone, 1 sample session, 2 active clients, 3 a specific group
    public $groupFor = 0; //if appFor is group, set the group id
    
    public function appType(){
        return 2;
    }
    
    public function translate(){
        $base = $this->baseTranslate();
        $base['appFor'] = $this->appFor;
        $base['groupFor'] = $this->groupFor;
        return $base;
    }
}

trait TraitAppointmentNeither{//neither, client or group
    use TraitAppointmentBase;
    public $description = '';
        
    public function appType(){
        return 1;
    }
    
    public function setDescription($description){
        $this->description = $description;
    }
    public function translate(){
        $base = $this->baseTranslate();
        $base['description'] = $this->description;
        return $base;
        
    }
}

trait TraitAppointmentClient{ //with client
    
    use TraitAppointmentNeither{
        translate as uptranslate;
    }

    public $clientID = -1;
    

    public function setClientID($clientID){
        $this->clientID = $clientID;
    }

    public function translate(){
        $base = $this->uptranslate();
        $base['clientID'] = $this->clientID;
        return $base;
    }
}

trait TraitAppointmentGroup{ //with group

    use TraitAppointmentNeither{
        translate as uptranslate;
    }

    public $groupID = -1;
    
    
    public function setGroupID($groupID){
        $this->groupID = $groupID;
    }
}



/**
 * SINGLETON
 */

trait Singleton
{
    private static $instance;
 
    public static function getInstance() {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self;
        }
        return self::$instance;
    }
    
    protected function __clone()
    {
        //Me not like clones! Me smash clones!
    }
}