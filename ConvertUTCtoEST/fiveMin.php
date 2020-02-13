<?php
include_once 'fileToFiveMin.php';
if ($argc != 3) {
    echo "****************************** USAGE ********************************************************\n\n";
    echo "USAGE 1==> php fiveMin.php <input_folder> <output_folder> \n";

    echo "**********************************************************************************************\n";
    exit();
}


$folder = substr($argv[1], strlen($argv[1]) - 1) == "/" ? $argv[1] : $argv[1] . "/";
$outfolder = substr($argv[2], strlen($argv[2]) - 1) == "/" ? $argv[2] : $argv[2] . "/";

$fileList = glob($folder . 'spike_converted*.csv');
foreach ($fileList as $file) {

    $fileToFiveMin = new fileToFiveMin($file, $outfolder);
    $line = Array();
    $line = fgetcsv($fileToFiveMin->infile);
    $fileToFiveMin->header = implode(",", $line) . "\n";
    fwrite($fileToFiveMin->fiveMinFile, $fileToFiveMin->header);

    $line = fgetcsv($fileToFiveMin->infile);
    check5Min($line[0], $line[1], $fileToFiveMin);
    while (($line = fgetcsv($fileToFiveMin->infile)) !== FALSE) {
        check5Min($line[0], $line[1], $fileToFiveMin);
    }

    echo $file . " fiveMined.\n";

    fclose($fileToFiveMin->fiveMinFile);
    fclose($fileToFiveMin->infile);
}
// merge fiveMinFiles
$i = 1;
$fileList = glob($outfolder . 'fiveMin_spike_converted*' . $i . '.csv');

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
    $fileList = glob($outfolder . 'fiveMin_spike_converted*' . $i . '.csv');
    fclose($fiveOutFile);
}

function check5Min($timeValue, $value, $fileToFiveMin)
{
    $parsedDate = date_parse($timeValue);
    if ($parsedDate['minute'] % 5 == 0) {
        $fileToFiveMin->fiveMinArray = Array();
        setNextMin($fileToFiveMin, $timeValue);

        $nDate = $parsedDate['year'] . "-" . str_repeat("0", 2 - strlen($parsedDate['month'])) . $parsedDate['month'] . "-" . str_repeat("0", 2 - strlen($parsedDate['day'])) . $parsedDate['day'] . " " . str_repeat("0", 2 - strlen($parsedDate['hour'])) . $parsedDate['hour'] . ":" . str_repeat("0", 2 - strlen($parsedDate['minute'])) . $parsedDate['minute'] . ":00";
        $str = $nDate . "," . $value . "\n";
        fwrite($fileToFiveMin->fiveMinFile, $str);
    } else {

        $countFive = count($fileToFiveMin->fiveMinArray);
        if ($countFive == 0 and $fileToFiveMin->nextMin == "") {
            setNextMin($fileToFiveMin, $timeValue);
        }
        $fileToFiveMin->fiveMinArray[$countFive]['time'] = $timeValue;
        $fileToFiveMin->fiveMinArray[$countFive]['value'] = $value;
        if (date_parse($timeValue)['minute'] > $fileToFiveMin->nextMin and abs(date_parse($timeValue)['minute'] - $fileToFiveMin->nextMin) < 10) {
            $toBeWritten = Array();
            $index = count($fileToFiveMin->fiveMinArray);

            $savedDate[0] = date_parse($fileToFiveMin->fiveMinArray[$index - 2]['time']);
            $savedDate[1] = date_parse($fileToFiveMin->fiveMinArray[$index - 1]['time']);
            $nearestMin = (intdiv($savedDate[0]['minute'], 5) * 5) + 5;
            $nearestMin2 = $nearestMin % 60;

            if (abs($nearestMin - $savedDate[0]['minute']) < abs($nearestMin2 - $savedDate[1]['minute'])) {
                $toBeWritten['time'] = $fileToFiveMin->fiveMinArray[$index - 1]['time'];
                $toBeWritten['value'] = $fileToFiveMin->fiveMinArray[$index - 2]['value'];
            } else {
                if (abs($nearestMin - $savedDate[0]['minute']) > abs($nearestMin2 - $savedDate[1]['minute'])) {
                    $toBeWritten['time'] = $fileToFiveMin->fiveMinArray[$index - 1]['time'];
                    $toBeWritten['value'] = $fileToFiveMin->fiveMinArray[$index - 1]['value'];
                } else {
                    if (60 - $savedDate[0]['second'] <= 60 - $savedDate[1]['second']) {
                        $toBeWritten['time'] = $fileToFiveMin->fiveMinArray[$index - 1]['time'];
                        $toBeWritten['value'] = $fileToFiveMin->fiveMinArray[$index - 2]['value'];
                    } else {
                        $toBeWritten['time'] = $fileToFiveMin->fiveMinArray[$index - 1]['time'];
                        $toBeWritten['value'] = $fileToFiveMin->fiveMinArray[$index - 1]['value'];
                    }
                }
            }
            $fileToFiveMin->fiveMinArray = Array();
            $parsedDate = date_parse($toBeWritten['time']);
            $nDate = $parsedDate['year'] . "-" . str_repeat("0", 2 - strlen($parsedDate['month'])) . $parsedDate['month'] . "-" . str_repeat("0", 2 - strlen($parsedDate['day'])) . $parsedDate['day'] . " " . str_repeat("0", 2 - strlen($parsedDate['hour'])) . $parsedDate['hour'] . ":" . str_repeat("0", 2 - strlen($nearestMin2)) . $nearestMin2 . ":00";
            $str = $nDate . "," . $toBeWritten['value'] . "\n";
            fwrite($fileToFiveMin->fiveMinFile, $str);
            setNextMin($fileToFiveMin, $nDate);
        }
    }
}

function setNextMin($fileToFiveMin, $datetime)
{
    $parsedDate = date_parse($datetime);
    $fileToFiveMin->nextMin = (($parsedDate['minute'] + 5) - (($parsedDate['minute'] + 5) % 5)) % 60;
}
