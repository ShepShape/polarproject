CONSOLE_DEBUGGING = false;
LOAD_EXTERNAL_MIDI = false;


var internalMidiReady = false;
var externalMidiReady = false;
var synthsCreated = false;
var canvasWidth = 1000;
var pngWidth = 304;
var globalUpSpeed = -0.2;


function createSynths() {
    if (internalMidiReady && externalMidiReady && !synthsCreated) {
        synthsCreated = true;
        var northPole = new PolarSynth({
            internalOrExternal: "internal",
            externalSynthChannel : 1,
            externalSynthString: "VirtualMIDISynth #1",
            internalSynthGMPatch: 0,
            internalSynthInstrument: "acoustic_grand_piano",
            moveSpeed : globalUpSpeed,
            whichSide : "left"
        });
        var southPole = new PolarSynth({
            internalOrExternal: "internal",
            externalSynthChannel : 1,
            externalSynthString: "VirtualMIDISynth #1",
            internalSynthGMPatch: 0,
            internalSynthInstrument: "acoustic_grand_piano",
            moveSpeed : globalUpSpeed,
            whichSide : "right"
        });
        paper.view.onFrame = function(event) {
            if (northPole) northPole.moveUp();
            if (southPole) southPole.moveUp();
        }

        $("#listen_today").click(function() {
            $("#intro").fadeTo("slow",0.0,function() {
                $("#intro").mouseenter(function(){
                    $("#intro").fadeTo("slow",1.0);
                });
                $("#intro").mouseleave(function(){
                    $("#intro").fadeTo("slow",0.0);
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


function PolarSynth(p) {
    this.params = p;
    if (LOAD_EXTERNAL_MIDI) {
        this.externalMidiOut = (this.params.hasOwnProperty("externalSynthString")) ? WebMidi.getOutputByName(this.params.externalSynthString) : WebMidi.outputs[0];
        this.externalMidiChannel = (this.params.hasOwnProperty("externalSynthChannel")) ? this.params.externalSynthChannel : 1;
    }
    this.internalMidiChannel = (this.params.hasOwnProperty("internalSynthGMPatch")) ?this.params.internalSynthGMPatch : 0;
    this.noteQueue = new Array();
    this.pointQueue = new Array();
    this.linePaths = new paper.Group();
    this.mapPaths = new paper.Group({applyMatrix:false});
    this.lineUpSpeed = -1;
    this.horzMultiplier = this.params.whichSide == "left" ? 1.0 : -1.0;
    this.sideBase = this.params.whichSide == "left" ? 0 : canvasWidth;
    if (this.params.moveSpeed) this.lineUpSpeed = 0+this.params.moveSpeed;
    this.lastNP = 0;
    this.lastVert = 0;
    this.vertOffset = 0;
    this.mapScaleFactor = 1.5;
    this.mapLeftPosition = this.params.whichSide == "left" ?
        (canvasWidth * (0.1) + (pngWidth * this.mapScaleFactor ) / 2) :
        canvasWidth - ((pngWidth * this.mapScaleFactor ) / 2 + canvasWidth * (0.1));
    this.loadComplete = false;
    var self = this;
    $(document).keypress(function(event) {
        self.handleKeyPress(event);
    });


    this.stopAllNotes = function() {
        while(this.noteQueue.length>0) {
            clearTimeout(this.noteQueue.pop());
        }
    }

    this.handleKeyPress = function(e) {
        if (((e.key == "l") || (e.key =="L")) && (this.linePaths)) {
            this.linePaths.visible = !this.linePaths.visible;
        }
        if (((e.key == "m") || (e.key =="M")) && (this.mapPaths)) {
            this.mapPaths.visible = !this.mapPaths.visible;
        }
    }

    this.startSynth = function(whichDate,northOrSouth) {
        this.stopAllNotes();
        this.linePaths.translate([0,(0-this.vertOffset)]);
        this.linePaths.removeChildren();
        this.noteQueue = new Array();
        this.pointQueue = new Array();
        this.linePaths = new paper.Group();
        this.lineUpSpeed = -1;
        this.horzMultiplier = this.params.whichSide == "left" ? 1.0 : -1.0;
        this.sideBase = this.params.whichSide == "left" ? 0 : canvasWidth;
        if (this.params.moveSpeed) this.lineUpSpeed = 0-this.params.moveSpeed;
        this.lastNP = 0;
        this.lastVert = 0;
        this.vertOffset = 0;
        this.loadComplete = false;
        this.fileString = "icefiles/"+whichDate.getFullYear()+"/"+(whichDate.getMonth()+1)+"/"+whichDate.getFullYear()+"-"+(whichDate.getMonth()+1)+"-"+whichDate.getDate()+"_"+northOrSouth;
        paper.project.importSVG(this.fileString+".svg",{insert:false,onLoad:this.setupSVG});

    }

    this.setupSVG = function(svgItem,svgStr) {

        self.mapSVG = svgItem;
        self.mapPaths.addChild(self.mapSVG);
        self.mapPaths.position = new paper.Point(0,0);
        self.mapSVG.fillColor = new paper.Color(0.7,1,1);
        self.mapSVG.opacity = 0.0;
        $.getJSON(self.fileString+".json" , function(data) {self.startMusic(data)});
    }

    this.drawNote = function(nP,nT,nV,nRx,nRy) {
        var vertPosition = (nT / 50);
        var newPath = new paper.Path();
        newPath.strokeWidth = 2;
        newPath.strokeColor = new paper.Color(nV/127);
        newPath.add([(this.sideBase + (this.lastNP * 3 * this.horzMultiplier)),this.lastVert]);
        newPath.add([(this.sideBase + (nP * 3 * this.horzMultiplier)),vertPosition]);
        newPath.translate(0,this.vertOffset);
        this.linePaths.addChild(newPath);
        this.lastVert = vertPosition;
        this.lastNP = nP;
        var thisPoint = this.pointQueue.shift();
        thisPoint.visible = true;
        thisPoint.tweenTo({'opacity':0.0},5000)
        paper.view.draw();
    }

    this.startMusic = function (data) {
        var notePitch,noteTime,noteDuration,noteVelocity,noteRawX,noteRawY,newPoint;
        if (self.params.internalOrExternal == "external") {
            MIDI.setVolume(self.internalMidiChannel, 127);
        }
        for(var i=0;i<data.orderedPoints.length;i++) {
            notePitch = data.orderedPoints[i].pitch;
            noteTime = data.orderedPoints[i].time;
            noteDuration = data.orderedPoints[i].duration;
            noteVelocity = data.orderedPoints[i].velocity;
            noteRawX = parseFloat(data.orderedPoints[i].rawx);
            noteRawY = parseFloat(data.orderedPoints[i].rawy);
            newPoint =  new paper.Path.Circle(new paper.Point(noteRawX,self.mapPaths.bounds.height-noteRawY), 1.0);
            newPoint.fillColor = new paper.Color(1,1,1);
            newPoint.visible = false;
            self.pointQueue.push(newPoint);
            self.mapPaths.addChild(newPoint);
            self.noteQueue.push(setTimeout(function(nP,nT,nV,nRx,nRy){
                if (self.params.internalOrExternal == "external") {
                    self.externalMidiOut.playNote(nP,self.externalMidiChannel,{duration:noteDuration,velocity:noteVelocity});
                } else {
                    MIDI.noteOn(self.internalMidiChannel, nP, noteVelocity, 0);
                    MIDI.noteOff(self.internalMidiChannel, nP,(noteDuration/1000));
                }
                self.drawNote(nP,nT,nV,nRx,nRy);
            },noteTime,notePitch,noteTime,noteVelocity,noteRawX,noteRawY));
        }
        self.mapPaths.translate(self.mapLeftPosition, 350);
        self.mapPaths.scale(self.mapScaleFactor);
        self.mapSVG.opacity = 0.5;
        this.loadComplete = true;
        if (isInstallation) setTimeout(self.startSynth,(noteTime+noteDuration));
    }

    this.moveUp = function() {
        if (self.loadComplete) {
            self.linePaths.translate([0,self.lineUpSpeed]);
            self.mapPaths.rotate(-0.1*self.horzMultiplier);
            self.vertOffset += self.lineUpSpeed;
            if (self.params.whichSide == "left") {
                globalUpSpeed = ((self.lastVert + self.vertOffset) - ($(window).height() / 2)) * -0.005 ;
                if (globalUpSpeed > -0.1) globalUpSpeed = -0.1;
            }
            self.lineUpSpeed = globalUpSpeed;
            paper.view.draw();
        }
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
    console.log('hello');
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




