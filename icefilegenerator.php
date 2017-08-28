<?php
/******************************
 *  ICE FILE GENERATOR FOR LOU SHEPPARD'S POLAR PROJECT
 */

define('NSIDC_SERVER','sidads.colorado.edu');
define('NORTH_POLE_DATA_PATH','DATASETS/NOAA/G02135/north/daily/geotiff');
define('SOUTH_POLE_DATA_PATH','DATASETS/NOAA/G02135/south/daily/geotiff');
define('NORTH_POLE_FILE_PREFIX','N_');
define('SOUTH_POLE_FILE_PREFIX','S_');
define('EXTENT_SUFFIX','_extent_v2.1.tif');
define('POTRACE_PATH','/home3/nolan/public_html/cgi-bin/potrace/potrace');
define('CONCENTRATION_SUFFIX','_concentration_v2.1.tif');
define('STARTING_YEAR',1990); //what year do you want to start collecting data?
define('ICE_FILES_PATH','icefiles');
define('REGENERATE_FILES',true);




class PolarProject
{



    public static function checkForIceFiles() {
        if (REGENERATE_FILES) { //are we going to regenerate the whole thing from scratch -- if there is a significant parameter or programming change we may want to
            exec('rm -rf icefiles'); //recursively delete the entire icefiles direectory
            mkdir('icefiles'); //recreate it
        }
        $tmpfirstyear = 2017;
        $tmpfirstmonth = 8;
        $tmpfirstday = 26;
        $yesterday = time() - (48 * 60 * 60); // get the time 2 days ago -- this is the last day we want to grab;
        $lastYear = intval(date('Y',$yesterday)); // get the last year based on $yesterday
        $lastMonth = intval(date('m',$yesterday)); // get the last month based on $yesterday
        $lastDay = intval(date('d',$yesterday)); // get the last day based on $yesterday
        for ($y=$tmpfirstyear;$y<=$lastYear;$y++) { // iterate over the years starting with STARTING_YEAR
            $lastMonthInYear = ($y==$lastYear) ? $lastMonth : 12; //last month is usually December but on the current year it's whatever $yesterday's month was since we end at $yesterday
            if (!file_exists(ICE_FILES_PATH."/".$y)) mkdir(ICE_FILES_PATH."/".$y); // create a directory for the year if it doesn't exist
            for ($m=$tmpfirstmonth;$m<=$lastMonthInYear;$m++) { //iterate over the months in the year from the outer loop
                $lastDayInMonth = (($y==$lastYear) && ($m==$lastMonthInYear)) ? $lastDay : cal_days_in_month(CAL_GREGORIAN,$m,$y); //last day of the month is either the last day of this calendar month or $yesterday's day if we've come up to the present
                if (!file_exists(ICE_FILES_PATH."/".$y."/".$m)) mkdir(ICE_FILES_PATH."/".$y."/".$m); // create a directory for the month if it doesn't exist
                for ($d=$tmpfirstday;$d<=$lastDayInMonth;$d++) { //iterate over the days in the month from the outer loop
                    if (!file_exists("./".ICE_FILES_PATH."/".$y."/".$m."/north_".$y."-".$m."-".$d.".json")) self::getTIFFs($y,$m,$d); // check to see if the JSON file exists for this day, if not, go get the TIFFs from NSIDC
                }
            }
        }
    }

    private static function getTIFFs($y,$m,$d) { //function to get TIFFs from NSIDC
        $month_dir = str_pad(strval($m),2,"0",STR_PAD_LEFT)."_".date("M",(strtotime($y."-".$m."-".$d))); //remote month dir on NSIDC server: pad the numerical month with one zero and append an undedrscore and month 3 letter
        $tmpNPExtentTiff = tempnam("tmp", "extent-"); //temporary file name for the north pole extent tiff
        $tmpNPDensityTiff = tempnam("tmp", "density-"); //temporary file name for the north pole density tiff
        $tmpSPExtentTiff = tempnam("tmp", "extent-"); //temporary file name for the south pole extent tiff
        $tmpSPDensityTiff = tempnam("tmp", "density-"); //temporary file name for the south pole density tiff
        $tmpNPExtentHandle = fopen($tmpNPExtentTiff,"w"); //create a file handle for the temporary north pole extent TIFF
        $tmpNPDensityHandle = fopen($tmpNPDensityTiff,"w"); //create a file handle for the temporary north pole density TIFF
        $tmpSPExtentHandle = fopen($tmpSPExtentTiff,"w"); //create a file handle for the temporary south pole extent TIFF
        $tmpSPDensityHandle = fopen($tmpSPDensityTiff,"w"); //create a file handle for the temporary south pole density TIFF
        $ftpNPExtentURL = "ftp://".NSIDC_SERVER."/".NORTH_POLE_DATA_PATH."/".$y."/".$month_dir."/".NORTH_POLE_FILE_PREFIX.$y.str_pad(strval($m),2,"0",STR_PAD_LEFT).str_pad(strval($d),2,"0",STR_PAD_LEFT).EXTENT_SUFFIX;
        $ftpNPDensityURL = "ftp://".NSIDC_SERVER."/".NORTH_POLE_DATA_PATH."/".$y."/".$month_dir."/".NORTH_POLE_FILE_PREFIX.$y.str_pad(strval($m),2,"0",STR_PAD_LEFT).str_pad(strval($d),2,"0",STR_PAD_LEFT).CONCENTRATION_SUFFIX;
        $ftpSPExtentURL = "ftp://".NSIDC_SERVER."/".SOUTH_POLE_DATA_PATH."/".$y."/".$month_dir."/".SOUTH_POLE_FILE_PREFIX.$y.str_pad(strval($m),2,"0",STR_PAD_LEFT).str_pad(strval($d),2,"0",STR_PAD_LEFT).EXTENT_SUFFIX;
        $ftpSPDensityURL = "ftp://".NSIDC_SERVER."/".SOUTH_POLE_DATA_PATH."/".$y."/".$month_dir."/".SOUTH_POLE_FILE_PREFIX.$y.str_pad(strval($m),2,"0",STR_PAD_LEFT).str_pad(strval($d),2,"0",STR_PAD_LEFT).CONCENTRATION_SUFFIX;
        $curl_north_extent = curl_init(); //initialize CURL for North Pole extent
        curl_setopt($curl_north_extent, CURLOPT_URL, $ftpNPExtentURL); //tell curl to use the URL generated from above
        curl_setopt($curl_north_extent, CURLOPT_RETURNTRANSFER, 1); // we return the transfer instead of spitting it out
        curl_setopt($curl_north_extent, CURLOPT_FILE,  $tmpNPExtentHandle); //stick what we get from CURL into this temp file
        curl_exec($curl_north_extent); //run CURL
        curl_close($curl_north_extent); //finish CURL
        fclose($tmpNPExtentHandle); //close the file handle
        $curl_south_extent = curl_init(); //initialize CURL for South Pole extent
        curl_setopt($curl_south_extent, CURLOPT_URL, $ftpSPExtentURL); //tell curl to use the URL generated from above
        curl_setopt($curl_south_extent, CURLOPT_RETURNTRANSFER, 1); // we return the transfer instead of spitting it out
        curl_setopt($curl_south_extent, CURLOPT_FILE,  $tmpSPExtentHandle); //stick what we get from CURL into this temp file
        curl_exec($curl_south_extent); //run CURL
        curl_close($curl_south_extent); //finish CURL
        fclose($tmpSPExtentHandle); //close the file handle
        $curl_north_density = curl_init(); //initialize CURL for North Pole density
        curl_setopt($curl_north_density, CURLOPT_URL, $ftpNPDensityURL); //tell curl to use the URL generated from above
        curl_setopt($curl_north_density, CURLOPT_RETURNTRANSFER, 1); // we return the transfer instead of spitting it out
        curl_setopt($curl_north_density, CURLOPT_FILE,  $tmpNPDensityHandle); //stick what we get from CURL into this temp file
        curl_exec($curl_north_density); //run CURL
        curl_close($curl_north_density); //finish CURL
        fclose($tmpNPDensityHandle); //close the file handle
        $curl_south_density = curl_init(); //initialize CURL for South Pole Density
        curl_setopt($curl_south_density, CURLOPT_URL, $ftpSPDensityURL); //tell curl to use the URL generated from above
        curl_setopt($curl_south_density, CURLOPT_RETURNTRANSFER, 1); // we return the transfer instead of spitting it out
        curl_setopt($curl_south_density, CURLOPT_FILE,  $tmpSPDensityHandle); //stick what we get from CURL into this temp file
        curl_exec($curl_south_density); //run CURL
        curl_close($curl_south_density); //finish CURL
        fclose($tmpSPDensityHandle); //close the file handle
        $northFileOutputName = "./".ICE_FILES_PATH."/".$y."/".$m."/north_".$y."-".$m."-".$d;
        $southFileOutputName = "./".ICE_FILES_PATH."/".$y."/".$m."/south_".$y."-".$m."-".$d;
        self::convertFiles($tmpNPExtentTiff,$tmpNPDensityTiff, $northFileOutputName,true); //convert the north pole extent and density files
        unlink($tmpNPExtentTiff); //delete temp TIFF files -- we no longer need them
        unlink($tmpNPDensityTiff); //delete temp TIFF files -- we no longer need them
        unlink($tmpSPExtentTiff); //delete temp TIFF files -- we no longer need them
        unlink($tmpSPDensityTiff); //delete temp TIFF files -- we no longer need them

    }

    private static function convertFiles($extentTIFF,$densityTIFF, $outputName, $isNorth) {
        if ((filesize($extentTIFF) > 0) && (filesize($densityTIFF) > 0)) {
            $tmpExtentBMP = tempnam("tmp", "densityBMP-");
            $extentImg = new Imagick($extentTIFF);
            $extentImg->transformImageColorspace(Imagick::COLORSPACE_RGB);
            $icetarget = 'rgba(255,255,255,1.0)';
            $nonicefill = 'black';
            $extentImg->opaquePaintImage($icetarget,$nonicefill,0,true);
            $extentImg->negateImage(false);
            $extentImg->setImageFormat('bmp');
            $extentImg->writeImage($tmpExtentBMP);
            $potraceSVGCommand = POTRACE_PATH." ".$tmpExtentBMP." -b svg -a 0 -o ".$outputName.".svg";
            exec($potraceSVGCommand);
            unlink($tmpExtentBMP);
        }
    }

}

PolarProject::checkForIceFiles();