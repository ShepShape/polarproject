<?php
/**
 * Created by PhpStorm.
 * User: kenny
 * Date: 2017-07-21
 * Time: 11:04 AM
 */
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
define('BASE_PATH','/home/lousheppard/polarproject.banff.org/'); //base path
define('ICE_FILES_PATH','icefiles'); //path to ice file hierarchy, included density image (JPEG), extent SVG, .mid files and .json files
$firstTime = mktime(0,0,0,1,1,1990);
$nowTime = time();
$foundDate = false;
$currentTime = $nowTime;
while (($currentTime>$firstTime) && ($foundDate == false)) {
    $y = date('Y',$currentTime);
    $m = date('n',$currentTime);
    $d = date('j',$currentTime);
    if (file_exists(BASE_PATH.ICE_FILES_PATH."/".$y."/".$m."/".$y."-".$m."-".$d."_south.json")) $foundDate = true;
    $currentTime -= (60*60*24);
}
?>
<html>
<head>
    <script>
    var currentDate = new Date((<?php print $currentTime; ?>*1000));
    var firstDate = new Date((<?php print $firstTime; ?>*1000));
    var isInstallation = <?php print ($_REQUEST['install'] == 'true') ? "true" : "false"; ?>;
    setTimeout(function() {
        location.reload();
    },(1000 * 60 * 60 * 24));
    </script>
    <base href="http://polarproject.banff.org/">
    <link rel="stylesheet" type="text/css" href="css/polarproject.css">
    <link rel="stylesheet"  href="http://code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.css" />
    <link rel="stylesheet" href="css/jquery.mobile.datepicker.css" />
    <link rel="stylesheet" href="css/jquery.mobile.datepicker.theme.css" />
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <script src="js/midi/Base64.js" type="text/javascript"></script>
    <script src="js/midi/Base64binary.js" type="text/javascript"></script>
    <script src="js/midi/WebAudioAPI.js" type="text/javascript"></script>
    <script src="js/midi/audioDetect.js" type="text/javascript"></script>
    <script src="js/midi/gm.js" type="text/javascript"></script>
    <script src="js/midi/loader.js" type="text/javascript"></script>
    <script src="js/midi/plugin.audiotag.js" type="text/javascript"></script>
    <script src="js/midi/plugin.webaudio.js" type="text/javascript"></script>
    <script src="js/midi/plugin.webmidi.js" type="text/javascript"></script>
    <script src="js/midi/dom_request_xhr.js" type="text/javascript"></script>
    <script src="js/midi/dom_request_script.js" type="text/javascript"></script>
    <!--enable the following line and the LOAD_EXTERNAL_MIDI variable to use an external softsynth -->
    <!--<script src="js/midi/webmidi.min.js"></script>-->
    <script src="http://code.jquery.com/jquery-1.11.1.min.js"></script>
    <script src="js/datepicker.js"></script>
    <script src="http://code.jquery.com/mobile/1.4.5/jquery.mobile-1.4.5.min.js"></script>
    <script src="js/jquery.mobile.datepicker.js"></script>
    <script src="js/paper/paper-full.js"></script>
    <script src="js/polarmusic.js"></script>


</head>
<body>
<div id="intro">
    <h1>Requiem For The Polar Regions</h1>
    <div id="description">
        <p>is an aural record of the shifting masses of sea ice in the Arctic and Antarctic oceans,
        both the annual melt and reformation of ice, and the long term decline of ice in the Arctic. Using the data
        provided by the National Snow and Ice Data Centre in Colorado this automated program generates
        a musical score based on the perimeter and concentration of sea ice in the Arctic and Antarctic.
        The program maps the coordinates of the ice imagery to a musical scale, generating a distinct composition each day.
        Ice which reaches further from the poles sounds as lower notes, while ice that sits closer to the pole sounds as higher notes. The score is
        composed in D Minor.</p>
        <p>You can listen to the present day's score or can choose a past date to listen to. The program produces scores based on all available daily data
        from the National Snow and Ice Data Centre, which stretches as far back as 1990. Each composition is approximately 15 minutes long.
        On screen you will see an animation which traces the contour and density of the sea ice as each note is played. The right side of the animation shows
            the counter of Arctic ice, while the left side shows the Antarctic Ice.</p>
        <p>Requiem fort the Polar regions was produced with support from the Hnatyshyn Foundation, THe Harrison McCain Foundation and Arts Nova Scotia, while
        Lou Sheppard was the 2017 Emerging Atlantic Artist in Residence at Banff Centre for Arts and Creativity. The project was conceived of by Loud Sheppard and programmed
        with the assistance of Kenny Lozowski. The project exists as an online installation, a gallery installation and a series of live performances.</p>
            <p>For more information on Requiem for the Polar Regions please contact Lou Sheppard at <a href="mailto:lou@lousheppard.com">lou@lousheppard.com</a></p>
        <button id="listen_today" data-role="none">Listen to the Score for this day: </button> <input type="text" class="date-input"  data-role="none" >
        <br /><br />
        <button id="get_midi_north" data-role="none">Download this North MIDI file</button>
        <button id="get_midi_south" data-role="none">Download this South MIDI file</button>
    </div>
</div>
<canvas id="polarCanvas" resize></canvas>
</body>
</html>
