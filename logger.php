<?php

/**
 * Copyright: Mason Pomeroy 2016.
 */

class logger{
    private $log = null;
    private $printingCategory = array(
        'error' => true,
        'getHarmonicIntervalBySlot' => false, 
        'getMelodicInterval' => false, 
        'pickNextFSInterval' => false,
        'retryAttempts' => false,
        'renderingInfo' => true);
    
    public function __construct(){
        $this->log = '';
    }
    
    
    public function log($message, $category){
        $array = array();
        $array['message'] = '<br>' . $message;
        $array['category'] = $category;
        $this->log[] = $array;
    }
    
    public function printLog(){
        foreach($this->log as $entry){
            if($this->printingCategory[$entry['category']] === true){
                print($entry['message']);
            }
        }
    }
}