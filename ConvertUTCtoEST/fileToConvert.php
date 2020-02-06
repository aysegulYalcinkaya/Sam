<?php

class fileToConvert
{

    var $infile;

    var $outfile;
    
    var $fiveMinFile;

    var $fname;

    var $outFileFullName;
    
    var $fiveMinFileFullName;

    var $prevValue;

    var $prevTime;

    var $prevValueSpike;

    var $spikeBias;

    var $logfile;

    var $header;
    
    var $fiveMinArray;
    
    var $nextMin;
    
    function __construct($file, $outfolder)
    {
        $this->fname = substr($file, strrpos($file, "/") + 1);
        $this->outFileFullName = $outfolder . 'converted_' . substr($this->fname, 0, strpos($this->fname, ".")) . "_1.csv";
        $logfileName = $outfolder . substr($this->fname, 0, strpos($this->fname, "-")) . ".log";
        if ($this->outfile = fopen($this->outFileFullName, 'w')) {
            if ($this->infile = fopen($file, 'r')) {
                $this->logfile = fopen($logfileName, 'w');
                $this->prevTime = "";
                $this->prevValue = "";
                $this->prevValueSpike = "";
                $this->fiveMinArray=array();
                
            } else {
                echo "Unable to open " . $file . "\n";
                exit();
            }
        } else {
            echo "Unable to open " . $this->outFileFullName . "\n";
            exit();
        }

        $this->fiveMinFileFullName = $outfolder . 'fiveMin_' . substr($this->fname, 0, strpos($this->fname, ".")) . "_1.csv";
        if (! ($this->fiveMinFile = fopen($this->fiveMinFileFullName, 'w'))) {
            echo "Unable to open " . $this->fiveMinFileFullName . "\n";
            exit();
        }
    }

    function splitOutFile($currentTime, $currentValue)
    {
        fclose($this->outfile);
        $index = substr($this->outFileFullName, strrpos($this->outFileFullName, "_"), strrpos($this->outFileFullName, ".") - strrpos($this->outFileFullName, "_"));
        $index ++;
        $this->outFileFullName = substr($this->outFileFullName, 0, strrpos($this->outFileFullName, "_")) . $index . ".csv";
        $this->fiveMinFileFullName = substr($this->fiveMinFileFullName, 0, strrpos($this->fiveMinFileFullName, "_")) . $index . ".csv";
        if ($this->outfile = fopen($this->outFileFullName, 'w')) {
            $this->prevTime = $currentTime;
            $this->prevValue = $currentValue;
        } else {
            echo "Unable to open " . $this->outFileFullName . "\n";
            exit();
        }
        if ($this->fiveMinFile= fopen($this->fiveMinFileFullName, 'w')) {
            
        } else {
            echo "Unable to open " . $this->fiveMinFileFullName . "\n";
            exit();
        }
    }
}

