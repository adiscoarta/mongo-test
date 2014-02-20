<?php

/**
 * Traits for each basic appointment Type
 */

trait TraitAppointmentBase{//base and available with everyone
    
    public $appointmentID, $start, $duration, $stop, $title, $created, $lastUpdated, $calendarID;
    
    public function appType(){
        return 2;
    }
    
}

trait TraitAppointmentAvailable{
    public $appFor = 0;//enum, 0 everyone, 1 sample session, 2 active clients, 3 a specific group
    public $groupFor = 0; //if appFor is group, set the group id
}

trait TraitAppointmentNeither{//neither, client or group
        
    private $description = '';
        
    public function appType(){
        return 1;
    }
}

trait TraitAppointmentClient{
    public $clientID = -1;

}

trait TraitAppointmentGroup{
    public $groupID = -1;
}
