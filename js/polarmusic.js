//configuration settings, set these as you wish

console_debugging = true;
synth1_substring = "VirtualMIDISynth #1";
synth1_channel1 = 1;


//program variables
var midiOut1;
var timeCounter = 0;

function WebMidiStart() {
    WebMidi.enable(configSynths);
}

function configSynths(err) {
    debug(WebMidi.outputs);
    midiOut1 = WebMidi.getOutputByName(synth1_substring);
    startMusic();
}

function startMusic() {
    var randNote, randLength;
    var numNotes = Math.floor(Math.random()*100)+10;
    for (i=0;i<numNotes;i++) {
        randNote = Math.floor(Math.random()*127);
        randLength = Math.floor(Math.random()*400)+100;
        midiOut1.playNote(randNote,synth1_channel1,{duration:randLength,time:timeCounter});
        timeCounter+=randLength;
    }


}


function debug(debug_arg) {
    if (console_debugging) console.log(debug_arg);
}