<?php

require('./php/midi/midi.class.php');

	$destFilename  = 'output.mid';
	
	$midi = new Midi();
	$midi->open();
	$midi->setBpm(120);
    $newTrack = $midi->newTrack();
	//$midi->addMsg($newTrack,"Par ch=1 c=7 v=100");
    //$midi->addMsg($newTrack,"Par ch=1 c=6 v=0");
	for ($i=1;$i<200;$i++) {
	    $startTime = $i*240;
	    $endTime = $i*240+200;
	    $noteNum = rand(20,100);
        $midi->addMsg($newTrack, $startTime." On ch=1 n=".$noteNum." v=80");
        $midi->addMsg($newTrack, $endTime." Off ch=1 n=".$noteNum." v=80");
    }
    $midi->downloadMidFile($destFilename);

