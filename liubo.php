<?php

$servername = "project-db.cck6swwdomno.us-east-1.rds.amazonaws.com";
$username = "admin";
$password = "1Dt0LV4OS3NockoJJjl2";
$dbname = "MIDI_Synthesiser";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {

    echo "Connection failed: " . $conn->connect_error;
}

$select_users = "SELECT username, password FROM users";
$users = $conn->query($select_users);
$row = $users->fetch_assoc();

echo $row["username"];
?>