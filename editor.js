
document.getElementById("import").addEventListener("change", importFile);
document.getElementById("export").addEventListener("click", exportFile);

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

    let url = "midi-parser.php";
    const dataToSend = new FormData();
    dataToSend.append('file', file);
    var content = await fetchData(url, dataToSend);
    //document.getElementById("timeline").innerHTML = content;
    song = content;
    console.log(song);
    console.log("imported " + file.name);

    loadTimeline(song);
}

function exportFile(content) {
    var filename = "song.json";

    var content = getSong();
    console.log(content);

    var a = document.createElement('a');
    var file = new Blob([JSON.stringify(content)], {type: 'text/plain'});
    a.href = URL.createObjectURL(file);
    a.download = filename;
    a.click();

    console.log("exported " + filename);
}

function loadTimeline(song) {
    numberOfTracks = 0;
    document.getElementById("timeline").innerHTML = "";

    for (track of Object.values(song)) {
        addTrack();
        console.log(Object.values(track));
        for (note of Object.values(track)) {
            addNote(note.note, note.start, note.end);
        }

    }
}

function getSong() {
    var songHTML = document.getElementById("timeline").childNodes;
    var trackNumber = 1;
    var songJSON = {};
    for (trackHTML of songHTML) {
        var trackJSON = [];
        for (let i = 3; i < trackHTML.childNodes.length; i++) {
            let noteHTML = trackHTML.childNodes[i];
            console.log(trackHTML.childNodes[i]);
            var noteJSON = {
                note: noteHTML.childNodes[0].innerHTML,
                start: noteHTML.childNodes[1].value,
                end: noteHTML.childNodes[2].value,
            }
            trackJSON.push(noteJSON);
        }
        songJSON["track" + (trackNumber++)] = trackJSON;
    }
    return songJSON;

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

    var trackDelete = document.createElement("button");
    trackDelete.setAttribute("class", "delete-track-button");
    trackDelete.innerHTML = "X";
    trackDelete.onclick = function () {
        trackDelete.parentElement.remove();
        return;
    };
    track.appendChild(trackDelete);

    var trackHeader = document.createElement("label");
    trackHeader.setAttribute("for", "track" + numberOfTracks);
    trackHeader.innerHTML = "track" + numberOfTracks;
    trackHeader.style.paddingTop = "5.5vh";
    trackHeader.style.marginLeft = "0.5vw";
    track.appendChild(trackHeader);

    document.getElementById("timeline").appendChild(track);
    document.getElementById("radio-track" + numberOfTracks).addEventListener("click", function (e) {
        chosenTrack = e.currentTarget.value;
    });
    document.getElementById("radio-track" + numberOfTracks).checked = true;
    chosenTrack = numberOfTracks;
}

function addNote(noteTextName, start = 0, end = 0) {

    //console.log("adding note:" + noteTextName + chosenOctave);
    var note = document.createElement("li");
    note.setAttribute("class", "note");

    var noteName = document.createElement("div");
    noteName.setAttribute("class", "note-name");
    var noteTextNameLastChar = noteTextName.slice(-1);
    if (noteTextNameLastChar.slice(-1) >= 0 && noteTextNameLastChar <= 9) noteName.innerHTML = noteTextName;
    else noteName.innerHTML = noteTextName + chosenOctave;
    note.appendChild(noteName);

    var noteStart = document.createElement("input");
    noteStart.setAttribute("type", "number");
    noteStart.setAttribute("class", "note-attribute");
    noteStart.setAttribute("value", start);
    note.appendChild(noteStart);

    var noteEnd = document.createElement("input");
    noteEnd.setAttribute("type", "number");
    noteEnd.setAttribute("class", "note-attribute");
    noteEnd.setAttribute("value", end);
    note.appendChild(noteEnd);

    var noteDelete = document.createElement("button");
    noteDelete.setAttribute("class", "delete-note-button");
    noteDelete.innerHTML = "X";
    noteDelete.onclick = function () {
        noteDelete.parentElement.remove();
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
    console.log("octave changed to: " + document.getElementById("octave").value);
    chosenOctave = document.getElementById("octave").value;
}