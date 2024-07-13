<?php
require_once 'connection.php';

// check if the user is logged in
if (!isset($_SESSION['user_ID'])) {
    // redirect to the sign-in page if they aren't logged in
    header("Location: signin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $buildingId = $_POST['building'] ?? '';
    $email = $_SESSION['user_ID']; // gets the email from the session

    // validates input
    $errors = array();

    // check if passwords match (if it's provided)
    if (!empty($password) && $password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    // if there are no errors, proceed with update
    if (empty($errors)) {
        // hash the password if provided
        $hashedPassword = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

        // update statements
        $stmts = array();

        if (!empty($firstName)) {
            $stmt = $conn->prepare("UPDATE User SET first_name = :firstName WHERE email = :email");
            $stmt->bindParam(':firstName', $firstName);
            $stmt->bindParam(':email', $email);
            $stmts[] = $stmt;
        }

        if (!empty($lastName)) {
            $stmt = $conn->prepare("UPDATE User SET last_name = :lastName WHERE email = :email");
            $stmt->bindParam(':lastName', $lastName);
            $stmt->bindParam(':email', $email);
            $stmts[] = $stmt;
        }

        if ($hashedPassword !== null) {
            $stmt = $conn->prepare("UPDATE User SET password = :password WHERE email = :email");
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':email', $email);
            $stmts[] = $stmt;
        }

        if (!empty($buildingId)) {
            $stmt = $conn->prepare("UPDATE User SET buildingID_id = :buildingId WHERE email = :email");
            $stmt->bindParam(':buildingId', $buildingId);
            $stmt->bindParam(':email', $email);
            $stmts[] = $stmt;
        }

        // update the user's allergies
        if (isset($_POST['allergies'])) {
            $newAllergies = $_POST['allergies'];

            // delete the user's existing allergies
            $stmt = $conn->prepare("DELETE FROM User_Allergies WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            // insert the new allergies
            foreach ($newAllergies as $allergen) {
                $stmt = $conn->prepare("INSERT INTO User_Allergies (email, Allergen) VALUES (:email, :allergen)");
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':allergen', $allergen);
                $stmt->execute();
            }
        }

        foreach ($stmts as $stmt) {
            $stmt->execute();
        }

        // redirect to the profile page after a successful update
        header("Location: profile.php");
        exit;
    }
}
?>