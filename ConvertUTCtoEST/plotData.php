<?php
include_once 'phplot/phplot.php';

class plotData
{

    var $fileName;

    var $spikeFileName;

    var $imageFileName;

    function __construct($fileName, $spikeFileName)
    {
        $this->fileName = $fileName;
        $this->spikeFileName = $spikeFileName;
        $filepart2=substr($fileName,strrpos($fileName, "/")+1);
        $fname = substr($fileName, 0, strrpos($fileName, "/")+1) . substr($filepart2, strpos($filepart2, "_") +1, strrpos($filepart2, ".") - strpos($filepart2, "_") - 1);
        echo $fname;
        $this->imageFileName = $fname;
    }

    function createGraph($xIndex=0,$y1Index=1,$y2Index=1)
    {
        $file = fopen($this->fileName, "r");
        $file2 = fopen($this->spikeFileName, "r");
        
        // Define some data
        $read=false;
        if ($line = fgetcsv($file) and $line2 = fgetcsv($file2)){
            $read=true;
        }
        
       
        $i = 0;
        $start=1;
        while ($read) {
            $data = Array();
            while ($i < 1000 and $read) {
                if ($line = fgetcsv($file) and $line2 = fgetcsv($file2)){
                    $read=true;
                    $data[] = array(
                        $line[$xIndex],
                        $line[$y1Index],
                        $line2[$y2Index]
                    );
                    $i++;
                }
                else {
                    $read=false;
                }
               
            }
            
            $graphFile= $this->imageFileName."_".$start."-".($start+$i-1).".png";
            $title="Data from row ". $start." to ".($start+$i-1);
            $start=$start+$i;
            $i=0;
            $plot = new PHPlot(10000, 400, $graphFile);
            $plot->SetIsInline(True);
            $min = min(array_column($data, 1));
            $max = max(array_column($data, 1));
            if ($max=="nan"){
                $max=$min+5;
            }
            $legendText = array(
                'Original Data',
                'Spike Removed'
            );

            $plot->SetTitle($title);
            $plot->SetImageBorderType('plain');
            $plot->SetPlotType('lines');
            $plot->SetDataType('text-data');
            $plot->SetDataColors(array(
                'red',
                'blue'
            ));
            $plot->SetDataValues($data);
            $plot->SetPlotAreaWorld(NULL, $min - 1, NULL, $max + 1);
            $plot->SetLegend($legendText);
            $plot->SetXDataLabelAngle(90);
            $plot->SetLineStyles('solid');
            $plot->SetXTickLabelPos('none');
            $plot->SetXTickPos('none');
            // Draw it
            $plot->DrawGraph();
        }
    }
}
?>