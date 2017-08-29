<?php
/**
 * Created by PhpStorm.
 * User: kenny
 * Date: 2017-07-21
 * Time: 11:04 AM
 */

?>
<html>
<head>
    <script src="js/webmidi.min.js"></script>
    <script src="js/jquery-3.2.1.min.js"></script>
    <script src="js/polarmusic.js"></script>
    <script>

         CONSOLE_DEBUGGING = true;
         $(function() {
        var northPole = new PolarSynth({
            synthString : "VirtualMIDISynth #1",
            synthDefaultChannel : 1,
        });
        northPole.WebMidiStart();


        $("#stop_all").click(function() {
            northPole.stopAllNotes();
        });
    });



    </script>
</head>
<body>
<button id="stop_all">Stop All Notes!</button>
</body>
</html>
