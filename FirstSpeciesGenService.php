<?php

/**
 * Copyright: Mason Pomeroy 2016.
 */

require_once 'CounterpointGenBase.php';

class FirstSpeciesGenService extends CounterpointGenBase{
    
    /**
     * Function to generate first species counterpoint.
     * 
     * @param bool $above   Whether the counterpoint is above or below the CF.
     */
    public function generateCP($above = true){
        $success = false;
        $reverseNotes = array_flip($this->scale);
        $availableIntervals = array('3', '6', '5', '8', '10', '13');
        
        if($this->getMelodicInterval($this->CF[0], $this->CF[1]) > 0){
            $octaveStart = true;
        }else{
            $octaveStart = false;
        }
        
        while(!$success){
            $this->CP = array();
            $i = 0;
            foreach($this->CF as $note){
                //have the counterpoint start and end in unison(or octave)
                if($i === 0 || $i === count($this->CF)-1){
                    $this->CP[] = ($octaveStart === true ? new note($note->class, $note->octave+1):$note);
                    $i++;
                    continue;
                }
                //get the note number and octave from the CF
                $octave = $note->octave;
                $noteNum = $reverseNotes[$note->class]-1;
                
                $chosenInterval = $this->pickNextFSInterval($availableIntervals);
                if($chosenInterval === null){
                    //counterpoint unsolvable from here
                    break;
                }
                
                
                //get one of the available intervals
                $newNoteNum = $noteNum+$chosenInterval;
                //if it falls out of the scale subtract 7 to bring it back in and increment octave
                if($newNoteNum >= 7){
                    $newNoteNum = $newNoteNum-7;
                    $octave++;
                }
                $this->CP[] = new note($this->scale[$newNoteNum], $octave);
                $i++;
            }
            
            $result = $this->checkFSCounterpoint();
            
            if($result['score'] < $this->scoreThreshold){
                $this->logger->printLog();
                print('<pre>');
                print_r($result);
                print('</pre>');
                $success = true;
            }else{
                $this->logger->log('This Counterpoint sucks, starting again.', 'retryAttempts');//this is for Kiki
            }
        }
       
    }
    
    /**
     * Getter function for getting the generated counterpoint.
     * 
     * @return type
     */
    public function getCP(){
        return($this->CP);
    }
    
    /**
     * Scores each possible interval where higher is better, picks the highest score.
     * 
     * @param type $counterpoint
     * @param type $availableIntervals
     */
    private function pickNextFSInterval($availableIntervals){
        $chosenInterval = null;
        $score = 0;
        $reverseNotes = array_flip($this->scale);
        $numOfNotes = count($this->CP);
        $this->logger->log('num of notes:' . $numOfNotes, 'pickNextFSInterval');
        foreach($availableIntervals as $harmonicInterval){
            $this->logger->log('<br>interval:' . $harmonicInterval, 'pickNextFSInterval');
            //reset the score and get the look back information required for 
            //evaluating this note
            $intervalScore = 0;
            
            //calculate the possible note.
            $possibleNoteNum = $reverseNotes[$this->CF[$numOfNotes]->class]+$harmonicInterval-1;
            $this->logger->log('$possibleNoteNum:' . $possibleNoteNum, 'getMelodicInterval');
            $octaveOffset = 0;
            while($possibleNoteNum >= 7){
                $possibleNoteNum = $possibleNoteNum-7;
                $octaveOffset++;
            }
            $possibleNote = new note($this->scale[$possibleNoteNum], $this->CF[$numOfNotes+1]->octave+$octaveOffset);
            $this->logger->log('$possibleNote:' . $possibleNote->class . $possibleNote->octave, 'getMelodicInterval');
            
            
            
            $melodicInterval = $this->getMelodicInterval($this->CP[$numOfNotes-1], $possibleNote);
            $this->logger->log('melodicInterval:' . $melodicInterval['diatonic'], 'pickNextFSInterval');
            
            
            $lastHarmonicInterval = $this->getHarmonicIntervalBySlot($numOfNotes-1, $numOfNotes-1);
            $this->logger->log('lasthinterval: ' . $lastHarmonicInterval, 'pickNextFSInterval');
            
            if($numOfNotes >= 2){
                $secondLastHarmonicInterval = $this->getHarmonicIntervalBySlot($numOfNotes-2, $numOfNotes-2);
                $this->logger->log('slasth: ' . $secondLastHarmonicInterval, 'pickNextFSInterval');
                $lastMelodicInterval = $this->getMelodicInterval($this->CP[$numOfNotes-2], $this->CP[$numOfNotes-1]);
                
            }
            if($numOfNotes >= 3){
                $thirdLastHarmonicInterval = $this->getHarmonicIntervalBySlot($numOfNotes-3, $numOfNotes-3);
                $this->logger->log('tlasth: ' . $thirdLastHarmonicInterval, 'pickNextFSInterval');
                $secondLastMelodicInterval = $this->getMelodicInterval($this->CP[$numOfNotes-3], $this->CP[$numOfNotes-2]);
            }
            if($numOfNotes >=4 ){
                $thirdLastMelodicInterval = $this->getMelodicInterval($this->CP[$numOfNotes-4], $this->CP[$numOfNotes-3]);
            }
            
            
            
            /********************************
             * Hard failures
             ********************************/
            //avoid parallel fifths and octaves
            if($lastHarmonicInterval == 5 && $harmonicInterval == 5){
                //this is a parallel fifth, don't score just continue.
                $this->logger->log('Parallel fifths, continuing...', 'pickNextFSInterval');
                continue;
            }
            if($lastHarmonicInterval == 0 && $harmonicInterval == 8){
                //this is a parallel octave, don't score just continue.
                $this->logger->log('Parallel octaves, continuing...', 'pickNextFSInterval');
                continue;
            }
            
            if($harmonicInterval == 5 && $this->getHarmonicInterval($possibleNote, $this->CF[$numOfNotes], false) == 6){
                //this "fifth" is really a tritone, not okay.
                $this->logger->log('Fifth was diminished, continuing...', 'pickNextFSInterval');
                continue;
            }
            //check for more than three parallel 3rds or 6ths
            if(isset($thirdLastHarmonicInterval) && 
                    $thirdLastHarmonicInterval == $secondLastHarmonicInterval && 
                    $secondLastHarmonicInterval == $lastHarmonicInterval && 
                    $lastHarmonicInterval == $harmonicInterval){
                //this is four consecutive anythings(likely 3rd or 6th)
                $this->logger->log('Three consecutive parallel thirds or sixes, continuing...', 'pickNextFSInterval');
                continue;
            }
            if(abs($melodicInterval['chromatic']) == 6 || 
                    abs($melodicInterval['chromatic']) == 10 || 
                    abs($melodicInterval['chromatic']) == 11){
                //these are dissonant melodic intervals, fail
                $this->logger->log('Dissonant melodic interval, continuing...', 'pickNextFSInterval');
                continue;
            }
            //if the previous melodic interval is a leap it must resolve by step
            if(abs($lastMelodicInterval['diatonic']) > 3){
                
                if($lastMelodicInterval['diatonic'] < 0 ){
                    if($melodicInterval['diatonic'] == 2){
                        $intervalScore += 4;
                    }else{
                        $this->logger->log('Leap not resolved, continuing...', 'pickNextFSInterval');
                        continue;
                    }
                    
                }elseif($lastMelodicInterval['diatonic'] > 0){
                    if($melodicInterval['diatonic'] == -2){
                        $intervalScore += 4;
                    }else{
                        $this->logger->log('Leap not resolved, continuing...', 'pickNextFSInterval');
                        continue;
                    }
                    
                }
            }
            /********************************
             * End of hard failures
             ********************************/
            
            //thirds and sixes are better than fifths and octaves
            if($harmonicInterval == 3 || $harmonicInterval == 6 || $harmonicInterval == 10 || $harmonicInterval == 13){
                $intervalScore = 3;
            }
            //if this is a step, bump it's score
            if(abs($melodicInterval['diatonic']) == 2){
                $intervalScore = $intervalScore+2;
                //if the previous melodic interval is a third we can continue moving, but it's better to resolve
                if(abs($lastMelodicInterval['diatonic']) == 3){
                    if($lastMelodicInterval['diatonic'] < 0 && $melodicInterval['diatonic'] > 0){
                        $intervalScore += 4;
                    }elseif($lastMelodicInterval['diatonic'] > 0 && $melodicInterval['diatonic'] < 0){
                        $intervalScore += 4;
                    }
                }
            }
            
            //check for more than two parallel 3rds or 6ths, not critical, but kind of nice to avoid
            if(isset($secondLastHarmonicInterval) && 
                    $secondLastHarmonicInterval == $lastHarmonicInterval && 
                    $lastHarmonicInterval == $harmonicInterval){
                $intervalScore--;
            }
            
            //successive leaps, not good, but not a deal breaker.
            if(abs($lastMelodicInterval['diatonic']) >= 3 && abs($melodicInterval['diatonic'] >= 3)){
                $intervalScore--;
            }
            
            if($intervalScore > $score){
                $score = $intervalScore;
                $chosenInterval = $harmonicInterval;
            }elseif($intervalScore === $score){//if two notes are tied, pick one at random
                if(rand(-10, 9) >= 0){
                    $score = $intervalScore;
                    $chosenInterval = $harmonicInterval;
                }
            }
            
            $this->logger->log('score:' . $intervalScore, 'pickNextFSInterval');
        }
        $this->logger->log('I chose:' .$chosenInterval . '<br><br>', 'pickNextFSInterval');
        return($chosenInterval);
    }
    
    
    
    /**
     * Calculates a score, where lower is better for the generate counterpoint.
     * 100 is a failure
     * 
     * @param type $counterpoint
     */
    private function checkFSCounterpoint(){
        $score = 0;
        $deductions = array();
        $reverseNotes = array_flip($this->scale);
        
        if(count($this->CP) < count($this->CF)){
            $score = 100;
            $deductions[] = 'Counterpoint unsuccessful, unsolvable interval encountered.';
        }
        $largestNote = 0;
        $validClimax = false;
        foreach($this->CP as $note){
            if($reverseNotes[$note->class]+$note->octave*7 > $largestNote){
                $largestNote = $reverseNotes[$note->class]+$note->octave*7;
                $validClimax = true;
            }elseif($reverseNotes[$note->class]+$note->octave*7 === $largestNote){
                $validClimax = false;
            }
        }
        if(!$validClimax){
            $score = 100;
            $deductions[] = 'Counterpoint climax duplicated.';
        }
        
        return(array('score' => $score, 'deductions' => $deductions));
    }
}