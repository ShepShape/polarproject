

var PolarSynth = (function() {

    var midiOut,params;
    var timeCounter = 0;
    var northPoints = new Array();
    var northMinRadius=-1;
    var northMaxRadius=-1;
    var temp_Current_Time = 0;
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
        $.getJSON( "icefiles/northice.json", parseGeoJSON); //get the json file in the first argument and call the parseGeoJSON function after getting it
    }
    

    var parseGeoJSON = function(data) {
        var coords; //an array of all the points in a feature
        var p; //individual point object from the json file
        var pPoint; // the new polarPoint object we will declare
        for (var i=0;i<data.features.length;i++) { // iterate through the features (closed polygons)
            coords = data.features[i].geometry.coordinates[0]; //assign a shorthand for all the coordinates in a feature (called coords)
            for(var j=0;j<coords.length;j++) { //iterate through the coordinates in the feature
                p = coords[j];
                pPoint = new PolarPoint(p[0],p[1],params.centreReference); //define the current polarpoint object from the point in the geoJSON file
                if ((northMinRadius == -1) || (pPoint.r < northMinRadius)) northMinRadius = pPoint.r; //set the minimum radius from the current pPoint if it has the smallest value to date;
                if ((northMaxRadius == -1) || (pPoint.r > northMaxRadius)) northMaxRadius = pPoint.r; //set the minimum radius from the current pPoint if it has the smallest value to date;
                northPoints.push(pPoint); //add the pPoint object to the array
            }
        }
        northPoints.sort(angleSort); //sort the Array of points by angle

        startMusic(); //now we play some music!
    }

    var translateRadiusToPitch = function(val,min,max,pMin,pMax,scaleLength,pScaleArr) {
        var input_mapping = val / (max - min); //translate the radius to an abstract number between 0 and 1;
        var num_notes = Math.floor((pMax-pMin)/scaleLength*pScaleArr.length); // this gives you the number of notes available to you considering the number of octaves and the number of notes in a scale in a single octave
        var raw_output = Math.floor(num_notes*input_mapping); //maps the radius to a note in the available notes;
        var num_octaves = Math.floor((pMax-pMin)/scaleLength);
        var current_octave = Math.floor(raw_output / pScaleArr.length);
        var scale_note = raw_output % pScaleArr.length;
        var output = pMin+(params.scaleLength*current_octave)+pScaleArr[scale_note];
        return output;
    }

    var translateAngleToTime =  function(val) {
        var mapping = val / 360;
        var rawOutputTime = Math.floor(mapping * 1000 * params.lengthInSeconds);
        var minQuantizeMillis = (60000 / params.tempoBPM) * params.tempoLengths[0];
        var quantizedOutputTime = Math.round(Math.round(rawOutputTime/minQuantizeMillis)*minQuantizeMillis);
        return quantizedOutputTime;
    }

    var startMusic = function () {
        var notePoint,notePitch,noteTime;
        for(var i=0;i<northPoints.length;i++) {
            notePoint = northPoints[i];
            notePitch = translateRadiusToPitch(notePoint.r,northMinRadius,northMaxRadius,params.minNote,params.maxNote,params.scaleLength,params.scaleNotes);
            noteTime = translateAngleToTime(notePoint.t);
            noteQueue.push(setTimeout(function(nP){
                midiOut.playNote(nP,params.synthDefaultChannel,{duration:(Math.floor(Math.random()*150+150)),velocity:(Math.random()*0.6+0.2)});
            },noteTime,notePitch));
        }

    }

    return PolarSynth;

}());

// utility functions

function debug(debug_arg) {

    if (CONSOLE_DEBUGGING) console.log(debug_arg);
}

function Point(x, y) {
    this.x = x;
    this.y = y;
}

function PolarPoint(x,y,p) {
    this.rawx = x;
    this.rawy = y;
    this.x = x - p.x;
    this.y = y - p.y;
    this.r = Math.sqrt(Math.pow(this.x,2)+Math.pow(this.x,2));
    this.t = Math.atan2(this.y,this.x);
    var t_angle = Math.atan2(this.y,this.x)*(180.0/Math.PI);
    if (t_angle < 0) this.t = t_angle + 360;
    else this.t = t_angle;
}

function angleSort(a,b) {
    if (a.t < b.t) return -1;
    if (b.t > a.t) return 1;
    return 1;

}
