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
            scaleLength: 12,
            scaleNotes: [0,2,3,5,7,8,7,10]
        });
        northPole.WebMidiStart();

    });

</script>
</head>
<body>

</body>
</html>
