<?php
/**
 * Created by PhpStorm.
 * User: kenny
 * Date: 2017-07-21
 * Time: 11:04 AM
 */
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

    </script>
    <link rel="stylesheet" type="text/css" href="css/polarproject.css">
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
    <script src="js/midi/webmidi.min.js"></script>
    <script src="js/jquery-3.2.1.min.js"></script>
    <script src="js/paper/paper-full.js"></script>
    <script src="js/polarmusic.js"></script>


</head>
<body>

<canvas id="polarCanvas" resize></canvas>
<button id="stop_all">Reset Synth</button>
</body>
</html>
