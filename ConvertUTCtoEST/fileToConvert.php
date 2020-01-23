<?php

class fileToConvert
{

    var $infile;

    var $outfile;

    var $fname;

    var $outFileFullName;

    var $fiveMinFile;

    var $prevValue;

    var $prevTime;

    var $prevValueSpike;

    var $spikeRatioMax;

    var $spikeRatioMin;

    var $logfile;

    var $header;

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
            } else {
                echo "Unable to open " . $file . "\n";
                exit();
            }
        } else {
            echo "Unable to open " . $this->outFileFullName . "\n";
            exit();
        }

        $fiveMinFileFullName = $outfolder . 'fiveMin_' . substr($this->fname, 0, strpos($this->fname, ".")) . "_1.csv";
        if (! ($this->fiveMinFile = fopen($fiveMinFileFullName, 'w'))) {
            echo "Unable to open " . $fiveMinFileFullName . "\n";
            exit();
        }
    }

    function splitOutFile($currentTime, $currentValue)
    {
        fclose($this->outfile);
        $index = substr($this->outFileFullName, strrpos($this->outFileFullName, "_"), strrpos($this->outFileFullName, ".") - strrpos($this->outFileFullName, "_"));
        $index ++;
        $this->outFileFullName = substr($this->outFileFullName, 0, strrpos($this->outFileFullName, "_")) . $index . ".csv";
        if ($this->outfile = fopen($this->outFileFullName, 'w')) {
            $this->prevTime = $currentTime;
            $this->prevValue = $currentValue;
        } else {
            echo "Unable to open " . $this->outFileFullName . "\n";
            exit();
        }
    }
}

