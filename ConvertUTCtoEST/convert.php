<?php
include_once 'fileToConvert.php';
switch ($argc) {
    case 3:
        $spikeBias = 0.4;
        
        break;
    case 4:
        $spikeBias = $argv[3];
        
        break;
    default:
        echo "****************************** USAGE ********************************************************\n\n";
        echo "USAGE 1==> php convert.php <input_folder> <output_folder> \n";
        echo "USAGE 2==> php convert.php <input_folder> <output_folder> <spike_bias> \n\n";
        echo "**********************************************************************************************\n";
        exit();
}

$from = 'UTC';

$folder = substr($argv[1], strlen($argv[1]) - 1) == "/" ? $argv[1] : $argv[1] . "/";
$outfolder = substr($argv[2], strlen($argv[2]) - 1) == "/" ? $argv[2] : $argv[2] . "/";

$fileList = glob($folder . '*.csv');
foreach ($fileList as $file) {

    $fileToConvert = new fileToConvert($file, $outfolder);
    $fileToConvert->spikeBias = $spikeBias;
  

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

// merge fiveMinFiles
$i = 1;
$fileList = glob($outfolder . 'fiveMin_*_' . $i . '.csv');

while (count($fileList) > 0) {
    $fiveOutFile = fopen($outfolder . 'mergedFiveMin_' . $i . '.csv', "w");
    $index = 0;
    $fiveInFile = Array();
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
      
        while ($line[$index][0] < $maxDate and  $line[$index] = fgetcsv($fiveIn)) {

           
        }
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
    $fileList = glob($outfolder . 'fiveMin_*_' . $i . '.csv');
    fclose($fiveOutFile);
}

function writeToFile($timeValue, $value, $fileToConvert)
{
    $to = 'Canada/Eastern';
    $format = 'Y-m-d H:i:s';

    $fileToConvert->prevTime = $timeValue;
    $fileToConvert->prevValue = $value;

    date_default_timezone_set($to);
    $timeValue = date($format, $timeValue);
 //   checkSpike($timeValue, $value, $fileToConvert);
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
        if (! (is_numeric($value) and is_numeric($fileToConvert->prevValueSpike))) {
            echo $timeValue . "-->Not numeric " . $value . "--" . $fileToConvert->prevValueSpike . "\n";
        }
        if (abs($value-$fileToConvert->prevValueSpike) > $fileToConvert->spikeBias) {
            
            writeToLog($fileToConvert, "Spike removed at " . $timeValue . " due to bias=" . $value . "-" . $fileToConvert->prevValueSpike . "=" . ($value - $fileToConvert->prevValueSpike) . "\n");
            $value = $fileToConvert->prevValueSpike;
        }
    }
    $fileToConvert->prevValueSpike = $value;
}

function check5Min($timeValue, $value, $fileToConvert)
{
    $parsedDate = date_parse($timeValue);
    if ($parsedDate['minute'] % 5 == 0) {
        $fileToConvert->fiveMinArray=Array();
        setNextMin($fileToConvert, $timeValue);
        
        $nDate = $parsedDate['year'] . "-" . str_repeat("0", 2 - strlen($parsedDate['month'])) . $parsedDate['month'] . "-" . str_repeat("0", 2 - strlen($parsedDate['day'])) . $parsedDate['day'] . " " . str_repeat("0", 2 - strlen($parsedDate['hour'])) . $parsedDate['hour'] . ":" . str_repeat("0", 2 - strlen($parsedDate['minute'])) . $parsedDate['minute'] . ":00";
        $str = $nDate . "," . $value . "\n";
        fwrite($fileToConvert->fiveMinFile, $str);
    } else {
       
        $countFive = count($fileToConvert->fiveMinArray);
        if ($countFive==0 and $fileToConvert->nextMin==""){
            setNextMin($fileToConvert, $timeValue);
            
        }
        $fileToConvert->fiveMinArray[$countFive]['time'] = $timeValue;
        $fileToConvert->fiveMinArray[$countFive]['value'] = $value;
        if (date_parse($timeValue)['minute']>$fileToConvert->nextMin and abs(date_parse($timeValue)['minute']-$fileToConvert->nextMin)<10){
            $toBeWritten=Array();
            $index=count($fileToConvert->fiveMinArray);
            
            $savedDate[0] = date_parse($fileToConvert->fiveMinArray[$index-2]['time']);
            $savedDate[1] = date_parse($fileToConvert->fiveMinArray[$index-1]['time']);
           $nearestMin=(intdiv($savedDate[0]['minute'], 5)*5)+5;
           $nearestMin2=$nearestMin%60;
           
           if (abs($nearestMin-$savedDate[0]['minute'])<abs($nearestMin2-$savedDate[1]['minute'])){
               $toBeWritten['time']=$fileToConvert->fiveMinArray[$index-1]['time'];
               $toBeWritten['value']=$fileToConvert->fiveMinArray[$index-2]['value'];
           }
           else {
               if (abs($nearestMin-$savedDate[0]['minute'])>abs($nearestMin2-$savedDate[1]['minute'])){
                   $toBeWritten['time']=$fileToConvert->fiveMinArray[$index-1]['time'];
                   $toBeWritten['value']=$fileToConvert->fiveMinArray[$index-1]['value'];
               }
               else {
                   if (60-$savedDate[0]['second']<=60-$savedDate[1]['second']){
                       $toBeWritten['time']=$fileToConvert->fiveMinArray[$index-1]['time'];
                       $toBeWritten['value']=$fileToConvert->fiveMinArray[$index-2]['value'];
                   }
                   else {
                       $toBeWritten['time']=$fileToConvert->fiveMinArray[$index-1]['time'];
                       $toBeWritten['value']=$fileToConvert->fiveMinArray[$index-1]['value'];
                   }
               }
           }
           $fileToConvert->fiveMinArray=Array();
           $parsedDate = date_parse($toBeWritten['time']);
           $nDate = $parsedDate['year'] . "-" . str_repeat("0", 2 - strlen($parsedDate['month'])) . $parsedDate['month'] . "-" . str_repeat("0", 2 - strlen($parsedDate['day'])) . $parsedDate['day'] . " " . str_repeat("0", 2 - strlen($parsedDate['hour'])) . $parsedDate['hour'] . ":" . str_repeat("0", 2 - strlen($nearestMin2)) . $nearestMin2 . ":00";
           $str = $nDate . "," . $toBeWritten['value'] . "\n";
           fwrite($fileToConvert->fiveMinFile, $str);
           setNextMin($fileToConvert, $nDate);
        }
    }
}
function setNextMin($fileToConvert,$datetime) {
    $parsedDate = date_parse($datetime);
    $fileToConvert->nextMin=(($parsedDate['minute']+5)-(($parsedDate['minute']+5)%5))%60;
}
?>