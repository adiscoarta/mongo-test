<?php

require_once("objects/traits.php");


//base appointment
class Appointment{
    
    private $appType;    
        
    public function __construct(){
       $this->appType = $this->appType();
    }    
    
    public function sayAppType(){
        echo "<pre>";
        var_dump($this);
        echo "</pre>";
    }

}

//available Appointment
class AppointmentAvailable extends Appointment{
    use TraitAppointmentAvailable,TraitAppointmentBase;
}

//app with client
class AppointmentClient extends Appointment{
    use TraitAppointmentClient,TraitAppointmentNeither,TraitAppointmentBase{
        TraitAppointmentNeither::appType insteadof TraitAppointmentBase;
    }
}

//app with neither
class AppointmentNeither extends Appointment{
    use TraitAppointmentNeither,TraitAppointmentBase{
        TraitAppointmentNeither::appType insteadof TraitAppointmentBase;
    }
}

//app with group
class AppointmentGroup extends Appointment{
    use TraitAppointmentGroup,TraitAppointmentNeither,TraitAppointmentBase{
        TraitAppointmentNeither::appType insteadof TraitAppointmentBase;
    }

}
