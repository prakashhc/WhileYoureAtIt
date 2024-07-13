<?php
require_once 'connection.php';

// get the postID
$postID = $_GET['postID'];
 // get the food items from the restauant thats tied to the postID
$stmt = $conn->prepare("
    SELECT foodID, name, price
    FROM FoodItem
    WHERE restaurant = (
        SELECT ff.restaurant
        FROM Fast_Food ff
        WHERE ff.postID = :postID
    )
");

$stmt->bindParam(':postID', $postID);
$stmt->execute();
$foodItems = $stmt->fetchAll(PDO::FETCH_ASSOC);



// return the food items as a JSON response
header('Content-Type: application/json');
echo json_encode($foodItems);
?>
