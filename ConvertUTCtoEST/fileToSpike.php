<?php

class fileToSpike
{

    var $infile;

    var $spikeFile;

    var $fname;

    var $spikeFileFullName;

    var $logfile;

    var $header;

    function __construct($file, $outfolder)
    {
        $this->fname = substr($file, strrpos($file, "/") + 1);
        $this->spikeFileFullName = $outfolder . 'spikeremoved_' . $this->fname;
        if ($this->infile = fopen($file, 'r')) {} else {
            echo "Unable to open " . $file . "\n";
            exit();
        }

        if (! ($this->spikeFile = fopen($this->spikeFileFullName, 'w'))) {
            echo "Unable to open " . $this->spikeFileFullName . "\n";
            exit();
        }
        if (! ($this->logfile = fopen($this->spikeFileFullName.".log", 'w'))) {
            echo "Unable to open " . $this->spikeFileFullName.".log" . "\n";
            exit();
        }
    }
    
    function removeSpike($index){
        $line = Array();
        $line = fgetcsv($this->infile);
        $this->header = implode(",", $line) . "\n";
        fwrite($this->spikeFile, $this->header);
        
        $windowSize=50;
        $i=0;
        $window=Array();
        while ($line = fgetcsv($this->infile)){
            if ($i<$windowSize){
                $window[]=$line;
                $i++;
            }
            else {
                $i=0;
                $this->writeToLog("Spike Removal from ".$window[0][0]." to ". $window[$windowSize-1][0]."\n");
                $this->deSpike($window,$index);
                $this->writeToFile($window);
                $window=Array();
                $window[]=$line;
                $i++;
            }
        }
        $this->deSpike($window,$index);
        $this->writeToFile($window);
        
        fclose($this->infile);
        fclose($this->spikeFile);
    }
    
    function deSpike(& $window, $index)
    {
        $x = 1;
        $test = 0;
        
        while ($x == 1 and $test < 1500) {
            $x = 0;
            $test ++;
            $windowCol = array_column($window, $index);
            
            $mean = array_sum($windowCol) / count($windowCol);
            $stdDev = $this->Stand_Deviation($windowCol);
            $this->writeToLog("Mean: ".$mean." StdDev: ".$stdDev."\n");
            $i = 0;
            
            foreach ($window as $row) {
                if (abs($row[$index] - $mean) > 1.5 * $stdDev) 
                {
                    $x = 1;
                   
                    if ($i > 0) {
                        if ($i < count($windowCol)-1) {
                            $newValue=($window[$i - 1][$index] + $window[$i + 1][$index]) / 2;
                            $this->writeToLog($window[$i][$index] ."is set to " .$newValue."\n" );
                            $window[$i][$index] = $newValue;
                            
                        }
                        else {
                            $this->writeToLog($window[$i][$index] ."is set to " .$window[$i - 1][$index]."\n" );
                            $window[$i][$index] = $window[$i - 1][$index];
                        }
                    }
                    else {
                        $this->writeToLog($window[$i][$index] ."is set to " .$window[$i + 1][$index]."\n" );
                        $window[$i][$index] = $window[$i + 1][$index];
                    }
                    
                }
                $i++;
                
            }
        }
    }

    function Stand_Deviation($arr)
    {
        $num_of_elements = count($arr);

        $variance = 0.0;

        // calculating mean using array_sum() method
        $average = array_sum($arr) / $num_of_elements;

        foreach ($arr as $i) {
            // sum of squares of differences between
            // all numbers and means.
            $variance += pow(($i - $average), 2);
        }

        return (float) sqrt($variance / $num_of_elements);
    }
    function writeToFile($valuesArray) {
        foreach ($valuesArray as $values) {
            fwrite($this->spikeFile, implode(",", $values)."\n");
        }
    }
    
    function writeToLog($str) {
        fwrite($this->logfile, $str);
    }
}

