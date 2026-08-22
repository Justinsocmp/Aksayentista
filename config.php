<?php
$host = "localhost";
$username = "root"; 
$password = "";     
$database = "acsci_sslg";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ADD THIS LINE BELOW TO SHOW THE CODE ON YOUR DASHBOARD
define('MASTER_VERIFICATION_CODE', 'ACSCI2026');
?>