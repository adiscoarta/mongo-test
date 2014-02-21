<?php

//base appointment
class Appointment{
    
    use MongoAppointmentScheme;
    
    public function __construct($array = array()){
       if(count($array)){
           $this->unwind($array);
       }
       $this->appType = $this->appType();
    }    
    
    public function sayAppType(){
        
    }
    
    public function save(){
        $this->validate($this->translate());
        return $this;//allow chaining
    }

}

//available Appointment
class AppointmentAvailable extends Appointment{
    use TraitAppointmentAvailable;

    const APP_AVAILABLE = 0; //enum, 0 everyone, 1 sample session, 2 active clients, 3 a specific group;
    const APP_SAMPLE = 1;
    const APP_ACTIVE = 2;
    const APP_GROUP = 3;
    
    public function __construct(){
        parent::__construct();
        $this->appFor = self::APP_AVAILABLE;
    }
    
    public function setAvailability($type = self::APP_AVAILABLE){
        $this->appFor = $type;
    }

}

//app with client
class AppointmentClient extends Appointment{
    use TraitAppointmentClient;
    public $appWithClient = 1;
}

//app with neither
class AppointmentNeither extends Appointment{
    use TraitAppointmentNeither;
}

//app with group
class AppointmentGroup extends Appointment{
    use TraitAppointmentGroup;
    public $appWithGroup = 1;

}
