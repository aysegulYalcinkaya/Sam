<?php
include_once 'fileToMerge.php';
include_once 'fileToSpike.php';
include_once 'plotData.php';
include_once 'Darksky.php';
include_once 'Ecobee.php';
switch ($argc) {
    case 5:
        $folder = substr($argv[1], strlen($argv[1]) - 1) == "/" ? $argv[1] : $argv[1] . "/";
        $outfolder = $folder;
        break;
    case 6:
        $folder = substr($argv[1], strlen($argv[1]) - 1) == "/" ? $argv[1] : $argv[1] . "/";
        $outfolder = substr($argv[4], strlen($argv[4]) - 1) == "/" ? $argv[4] : $argv[4] . "/";
        break;
    default:
        echo "****************************** USAGE ********************************************************\n\n";
        echo "USAGE 1==> php merge.php <input_folder> <solar_file> <darksky_file> <ecobee_folder>\n";
        echo "USAGE 2==> php merge.php <input_folder> <solar_file> <darksky_file> <ecobee_folder> <output_folder> \n\n";
        echo "**********************************************************************************************\n";
        exit();
}
$solarFile = $argv[2];
$darkskyFile = $argv[3];
$ecobeeFolder=substr($argv[4], strlen($argv[4]) - 1) == "/" ? $argv[4] : $argv[4] . "/";


$darksky = new Darksky($darkskyFile);
$darkskyFile=$darksky->interpolateDarkSky();

$ecobee=new Ecobee($ecobeeFolder);
$ecobee->makeOneEcobee();
$ecobee->ecobeeFile=$ecobee->interpolate5MinEcobee();
$ecobee->ecobeeFile=$ecobee->interpolateEcobee();

$fileToSpike = new fileToSpike($ecobeeFolder."total_ecobee_interpolated.csv", $ecobeeFolder);
$fileToSpike->removeSpike(4);

$ecobee->ecobeeFile=fopen($fileToSpike->spikeFileFullName, "r");

$plotData=new plotData($ecobeeFolder."total_ecobee_interpolated.csv", $ecobeeFolder."spikeremoved_total_ecobee_interpolated.csv");
$plotData->createGraph(1,4,4);

$fileList = glob($folder . 'mergedFiveMin*.csv');
foreach ($fileList as $file) {

    $fileToMerge = new fileToMerge($file, $outfolder, $solarFile, $darkskyFile);

    $line = Array();
    $line = fgetcsv($fileToMerge->infile);

    $lineSolar = Array();
    $lineSolar = fgetcsv($fileToMerge->solarFile);
    
    $lineDarksky = Array();
    $lineDarksky = fgetcsv($fileToMerge->darkskyFile);
    
    $lineEcobee = Array();
    $lineEcobee = fgetcsv($ecobee->ecobeeFile);
   
    $fileToMerge->header = implode(",", $line) . "," . implode(",", array_slice($lineSolar, 1)) .",".implode(",", array_slice($lineDarksky, 1)) .",".implode(",", array_slice($lineEcobee, 2)) ."\n";
    fwrite($fileToMerge->outfile, $fileToMerge->header);

    $lineSolar = fgetcsv($fileToMerge->solarFile);
    $lineDarksky = fgetcsv($fileToMerge->darkskyFile);
    $lineEcobee = fgetcsv($ecobee->ecobeeFile);

    while ($line = fgetcsv($fileToMerge->infile)) {
        $date = $line[0];

        while ($date > $lineSolar[0]) {
            $lineSolar = fgetcsv($fileToMerge->solarFile);
        }
        
        while ($date > $lineDarksky[0]) {
            $lineDarksky = fgetcsv($fileToMerge->darkskyFile);
        }
        
        while ($date > $lineEcobee[0]." ".$lineEcobee[1]) {
            $lineEcobee = fgetcsv($ecobee->ecobeeFile);
        }
        if ($date == $lineSolar[0]) {
            $str = implode(",", $line) . "," . implode(",", array_slice($lineSolar, 1));
        } else {
            $str = implode(",", $line);
            $logStr = $line[0] . " cannot be found in Solar file\n";
            writeToLog($fileToMerge, $logStr);
        }
        if ($date == $lineDarksky[0]) {
            $str =$str . "," . implode(",", array_slice($lineDarksky, 1));
        } else {
            
            $logStr = $line[0] . " cannot be found in Darksky file\n";
            writeToLog($fileToMerge, $logStr);
        }
        
        if ($date == $lineEcobee[0]." ".$lineEcobee[1]) {
            $str =$str . "," . implode(",", array_slice($lineEcobee, 2));
        } else {
            
            $logStr = $line[0] ."--".$lineEcobee[0]." ".$lineEcobee[1]. " cannot be found in Ecobee file\n";
            writeToLog($fileToMerge, $logStr);
        }
        fwrite($fileToMerge->outfile, $str."\n");
    }

    echo $file . " merged.\n";
    fclose($fileToMerge->outfile);
    fclose($fileToMerge->infile);
    fclose($fileToMerge->solarFile);
    fclose($fileToMerge->logfile);
}

function writeToLog($fileToMerge, $str)
{
    fwrite($fileToMerge->logfile, $str);
}

?>
