<?php

class Ecobee
{

    var $ecobeeFile;

    var $ecobeeInterpolatedFile;

    var $interpolated;

    var $ecobeeInterpolated5MinFile;

    var $interpolated5Min;

    var $ecobeeFolder;

    function __construct($ecobeeFolder)
    {
        $this->ecobeeFolder = $ecobeeFolder;
        $this->ecobeeFile = fopen($ecobeeFolder . "total_ecobee.csv", "w");
    }

    function makeOneEcobee()
    {
        $fileList = glob($this->ecobeeFolder . 'report*.csv');
        $header = true;
        foreach ($fileList as $file) {
            $handle = fopen($file, "r");
            while ($line = fgetcsv($handle)) {
                if ($line[0] == "Date") {
                    if ($header) {
                        fwrite($this->ecobeeFile, implode(",", array_slice($line, 0,4)).",".implode(",", array_slice($line, 8,2)) .",".implode(",", array_slice($line, 13,2))  . "\n");
                        $header = false;
                    }
                    break;
                }
            }
            while ($line = fgetcsv($handle)) {
                $date=$line[0]." ".$line[1];
                if ($date>="2019-09-29 21:05:00"){
                    fwrite($this->ecobeeFile, implode(",", array_slice($line, 0,4)).",".implode(",", array_slice($line, 8,2)) .",".implode(",", array_slice($line, 13,2)) . "\n");
                }
            }
            fclose($handle);
        }

        fclose($this->ecobeeFile);

        $this->ecobeeFile = fopen($this->ecobeeFolder . "total_ecobee.csv", "r");
    }

    function interpolate5MinEcobee()
    {
        $this->interpolated5Min = $this->ecobeeFolder . "total_ecobee_5Mininterpolated.csv";
        $this->ecobeeInterpolated5MinFile = fopen($this->interpolated5Min, "w");
        $flog = fopen($this->ecobeeFolder . "ecobee_interpolate5Min.log", "w");

        $header = fgetcsv($this->ecobeeFile);
        fwrite($this->ecobeeInterpolated5MinFile, implode(",", $header) . "\n");
        $line = fgetcsv($this->ecobeeFile);
        fwrite($this->ecobeeInterpolated5MinFile, implode(",", $line) . "\n");
        $linePrev = $line;
        while ($line = fgetcsv($this->ecobeeFile)) {

            $diff = strtotime($line[0] . " " . $line[1]) - strtotime($linePrev[0] . " " . $linePrev[1]);
            if ($diff != 300) {
                $interval = $diff / 300;
                $valueToAdd = Array();
                $valueToAdd[0] = 5;

                $parsedDate = date_parse(($linePrev[0] . " " . $linePrev[1]));

                for ($i = 2; $i < count($linePrev); $i ++) {
                    switch ($i){
                        case 2:
                        case 3:
                        case 4:
                            $valueToAdd[] = $linePrev[$i];
                            break;
                        case 5:
                            $valueToAdd[] = ($line[$i] - $linePrev[$i]) / $interval;
                            break;
                        case 6:
                        case 7:
                            if ($linePrev[3]!="heatOff"){
                                $valueToAdd[] = ($line[$i] - $linePrev[$i]) / $interval;
                            }
                    }
                    
                }
                for ($i = 1; $i < $interval; $i ++) {
                    $lineToWrite = Array();
                    $lineToWrite[0] = $parsedDate['year'] . "-" . str_repeat("0", 2 - strlen($parsedDate['month'])) . $parsedDate['month'] . "-" . str_repeat("0", 2 - strlen($parsedDate['day'])) . $parsedDate['day'];
                    $lineToWrite[1] = str_repeat("0", 2 - strlen($parsedDate['hour'])) . $parsedDate['hour'] . ":" . str_repeat("0", 2 - strlen($parsedDate['minute'] + $i * 5)) . ($parsedDate['minute'] + $i * 5) . ":00";
                    for ($k = 2; $k < count($linePrev); $k ++) {
                        if (is_numeric($line[$k]) && is_numeric($linePrev[$k])) {
                            $lineToWrite[] = $linePrev[$k] + ($i * $valueToAdd[$k]);
                        } else {
                            $lineToWrite[] = $valueToAdd[$k];
                        }
                    }

                    fwrite($this->ecobeeInterpolated5MinFile, implode(",", $lineToWrite) . "\n");
                    fwrite($flog, $lineToWrite[0] . " " . $lineToWrite[1] . " written to interpolated ecobee");
                }
            }
            fwrite($this->ecobeeInterpolated5MinFile, implode(",", $line) . "\n");
            $linePrev = $line;
        }
        fclose($this->ecobeeInterpolated5MinFile);
        fclose($flog);
        return (fopen($this->interpolated5Min, "r"));
    }

    function interpolateEcobee()
    {
        $this->interpolated = $this->ecobeeFolder . "total_ecobee_interpolated.csv";
        $this->ecobeeInterpolatedFile = fopen($this->interpolated, "w");
        $flog = fopen($this->ecobeeFolder . "ecobee_interpolate.log", "w");

        $header = fgetcsv($this->ecobeeFile);
        fwrite($this->ecobeeInterpolatedFile, implode(",", $header) . "\n");
        $line = fgetcsv($this->ecobeeFile);
        fwrite($this->ecobeeInterpolatedFile, implode(",", $line) . "\n");
        $linePrev = $line;
        while ($line = fgetcsv($this->ecobeeFile)) {
            $emptyLines = Array();
            if ($line[2] == "") {

                while ($line[2] == "") {
                    $emptyLines[] = $line;
                    $line = fgetcsv($this->ecobeeFile);
                }
            }
            if (count($emptyLines) > 0) {
                $x = 1;
                foreach ($emptyLines as $empty) {
                    for ($t = 2; $t < count($empty); $t ++) {
                        if ($empty[$t] == "") {
                            switch ($t){
                                case 2:
                                case 3:
                                case 4:
                                    $empty[$t] = $linePrev[$t];
                                    break;
                                case 5:
                                    $empty[$t] = $linePrev[$t] + (($line[$t] - $linePrev[$t]) / (count($emptyLines) + 1) * $x);
                                    break;
                                case 6:
                                case 7:
                                    if ($linePrev[3]!="heatOff"){
                                        $empty[$t] = $linePrev[$t] + (($line[$t] - $linePrev[$t]) / (count($emptyLines) + 1) * $x);
                                    }
                            }
                       
                        }
                    }
                    $x ++;
                    fwrite($this->ecobeeInterpolatedFile, implode(",", $empty) . "\n");
                    fwrite($flog, implode(",", $empty) . " written to interpolated ecobee\n");
                }
            }
            fwrite($this->ecobeeInterpolatedFile, implode(",", $line) . "\n");
            $linePrev = $line;
        }
        fclose($this->ecobeeInterpolatedFile);
        fclose($flog);
        return (fopen($this->interpolated, "r"));
    }
}

