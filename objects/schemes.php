<?php


 /**
  * all schemes must implement && use this scheme
  */ 

  
trait BaseScheme{
    abstract public function validate();
    abstract public function save();
    
    
    private $errors; 
    public $elements;
    
    public function setValidationError($scheme, $error){
        $this->errors[] = array("scheme"=>$scheme, "error"=>$error);
        
    }
    
    public function hasValidationErrors(){
        return count($this->errors) > 0 ? true: false;
    }
    
    public function getValidationError(){
        return count($this->errors)? array_shift($this->errors) : false;
    }

}

trait GenericScheme{
    use BaseScheme;
    
    /**
     * transpose the object members that we want to save into an array
     */
    public function save($array = array()){
        if(count($array)){
            foreach($array as $element){
                $this->elements[$element] = $this->$element;
            }
        }
        
    }
    
    public function validate(){}
}
  
 /**
 * Basic Appointment Schema
 */

trait MongoAppointmentScheme{
        
    use BaseScheme;
      
    public $validationScheme = "MongoAppointmentScheme";
    public $collection = 'appointments';
    
    private function baseScheme(){
        
        if($this->elements['start'] === null || $this->elements['start'] === ''){
            $this->setValidationError('baseScheme', 'Appointment has no start date defined');
        }
        
        if($this->elements['duration'] === null || $this->elements['duration'] === '' || $this->elements['duration'] == 0){
            $this->setValidationError('baseScheme', 'Appointment has no duration defined');
        }
        
        if($this->elements['stop'] === null || $this->elements['stop'] === ''){
            $this->setValidationError('baseScheme', 'Appointment has no end time defined');
        }
    }
    
    private function availableScheme(){
        if($this->elements['appFor'] === null || $this->elements['appFor'] === ''){
            $this->setValidationError('availableScheme', 'Appointment has no available for defined');
        }

        else if($this->elements['appFor'] == AppointmentAvailable::APP_GROUP && ($this->elements['groupFor'] === null || $this->elements['groupFor'] === '' || $this->elements['groupFor'] == 0)){
            $this->setValidationError('availableBaseScheme', 'Appointment has no available group defined');
        }
    }
    
    private function neitherScheme(){
        
    }
    
    private function clientScheme(){
        if(!isset($this->elements['clientID']) || $this->elements['clientID'] === null || $this->elements['clientID'] === ''){
            $this->setValidationError('clientScheme', 'Appointment has no Client ID defined');
        }
    }
    
    private function groupScheme(){
        if(!isset($this->elements['groupID']) || $this->elements['groupID'] === null || $this->elements['groupID'] === ''){
            $this->setValidationError('groupScheme', 'Appointment has no Group ID defined');
        }
        
    }
    
    private function validate($elements){
        $this->elements = $elements;
        
        $this->baseScheme();
        
        if($elements['appType'] == 2){ //available
            $this->availableScheme();
        }else if($elements['appType'] == 1){//neither or with client/group
            
            $this->neitherScheme();
            if(isset($this->appWithClient)){
                $this->clientScheme();
            }
            
            if(isset($this->appWithGroup)){
                $this->groupScheme();
            }
            
        }
        
        $this->applyDefaults();
        
    }
    
    private function applyDefaults(){

        if($this->elements['created'] == null || $this->elements['created'] == ''){
            $this->element['created'] = new MongoDate();
        }

        if($this->elements['lastUpdated'] == null || $this->elements['lastUpdated'] == ''){
            $this->elements['lastUpdated'] = new MongoDate();
        }
    }
    
    private function prepare($array){
        return $this->validate($array);
    }
}
 