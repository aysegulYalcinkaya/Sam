<?php

class fileToMerge
{

    var $infile;

    var $outfile;

    var $solarFile;
    
    var $darkskyFile;
    
    var $fname;

    var $outFileFullName;
    
    var $prevValue;

    var $prevTime;

    var $logfile;

    var $header;
    
    function __construct($file, $outfolder,$solar,$darksky)
    {
        $this->fname = substr($file, strrpos($file, "/") + 1);
        $this->outFileFullName = $outfolder . 'FINAL_' . substr($this->fname, 0, strpos($this->fname, ".")) . ".csv";
        $logfileName = $outfolder . 'FINAL_' . substr($this->fname, 0, strpos($this->fname, ".")) . ".log";
        
        $this->darkskyFile=$darksky;
        if ($this->outfile = fopen($this->outFileFullName, 'w')) {
            if ($this->infile = fopen($file, 'r')) {
                $this->logfile = fopen($logfileName, 'w');
               
            } else {
                echo "Unable to open " . $file . "\n";
                exit();
            }
        } else {
            echo "Unable to open " . $this->outFileFullName . "\n";
            exit();
        }
        if ($this->solarFile=fopen($solar, "r")){
            
        }
        else {
            echo "Unable to open " . $solar . "\n";
            exit();
        }
        
    }
    

}

