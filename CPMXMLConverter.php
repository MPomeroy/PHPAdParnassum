<?php

/**
 * Copyright: Mason Pomeroy 2016.
 */

require_once 'lib/PHPMusicXML/src/PHPMusicXML/PHPMusicXML.php';


class CPMXMLConverter{
    
    private $logger = null;
    private $CF = null;
    private $CP = null;
    private $xml = null;
    
    private $measureDesc = array(
                            'divisions' => null,
                            'direction' => array(
                                    'placement' => 'below',
                                    'direction-type' => array(
                                            'words' => array(
                                                    'default-x' => 0,
                                                    'default-y' => 15,
                                                    'font-size' => 10,
                                                    'font-weight' => 'bold',
                                                    'font-style' => 'italic',
                                                    'text' => 'Andantino'
                                            )
                                    ),
                                    'staff' => 1,
                                    'sound-dynamics' => 40
                            ),
                            'barline' => array(
                                    array(
                                            'location' => 'right',
                                            'bar-style' => 'heavy-light',
                                            'ending' => array(
                                                    'type' => 'stop',
                                                    'number' => 1
                                            )
                                    )
                            ),
                            'implicit' => true,
                            'number' => 1,
                            'width' => 180
                            );
    
    public function __construct($CF, $CP, $logger){
        $this->CP = $CP;
        $this->CF = $CF;
        $this->logger = $logger;
        $this->convertCP();
    }
    
    private function convertCP(){
        $score = new ianring\Score();
        
        $score->addPart($this->createPart($this->CP));
        $score->addPart($this->createPart($this->CF));
        
        try {
            $this->xml = $score->toXML('partwise');
        }catch (Exception $e){
            $this->logger->log('Converting to XML failed. Exception:' . $e, 'error');
        }
    }
    
    private function createPart($noteArray){
        $currMesaureDesc = $this->measureDesc;
        $currMesaureDesc['divisions'] = count($noteArray);
        
        $part = new ianring\Part();
        $measure = new ianring\Measure($currMesaureDesc);
        
        $this->addCPNoteArrayToMeasure($noteArray, $measure);
        
        $part->addMeasure($measure);
        
        return($part);
    }
    
    private function addCPNoteArrayToMeasure($notes, &$measure){
        foreach($notes as $note){
            $this->logger->log('Converting note ' . $note->class.$note->octave . ' to ianring\\note<br>', 'renderingInfo');
            
            $IRNote = new ianring\Note(
                    array(
                        'pitch'=>$note->class.$note->octave, 
                        'duration'=>1, 
                        'type'=>'whole'));
            $measure->addNote($IRNote);
            
        }
    }
    
    public function getXml(){
        return($this->xml);
    }
    
}