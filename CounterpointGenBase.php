<?php

/**
 * Copyright: Mason Pomeroy 2016.
 */

class note{
    public $class = null;
    public $octave = null;
    
    public function __construct($class, $octave){
        $this->class = $class;
        $this->octave = $octave;
    }
}

abstract class CounterPointGenBase{
    protected $notes = array('C', 'C#', 'D', 'D#' , 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B');
    protected $CP = null;
    protected $scale = null;
    protected $scoreThreshold = null;
    protected $logger = null;
    
    public function __construct($CF, $scale, $scoreThreshold, $logger){
        $this->CF = $CF;
        $this->scale = $scale;
        $this->scoreThreshold = $scoreThreshold;
        $this->logger = $logger;
    }
    
    //Ensure the counterpoint interface is consistent
    abstract function generateCP($above = true);
    abstract function getCP();
    
    //-------------------------------
    // From here down are common function that are required by all species.
    //-------------------------------
    
    /**
     * Returns the interval between a slot of the cantus firmus and counterpoint.
     * if diatonic is true it returns a diatonic interval, otherwise it's the number
     * of semitones between them.
     * 
     * @param int $CFSlot
     * @param int $CPSlot
     * @param bool $diatonic
     * @return int
     */
    protected function getHarmonicIntervalBySlot($CFSlot, $CPSlot, $diatonic = true){
        return($this->getHarmonicInterval($this->CF[$CFSlot], $this->CP[$CPSlot], $diatonic));
    }
    
    protected function getHarmonicInterval($CFNote, $CPNote, $diatonic){
        if($diatonic === true){
            $reverseNotes = array_flip($this->scale);
        }else{
            $reverseNotes = array_flip($this->notes);
        }
        //     Class value, add 1 to account for array indexing   multiply the octave by 7 to get the inversion right
        $CPValue = (($reverseNotes[$CPNote->class]+1)+(($CPNote->octave-1)*7));
        $CFValue = (($reverseNotes[$CFNote->class]+1)+(($CFNote->octave-1)*7));
        
        //log stuff for debugging purposes
        $this->logger->log('CFNote:' . $CFNote->class . $CFNote->octave . ' Value:' . $CFValue, 'getHarmonicIntervalBySlot');
        $this->logger->log('CPNote:' . $CPNote->class . $CPNote->octave . ' Value:' . $CPValue, 'getHarmonicIntervalBySlot');
        
        //add one here to get correct for normal diatonic interval offset(unison = 1, etc...)
        $diffValue = ($diatonic === true ? abs($CPValue - $CFValue)+1:abs($CPValue - $CFValue));
        return($diffValue);
    }
    
    /**
     * Calculate the interval between $note1 and $note2.
     * 
     * @param note $note1
     * @param note $note2
     * @return int
     */
    protected function getMelodicInterval($note1, $note2){
        $reverseNotes = array_flip($this->scale);
        $reverseChromNotes = array_flip($this->notes);
        
        $DiatonicNote1Value = (($reverseNotes[$note1->class]+1)+(($note1->octave-1)*7));
        $DiatonicNote2Value = (($reverseNotes[$note2->class]+1)+(($note2->octave-1)*7));
        $this->logger->log('MIntervalNote1:' . $note1->class . $note1->octave . ' Value:' . $DiatonicNote1Value, 'getMelodicInterval');
        $this->logger->log('MIntervalNote2:' . $note2->class . $note2->octave . ' Value:' . $DiatonicNote2Value, 'getMelodicInterval');
        
        $ChromNote1Value = (($reverseChromNotes[$note1->class]+1)+(($note1->octave-1)*12));
        $ChromNote2Value = (($reverseChromNotes[$note2->class]+1)+(($note2->octave-1)*12));
        
        if($DiatonicNote1Value > $DiatonicNote2Value){
            $diatonicDiff = ($DiatonicNote1Value-$DiatonicNote2Value+1)*-1;
            $chromDiff = ($ChromNote1Value-$ChromNote2Value+1)*-1;
        }else{
            $diatonicDiff = $DiatonicNote2Value-$DiatonicNote1Value+1;
            $chromDiff = $ChromNote2Value-$ChromNote1Value+1;
        }
        
        $tempObj = array('diatonic' => $diatonicDiff,
                         'chromatic' => $chromDiff);
        
        return($tempObj);
    }
}