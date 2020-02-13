<?php
include_once 'fileToConvert.php';
include_once 'fileToSpike.php';
include_once 'fileToFiveMin.php';
include_once 'plotData.php';

if ($argc != 3) {
    echo "****************************** USAGE ********************************************************\n\n";
    echo "USAGE 1==> php convert.php <input_folder> <output_folder> \n";
    echo "**********************************************************************************************\n";
    exit();
}

// *********** Convert Date, checkInterval and Split *****************
$from = 'UTC';

$folder = substr($argv[1], strlen($argv[1]) - 1) == "/" ? $argv[1] : $argv[1] . "/";
$outfolder = substr($argv[2], strlen($argv[2]) - 1) == "/" ? $argv[2] : $argv[2] . "/";

$fileList = glob($folder . '*.csv');
foreach ($fileList as $file) {

    $fileToConvert = new fileToConvert($file, $outfolder);

    $line = Array();
    $line = fgetcsv($fileToConvert->infile);
    $fileToConvert->header = $line[3] . "," . substr($fileToConvert->fname, 0, strpos($fileToConvert->fname, "-")) . "\n";
    fwrite($fileToConvert->outfile, $fileToConvert->header);
   
    $line = fgetcsv($fileToConvert->infile);
   
    $date = date(str_replace(" UTC", "", $line[3])); // UTC time
    date_default_timezone_set($from);
    $newDatetime = strtotime($date);
    if (date('Y-m-d H:i:s',$newDatetime)>="2019-09-29 21:05:00"){
        writeToFile($newDatetime, $line[1], $fileToConvert);
    }
    while (($line = fgetcsv($fileToConvert->infile)) !== FALSE) {

        $date = date(str_replace(" UTC", "", $line[3])); // UTC time
        date_default_timezone_set($from);
        $newDatetime = strtotime($date);
        if (date('Y-m-d H:i:s',$newDatetime)>="2019-09-29 21:05:00"){
            checkInterval($line[3], $line[1], $fileToConvert);
            
            writeToFile($newDatetime, $line[1], $fileToConvert);
        }
    }

    echo $file . " converted.\n";
    fclose($fileToConvert->outfile);
    fclose($fileToConvert->infile);
}
// ************************************************************************

// *********** Run spike removal on converted files ******************

$fileList = glob($outfolder . 'converted_*.csv');
foreach ($fileList as $file) {
    echo "Spike Remove on ".$file."\n";
    $fileToSpike = new fileToSpike($file, $outfolder);
    $fileToSpike->removeSpike(1);
    echo "Spike Removed"."\n";
    
    $plotData=new plotData($file, $fileToSpike->spikeFileFullName);
    $plotData->createGraph();
}

// ********************************************************************

// *********** Run fivemin on spike removed files ******************

$fileList = glob($outfolder . 'spikeremoved_converted*.csv');
foreach ($fileList as $file) {
    
    $fileToFiveMin = new fileToFiveMin($file, $outfolder);
    $fileToFiveMin->fiveMin();
    echo  $file ." processed. \n";
}

fileToFiveMin::mergeFiles($outfolder);

// ********************************************************************


function writeToFile($timeValue, $value, $fileToConvert)
{
    $to = 'Canada/Eastern';
    $format = 'Y-m-d H:i:s';

    $fileToConvert->prevTime = $timeValue;
    $fileToConvert->prevValue = $value;

    date_default_timezone_set($to);
    $timeValue = date($format, $timeValue);
    
    $str = $timeValue . "," . $value . "\n";

    fwrite($fileToConvert->outfile, $str);
}

function writeToLog($fileToConvert, $str)
{
    fwrite($fileToConvert->logfile, $str);
}

function checkInterval($currentTime, $currentValue, $fileToConvert)
{
    $increment = 89;
    $hourInc = 3600;
    $min = 60;
    $format = 'Y-m-d H:i:s';
    $currentTime = strtotime($currentTime);

    if ($fileToConvert->prevTime != "") {
        $diff = $currentTime - $fileToConvert->prevTime;
        if ($diff > $increment) {
            if ($diff < $hourInc) {
                writeToLog($fileToConvert, "Missing Data\n");
                $missingMin = intdiv($diff, $min);
                $valueDiff = $currentValue - $fileToConvert->prevValue;
                $valueToAdd = $valueDiff / ($missingMin + 1);
                $secToAdd = intdiv($diff, ($missingMin + 1));

                for ($i = 1; $i <= $missingMin; $i ++) {
                    $value = $fileToConvert->prevValue + $valueToAdd;
                    $timeValue = $fileToConvert->prevTime + $secToAdd;
                    writeToLog($fileToConvert, date($format, $timeValue) . "--" . $value . " added.\n");
                    writeToFile($timeValue, $value, $fileToConvert);
                }

                return;
            } else {
                writeToLog($fileToConvert, "SPLIT FILE");
                $fileToConvert->splitOutFile($currentTime, $currentValue);
                fwrite($fileToConvert->outfile, $fileToConvert->header);
                
                return;
            }
        }
    }

    return;
}


?>