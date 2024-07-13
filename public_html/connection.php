<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Parameters of the MySQL connection
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

session_start();


// If the user_ID session is not set, then the user has not logged in yet
if (!isset($_SESSION['user_ID'])) {
    // If the page is receiving the email and password from the login form then verify the login data
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $stmt = $conn->prepare("SELECT email, password FROM User WHERE email=:email");
        $stmt->bindValue(':email', $_POST['email']);
        $stmt->execute();
        $queryResult = $stmt->fetch();
    

        // Verify password submitted by the user with the hash stored in the database
        if (!empty($queryResult) && password_verify($_POST["password"], $queryResult['password'])) {
            // Create session variable
            $_SESSION['user_ID'] = $queryResult['email'];

            // Redirect to main page
            header("Location: index.php");
        } else {
            // Password mismatch, show login page
            require('signin.php');
            exit();
        }
    } else {
        // Show login page
        require('signin.php');
        exit();
    }
}
?>