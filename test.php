<!DOCTYPE html>
<html xmlns = "http://www.w3.org/1999/xhtml">
<head>
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

</head>
<body>
<script type="text/javascript">
    window.onload = function () {
        MIDI.loadPlugin({
            soundfontUrl: "./soundfont/",
            instrument: "acoustic_grand_piano",
            onprogress: function(state, progress) {
                console.log(state, progress);
            },
            onsuccess: function() {
                var delay = 0; // play one note every quarter second
                var note = 78; // the MIDI note
                var velocity = 127; // how hard the note hits
                // play the note
                MIDI.setVolume(0, 127);
                MIDI.noteOn(0, 50, 90, 0);
                MIDI.noteOff(0, 50,90, 1);
                MIDI.noteOn(0, 52, 90, 2);
                MIDI.noteOff(0, 52,90, 3);
                MIDI.noteOn(0, 54,90, 4);
                MIDI.noteOff(0, 54,90, 5);
            }
        });
    };
</script>
</body>
</html>