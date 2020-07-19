<?php

$uploaddir = 'uploads/';
$uploadfile = $uploaddir . basename($_FILES['file']['name']);
//upload file
if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
    //echo "File was successfully uploaded.\n";
} else {
    echo "Error uploading file!";
}
$ext = strtolower(pathinfo($uploadfile, PATHINFO_EXTENSION));
if($ext == "json"){
    $filesize = filesize($uploadfile);
    $fp = fopen($uploadfile, 'r');
    $json = fread($fp, $filesize);
    fclose($fp);
    echo $json;
    exit();
}

$filesize = filesize($uploadfile);
// open file for reading in binary mode
$fp = fopen($uploadfile, 'rb');
// read the entire file into a binary string
$binary = fread($fp, $filesize);
// finally close the file
fclose($fp);

class song{
}

class Midi{

    var $tracks;
    var $type;
    var $timebase;
    var $tempoMsgNum;
    var $tempo;

    function importMidiFile($uploadfile){

        $filesize = filesize($uploadfile);
        // open file for reading in binary mode
        $file = fopen($uploadfile, 'rb');
        // read the entire file into a binary string
        $song = fread($file, $filesize);
        // finally close the file
        fclose($file);

        if (strpos($song,'MThd')>0) $song = substr($song,strpos($song,'MThd'));//remove header start
        $header = substr($song,0,14);
        if (substr($header,0,8)!="MThd\0\0\0\6") error('wrong MIDI-header');
        $type = ord($header[9]);
        if ($type>1) error('only SMF Type 0 and 1 supported');
        $timebase = ord($header[12])*256 + ord($header[13]);

        $this->type = $type;
        $this->timebase = $timebase;
        $this->tempo = 0;
        $trackStrings = explode('MTrk',$song);
        array_shift($trackStrings);
        $tracks = array();
        $tsc = count($trackStrings);
        if (func_num_args()>1){
            $tn =  func_get_arg(1);
            if ($tn>=$tsc) error('SMF has less tracks than $tn');
            $tracks[] = $this->parseTrack($trackStrings[$tn],$tn);
        } else
            for ($i=0;$i<$tsc;$i++)  $tracks[] = $this->parseTrack($trackStrings[$i],$i);
        $this->tracks = $tracks;
    }

    function parseTrack($binStr, $tn){

        $trackLen = strlen($binStr);

        $p=4;
        $time = 0;
        $track = array();
        while ($p < $trackLen){
            //delta time = how much time has to pass after the previous event for the current event to take place
            $dt = readVarLen($binStr,$p);
            $time += $dt;
            $byte = ord($binStr[$p]);
            $high = $byte >> 4;
            $low = $byte - $high*16;
            $chan = $low+1;
            switch($high){
                case 0x0C: //PrCh = ProgramChange
                    $chan = $low+1;
                    $prog = ord($binStr[$p+1]);
                    $last = 'PrCh';
                    $track[] = "$time PrCh ch=$chan p=$prog";
                    $p+=2;
                    break;
                case 0x09: //On
                    $chan = $low+1;
                    $note = ord($binStr[$p+1]);
                    $vel = ord($binStr[$p+2]);
                    $last = 'On';
                    $track[] = "$time On ch=$chan n=$note v=$vel";
                    $p+=3;
                    break;
                case 0x08: //Off
                    $chan = $low+1;
                    $note = ord($binStr[$p+1]);
                    $vel = ord($binStr[$p+2]);
                    $last = 'Off';
                    $track[] = "$time Off ch=$chan n=$note v=$vel";
                    $p+=3;
                    break;
                case 0x0A: //PoPr = PolyPressure
                    $chan = $low+1;
                    $note = ord($binStr[$p+1]);
                    $val = ord($binStr[$p+2]);
                    $last = 'PoPr';
                    $track[] = "$time PoPr ch=$chan n=$note v=$val";
                    $p+=3;
                    break;
                case 0x0B: //Par = ControllerChange
                    $chan = $low+1;
                    $c = ord($binStr[$p+1]);
                    $val = ord($binStr[$p+2]);
                    $last = 'Par';
                    $track[] = "$time Par ch=$chan c=$c v=$val";
                    $p+=3;
                    break;
                case 0x0D: //ChPr = ChannelPressure
                    $chan = $low+1;
                    $val = ord($binStr[$p+1]);
                    $last = 'ChPr';
                    $track[] = "$time ChPr ch=$chan v=$val";
                    $p+=2;
                    break;
                case 0x0E: //Pb = PitchBend
                    $chan = $low+1;
                    $val = (ord($binStr[$p+1]) & 0x7F) | (((ord($binStr[$p+2])) & 0x7F) << 7);
                    $last = 'Pb';
                    $track[] = "$time Pb ch=$chan v=$val";
                    $p+=3;
                    break;
                default:
                    switch($byte){
                        case 0xFF: // Meta
                            $meta = ord($binStr[$p+1]);
                            switch ($meta){
                                case 0x00: // sequence_number
                                    $tmp = ord($binStr[$p+2]);
                                    if ($tmp==0x00) { $num = $tn; $p+=3;}
                                    else { $num= 1; $p+=5; }
                                    $track[] = "$time Seqnr $num";
                                    break;
        
                                case 0x01: // Meta Text
                                case 0x02: // Meta Copyright
                                case 0x03: // Meta TrackName ???sequence_name???
                                case 0x04: // Meta InstrumentName
                                case 0x05: // Meta Lyrics
                                case 0x06: // Meta Marker
                                case 0x07: // Meta Cue
                                    $texttypes = array('Text','Copyright','TrkName','InstrName','Lyric','Marker','Cue');
                                    $type = $texttypes[$meta-1];
                                    $p +=2;
                                    $len = readVarLen($binStr, $p);
                                    if (($len+$p) > $trackLen) error("Meta $type has corrupt variable length field ($len) [track: $tn dt: $dt]");
                                    $txt = substr($binStr, $p,$len);
                                    $track[] = "$time Meta $type \"$txt\"";
                                    $p+=$len;
                                    break;
                                case 0x20: // ChannelPrefix
                                    $chan = ord($binStr[$p+3]);
                                    if ($chan<10) $chan = '0'.$chan;//???
                                    $track[] = "$time Meta 0x20 $chan";
                                    $p+=4;
                                    break;
                                case 0x21: // ChannelPrefixOrPort
                                    $chan = ord($binStr[$p+3]);
                                    if ($chan<10) $chan = '0'.$chan;//???
                                    $track[] = "$time Meta 0x21 $chan";
                                    $p+=4;
                                    break;
                                case 0x2F: // Meta TrkEnd
                                    $track[] = "$time Meta TrkEnd";
                                    return $track;//ignore rest
                                    break;
                                case 0x51: // Tempo
                                    $tempo = ord($binStr[$p+3])*256*256 + ord($binStr[$p+4])*256 + ord($binStr[$p+5]);
                                    $track[] = "$time Tempo $tempo";
                                    if ($tn==0 && $time==0) {
                                        $this->tempo = $tempo;// ???
                                        $this->tempoMsgNum = count($track) - 1;
                                    }
                                    $p+=6;
                                    break;
                                case 0x54: // SMPTE offset
                                    $h = ord($binStr[$p+3]);
                                    $m = ord($binStr[$p+4]);
                                    $s = ord($binStr[$p+5]);
                                    $f = ord($binStr[$p+6]);
                                    $fh = ord($binStr[$p+7]);
                                    $track[] = "$time SMPTE $h $m $s $f $fh";
                                    $p+=8;
                                    break;
                                case 0x58: // TimeSig
                                    $z = ord($binStr[$p+3]);
                                    $t = pow(2,ord($binStr[$p+4]));
                                    $mc = ord($binStr[$p+5]);
                                    $c = ord($binStr[$p+6]);
                                    $track[] = "$time TimeSig $z/$t $mc $c";
                                    $p+=7;
                                    break;
                                case 0x59: // KeySig
                                    $vz = ord($binStr[$p+3]);
                                    $g = ord($binStr[$p+4])==0?'major':'minor';
                                    $track[] = "$time KeySig $vz $g";
                                    $p+=5;
                                    break;
                                case 0x7F: // Sequencer specific data (string or hexString???)
                                    $p +=2;
                                    $len = readVarLen($binStr, $p);
                                    if (($len+$p) > $trackLen) error("SeqSpec has corrupt variable length field ($len) [track: $tn dt: $dt]");
                                    $p-=3;
                                    $data='';
                                    for ($i=0;$i<$len;$i++) $data.=' '.sprintf("%02x",ord($binStr[$p+3+$i]));
                                    $track[] = "$time SeqSpec$data";
                                    $p+=$len+3;
                                    break;
        
                                default:
            // MM added: accept "unknown" Meta-Events
                                    $metacode = sprintf("%02x", ord($binStr[$p+1]) );
                                    $p +=2;
                                    $len = readVarLen($binStr, $p);
                                    if (($len+$p) > $trackLen) error("Meta $metacode has corrupt variable length field ($len) [track: $tn dt: $dt]");
                                    $p -=3;
                                    $data='';
                                    for ($i=0;$i<$len;$i++) $data.=' '.sprintf("%02x",ord($binStr[$p+3+$i]));
                                    $track[] = "$time Meta 0x$metacode $data";
                                    $p+=$len+3;
                                    break;
                            } // switch ($meta)
                            break; // End Meta
                        case 0xF0: // SysEx
                            $p +=1;
                            $len = readVarLen($binStr, $p);
                            if (($len+$p) > $trackLen) error("SysEx has corrupt variable length field ($len) [track: $tn dt: $dt p: $p]");
                            $str = 'f0';
                            for ($i=0;$i<$len;$i++) $str.=' '.sprintf("%02x",ord($binStr[$p+$i])); # FIXED
                            $track[] = "$time SysEx $str";
                            $p+=$len;
                            break;
                        case 0xF7:  // DivSysEx
                            $p +=1;
                            $len = readVarLen($binStr, $p);
                            if (($len+$p) > $trackLen) error("DivSysEx has corrupt variable length field ($len) [track: $tn dt: $dt p: $p]");
                            $str = 'f0';
                            for ($i=0;$i<$len;$i++) $str.=' '.sprintf("%02x",ord($binStr[$p+$i])); # FIXED
                            $track[] = "$time DivSysEx $str";
                            $p+=$len;
                            break;
                        default: // Repetition of last event?
                            error("unknown event: $byte");
                    } // switch ($byte)
            } // switch ($high)
        } // while
        return $track;
    }

    function midi2json(){
        //$song = array();
        $song = new song();
        $track_num = 1;
        foreach($this->tracks as $track){
            $json_track = array();
            $track_length = count($track);
            $track_end_time = explode(' ',$track[$track_length - 2])[0];
            for($i = 0; $i < $track_length; $i++)
            {
                $event = explode(' ', $track[$i]);
                if($event[1] === "On"){
                    $end_note = $track_end_time;
                    for($j = $i + 1; $j < $track_length; $j++)
                    {
                        $searching_note = explode(' ', $track[$j]);
                        if($searching_note[1] === "Off" && $searching_note[2] === $event[2] && $searching_note[3] == $event[3]){
                            $end_note = $searching_note[0];
                            break;
                        }
                    }
                    $note_int = explode('=',$event[3])[1];
                    $note_names = ["c", "c_", "d", "d_", "e", "f", "f_", "g", "g_", "a", "a_", "b"];
                    $note_name = $note_names[($note_int-24) % 12].floor(($note_int-12)/12);
                    $json_note = array("note"=>$note_name, "start"=>$event[0], "end"=>$end_note);
                    array_push($json_track, $json_note);
                }
            }
            if(count($json_track) > 0){
                //array_push($song, array("track".$track_num => $json_track));
                $track_name = "track".$track_num;
                $song->$track_name = $json_track;
                $track_num++;
            }
        }
        $fp = fopen('example.json', 'w');
        fwrite($fp, json_encode($song));
        fclose($fp);
        echo json_encode($song);
    }

}

function readVarLen($str,&$pos){
    if ( ($value = ord($str[$pos++])) & 0x80 ){
        $value &= 0x7F;
        do {
            $value = ($value << 7) + (($c = ord($str[$pos++])) & 0x7F);
        } while ($c & 0x80);
    }
    return($value);
}
function error($str){
    echo "Error: ".$str;
    exit();
}

$midi = new Midi;
$midi->importMidiFile($uploadfile);
$midi->midi2json();

?>