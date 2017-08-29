

var PolarSynth = (function() {

    var midiOut,params;
    var noteQueue = new Array();

    function PolarSynth(p) {
        params = p;
    }

    PolarSynth.prototype.WebMidiStart = function() {
        WebMidi.enable(configSynths); //this enables the WebMIDI library and calls the configSynths function when this is done
    }

    PolarSynth.prototype.stopAllNotes = function() {
        while(noteQueue.length>0) {
            clearTimeout(noteQueue.pop());
        }
    }

    var configSynths = function(err) {
        debug(WebMidi.outputs); //spits out all the available WebMIDI outputs to the console
        midiOut= WebMidi.getOutputByName(params.synthString); //sets a midi output object by using the midi output string name as a reference, find the string name in the output from the above line
        $.getJSON( "icefiles/2017/8/north_2017-8-27.json", startMusic); //get the json file in the first argument and call the parseGeoJSON function after getting it
    }


    var startMusic = function (data) {
        var notePitch,noteTime;
        for(var i=0;i<data.orderedPoints.length;i++) {
            notePitch = data.orderedPoints[i].pitch;
            noteTime = data.orderedPoints[i].time;
            noteQueue.push(setTimeout(function(nP){
                midiOut.playNote(nP,params.synthDefaultChannel,{duration:(Math.floor(Math.random()*150+150)),velocity:80});
            },noteTime,notePitch));
        }

    }

    return PolarSynth;

}());

// utility functions

function debug(debug_arg) {

    if (CONSOLE_DEBUGGING) console.log(debug_arg);
}


