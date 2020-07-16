
document.getElementById("import").addEventListener("change", importFile);
document.getElementById("export").addEventListener("click", exportFile);
document.getElementById("octave").addEventListener("change", changeOctave());

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

async function importFile(event) {

    const files = document.querySelector('[type=file]').files;
    var file = files[0];
    
    console.log("importing " + file.name);
    let url = "midi-parser.php";
    const dataToSend = new FormData();
    dataToSend.append('file', file);
    var content = await fetchData(url, dataToSend);
    document.getElementById("timeline").innerHTML = content;
    song = content;
    console.log(song);
    console.log("imported " + file.name);

    loadTimeline(song);
}

function displayContents(contents) {
    var element = document.getElementById('timeline');
    element.innerHTML = contents;
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

function addTrack(){

    var track = document.createElement("section");
    track.setAttribute("id", "track" + (numberOfTracks + 1));
    track.setAttribute("class", "tracks");
    document.getElementById("timeline").appendChild(track);
}

function addNote(noteTextName){
    console.log("adding note:" + note);
    var note = document.createElement("li");
    note.setAttribute("class", "note");

    var noteName = document.createTextNode(noteTextName);
    noteName.innerHTML = "Choose note";
    note.appendChild(noteName);

    var noteStart = document.createElement("input");
    noteStart.setAttribute("type", "number");
    noteStart.setAttribute("class", "note-attribute");
    note.appendChild(noteStart);

    var noteEnd = document.createElement("input");
    noteEnd.setAttribute("type", "number");
    noteEnd.setAttribute("class", "note-attribute");
    note.appendChild(noteEnd);

    document.getElementById("track" + chosenTrack).appendChild(note);
}
function showNotes(){
    var x = document.getElementById("note-tools");
    if (x.style.display === "flex") {
      x.style.display = "none";
    } else {
      x.style.display = "flex";
    }
}
function changeOctave(){
    chosenOctave = document.getElementById("octave").value;
}