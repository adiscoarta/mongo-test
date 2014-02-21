<?php

/**
 * Groups Object
 */

class Group{
    
    use TraitArrayTranspose, GenericScheme;
    
    public function __construct($array = array()){
        $this->unwind($array);
    }
}
