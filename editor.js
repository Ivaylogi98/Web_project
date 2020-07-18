
document.getElementById("import").addEventListener("change", importFile);
document.getElementById("export").addEventListener("click", exportFile);

var song;
var numberOfTracks = 0;
var chosenTrack = 1;
var chosenOctave = 1;

async function fetchData(url, dataToSend) {
    try {
        let response = await fetch(url, {
            method: "post",
            body: dataToSend
        });
        return response.json();
    } catch (error) {
        console.error(error);
    }
}

async function importFile() {

    const files = document.querySelector('[type=file]').files;
    var file = files[0];

    console.log("importing " + file.name);
    let url = "midi-parser.php";
    const dataToSend = new FormData();
    dataToSend.append('file', file);
    var content = await fetchData(url, dataToSend);
    //document.getElementById("timeline").innerHTML = content;
    song = content;
    console.log(song);
    console.log("imported " + file.name);

    showSong();

    loadTimeline(song);
}

function exportFile(content) {
    var filename = "foo.txt";

    var content = document.getElementById("timeline").innerHTML;

    var pom = document.createElement('a');
    pom.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(content));
    pom.setAttribute('download', filename);

    if (document.createEvent) {
        var event = document.createEvent('MouseEvents');
        event.initEvent('click', true, true);
        pom.dispatchEvent(event);
    }
    else {
        pom.click();
    }

    console.log("exporting " + filename);
}

function displaySongTimeline() {
    for (let i = 0; i < numberOfTracks; i++) {
        addTrack();
        //add note events
    }
}

function addTrack() {
    var track = document.createElement("li");
    track.setAttribute("id", "track" + (++numberOfTracks));
    track.setAttribute("class", "track");

    var trackCheckBox = document.createElement("input");
    trackCheckBox.setAttribute("type", "radio");
    trackCheckBox.setAttribute("id", "radio-track" + numberOfTracks);
    trackCheckBox.setAttribute("value", numberOfTracks);
    trackCheckBox.setAttribute("name", "tracks");
    trackCheckBox.style.marginTop = "6vh";
    track.appendChild(trackCheckBox);

    var trackHeader = document.createElement("label");
    trackHeader.setAttribute("for", "track" + numberOfTracks);
    trackHeader.innerHTML = "track" + numberOfTracks;
    trackHeader.style.paddingTop = "5.5vh";
    trackHeader.style.marginLeft = "0.5vw";
    track.appendChild(trackHeader);

    document.getElementById("timeline").appendChild(track);
    document.getElementById("radio-track" + numberOfTracks).addEventListener("click", function(e){
        chosenTrack = e.currentTarget.value;
    });
}

function addNote(noteTextName) {

    console.log("adding note:" + noteTextName + chosenOctave);
    var note = document.createElement("li");
    note.setAttribute("class", "note");

    var noteName = document.createElement("div");
    noteName.setAttribute("class", "note-name");
    noteName.innerHTML = noteTextName + chosenOctave;
    note.appendChild(noteName);

    var noteStart = document.createElement("input");
    noteStart.setAttribute("type", "number");
    noteStart.setAttribute("class", "note-attribute");
    note.appendChild(noteStart);

    var noteEnd = document.createElement("input");
    noteEnd.setAttribute("type", "number");
    noteEnd.setAttribute("class", "note-attribute");
    note.appendChild(noteEnd);

    var noteDelete = document.createElement("button");
    noteDelete.setAttribute("class", "delete-button");
    noteDelete.innerHTML = "X";
    noteDelete.onclick = function(){
        noteDelete.parentElement.remove()
        return;
    };
    note.appendChild(noteDelete);

    document.getElementById("track" + chosenTrack).appendChild(note);
}
function showAddNoteButtons() {
    var x = document.getElementById("note-tools");
    if (x.style.display === "flex") {
        x.style.display = "none";
    } else {
        x.style.display = "flex";
    }
}
function changeOctave() {
    console.log(document.getElementById("octave").value);
    chosenOctave = document.getElementById("octave").value;
}

function pickTrack() {
    var pickTrack = document.querySelectorAll("[name=pickTrack"),
        parentTrack = document.getElementById("pickTrack");
    for (var index = 0; index < pickTrack.length; index++) {
        var cb = pickTrack[index];
        cb.addEventListener("change", function (evt) {
            var checked = 0;
            for (var j = 0; j < pickTrack.length; j++) {
                if (pickTrack[j].checked) {
                    checked++;
                } 
            }
            switch (checked) {
                case 0:
                    parentTrack.checked = false;
                    parentTrack.indeterminate = false;
                    break;
                case 1:
                    parentTrack.checked = false;
                    parentTrack.indeterminate = true;
                    break;
                default:
                    parentTrack.checked = true;
                    parentTrack.indeterminate = false;
                    break;
            }
        });
    }
}