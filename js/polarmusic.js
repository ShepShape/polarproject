CONSOLE_DEBUGGING = true;
var northPolePaths,southPolePaths;

function createSynths(err) {
    var northPole = new PolarSynth({
        fileString : "icefiles/2017/8/north_2017-8-30.json",
        synthDefaultChannel : 1,
    });
   /** var southPole = new PolarSynth({
        fileString : "icefiles/2017/8/south_2017-8-30.json",
        synthDefaultChannel : 2,
    }); */
    $("#stop_all").click(function() {
        northPole.stopAllNotes();
        southPole.stopAllNotes();
    });
}

// utility functions

function debug(debug_arg) {
    if (CONSOLE_DEBUGGING) console.log(debug_arg);
}

function drawInit() {
    northPolePaths = new Array();
    southPoleSouths = new Array();
}






function PolarSynth(p) {
    this.params = p;
    this.midiOut= WebMidi.outputs[0];
    this.noteQueue = new Array();
    this.paths = new Array();
    var self = this;


    this.stopAllNotes = function() {
        while(this.noteQueue.length>0) {
            clearTimeout(this.noteQueue.pop());
        }
    }

    this.resetSynth = function() {
        console.log('the end');
    }

    this.drawNote = function(nP,nT) {
        var vertPosition = nT / 50;
        var myIndex = this.paths.push(new paper.Path()) - 1;
        this.paths[myIndex].strokeColor = 'black';
        this.paths[myIndex].add([0,vertPosition]);
        this.paths[myIndex].add([nP,vertPosition]);
        paper.view.draw();
    }

    this.startMusic = function (data) {
        var notePitch,noteTime,noteDuration,noteVelocity;
        for(var i=0;i<data.orderedPoints.length;i++) {
            notePitch = data.orderedPoints[i].pitch;
            noteTime = data.orderedPoints[i].time;
            noteDuration = data.orderedPoints[i].duration;
            noteVelocity = data.orderedPoints[i].velocity;
            self.noteQueue.push(setTimeout(function(nP,nT){
                self.midiOut.playNote(nP,self.params.synthDefaultChannel,{duration:noteDuration,velocity:noteVelocity});
                self.drawNote(nP,nT);
            },noteTime,notePitch,noteTime));
        }
        setTimeout(self.resetSynth,(noteTime+noteDuration));
    }

    paper.view.onFrame = function(event) {
        for (i=0;i<self.paths.length;i++) {
           self.paths[i].translate([0,-1]);
        }
    }

    $.getJSON(this.params.fileString, function(data) {self.startMusic(data)});
}


$(function() {
    var canvas = $("#polarCanvas")[0];
    paper.setup(canvas);
    WebMidi.enable(createSynths);
});




