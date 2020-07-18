<?php
require("./header.php");
?>

<script async src="editor.js"></script>

<div class="tools">
    <button class="tool" id="add-track" onclick="addTrack()">Add track</button>
    <button class="tool" id="add-note" onclick="showAddNoteButtons()">Show notes</button>
    <label for="import" class="import-tool">Import</label>
    <input type="file" id="import"></input>
    <button type="button" class="tool" id="export">Export</button>
</div>
<div class="tools" id="note-tools">
    <label for="octave" class="note-tool">Octave:</label>
    <input type="number" id="octave" min=0 max=7 value="1" oninput="changeOctave()">
    <label class="note-tool">Add:</label>
    <button class="note-name-button" id="add-track" onclick="addNote('c')">C</button>
    <button class="note-name-button" id="add-track" onclick="addNote('c_')">C#</button>
    <button class="note-name-button" id="add-track" onclick="addNote('d')">D</button>
    <button class="note-name-button" id="add-track" onclick="addNote('d_')">D#</button>
    <button class="note-name-button" id="add-track" onclick="addNote('e')">E</button>
    <button class="note-name-button" id="add-track" onclick="addNote('f')">F</button>
    <button class="note-name-button" id="add-track" onclick="addNote('f_')">F#</button>
    <button class="note-name-button" id="add-track" onclick="addNote('g')">G</button>
    <button class="note-name-button" id="add-track" onclick="addNote('g_')">G#</button>
    <button class="note-name-button" id="add-track" onclick="addNote('a')">A</button>
    <button class="note-name-button" id="add-track" onclick="addNote('a_')">A#</button>
    <button class="note-name-button" id="add-track" onclick="addNote('b')">B</button>
</div>
<ul id="timeline"></ul>

<?php
require("./footer.php");
?>