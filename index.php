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
            centreReference : new Point(152,224),
            minNote : 24,
            maxNote : 96,
            scaleLength : 12,
            scaleNotes : [1,2,4,5,7,9,11],
            lengthInSeconds : 1800,
            tempoBPM : 70,
            tempoLengths : [0.25,0.5,1,2,4]
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
