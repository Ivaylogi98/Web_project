<?php

$uploaddir = 'uploads/';
$uploadfile = $uploaddir . basename($_FILES['file']['name']);
//upload file
if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadfile)) {
    //echo "File was successfully uploaded.\n";
} else {
    echo "Error uploading file!";
}

$filesize = filesize($uploadfile);
// open file for reading in binary mode
$fp = fopen($uploadfile, 'rb');
// read the entire file into a binary string
$binary = fread($fp, $filesize);
// finally close the file
fclose($fp);

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
            $last = 'NotSet';
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
                            break; // Ende Meta
        
                        case 0xF0: // SysEx
                            $p +=1;
                            $len = readVarLen($binStr, $p);
                            if (($len+$p) > $trackLen) error("SysEx has corrupt variable length field ($len) [track: $tn dt: $dt p: $p]");
                            $str = 'f0';
                            for ($i=0;$i<$len;$i++) $str.=' '.sprintf("%02x",ord($binStr[$p+$i])); # FIXED
                            $track[] = "$time SysEx $str";
                            $p+=$len;
                            break;
                        default: // Repetition of last event?
                            switch ($last){
                                case 'On':
                                case 'Off':
                                    $note = ord($binStr[$p]);
                                    $vel = ord($binStr[$p+1]);
                                    $track[] = "$time $last ch=$chan n=$note v=$vel";
                                    $p+=2;
                                    break;
                                case 'PrCh':
                                    $prog = ord($binStr[$p]);
                                    $track[] = "$time PrCh ch=$chan p=$prog";
                                    $p+=1;
                                    break;
                                case 'PoPr':
                                    $note = ord($binStr[$p+1]);
                                    $val = ord($binStr[$p+2]);
                                    $track[] = "$time PoPr ch=$chan n=$note v=$val";
                                    $p+=2;
                                    break;
                                case 'ChPr':
                                    $val = ord($binStr[$p]);
                                    $track[] = "$time ChPr ch=$chan v=$val";
                                    $p+=1;
                                    break;
                                case 'Par':
                                    $c = ord($binStr[$p]);
                                    $val = ord($binStr[$p+1]);
                                    $track[] = "$time Par ch=$chan c=$c v=$val";
                                    $p+=2;
                                    break;
                                case 'Pb':
                                    $val = (ord($binStr[$p])  & 0x7F) | (( ord($binStr[$p+1]) & 0x7F)<<7);
                                    $track[] = "$time Pb ch=$chan v=$val";
                                    $p+=2;
                                    break;
                                default:
            // MM: ToDo: Repetition of SysEx and META-events? with <last>?? \n";
                                    error("unknown repetition: $last");
                            }  // switch ($last)
                    } // switch ($byte)
            } // switch ($high)
        } // while
        return $track;
    }

    function midi2json(){
        $notes = array();
        foreach($this->tracks as $track){
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
                    $notes_str = ["c3", "c_3", "d3", "d_3", "e3", "f3", "f_3", "g3", "a3", "a_3", "b3", "c4", "c_4", "d4", "d_4", "e4", "f4", "f_4", "g4", "a4", "a_4", "b4"];
                    
                    $note = $notes_str[$note_int - 48].' '.$event[0].' '.$end_note;
                    array_push($notes, $note);
                }
            }
        }
        echo json_encode($notes);
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
    if ((int)phpversion()>=5)
	eval('throw new Exception($str);'); // throws php5-exceptions. the main script can deal with these errors.
	else
    die('>>> '.$str.'!');
}

$midi = new Midi;
$midi->importMidiFile($uploadfile);
$midi->midi2json();

?>