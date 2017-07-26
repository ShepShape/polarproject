//configuration settings, set these as you wish

CONSOLE_DEBUGGING = true;
SYNTH1_SUBSTRING = "VirtualMIDISynth #1";
SYNTH1_CHANNEL1 = 1;
NORTH_CENTRE_REFERENCE = new Point(152,224);
NORTH_MIN_NOTE = 24;
NORTH_MAX_NOTE = 96;
NORTH_SCALE_LENGTH = 12;
NORTH_SCALE_ARR = [0,2,3,5,7,8,7,10]


//program variables
var midiOut1;
var timeCounter = 0;
var northPoints = new Array();
var northMinRadius=-1;
var northMaxRadius=-1;

function WebMidiStart() {
    WebMidi.enable(configSynths); //this enables the WebMIDI library and calls the configSynths function when this is done
}

function configSynths(err) {
    debug(WebMidi.outputs); //spits out all the available WebMIDI outputs to the console
    midiOut1 = WebMidi.getOutputByName(SYNTH1_SUBSTRING); //sets a midi output object by using the midi output string name as a reference, find the string name in the output from the above line
    $.getJSON( "icefiles/northice.json", parseGeoJSON); //get the json file in the first argument and call the parseGeoJSON function after getting it
    //startMusic();
}


function parseGeoJSON(data) {
    var coords; //an array of all the points in a feature
    var p; //individual point object from the json file
    var pPoint; // the new polarPoint object we will declare
    for (var i=0;i<data.features.length;i++) { // iterate through the features (closed polygons)
        coords = data.features[i].geometry.coordinates[0]; //assign a shorthand for all the coordinates in a feature (called coords)
        for(var j=0;j<coords.length;j++) { //iterate through the coordinates in the feature
            p = coords[j];
            pPoint = new PolarPoint(p[0],p[1],NORTH_CENTRE_REFERENCE); //define the current polarpoint object from the point in the geoJSON file
            if ((northMinRadius == -1) || (pPoint.r < northMinRadius)) northMinRadius = pPoint.r; //set the minimum radius fro the current pPoint if it has the smallest value to date;
            if ((northMaxRadius == -1) || (pPoint.r > northMaxRadius)) northMaxRadius = pPoint.r; //set the minimum radius fro the current pPoint if it has the smallest value to date;
            northPoints.push(pPoint); //add the pPoint object to the array
        }
    }
    northPoints.sort(angleSort); //sort the Array of points by angle

    startMusic(); //now we play some music!
}

function translateRadiusToPitch(val,min,max,pMin,pMax,pScaleLength,pScaleArr) {
    var in_mapping = val / (max - min); //translate the radius to an abstract number between 0 and 1;
    var num_notes = Math.floor((pMax-pMin)/pScaleLength*pScaleArr.length); // this gives you the number of notes available to you considering the number of octaves and the number of notes in a scale in a single octave
    var raw_output = Math.floor(num_notes*in_mapping); //maps the radius to a note in the available notes;
    var num_octaves = Math.floor((pMax-pMin)/pScaleLength);
    var current_octave = Math.floor(raw_output / pScaleArr.length);
    var scale_note = raw_output % pScaleArr.length;
    //console.log(num_octaves+" "+current_octave+" "+scale_note);
    var output = pMin+(pScaleLength*current_octave)+pScaleArr[scale_note];
    return output;
}

function translateAngleToTime(val) {
    var mapping = val / 360;
    var output = Math.floor(mapping * 1800000);
    return output
}

function startMusic() {
    var notePoint,notePitch,noteTime;
    for(var i=0;i<northPoints.length;i++) {
        notePoint = northPoints[i];
        notePitch = translateRadiusToPitch(notePoint.r,northMinRadius,northMaxRadius,NORTH_MIN_NOTE,NORTH_MAX_NOTE,NORTH_SCALE_LENGTH,NORTH_SCALE_ARR);
        noteTime = translateAngleToTime(notePoint.t);
        console.log(i+" "+notePitch+" "+noteTime);
        midiOut1.playNote(notePitch,SYNTH1_CHANNEL1,{time:noteTime,duration:150});
    }

   /** var randNote, randLength;
    var numNotes = Math.floor(Math.random()*100)+10;
    for (i=0;i<numNotes;i++) {
        randNote = Math.floor(Math.random()*127);
        randLength = Math.floor(Math.random()*400)+100;
        midiOut1.playNote(randNote,SYNTH1_CHANNEL1,{duration:randLength,time:timeCounter});
        timeCounter+=randLength;
    } */


}

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
