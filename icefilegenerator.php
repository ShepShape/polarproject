<?php
/******************************
 *  ICE FILE GENERATOR FOR LOU SHEPPARD'S POLAR PROJECT
 *  Any questions? -  kennylozowski@gmail.com
 */
ini_set('MAX_EXECUTION_TIME', -1);
ini_set('MAX_INPUT_TIME', -1);
set_time_limit(0);


define('NSIDC_SERVER','sidads.colorado.edu'); //hostname of the NSIDC FTP server
define('NORTH_POLE_DATA_PATH','DATASETS/NOAA/G02135/north/daily/geotiff'); //path to north pole TIFF files
define('SOUTH_POLE_DATA_PATH','DATASETS/NOAA/G02135/south/daily/geotiff'); //path to south pole TIFF files
define('NORTH_POLE_FILE_PREFIX','N_'); //prefix for north pole TIFF files before date
define('SOUTH_POLE_FILE_PREFIX','S_'); //prefix for south pole TIFF files before date
define('EXTENT_SUFFIX','_extent_v3.0.tif'); //suffix for extent TIFF files
define('CONCENTRATION_SUFFIX','_concentration_v3.0.tif'); //suffix for concentration TIFF files
define('BASE_PATH','/home/loursheppard/polarregions.net/installation/'); //base path
define('POTRACE_PATH','potrace/potrace'); //path to potrace binary file
define('STARTING_YEAR',1990); //what year do you want to start collecting data?
define('MIDI_PPQ',480); //default pulses per quarter note of .mid file generation
define('BASE_VELOCITY',25); //lowest possible velocity setting
define('ICE_FILES_PATH','icefiles'); //path to ice file hierarchy, included density image (JPEG), extent SVG, .mid files and .json files
define('REGENERATE_FILES',false); // should we regenerate all files each time or only generate files that we don't already have?
define('MAXIMUM_FILE_COUNT',8); // maximum file count to prevent a bug in imagick
define('ONE_DAY_ONLY',false); //should we generate one set of files only? this is good for testing or if you want to go fast
define('POINT_DEBUG',false); //individual point debug info
define('SHOW_SKIPPED_FILES',false); //show which files are getting skipped
require(BASE_PATH.'php/midi/midi.class.php'); //include the MIDI library for .mid file generation

$GLOBALS["northPoleMIDIParams"] = (object) [
    whichPole => "north", //indicates which pole we're dealing with
    minNote => 24, //lowest allowable MIDI note
    maxNote => 96, //highest allowable MIDI note
    scaleLength => 12, //how long do we want our scale to be? 12 is a normal octacve
    centreReference => new Point(152,224), //centre reference point in the image, if you want to move the pole and have another point from which to generate polar coordinates ,put it here
    scaleNotes => array(1,2,4,5,7,9,11), //notes in the scale we used
    lengthInSeconds => 900, //since the generated piece will always be '360 degrees' long, we determine how long that should be in seconds
    tempoBPM => 60, //tempo we are using
    tempoLengths => array(0.25,0.5,1,2) //available tempo lengths
];

$GLOBALS["southPoleMIDIParams"] = (object) [
    whichPole => "south", //indicates which pole we're dealing with
    minNote => 24, //lowest allowable MIDI note
    maxNote => 96, //highest allowable MIDI note
    scaleLength => 12, //how long do we want our scale to be? 12 is a normal octacve
    centreReference => new Point(152,224), //centre reference point in the image, if you want to move the pole and have another point from which to generate polar coordinates ,put it here
    scaleNotes => array(1,2,4,5,7,9,11), //notes in the scale we used
    lengthInSeconds => 900, //since the generated piece will always be '360 degrees' long, we determine how long that should be in seconds
    tempoBPM => 60, //tempo we are using
    tempoLengths => array(0.25,0.5,1,2) //available tempo lengths
];


class PolarProject
{

    public static function checkForIceFiles() { //function that goes through all the files on the remote NSIDC server to get them
        $fileCount = 0; //set a file count to exit to prevent a bug
        print "\n\nBeginning file run at ".date("Y-m-d h:i:sa")."\n";
        if (REGENERATE_FILES) { //are we going to regenerate the whole thing from scratch -- if there is a significant parameter or programming change we may want to -- caution, this can take a month!
            exec('rm -rf '.BASE_PATH.'icefiles'); //recursively delete the entire icefiles direectory
            mkdir(BASE_PATH.'icefiles'); //recreate it
        }
        $yesterday = time() - (48 * 60 * 60); // get the time 2 days ago -- this is the last day we want to grab;
        $lastYear = intval(date('Y',$yesterday)); // get the last year based on $yesterday
        $lastMonth = intval(date('m',$yesterday)); // get the last month based on $yesterday
        $lastDay = intval(date('d',$yesterday)); // get the last day based on $yesterday
        $firstYear = (ONE_DAY_ONLY) ? $lastYear : STARTING_YEAR; //first year should be the starting year constant unless we go for one day only in which case it's same as last day
        $firstMonth = (ONE_DAY_ONLY) ? $lastMonth : 1; //first month should be Jan. of  starting year constant unless we go for one day only in which case it's same as last day
        $firstDay = (ONE_DAY_ONLY) ? $lastDay : 1; //first day should be Jan 1 of  starting year constant unless we go for one day only in which case it's same as last day
        for ($y=$firstYear;$y<=$lastYear;$y++) { // iterate over the years starting with STARTING_YEAR
            $lastMonthInYear = ($y==$lastYear) ? $lastMonth : 12; //last month is usually December but on the current year it's whatever $yesterday's month was since we end at $yesterday
            if (!file_exists(BASE_PATH.ICE_FILES_PATH."/".$y)) mkdir(BASE_PATH.ICE_FILES_PATH."/".$y); // create a directory for the year if it doesn't exist
            for ($m=$firstMonth;$m<=$lastMonthInYear;$m++) { //iterate over the months in the year from the outer loop
                $lastDayInMonth = (($y==$lastYear) && ($m==$lastMonthInYear)) ? $lastDay : cal_days_in_month(CAL_GREGORIAN,$m,$y); //last day of the month is either the last day of this calendar month or $yesterday's day if we've come up to the present
                if (!file_exists(BASE_PATH.ICE_FILES_PATH."/".$y."/".$m)) mkdir(BASE_PATH.ICE_FILES_PATH."/".$y."/".$m); // create a directory for the month if it doesn't exist
                for ($d=$firstDay;$d<=$lastDayInMonth;$d++) { //iterate over the days in the month from the outer loop
                    if (!file_exists(BASE_PATH.ICE_FILES_PATH."/".$y."/".$m."/".$y."-".$m."-".$d."_south.json")) {
                        if (POINT_DEBUG) print "Processing";
                        $startTime = time();
                        print "Starting ".$y."-".$m."-".$d."...";
                        self::getTIFFs($y, $m, $d); // check to see if the JSON file exists for this day, if not, go get the TIFFs from NSIDC
                        $endTime = time();
                        if (POINT_DEBUG) print "\n";
                        print "Converted ".$y."-".$m."-".$d." in ".($endTime-$startTime)." seconds.\n";
                        $fileCount++;
                    } else {
                        if (SHOW_SKIPPED_FILES) print "Skipping ".$y."-".$m."-".$d." because files exist.\n";
                    }
                    if ($fileCount > MAXIMUM_FILE_COUNT) {
                        print "Reached maximum file count to avoid imagick bug -- try a restart!";
                        exit;
                    }
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
        $northFileOutputName = BASE_PATH.ICE_FILES_PATH."/".$y."/".$m."/".$y."-".$m."-".$d."_north"; //north file naming convention - same for density image, extent SVG, JSON and .MID file
        $southFileOutputName = BASE_PATH.ICE_FILES_PATH."/".$y."/".$m."/".$y."-".$m."-".$d."_south"; // south file naming convention
        self::convertImageFiles($tmpNPExtentTiff,$tmpNPDensityTiff, $northFileOutputName,true); //convert the north pole extent and density files
        self::convertImageFiles($tmpSPExtentTiff,$tmpSPDensityTiff, $southFileOutputName,false); //convert the south pole extent and density files
        unlink($tmpNPExtentTiff); //delete temp TIFF files -- we no longer need them
        unlink($tmpNPDensityTiff); //delete temp TIFF files -- we no longer need them
        unlink($tmpSPExtentTiff); //delete temp TIFF files -- we no longer need them
        unlink($tmpSPDensityTiff); //delete temp TIFF files -- we no longer need them

    }

    private static function convertImageFiles($extentTIFF,$densityTIFF, $outputName, $isNorth) { //this function converts image files from temporary TIFFs to PNG & SVG/GeoJSON as necessary
        if ((filesize($extentTIFF) > 0) && (filesize($densityTIFF) > 0)) { //only do any of this if we have successfully gotten TIFFs -- ie out temporary TIFF files are bigger than size 0
            $tmpExtentBMP = tempnam("tmp", "densityBMP-"); // temporary BMP for potrace, it requires this image format
            $tmpGeoJSON =  tempnam("tmp", "geoJSON-"); // temporary GeoJSON file, we won't store it
            $extentImg = new Imagick($extentTIFF); //an imagemagick object representing our extent TIFF
            $extentImg->setResourceLimit(imagick::RESOURCETYPE_MEMORY, 256); //constants for imagemagick setup
            $extentImg->setResourceLimit(imagick::RESOURCETYPE_MAP, 256);
            $extentImg->setResourceLimit(imagick::RESOURCETYPE_AREA, 1512);
            $extentImg->setResourceLimit(imagick::RESOURCETYPE_FILE, 768);
            $extentImg->setResourceLimit(imagick::RESOURCETYPE_DISK, -1);
            $extentImg->transformImageColorspace(Imagick::COLORSPACE_RGB); //transform the extent TIFF from indexed colour to true RGB
            $icetarget = 'rgba(255,255,255,1.0)'; // the color of the ice
            $nonicefill = 'black'; //the color of everything else but the ice
            $extentImg->opaquePaintImage($icetarget,$nonicefill,0,true); // fill everything except the ice with black
            $extentImg->negateImage(false); //now invert the image, this gives us black ice on white
            $extentImg->setImageFormat('bmp'); // convert the TIFF to BMP
            $extentImg->writeImage($tmpExtentBMP); // write out the BMP to disk
            $extentImg->clear(); //destroy the imagemagick resource
            $densityImg = new Imagick($densityTIFF);
            $extentImg = new Imagick($extentTIFF);
            $densityImg->setResourceLimit(imagick::RESOURCETYPE_MEMORY, 256); //constants for imagemagick setup
            $densityImg->setResourceLimit(imagick::RESOURCETYPE_MAP, 256);
            $densityImg->setResourceLimit(imagick::RESOURCETYPE_AREA, 1512);
            $densityImg->setResourceLimit(imagick::RESOURCETYPE_FILE, 768);
            $densityImg->setResourceLimit(imagick::RESOURCETYPE_DISK, -1);
            $densityImg->transformImageColorspace(Imagick::COLORSPACE_RGB); //transform the extent TIFF from indexed colour to true RGB
            $densityImg->setImageFormat('png');
            $densityImg->writeImage("png24:".$outputName.".png"); // write out the BMP to disk
            $densityImg->clear(); //destroy the imagemagick resource
            $potraceSVGCommand = BASE_PATH.POTRACE_PATH." ".$tmpExtentBMP."  -b svg  -n -a 0 -t 0 -O 0 -u 100 -o ".$outputName.".svg"; //define a potrace command to generate an SVG we can use in the browser as a visual if we want
            $potraceJSONCommand = BASE_PATH.POTRACE_PATH." ".$tmpExtentBMP." -b geojson -n -a 0 -t 0 -O 0 -u 100 -o ".$tmpGeoJSON; // define a potrace command again to generate a temporary GeoJSON file to parse MIDI
            exec($potraceSVGCommand); //run the first potrace command
            exec($potraceJSONCommand); // run the second potrace command
            if ($isNorth) self::generateMIDI($tmpGeoJSON,$GLOBALS["northPoleMIDIParams"],$outputName);
            else self::generateMIDI($tmpGeoJSON,$GLOBALS["southPoleMIDIParams"],$outputName);
            unlink($tmpGeoJSON); //delete the temporary geojson file
            unlink($tmpExtentBMP); //delete the temporary BMP file
        }
    }

    private static function generateMIDI($geoJSONFile,$params,$fileName) { //this function generates a .MID file to play MIDI in any app and a .JSON file to play MIDI syncrhonuized with drawing in the browser
        $cleanedJSON = self::parseGeoJSON($geoJSONFile,$params->centreReference); //parse the raw GeoJSON file
        $midi = new Midi(); //instantiate a new midi file
        $midi->open(MIDI_PPQ); //open it with the timebase specified by the constant
        $midi->setBpm($params->tempoBPM); //set the playback BPM of the MIDI file
        $midi->newTrack(); // create a new empty track
        $lastNoteT = 0; // angle of the last Note -- used for computing duration
        $lastNoteX = $params->centreReference->x;// cartesian coordinates of the last note, used to compute velocity
        $lastNoteY = 0;
        $count = 0;
        $densityImg = new Imagick($fileName.".png");
        $densityImg->setResourceLimit(imagick::RESOURCETYPE_MEMORY, 256); //constants for imagemagick setup
        $densityImg->setResourceLimit(imagick::RESOURCETYPE_MAP, 256);
        $densityImg->setResourceLimit(imagick::RESOURCETYPE_AREA, 1512);
        $densityImg->setResourceLimit(imagick::RESOURCETYPE_FILE, 768);
        $densityImg->setResourceLimit(imagick::RESOURCETYPE_DISK, -1);
        $densityImg->setResourceLimit(imagick::RESOURCETYPE_TIME, 600);
        foreach ($cleanedJSON->orderedPoints as $notePoint) { //for every point in the JSON file
            if (POINT_DEBUG) print ".";
            $count++;
            $notePoint->pitch = self::translateRadiusToPitch($notePoint->r,$cleanedJSON->minRadius,$cleanedJSON->maxRadius,$params->minNote,$params->maxNote,$params->scaleLength,$params->scaleNotes); //translate the radius of the polar coordinate arm to a note value
            $notePoint->time = self::translateAngleToTime($notePoint->t,$params->lengthInSeconds,$params->tempoBPM,$params->tempoLengths); // translate the angle of the polar coordinate arm to a time
            $notePoint->duration = self::translateAngleDifferenceToDuration($notePoint->t,$lastNoteT,$params->lengthInSeconds,$params->tempoBPM,$params->tempoLengths); // translate the difference in angles between current and last notes to a duration;
            $notePoint->velocity = BASE_VELOCITY + self::getVelocityFromDensity($notePoint->rawx,$notePoint->rawy,$lastNoteX,$lastNoteY,$params->centreReference->x,$params->centreReference->y,$densityImg);
            $ppqTimestamp = round(($notePoint->time/1000) * ($params->tempoBPM/60) * MIDI_PPQ); //convert the timestamp in seconds to a timestamp in PPQ
            $noteLength = round(($notePoint->duration/1000) * ($params->tempoBPM/60) * MIDI_PPQ); //convert the timestamp in seconds to a timestamp in PPQ
            $midi->insertMsg(1,  $ppqTimestamp." On ch=1 n=".$notePoint->pitch." v=".$notePoint->velocity); //add the note on message to the midi file
            $midi->insertMsg(1,  ($ppqTimestamp+$noteLength)." Off ch=1 n=".$notePoint->pitch." v=".$notePoint->velocity); //add the note off message to the midi file
            $lastNoteT = $notePoint->t;
            $lastNoteX = $notePoint->rawx;
            $lastNoteY = $notePoint->rawy;
        }
        $densityImg->clear();
        $cleanedJSON->midiParams = $params; //add the midi parameter data to the JSON file
        file_put_contents($fileName.'.json',json_encode($cleanedJSON)); //write the JSON file
        $midi->saveMidFile($fileName.'.mid'); //write the MIDI file
    }

    private static function parseGeoJSON($geoJSONFile,$centreReference) { //parses the RAW geoJSON file to get the beginnings of a MIDI file
        $geoJSONString =  file_get_contents($geoJSONFile); // get a JSON string from the temporary geoJSON file
        $geoObj = json_decode($geoJSONString); // convert the JSON string to a php object
        $outputObj = new stdClass(); // create an object for our MIDI output JSON
        $outputObj->minRadius = -1; // initialize a minimum radius
        $outputObj->maxRadius = -1; // initialize a maximum radius
        $outputObj->orderedPoints = array();
        foreach($geoObj->features as $feature) { //iterate through the features in the geoJSON
            $coords = $feature->geometry->coordinates[0]; //assign a shorthand for all the coordinates in a feature (called coords)
            foreach($coords as $coordinate) { //iterate through the coordinates in the feature
                $pPoint = new PolarPoint($coordinate[0],$coordinate[1],$centreReference);  //define the current polarpoint object from the point in the geoJSON file
                if (($outputObj->minRadius == -1) || ($pPoint->r < $outputObj->minRadius)) $outputObj->minRadius = $pPoint->r; //set the minimum radius from the current pPoint if it has the smallest value to date;
                if (($outputObj->maxRadius == -1) || ($pPoint->r > $outputObj->maxRadius)) $outputObj->maxRadius = $pPoint->r; //set the minimum radius from the current pPoint if it has the smallest value to date;
                array_push($outputObj->orderedPoints,$pPoint); //add the point to the point array
                $count++;
            }
        }
        usort($outputObj->orderedPoints,array('PolarProject','angleSort')); //sort the points according to angle
        return $outputObj; //return the cleaned JSON object
    }

    private static function translateRadiusToPitch($val,$min,$max,$pMin,$pMax,$scaleLength,$pScaleArr) {
        $input_mapping = $val / ($max - $min); //translate the radius to an abstract number between 0 and 1;
        $num_notes = floor(($pMax-$pMin)/$scaleLength*count($pScaleArr)); // this gives you the number of notes available to you considering the number of octaves and the number of notes in a scale in a single octave
        $raw_output = floor($num_notes*$input_mapping); //maps the radius to a note in the available notes;
        $num_octaves = floor(($pMax-$pMin)/$scaleLength); //maps the radius among octaves
        $current_octave = floor($raw_output / count($pScaleArr)); //determines the current octave according to the scale
        $scale_note = $raw_output % count($pScaleArr); //determines the scale note from the array fo available notes
        $output = $pMin+($scaleLength*$current_octave)+$pScaleArr[$scale_note]; //computers the output note
        return $output;
    }

    private static function translateAngleToTime($val,$lengthInSeconds,$tempoBPM,$tempoLengths) {
        $mapping = $val / 360; //gives you a number between 0 and 1 by dividing by the number of degrees (angle should always
        $rawOutputTime =floor($mapping * 1000 * $lengthInSeconds); //raw output time in seconds before quantization
        $minQuantizeMillis = (60000 / $tempoBPM) * $tempoLengths[0];
        $quantizedOutputTime = round(round($rawOutputTime/$minQuantizeMillis)*$minQuantizeMillis);
        return $quantizedOutputTime;
    }

    private static function translateAngleDifferenceToDuration($valCurrent,$valLast,$lengthInSeconds,$tempoBPM,$tempoLengths) {
        $mapping = ($valCurrent - $valLast) / 360;
        $rawOutputTime =floor($mapping * 1000 * $lengthInSeconds);
        $minQuantizeMillis = (60000 / $tempoBPM) * $tempoLengths[0];
        $maxQuantizeMillis = (60000 / $tempoBPM) * $tempoLengths[count($tempoLengths)-1];
        $quantizedOutputTime = round(round($rawOutputTime/$minQuantizeMillis)*$minQuantizeMillis);
        if ($quantizedOutputTime < $minQuantizeMillis) $quantizedOutputTime = $minQuantizeMillis;
        if ($quantizedOutputTime > $maxQuantizeMillis) $quantizedOutputTime = $maxQuantizeMillis;
        return $quantizedOutputTime;
    }

    private static function getVelocityFromDensity($x1,$y1,$x2,$y2,$x3,$y3,$dI) {
        $d = $dI->getImageGeometry();
        $w = $d['width'];
        $h = $d['height'];
        $minX = min(array((int)$x1,(int)$x2,(int)$x3));
        if ($minX < 0) $minX = 0;
        $maxX = max(array((int)$x1,(int)$x2,(int)$x3));
        if ($maxX > $w) $maxX = $w;
        $minY = min(array((int)$y1,(int)$y2,(int)$y3));
        if ($minX < 0) $minX = 0;
        $maxY = max(array((int)$y1,(int)$y2,(int)$y3));
        if ($maxY > $h) $maxY = $h;
        $tcount = 0;
        $icount = 0;
        for ($y=$minY;$y<$maxY;$y++) {
            for ($x=$minX;$x<$maxX;$x++) {
                if (self::pointInTriangle(new Point($x,$y),new Point($x1,$y1),new Point($x2,$y2),new Point($x3,$y3))) {
                    $tcount++;
                    try {
                        $pixel = $dI->getImagePixelColor($x, $y);
                        $rgb = $pixel->getColor();
                        $icount += $rgb["r"];
                    } catch (Exception $e) {
                        print "Pixel Reading Error: ".$e->getMessage()."\n";
                        $icount += 128;
                    }
                }
            };
        };
        if ($tcount == 0) $tcount = 1;
        if ($icount == 0) $icount = 1;
        $velocity = round( ($icount / ($tcount*255)) * 127);
        return $velocity;
    }

    private static function angleSort($a,$b) { // utility function for sorting angles
        if ($a->t < $b->t) return -1;
        if ($b->t > $a->t) return 1;
        return 1;

    }

    private static function sign ($p1, $p2, $p3) { //utility function for determining if a point is in a triangle
        return ($p1->x - $p3->x) * ($p2->y - $p3->y) - ($p2->x - $p3->x) * ($p1->y - $p3->y);
    }

    private static function pointInTriangle ($pt, $v1, $v2, $v3) { //determines if a point is in a given triangle
        $b1 = self::sign($pt, $v1, $v2) < 0.0;
        $b2 = self::sign($pt, $v2, $v3) < 0.0;
        $b3 = self::sign($pt, $v3, $v1) < 0.0;
        return (($b1 == $b2) && ($b2 == $b3));
    }

}



class Point {
    function __construct($x,$y) {
        $this->x = $x;
        $this->y = $y;
    }
}

class PolarPoint {
    function __construct($x,$y,$p) {
        $this->rawx = $x;
        $this->rawy = $y;
        $this->x = $x - $p->x;
        $this->y = $y - $p->y;
        $this->r = sqrt(pow($this->x,2)+pow($this->y,2));
        $t_angle = atan2($this->y,$this->x)*(180.0/pi());
        if ($t_angle < 0) $this->t = $t_angle + 360;
        else $this->t = $t_angle;
    }
}

PolarProject::checkForIceFiles();