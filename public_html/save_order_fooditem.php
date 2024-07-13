<?php
require_once 'connection.php';

// function to send order details by email to the courier of the post - COULDN"T IMPLEMENT
//since no email, display all orders on the user's deliver page

/*
function sendOrderEmail($conn, $postID, $orderID) {
    // get the email of the courier
    $stmt = $conn->prepare("
        SELECT p.email, u.first_name, u.last_name
        FROM Post p
        JOIN User u ON p.email = u.email
        WHERE p.postID = :postID
    ");
    $stmt->bindParam(':postID', $postID);
    $stmt->execute();
    $userDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    $userEmail = $userDetails['email'];
    $userName = $userDetails['first_name'] . " " . $userDetails['last_name'];

    // get the food items
    $stmt = $conn->prepare("
        SELECT f.name, f.price
        FROM Order_FoodItem ofi
        JOIN FoodItem f ON ofi.foodID = f.foodID
        WHERE ofi.orderID = :orderID
    ");
    $stmt->bindParam(':orderID', $orderID);
    $stmt->execute();
    $orderedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //tried doing email stuff but couldn't
    // create the email content
    /*$subject = "New Order for Your Post (Post ID: $postID)";
    $emailContent = "Hello $userName,\n\n";
    $emailContent .= "You have received a new order for your post (Post ID: $postID). Here are the details:\n\n";
    foreach ($orderedItems as $item) {
        $emailContent .= "- " . $item['name'] . " ($" . number_format($item['price'], 2) . ")\n";
    }
    $emailContent .= "\nThank you,\nWhile You're At It";

    // send the email
    //$headers = "From: kshitijkokkera@gmail.com";
    //mail($userEmail, $subject, $emailContent, $headers);
    
}*/


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postID = $_POST['postID'];
    $foodItems = isset($_POST['foodItems']) ? $_POST['foodItems'] : [];

    try {
        // start a transaction
        $conn->beginTransaction();

        // create a new order into the Order_Item table
        $stmt = $conn->prepare("INSERT INTO Order_Item (postID) VALUES (:postID)");
        $stmt->bindParam(':postID', $postID);
        $stmt->execute();
        $orderID = $conn->lastInsertId();

        // add the selected food items into the Order_FoodItem table
        if (!empty($foodItems)) {
            foreach ($foodItems as $foodID) {
                $stmt = $conn->prepare("INSERT INTO Order_FoodItem (orderID, foodID) VALUES (:orderID, :foodID)");
                $stmt->bindParam(':orderID', $orderID);
                $stmt->bindParam(':foodID', $foodID);
                $stmt->execute();
            }
        }

        // add the user's email and orderID into the Customer table
        $stmt = $conn->prepare("INSERT INTO Customer (email, orderID) VALUES (:email, :orderID)");
        $stmt->bindParam(':email', $_SESSION['user_ID']);
        $stmt->bindParam(':orderID', $orderID);
        $stmt->execute();

        // commit the transaction
        $conn->commit();

        // send the order details by email to the user of the post
        //sendOrderEmail($conn, postID, orderID);

        echo "Order saved successfully!";

    } 
    
    catch (PDOException $e) {
        // roll back the transaction in case of an error
        $conn->rollBack();
        echo "Failed to save order: " . $e->getMessage();
    }
}
?>
