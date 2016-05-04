<?php

/**
 * Copyright: Mason Pomeroy 2016.
 */

require_once 'FirstSpeciesGenService.php';
require_once 'CounterpointGenBase.php';
require_once 'logger.php';
require_once 'CPMXMLConverter.php';

/**
 * This class is the main counterpoint class, it handles the following:
 * 
 *  * Processing of the cantusfirmus(though this should be moved to a seperate class) and routing.
 *  * It initializes the logger, which is then passed around to the different subclasses.
 *  * It routes the cantus firmus to the appropriate species generator.
 *  * It hosts an interface to export the generated counterpoint in a printed array,
 *    printed XML or saved to a file.
 */
class counterpoint{
    private $notes = array('C', 'C#', 'D', 'D#' , 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B');
    private $scale = null;
    
    private $scoreThreshold = 100;
    
    private $logger = null;
    
    private $CF = null;
    private $completeCP = null;
    
   public function __construct() {
       $this->logger = new logger();
   }

    public function process(){
        $this->buildScaleArray('C', 'major');
        $this->CF = array(
            new note('C', 4), 
            new note('E', 4), 
            new note('F', 4), 
            new note('D', 4), 
            new note('E', 4), 
            new note('G', 4),
            new note('F', 4), 
            new note('D', 4),
            new note('E', 4),
            new note('D', 4),
            new note('C', 4));//TODO: get this from user
        
        $species = 'first';//TODO:get from user
        
        if($species === 'first'){
            $generator = new FirstSpeciesGenService($this->CF, $this->scale, $this->scoreThreshold, $this->logger);
            $generator->generateCP();
            $this->completeCP = $generator->getCP();
        }
    }
    
    /**
     * Prints the completed Counterpoint, prints an error
     * if non has been generated.
     */
    public function printCompleteCPArray(){
        if($this->completeCP !== null){
            print_r($this->completeCP);
        }else{
            print('Error! No counterpoint has been generated. Please call a processing function before exporting the counterpoint.');
        }
    }
    /**
     * Prints a MusicXML formatted version of the completed Counterpoint, 
     * prints an error if non has been generated.
     */
    public function printCompleteCPXML(){
        if($this->completeCP !== null){
            $CPConverter = new CPMXMLConverter($this->CF, $this->completeCP, $this->logger);
            print(htmlentities($CPConverter->getXML()));
        }else{
            print('Error! No counterpoint has been generated. Please call a processing function before exporting the counterpoint.');
        }
    }
    /**
     * Saves a MusicXML file for the generated Countpoint.
     * 
     * @param str $filename         The filename with which to save the file.
     */
    public function renderCPToFile($filename){
        if($this->completeCP !== null){
            $CPConverter = new CPMXMLConverter($this->CF, $this->completeCP, $this->logger);
            $file = fopen('outputXMLFiles/'.$filename, 'w');
            
            fwrite($file, $CPConverter->getXML());
        }else{
            print('Error! No counterpoint has been generated. Please call a processing function before exporting the counterpoint.');
        }
    }
    
    public function printLog(){
        $this->logger->printLog();
    }

    
    private function buildScaleArray($note, $quality){
        $reverseNotes = array_flip($this->notes);
        $noteNum = $reverseNotes[$note];
        
        $this->scale = array($this->notes[$noteNum]);
        if($quality === 'major'){
            $steps = array('2', '2', '1', '2', '2', '2');
        }elseif($quality === 'minor'){
            $steps = array('2', '1', '2', '2', '1', '2');
        }
        
        foreach($steps as $step){
            $newNoteNum = $noteNum+$step;
            if($newNoteNum >= 12){
                $newNoteNum = $newNoteNum-12;
            }
            $this->scale[] = $this->notes[$newNoteNum];
            
            $noteNum = $newNoteNum;
        }
    }
        
}