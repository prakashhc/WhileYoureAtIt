<?php
require_once 'connection.php';

//check if user is logged in 
if (!isset($_SESSION['user_ID'])) {

    header("Location: signin.php");
    exit;
}

//check if the user is an admin
$stmt = $conn->prepare("SELECT * FROM Admins WHERE email = :email");
$stmt->bindParam(':email', $_SESSION['user_ID']);
$stmt->execute();
$isAdmin = $stmt->rowCount() > 0;

//function to get buildingID 
function getUserBuildingID($userEmail)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT buildingID_id
        FROM User
        WHERE email = :userEmail");


    $stmt->bindParam(':userEmail', $userEmail);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        return $result['buildingID_id'];
    } 
    
    else {
        return null;
    }
}

 //delete the user from the Courier table if they exist because they need to be updated with the most recent order
 function isUserCourier($userEmail){
    global $conn;
        
    $stmt = $conn->prepare("SELECT * FROM Courier WHERE email = :email");
    $stmt->bindParam(':email', $userEmail);
    $stmt->execute();
        
    return $stmt->rowCount() > 0;
}

$errors = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //$orderCapacity = $_POST['orderCapacity'] ?? ''; // DONT NEED ORDER CAPACITY ANYMORE
    $datePosted = $_POST['datePosted'] ?? '';
    $email = $_SESSION['user_ID'];
    $restaurant = $_POST['restaurant'] ?? '';

    //user's buildingID_id
    $userBuildingID = getUserBuildingID($_SESSION['user_ID']);

    if (empty($datePosted) || empty($restaurant)) {
        $errors[] = "All fields are required.";
    }


    if (empty($errors)) {
        try {
            //start a transaction
            $conn->beginTransaction();

            $stmt = $conn->prepare("INSERT INTO Post (datePosted, email, type) VALUES (:datePosted, :email, 'Fast_Food')");
            //$stmt->bindParam(':orderCapacity', $orderCapacity);
            $stmt->bindParam(':datePosted', $datePosted);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $postID = $conn->lastInsertId();

            //insert into Restaurant table (ignore duplicates)
            $stmt = $conn->prepare("INSERT IGNORE INTO Restaurant (restaurantName) VALUES (:restaurant)");
            $stmt->bindParam(':restaurant', $restaurant);
            $stmt->execute();

            //insert fast_food values
            $stmt = $conn->prepare("INSERT INTO Fast_Food (postID, restaurant) VALUES (:postID, :restaurant)");
            $stmt->bindParam(':postID', $postID);
            $stmt->bindParam(':restaurant', $restaurant);
            $stmt->execute();

            //check if the user is already a courier 
            $isUserCourier = isUserCourier($email);


            //MUST ENSURE THAT THE COURIER DELIVER TO THE CORRECT BUILDING
            //DELETE THE CURRENT INSTANCE OF THAT USER IN COURIER
            //ADD IT BACK WITH THE UPDATED BUILDINGID (IN CASE USER TRIED TO CHANGE THE BUILDING )

            // delete the user from the Courier table if they exist
            if ($isUserCourier) {
                $stmt = $conn->prepare("DELETE FROM Courier WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
            }

            $stmt = $conn->prepare("INSERT INTO Courier (email, buildingID) VALUES (:email, :userBuildingID)");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':userBuildingID', $userBuildingID);
            $stmt->execute();

            $conn->commit();

            header("Location: index.php"); //redirect when successful
            exit;
        } 
        
        //use for rollback if theres an error
        catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = "Error occurred: " . $e->getMessage();
        }
    }
}

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
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
                        <a class="nav-link active" aria-current="page" href="index.php">Home</a>
                    </li>

                    <!-- Deliver -->
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="deliver.php">Deliver</a>
                    </li>

                    <!-- Order -->
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="order.php">Order</a>
                    </li>

                    <!-- Profile -->
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="profile.php">Profile</a>
                    </li>

                    <!-- Checks if is admin --> 
                    <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="admin_settings.php">Admin Settings</a>
                    </li>

                <?php endif; ?>


                </ul>

                <form class="d-flex" role="search">
                    <a href="signout.php" class="btn btn-outline-success">Logout</a>
                </form>
            </div>
        </div>
    </nav>
    <!-- end of bootstrap nav -->

    <div class="IndexTitle">
        <h1>Create Fast Food Post</h1>
        <br>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
            <h5>This is the page where you will sign up to create your own posts for other users to view and place their orders.</h5>
            <br>
            <label for="datePosted">Date & Time</label>
            <input type="datetime-local" id="datePosted" name="datePosted" placeholder="Date & Time" value="<?php echo isset($datePosted) ? $datePosted : ''; ?>" required>
            <br><br>
            <label for="restaurant">Restaurant</label>

            <div class="col">
                <select class="form-control" id="restaurant" name="restaurant" required>
                    <!-- restaurant dropdown -->
                    <?php
                    $stmt = $conn->prepare("SELECT restaurantName FROM Restaurant");
                    $stmt->execute();
                    $restaurants = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($restaurants as $restaurant) {
                        echo "<option value=\"$restaurant\">$restaurant</option>";
                    }
                    ?>
                </select>
            </div>

            <br><br>
            <button type="submit">Create Post</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>