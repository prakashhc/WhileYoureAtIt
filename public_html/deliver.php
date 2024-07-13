<?php
//PAGE WHERE USERS CHOOSE WHETHER THEY WANT TO POST AN ORDER FOR DELIVERY FOR HOME-COOKED OR FAST-FOOD

require_once 'connection.php';


//check if the user is logged in
if (!isset($_SESSION['user_ID'])) {
    header("Location: signin.php");
    exit;
}


//check if the user is an admin
$stmt = $conn->prepare("SELECT * FROM Admins WHERE email = :email");
$stmt->bindParam(':email', $_SESSION['user_ID']);
$stmt->execute();
$isAdmin = $stmt->rowCount() > 0;

//find orders related to posts made by the current user
//must take from order_item (orderID; postID); post (postID, datePosted, email, type); home_cooked (Dish, email); fast_food(email, restaurant); 
$stmt = $conn->prepare("
    SELECT oi.orderID, oi.postID, post.datePosted, post.type, homecook.Dish, fastfood.restaurant
    FROM Order_Item oi JOIN Post post ON oi.postID = post.postID LEFT JOIN Home_Cooked homecook ON post.postID = homecook.postID LEFT JOIN Fast_Food fastfood ON post.postID = fastfood.postID
    WHERE post.email = :email
    ORDER BY post.datePosted DESC");
$stmt->bindParam(':email', $_SESSION['user_ID']);
$stmt->execute();

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <title>While You're At It</title>
</head>



<body>
    <!-- Bootstrap Navigation bar -->
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container-fluid">
            <!-- Title -->
            <a class="navbar-brand" href="#">While You're At It</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <!-- Home -->
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>

                    <!-- Deliver -->
                    <li class="nav-item">
                        <a class="nav-link" href="deliver.php">Deliver</a>
                    </li>

                    <!-- Order -->
                    <li class="nav-item">
                        <a class="nav-link" href="order.php">Order</a>
                    </li>

                    <!-- Profile -->
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>

                    <!-- Admin Settings -->
                    <?php if ($isAdmin): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_settings.php">Admin Settings</a>
                        </li>
                    <?php endif; ?>
                </ul>

                <form class="d-flex" role="search">
                    <a href="signout.php" class="btn btn-outline-success">Logout</a>
                </form>
            </div>
        </div>
    </nav>
    <!-- End of Bootstrap nav -->



    <div class="IndexTitle">
        <h1>Delivery</h1>
        <h6>This is the delivery page. Here, you are doing the service. Below are two buttons: Home-Cooked and Fast-Food. Clicking on the home-cooked button will navigate you to the home-cooked form page, where you need to enter information about the meal you are willing to prepare for your building's residents.
            The Fast-Food option will also present you to a similar form where you indicate the Fast-Food restaurant you'll be picking food from. 
        </h6>

        <div class="buttons-container">
            <a href="homecooked.php" class="buttonDeliver">Home Cooked</a>
            <a href="fastfood.php" class="buttonDeliver">Fast Food</a>
        </div>
        <br>
        <br>

        <!-- orders that people submitted to the user's posts -->
        <div class="user-post-orders">
            <h2>Your Orders (From Posts Created):</h2>
            <?php if (!empty($orders)): ?>
                <ul>
                    <?php foreach ($orders as $order): ?>
                        <li>
                            
                            <strong>Order ID:</strong> <?php echo $order['orderID']; ?><br>
                            <strong>Post ID:</strong> <?php echo $order['postID']; ?><br>
                            <strong>Date Posted:</strong> <?php echo $order['datePosted']; ?><br>
                            <strong>Type:</strong> <?php echo $order['type']; ?><br>

                            <!--if its home_cooked, then we need to have dish; if its fast_food then add restaurant (ENUM so else)-->
                            <?php if ($order['type'] === 'Home_Cooked'): ?>
                                <strong>Dish:</strong> <?php echo $order['Dish']; ?><br>
                            <?php else: ?>
                                <strong>Restaurant:</strong> <?php echo $order['restaurant']; ?><br>

                            <?php endif; ?>

                            
                            <?php
                            

                            //food items that user ordered - name of item and price
                            $stmt = $conn->prepare("
                                SELECT fooditem.name, fooditem.price
                                FROM Order_FoodItem orderfi JOIN FoodItem fooditem USING (foodID)
                                WHERE orderfi.orderID = :orderID");

                            $stmt->bindParam(':orderID', $order['orderID']);
                            $stmt->execute();
                            $foodItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (!empty($foodItems)): ?>
                                <strong>Food Items:</strong><br>
                                <ul>
                                    <?php foreach ($foodItems as $foodItem): ?> <!--display all foods (for each item that was submitted in the Order_FoodItem) -->
                                        <li><?php echo $foodItem['name']; ?> - $<?php echo number_format($foodItem['price'], 2); ?></li>
                                    <?php endforeach; 
                                    ?>

                                </ul>

                            <?php endif; ?>


                            <hr>
                        </li>

                    <?php endforeach; ?>

                </ul>
            <?php 
        // when there r no associated orders 
        else: ?>

                <p>No orders related to your posts.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
