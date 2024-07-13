<?php
// Report all errors and display them (for debugging purposes)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Parameters for the MySQL connection
$servername = "cmsc508.com";
$username = "24SP_chatlanipr";
$password = "";
$database = "24SP_chatlanipr_pr";

try {
    // Establish a connection with the MySQL server
    $conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

// Start session
session_start();
