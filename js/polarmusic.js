CONSOLE_DEBUGGING = false;
LOAD_EXTERNAL_MIDI = false;


var internalMidiReady = false;
var externalMidiReady = false;
var synthsCreated = false;
var canvasWidth = 800;

function createSynths() {
    if (internalMidiReady && externalMidiReady && !synthsCreated) {
        synthsCreated = true;
        var northPole = new PolarSynth({
            fileString : "icefiles/"+currentDate.getFullYear()+"/"+(currentDate.getMonth()+1)+"/"+currentDate.getFullYear()+"-"+(currentDate.getMonth()+1)+"-"+currentDate.getDate()+"_north.json",
            internalOrExternal: "internal",
            externalSynthChannel : 1,
            externalSynthString: "VirtualMIDISynth #1",
            internalSynthGMPatch: 0,
            internalSynthInstrument: "acoustic_grand_piano",
            moveSpeed : 0.2,
            whichSide : "left"
        });
        var southPole = new PolarSynth({
            fileString : "icefiles/"+currentDate.getFullYear()+"/"+(currentDate.getMonth()+1)+"/"+currentDate.getFullYear()+"-"+(currentDate.getMonth()+1)+"-"+currentDate.getDate()+"_south.json",
            internalOrExternal: "internal",
            externalSynthChannel : 1,
            externalSynthString: "VirtualMIDISynth #1",
            internalSynthGMPatch: 0,
            internalSynthInstrument: "acoustic_grand_piano",
            moveSpeed : 0.2,
            whichSide : "right"
        });
        paper.view.onFrame = function(event) {
            if (northPole) northPole.moveUp();
            if (southPole) southPole.moveUp();
        }

        $("#listen_today").click(function() {
            $("#intro").fadeTo("slow",0.1,function() {
                $("#intro").mouseenter(function(){
                    $("#intro").fadeTo("slow",1.0);
                });
                $("#intro").mouseleave(function(){
                    $("#intro").fadeTo("slow",0.1);
                });
            });

            northPole.startSynth($(".date-input").datepicker("getDate"),"north");
            southPole.startSynth($(".date-input").datepicker("getDate"),"south");
        });
        if (isInstallation) {
            debug('Installation Version');
            northPole.startSynth($(".date-input").datepicker("getDate"),"north");
            southPole.startSynth($(".date-input").datepicker("getDate"),"south");
        } else {
            debug('Web Version');
            $("#intro").fadeIn("slow");
        }
    } else {
        setTimeout(createSynths,500);
    }



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
    if (LOAD_EXTERNAL_MIDI) {
        this.externalMidiOut = (this.params.hasOwnProperty("externalSynthString")) ? WebMidi.getOutputByName(this.params.externalSynthString) : WebMidi.outputs[0];
        this.externalMidiChannel = (this.params.hasOwnProperty("externalSynthChannel")) ? this.params.externalSynthChannel : 1;
    }
    this.internalMidiChannel = (this.params.hasOwnProperty("internalSynthGMPatch")) ?this.params.internalSynthGMPatch : 0;
    this.noteQueue = new Array();
    this.paths = new paper.Group();
    this.upSpeed = -1;
    this.horzMultiplier = this.params.whichSide == "left" ? 1.0 : -1.0;
    this.sideBase = this.params.whichSide == "left" ? 0 : canvasWidth;
    if (this.params.moveSpeed) this.upSpeed = 0-this.params.moveSpeed;
    this.lastNP = 0;
    this.lastVert = 0;
    this.vertOffset = 0;
    var self = this;


    this.stopAllNotes = function() {
        while(this.noteQueue.length>0) {
            clearTimeout(this.noteQueue.pop());
        }
    }

    this.startSynth = function(whichDate,northOrSouth) {
        var fileString = "icefiles/"+whichDate.getFullYear()+"/"+(whichDate.getMonth()+1)+"/"+whichDate.getFullYear()+"-"+(whichDate.getMonth()+1)+"-"+whichDate.getDate()+"_"+northOrSouth+".json";
        this.stopAllNotes();
        this.paths.translate([0,(0-this.vertOffset)]);
        this.paths.removeChildren();
        this.noteQueue = new Array();
        this.upSpeed = -1;
        this.horzMultiplier = this.params.whichSide == "left" ? 1.0 : -1.0;
        this.sideBase = this.params.whichSide == "left" ? 0 : canvasWidth;
        if (this.params.moveSpeed) this.upSpeed = 0-this.params.moveSpeed;
        this.lastNP = 0;
        this.lastVert = 0;
        this.vertOffset = 0;
        $.getJSON( fileString , function(data) {self.startMusic(data)});
    }

    this.drawNote = function(nP,nT,nV) {
        var vertPosition = (nT / 50);
        var newPath = new paper.Path();
        newPath.strokeWidth = 2;
        newPath.strokeColor = new paper.Color(nV/127);
        newPath.add([(this.sideBase + (this.lastNP * 3 * this.horzMultiplier)),this.lastVert]);
        newPath.add([(this.sideBase + (nP * 3 * this.horzMultiplier)),vertPosition]);
        newPath.translate(0,this.vertOffset);
        this.paths.addChild(newPath);
        this.lastVert = vertPosition;
        this.lastNP = nP;
        paper.view.draw();
    }

    this.startMusic = function (data) {
        var notePitch,noteTime,noteDuration,noteVelocity;
        if (self.params.internalOrExternal == "external") {
            MIDI.setVolume(self.internalMidiChannel, 127);
        }
        for(var i=0;i<data.orderedPoints.length;i++) {
            notePitch = data.orderedPoints[i].pitch;
            noteTime = data.orderedPoints[i].time;
            noteDuration = data.orderedPoints[i].duration;
            noteVelocity = data.orderedPoints[i].velocity;
            self.noteQueue.push(setTimeout(function(nP,nT,nV){
                if (self.params.internalOrExternal == "external") {
                    self.externalMidiOut.playNote(nP,self.externalMidiChannel,{duration:noteDuration,velocity:noteVelocity});
                } else {
                    MIDI.noteOn(self.internalMidiChannel, nP, noteVelocity, 0);
                    MIDI.noteOff(self.internalMidiChannel, nP,(noteDuration/1000));
                }
                self.drawNote(nP,nT,nV);
            },noteTime,notePitch,noteTime,noteVelocity));
        }
        if (isInstallation) setTimeout(self.startSynth,(noteTime+noteDuration));
    }

    this.moveUp = function() {
        self.paths.translate([0,self.upSpeed]);
        self.vertOffset += self.upSpeed;
    }

}




$(function() {
    $(".date-input").val(currentDate.getFullYear()+"-"+("0" + (currentDate.getMonth()+1)).slice(-2)+"-"+("0" + currentDate.getDate()).slice(-2));
    $("#get_midi_north").click(function() {
        whichDate = $(".date-input").datepicker("getDate");
        var fileString = "icefiles/"+whichDate.getFullYear()+"/"+(whichDate.getMonth()+1)+"/"+whichDate.getFullYear()+"-"+(whichDate.getMonth()+1)+"-"+whichDate.getDate()+"_north.mid";
        document.location.href=fileString;
    });
    $("#get_midi_south").click(function() {
        whichDate = $(".date-input").datepicker("getDate");
        var fileString = "icefiles/"+whichDate.getFullYear()+"/"+(whichDate.getMonth()+1)+"/"+whichDate.getFullYear()+"-"+(whichDate.getMonth()+1)+"-"+whichDate.getDate()+"_south.mid";
        document.location.href=fileString;
    });
    var canvas = $("#polarCanvas")[0];
    paper.setup(canvas);
    if (LOAD_EXTERNAL_MIDI) {
        WebMidi.enable(function() {
            debug('external MIDI subsystem loaded');
            externalMidiReady = true;
            createSynths();

        });
    } else {
        externalMidiReady = true;
    }
    MIDI.loadPlugin({
        soundfontUrl: "./soundfont/",
        instrument: "acoustic_grand_piano",
        onprogress: function(state, progress) {
        },
        onsuccess: function() {
            debug('internal MIDI subsystem loaded');
            internalMidiReady = true;
            $(".date-input").datepicker({minDate:firstDate, maxDate:currentDate,defaultDate:currentDate,dateFormat:"yy-mm-dd"});
            createSynths();
        }
    });
});




