<?php
include_once 'fileToConvert.php';
switch ($argc){
    case 3:
        $spikeRatioMax = 1.05;
        $spikeRatioMin = 0.95;
        break;
    case 5:
        $spikeRatioMin = $argv[3];
        $spikeRatioMax = $argv[4];
        break;
    default:
        echo "****************************** USAGE ********************************************************\n\n";
        echo "USAGE 1==> php convert.php <input_folder> <output_folder> \n";
        echo "USAGE 2==> php convert.php <input_folder> <output_folder> <spike_ratio_min> <spike_ratio_max> \n\n";
        echo "**********************************************************************************************\n";
        exit();
}

$from = 'UTC';

$folder = substr($argv[1], strlen($argv[1]) - 1) == "/" ? $argv[1] : $argv[1] . "/";
$outfolder = substr($argv[2], strlen($argv[2]) - 1) == "/" ? $argv[2] : $argv[2] . "/";


$fileList = glob($folder . '*.csv');
foreach ($fileList as $file) {

    $fileToConvert = new fileToConvert($file, $outfolder);
    $fileToConvert->spikeRatioMax = $spikeRatioMax;
    $fileToConvert->spikeRatioMin = $spikeRatioMin;

    $line = Array();
    $line = fgetcsv($fileToConvert->infile);
    $fileToConvert->header = $line[3] . "," . substr($fileToConvert->fname, 0, strpos($fileToConvert->fname, "-")) . "\n";
    fwrite($fileToConvert->outfile, $fileToConvert->header);
    fwrite($fileToConvert->fiveMinFile, $fileToConvert->header);

    $line = fgetcsv($fileToConvert->infile);

    $date = date(str_replace(" UTC", "", $line[3])); // UTC time
    date_default_timezone_set($from);
    $newDatetime = strtotime($date);
    writeToFile($newDatetime, $line[1], $fileToConvert);

    while (($line = fgetcsv($fileToConvert->infile)) !== FALSE) {

        checkInterval($line[3], $line[1], $fileToConvert);
        $date = date(str_replace(" UTC", "", $line[3])); // UTC time
        date_default_timezone_set($from);
        $newDatetime = strtotime($date);
        writeToFile($newDatetime, $line[1], $fileToConvert);
    }

    echo $file . " converted.\n";
    fclose($fileToConvert->outfile);
    fclose($fileToConvert->infile);
}

function writeToFile($timeValue, $value, $fileToConvert)
{
    $to = 'Canada/Eastern';
    $format = 'Y-m-d H:i:s';

    $fileToConvert->prevTime = $timeValue;
    $fileToConvert->prevValue = $value;

    date_default_timezone_set($to);
    $timeValue = date($format, $timeValue);
    checkSpike($timeValue, $value, $fileToConvert);
    check5Min($timeValue, $value, $fileToConvert);
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
                fwrite($fileToConvert->fiveMinFile, $fileToConvert->header);

                return;
            }
        }
    }

    return;
}

function checkSpike(&$timeValue, &$value, $fileToConvert)
{
    if ($fileToConvert->prevValueSpike != "") {
        if (!(is_numeric($value) and is_numeric($fileToConvert->prevValueSpike))){
            echo $timeValue."-->Not numeric ".$value ."--".$fileToConvert->prevValueSpike."\n";
        }
        if ($value / $fileToConvert->prevValueSpike > $fileToConvert->spikeRatioMax or $value / $fileToConvert->prevValueSpike < $fileToConvert->spikeRatioMin) {
            writeToLog($fileToConvert, "Spike removed at " . $timeValue . " due to ratio=" . $value ."/". $fileToConvert->prevValueSpike ."=".$value / $fileToConvert->prevValueSpike. "\n");
            $value = $fileToConvert->prevValueSpike;
        }
    }
    $fileToConvert->prevValueSpike = $value;
}

function check5Min($timeValue, $value, $fileToConvert)
{
    $parsedDate = date_parse($timeValue);
    if ($parsedDate['minute'] % 5 == 0) {
        
        $parsedDate['second']=0;
        $nDate=$parsedDate['year']."-".
            str_repeat("0", 2-strlen($parsedDate['month'])).$parsedDate['month']."-".
            str_repeat("0", 2-strlen($parsedDate['day'])).$parsedDate['day']." ".
            str_repeat("0", 2-strlen($parsedDate['hour'])).$parsedDate['hour'].":".
            str_repeat("0", 2-strlen($parsedDate['minute'])).$parsedDate['minute'].":00";
        $str = $nDate . "," . $value . "\n";
       fwrite($fileToConvert->fiveMinFile, $str);
    }
}
?>