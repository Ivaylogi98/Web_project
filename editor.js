
document.getElementById("import").addEventListener("change", importFile);
document.getElementById("export").addEventListener("click", exportFile);

async function fetchData(url, dataToSend) {
    try {
        let response = await fetch(url, {
            method: "post",
            body: dataToSend
        });
        return response.text();
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

    console.log("imported " + file.name);
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