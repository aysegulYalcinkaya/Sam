<?php

class fileToFiveMin
{

    var $infile;
    
    var $fiveMinFile;

    var $fname;
    
    var $fiveMinFileFullName;

    var $logfile;

    var $header;
    
    var $fiveMinArray;
    
    var $nextMin;
    
    function __construct($file, $outfolder)
    {
        $this->fname = substr($file, strrpos($file, "/") + 1);
        $this->fiveMinFileFullName = $outfolder . 'fiveMin_' .$this->fname;
            if ($this->infile = fopen($file, 'r')) {
               
                $this->fiveMinArray=array();
                
            } else {
                echo "Unable to open " . $file . "\n";
                exit();
            }
    
        
        if (! ($this->fiveMinFile = fopen($this->fiveMinFileFullName, 'w'))) {
            echo "Unable to open " . $this->fiveMinFileFullName . "\n";
            exit();
        }
    }
    
    function fiveMin(){
        $line = Array();
        $line = fgetcsv($this->infile);
        $this->header = implode(",", $line) . "\n";
        fwrite($this->fiveMinFile, $this->header);
        
        $line = fgetcsv($this->infile);
        $this->check5Min($line[0], $line[1]);
        while (($line = fgetcsv($this->infile)) !== FALSE) {
            
            $this->check5Min($line[0], $line[1]);
        }
        
        fclose($this->fiveMinFile);
        fclose($this->infile);
    }
    
    function check5Min($timeValue, $value)
    {
        $parsedDate = date_parse($timeValue);
        if ($parsedDate['minute'] % 5 == 0) {
            $this->fiveMinArray = Array();
            $this->setNextMin($timeValue);
            
            $nDate = $parsedDate['year'] . "-" . str_repeat("0", 2 - strlen($parsedDate['month'])) . $parsedDate['month'] . "-" . str_repeat("0", 2 - strlen($parsedDate['day'])) . $parsedDate['day'] . " " . str_repeat("0", 2 - strlen($parsedDate['hour'])) . $parsedDate['hour'] . ":" . str_repeat("0", 2 - strlen($parsedDate['minute'])) . $parsedDate['minute'] . ":00";
            $str = $nDate . "," . $value . "\n";
            fwrite($this->fiveMinFile, $str);
        } else {
            
            $countFive = count($this->fiveMinArray);
            if ($countFive == 0 and $this->nextMin == "") {
                $this->setNextMin($timeValue);
            }
            $this->fiveMinArray[$countFive]['time'] = $timeValue;
            $this->fiveMinArray[$countFive]['value'] = $value;
            if (date_parse($timeValue)['minute'] > $this->nextMin and abs(date_parse($timeValue)['minute'] - $this->nextMin) < 10) {
                $toBeWritten = Array();
                $index = count($this->fiveMinArray);
                
                $savedDate[0] = date_parse($this->fiveMinArray[$index - 2]['time']);
                $savedDate[1] = date_parse($this->fiveMinArray[$index - 1]['time']);
                $nearestMin = (intdiv($savedDate[0]['minute'], 5) * 5) + 5;
                $nearestMin2 = $nearestMin % 60;
                
                if (abs($nearestMin - $savedDate[0]['minute']) < abs($nearestMin2 - $savedDate[1]['minute'])) {
                    $toBeWritten['time'] = $this->fiveMinArray[$index - 1]['time'];
                    $toBeWritten['value'] = $this->fiveMinArray[$index - 2]['value'];
                } else {
                    if (abs($nearestMin - $savedDate[0]['minute']) > abs($nearestMin2 - $savedDate[1]['minute'])) {
                        $toBeWritten['time'] = $this->fiveMinArray[$index - 1]['time'];
                        $toBeWritten['value'] = $this->fiveMinArray[$index - 1]['value'];
                    } else {
                        if (60 - $savedDate[0]['second'] <= 60 - $savedDate[1]['second']) {
                            $toBeWritten['time'] = $this->fiveMinArray[$index - 1]['time'];
                            $toBeWritten['value'] = $this->fiveMinArray[$index - 2]['value'];
                        } else {
                            $toBeWritten['time'] = $this->fiveMinArray[$index - 1]['time'];
                            $toBeWritten['value'] = $this->fiveMinArray[$index - 1]['value'];
                        }
                    }
                }
                $this->fiveMinArray = Array();
                $parsedDate = date_parse($toBeWritten['time']);
                $nDate = $parsedDate['year'] . "-" . str_repeat("0", 2 - strlen($parsedDate['month'])) . $parsedDate['month'] . "-" . str_repeat("0", 2 - strlen($parsedDate['day'])) . $parsedDate['day'] . " " . str_repeat("0", 2 - strlen($parsedDate['hour'])) . $parsedDate['hour'] . ":" . str_repeat("0", 2 - strlen($nearestMin2)) . $nearestMin2 . ":00";
                $str = $nDate . "," . $toBeWritten['value'] . "\n";
                
                fwrite($this->fiveMinFile, $str);
                $this->setNextMin($nDate);
            }
        }
    }
    
    function setNextMin($datetime)
    {
        $parsedDate = date_parse($datetime);
        $this->nextMin = (($parsedDate['minute'] + 5) - (($parsedDate['minute'] + 5) % 5)) % 60;
    }
    
    static function mergeFiles($outfolder){
        $i = 1;
        $fileList = glob($outfolder . 'fiveMin_spikeremoved_converted*' . $i . '.csv');
        
        while (count($fileList) > 0) {
            $fiveOutFile = fopen($outfolder . 'mergedFiveMin_' . $i . '.csv', "w");
            $index = 0;
            $fiveInFile = Array();
            $headerArray = Array();
            foreach ($fileList as $fiveMinFile) {
                $fiveInFile[$index] = fopen($fiveMinFile, "r");
                $l = fgetcsv($fiveInFile[$index]);
                $headerArray[$index] = $l[1];
                $index ++;
            }
            
            $header = "created_at," . implode(",", $headerArray) . "\n";
            fwrite($fiveOutFile, $header);
            $index = 0;
            foreach ($fiveInFile as $fiveIn) {
                $line[$index] = fgetcsv($fiveIn);
                $firstDateArray[$index] = $line[$index][0];
                $index ++;
            }
            $maxDate = "";
            foreach ($firstDateArray as $firstDate) {
                if ($firstDate > $maxDate) {
                    $maxDate = $firstDate;
                }
            }
            
            $index = 0;
            
            foreach ($fiveInFile as $fiveIn) {
                
                while ($line[$index][0] < $maxDate and $line[$index] = fgetcsv($fiveIn)) {}
                $index ++;
            }
            $str = $maxDate . "," . implode(",", array_column($line, 1)) . "\n";
            fwrite($fiveOutFile, $str);
            
            $index = 0;
            
            while ($line[$index] = fgetcsv($fiveInFile[$index])) {
                for ($x = 1; $x < count($fiveInFile); $x ++) {
                    $line[$x] = fgetcsv($fiveInFile[$x]);
                }
                $str = $line[$index][0] . "," . implode(",", array_column($line, 1)) . "\n";
                fwrite($fiveOutFile, $str);
            }
            
            $i ++;
            $fileList = glob($outfolder . 'fiveMin_spikeremoved_converted*' . $i . '.csv');
            fclose($fiveOutFile);
        }
    }
}

