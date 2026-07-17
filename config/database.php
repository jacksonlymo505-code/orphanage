<?php
$host = "localhost";
$username = "root";  // your database username
$password = "";      // your database password
$database = "orphanage_db";  // your database name

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?> 