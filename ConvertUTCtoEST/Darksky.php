<?php

class Darksky
{
    
    var $darkskyFile;
    
    var $darkskyInterpolatedFile;
    
    var $interpolated;
    
    var $flog;
    
    function __construct($darksky)
    {
        $this->interpolated =substr($darksky, 0, strpos($darksky, "."))."_interpolated.csv" ;
        if ($this->darkskyFile=fopen($darksky, "r")){
            $this->darkskyInterpolatedFile=fopen($this->interpolated,"w");
            $this->flog = fopen(substr($darksky, 0, strrpos($darksky, ".")). ".log", "w");
        }
        else {
            echo "Unable to open " . $darksky . "\n";
            exit();
        }
    }
    
    function interpolateDarkSky(){
        
        $header=fgetcsv($this->darkskyFile);
        fwrite($this->darkskyInterpolatedFile, implode(",", $header)."\n");
        $line=fgetcsv($this->darkskyFile);
        fwrite($this->darkskyInterpolatedFile, implode(",", $line)."\n");
        $linePrev=$line;
        while ($line=fgetcsv($this->darkskyFile)){
            
            $diff=strtotime($line[0])-strtotime($linePrev[0]);
            $interval=$diff/300;
            $valueToAdd=Array();
            $valueToAdd[0]=5;
            
            $parsedDate=date_parse(($linePrev[0]));
           
            for ($i=1;$i<count($linePrev);$i++){
                if (is_numeric($line[$i]) && is_numeric($linePrev[$i]) ){
                    $valueToAdd[]=($line[$i]-$linePrev[$i])/$interval;
                }
                else {
                    $valueToAdd[]=$linePrev[$i];
                }
            }
            for ($i=1;$i<$interval;$i++){
                $lineToWrite=Array();
                $lineToWrite[0]= $parsedDate['year'] . "-" . str_repeat("0", 2 - strlen($parsedDate['month'])) . $parsedDate['month'] . "-" . str_repeat("0", 2 - strlen($parsedDate['day'])) . $parsedDate['day'] . " " . str_repeat("0", 2 - strlen($parsedDate['hour'])) . $parsedDate['hour'] . ":" . str_repeat("0", 2 - strlen($parsedDate['minute']+$i*5)) . ($parsedDate['minute']+$i*5) . ":00";
                for ($k=1;$k<count($linePrev);$k++){
                    if (is_numeric($line[$k]) && is_numeric($linePrev[$k]) ){
                        $lineToWrite[]=$linePrev[$k]+($i*$valueToAdd[$k]);
                    }
                    else {
                        $lineToWrite[]=$valueToAdd[$k];
                    }
                }
                
                fwrite($this->darkskyInterpolatedFile, implode(",", $lineToWrite)."\n");
                fwrite($this->flog, implode(",", $lineToWrite) . " written to interpolated darksky\n");
            }
            
            fwrite($this->darkskyInterpolatedFile, implode(",", $line)."\n");
            $linePrev=$line;
        }
        fclose($this->darkskyInterpolatedFile);
        fclose($this->flog);
        return (fopen($this->interpolated, "r"));
    }
}

