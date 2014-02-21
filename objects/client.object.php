<?php

/**
 * Client Object
 */

class Client{
    use GenericScheme, TraitArrayTranspose;
    
    public function __construct($array){
        $this->unwind($array);
    }
}
