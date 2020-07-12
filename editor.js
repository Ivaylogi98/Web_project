
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
    //event.preventDefault();
    /*var file = event.target.files[0];
    if (!file) {
        return;
    }
    var reader = new FileReader();
    reader.onload = function (e) {
        var contents = reader.result;
        midiParser(new DataView(contents));
    };
    reader.readAsArrayBuffer(file);*/
    const files = document.querySelector('[type=file]').files;
    var file = files[0];
    //console.log(file);
    
    console.log("importing " + file.name);
    let url = "midi-parser.php";
    const dataToSend = new FormData();
    dataToSend.append('file', file);
    var content = await fetchData(url, dataToSend);
    document.getElementById("timeline").innerHTML = content;

    console.log("imported " + file.name);
}

function midiParser(buffer) {
    //displayContents(content);
    //console.log(typeof(content));

    parser = {
        buffer: buffer,
        position: 0,
        readByte: function () {
            return this.buffer.getUint8(this.position++);
        },
        read2Bytes: function () {
            return this.buffer.getUint16(this.position++);
        },
        readVLV: function () {
            var value = 0;
            var i = 0;
            var byte;

            while (i++ < 4) {
                byte = this.readUint8();

                if (byte & 0x80) {
                    value += byte & 0x7f;
                    value <<= 7;
                } else {
                    return value + byte;
                }
            }
            throw new Error("Error with VLV");
        },
        readBytes: function (length) {
            var bytes = [];

            while (0 < length) {
                bytes.push(this.readUint8());
                length--;
            }
            return bytes;
        },
        isEnd: function () {
            return this.position === this.buffer.byteLength;
        }
    }
    /*var chunkSize = parseInt(content.substring(0,4));
    var format;
    var numberOfTracks;
    var time_division;
    var events;
    console.log("Chunk size: " + content.substring(0,4));
    for(let i = 0; i < content.length; i++){
        console.log(content[i]);
        //events.push();
    }*/

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