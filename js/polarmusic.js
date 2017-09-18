CONSOLE_DEBUGGING = true;
var northPolePaths,southPolePaths;

function createSynths(err) {
    var northPole = new PolarSynth({
        fileString : "icefiles/2017/1/2017-1-1_north.json",
        internalOrExternal: "external",
        externalSynthChannel : 1,
        externalSynthString: "VirtualMIDISynth #1",
        internalSynthGMPatch: 1,
        internalSynthInstrument: "acoustic_grand_piano",
        moveSpeed : 0.2
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
    this.externalMidiOut = (this.params.externalSynthString) ? WebMidi.getOutputByName(this.params.externalSynthString) : WebMidi.outputs[0] ;
    this.externalMidiChannel = (this.params.externalSynthChannel) ? this.params.externalSynthChannel : 1;
    this.noteQueue = new Array();
    this.paths = new paper.Group();
    this.upSpeed = -1;
    if (this.params.moveSpeed) this.upSpeed = 0-this.params.moveSpeed;
    console.log(this.upSpeed);
    this.lastNP = 0;
    this.lastVert = 0;
    this.vertOffset = 0;
    var self = this;


    this.stopAllNotes = function() {
        while(this.noteQueue.length>0) {
            clearTimeout(this.noteQueue.pop());
        }
    }

    this.resetSynth = function() {
        console.log('the end');
    }

    this.drawNote = function(nP,nT,nV) {
        var vertPosition = (nT / 50);
        var newPath = new paper.Path();

        newPath.fillColor = new paper.Color(nV/127);
        newPath.add([0,this.lastVert]);
        newPath.add([this.lastNP,this.lastVert]);
        newPath.add([nP,vertPosition]);
        newPath.add([0,vertPosition]);
        newPath.closed = true;
        newPath.translate(0,this.vertOffset);
        this.paths.addChild(newPath);
        this.lastVert = vertPosition;
        this.lastNP = nP;
        paper.view.draw();
    }

    this.startMusic = function (data) {
        var notePitch,noteTime,noteDuration,noteVelocity;
        for(var i=0;i<data.orderedPoints.length;i++) {
            notePitch = data.orderedPoints[i].pitch;
            noteTime = data.orderedPoints[i].time;
            noteDuration = data.orderedPoints[i].duration;
            noteVelocity = data.orderedPoints[i].velocity;
            self.noteQueue.push(setTimeout(function(nP,nT,nV){
            self.noteQueue.push(setTimeout(function(nP,nT,nV){
                if (self.params.internalOrExternal == "external") {
                    self.externalMidiOut.playNote(nP,self.externalMidiChannel,{duration:noteDuration,velocity:noteVelocity});
                } else {

                }
                self.drawNote(nP,nT,nV);
            },noteTime,notePitch,noteTime,noteVelocity));
        }
        setTimeout(self.resetSynth,(noteTime+noteDuration));
    }

    paper.view.onFrame = function(event) {
           self.paths.translate([0,self.upSpeed]);
           self.vertOffset += self.upSpeed;
    }

    $.getJSON(this.params.fileString, function(data) {self.startMusic(data)});
}


$(function() {
    var canvas = $("#polarCanvas")[0];
    paper.setup(canvas);
    WebMidi.enable(createSynths);
});




