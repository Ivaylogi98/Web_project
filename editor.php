<?php
require("./header.php");
?>

<script async src="editor.js"></script>

<div class="tools">
    <button class="tool">Add note</button>
    <button class="tool">Add space</button>
    <button class="tool">Set tempo</button>
    <label for="import" class="import-tool">Import</label>
    <input type="file" id="import"></input>
    <button type="button" class="tool" id="export">Export</button>
</div>
<div id="timeline" contenteditable="true">Timeline</div>

<?php
require("./footer.php");
?>