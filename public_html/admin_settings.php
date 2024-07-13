<?php
require_once 'connection.php';

//check if user is currently logged in and send to signin pae if not
if (!isset($_SESSION['user_ID'])) {
    header("Location: signin.php");
    exit;
}

//check to see if the user is an admin 
$stmt = $conn->prepare("SELECT * FROM Admins WHERE email = :email");
$stmt->bindParam(':email', $_SESSION['user_ID']);
$stmt->execute();
$isAdmin = $stmt->rowCount() > 0;

//if user tries to access page and isn't an admin, redirect back to home
if (!$isAdmin) {
    header("Location: index.php");
    exit;
}

$errors = array();


/*
 * 
 * admins have multiple permissions (inserting):
 * add buildings to an existing list
 * add allergies 
 * add restaurants
 * add Food Items
 *  
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['addBuilding'])) {
        $address = $_POST['address'] ?? '';
        $city = $_POST['city'] ?? '';
        $state = $_POST['state'] ?? '';
        $zipCode = $_POST['zipCode'] ?? '';
        $buildingName = $_POST['buildingName'] ?? '';

        // make sure all fields are filled out when you hit submit
        if (empty($address) || empty($city) || empty($state) || empty($zipCode) || empty($buildingName)) {
            $errors[] = "All fields are required.";
        } 
        
        else {
            if (strlen($state) !== 2) { //ensure 2 character state
                $errors[] = "State must be a 2-character code.";
            }

            //ensure zip code is 5 digits
            if (!preg_match('/^\d{5}$/', $zipCode)) {
                $errors[] = "Invalid zip code format. Please enter a 5-digit number.";
            }
        }

        //if formatting is correct and all fields are filled in, then add to webapp_building
        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("INSERT IGNORE INTO webapp_building (address, city, state, zipCode, buildingName) VALUES (:address, :city, :state, :zipCode, :buildingName)");
                $stmt->bindParam(':address', $address);
                $stmt->bindParam(':city', $city);
                $stmt->bindParam(':state', $state);
                $stmt->bindParam(':zipCode', $zipCode);
                $stmt->bindParam(':buildingName', $buildingName);
                $stmt->execute();

                //success msg
                header("Location: admin_settings.php?success=building");
                exit;
            } 
            catch (PDOException $e) {
                $errors[] = "Error occurred: " . $e->getMessage();
            }
        }
    } 
    
    elseif (isset($_POST['addAllergy'])) {
        $allergen = $_POST['allergen'] ?? '';

        if (empty($allergen)) {
            $errors[] = "Allergen field is required.";
        }

        //insert into allergies table
        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("INSERT IGNORE INTO Allergy (Allergen) VALUES (:allergen)");
                $stmt->bindParam(':allergen', $allergen);
                $stmt->execute();

                header("Location: admin_settings.php?success=allergy");
                exit;
            } 
            
            catch (PDOException $e) {
                $errors[] = "Error occurred: " . $e->getMessage();
            }
        }
    } 
    
    elseif (isset($_POST['addRestaurant'])) {
        $restaurantName = $_POST['restaurantName'] ?? '';

        if (empty($restaurantName)) {
            $errors[] = "Restaurant Name field is required.";
        }

        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("INSERT IGNORE INTO Restaurant (restaurantName) VALUES (:restaurantName)"); //need ignore in case restaurant already exists - duplicate entries
                $stmt->bindParam(':restaurantName', $restaurantName);
                $stmt->execute();

                header("Location: admin_settings.php?success=restaurant");
                exit;
            } 
            
            catch (PDOException $e) {
                $errors[] = "Error occurred: " . $e->getMessage();
            }
        }
    }

    //add FoodItem - has auto increment for FoodID; restaurant; price, name
    elseif (isset($_POST['addFoodItem'])) {

        $itemName = $_POST['itemName'] ?? '';
        $price = $_POST['price'] ?? '';
        $restaurant = $_POST['restaurant'] ?? '';

        if (empty($itemName) || empty($price) || empty($restaurant)) {
            $errors[] = "All fields are required.";
        } 
        
        else {

            if (!is_numeric($price)) {
                $errors[] = "Price must be a valid number.";
            }

            //check if the restaurant is already in the list and if not, then add it to the list of restaurants (OTHERWISE NULL ERROR)
            $stmt = $conn->prepare("SELECT * FROM Restaurant WHERE restaurantName = :restaurant");
            $stmt->bindParam(':restaurant', $restaurant);
            $stmt->execute();
            if ($stmt->rowCount() == 0) {

                try {
                    $stmt = $conn->prepare("INSERT INTO Restaurant (restaurantName) VALUES (:restaurant)");
                    $stmt->bindParam(':restaurant', $restaurant);
                    $stmt->execute();
                } 
                
                catch (PDOException $e) {
                    $errors[] = "Error occurred while adding restaurant: " . $e->getMessage();
                }
            }


            try {
                $stmt = $conn->prepare("INSERT INTO FoodItem (name, price, restaurant) VALUES (:name, :price, :restaurant)");
                $stmt->bindParam(':name', $itemName);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':restaurant', $restaurant);


                $stmt->execute();

                header("Location: admin_settings.php?success=FoodItem");
                exit;
            } 
            
            catch (PDOException $e) {
                $errors[] = "Error occurred: " . $e->getMessage();
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("INSERT IGNORE INTO FoodItem (name, price, restaurant) VALUES (:name, :price, :restaurant)");
            $stmt->bindParam(':name', $itemName);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':restaurant', $restaurant);
            
            $stmt->execute();

            header("Location: admin_settings.php?success=FoodItem");
            exit;
        } 
        
        catch (PDOException $e) {
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

                <!-- Admin Settings -->
                <li class="nav-item">
                    <a class="nav-link" aria-current="page" href="admin_settings.php">Admin Settings</a>
                </li>
            </ul>

            <form class="d-flex" role="search">
                <a href="signout.php" class="btn btn-outline-success">Logout</a>
            </form>

        </div>
    </div>
</nav>
<!-- end of bootstrap nav -->

<div class="IndexTitle">
    <h1>Admin Settings</h1>
    <br>

    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!--success banners if the forms have no errors/filled out properly -->
    <?php if (isset($_GET['success']) && $_GET['success'] === 'building'): ?>
        <div class="alert alert-success" role="alert">Building added successfully!</div>


    <?php elseif (isset($_GET['success']) && $_GET['success'] === 'allergy'): ?>
        <div class="alert alert-success" role="alert">Allergen added successfully!</div>

    <?php elseif (isset($_GET['success']) && $_GET['success'] === 'restaurant'): ?>
        <div class="alert alert-success" role="alert">Restaurant added successfully!</div>


    <?php elseif (isset($_GET['success']) && $_GET['success'] === 'FoodItem'): ?>
        <div class="alert alert-success" role="alert">Food item added successfully!</div>
    
    
    <?php endif; ?>



    <h5>Welcome to admin settings. As an admin, you have special permissions to add different buildings, restaurants, allergies, and food items.</h5>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <br><br>
        <h3>Add a building:</h3>

        <div class="form-row">
            <div class="col">
                <input type="text" class="form-control" id="address" name="address" placeholder="Address" value="<?php echo isset($address) ? $address : ''; ?>" required>
            </div>
            <div class="col">
                <input type="text" class="form-control" id="city" name="city" placeholder="City" value="<?php echo isset($city) ? $city : ''; ?>" required>
            </div>
            <div class="col">
                <input type="text" class="form-control" id="state" name="state" placeholder="State" maxlength="2" value="<?php echo isset($state) ? $state : ''; ?>" required>
            </div>
            <div class="col">
                <input type="text" class="form-control" id="zipCode" name="zipCode" placeholder="Zip Code" value="<?php echo isset($zipCode) ? $zipCode : ''; ?>" required>
            </div>
            <div class="col">
                <input type="text" class="form-control" id="buildingName" name="buildingName" placeholder="Building Name" value="<?php echo isset($buildingName) ? $buildingName : ''; ?>" required>
            </div>
        </div>
        <br>
        <button type="submit" class="btn btn-primary" name="addBuilding">Add Building</button>
    </form>

    <br><br>

    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <h3>Add an allergen:</h3>
        <div class="form-row">
            <div class="col">
                <input type="text" class="form-control" id="allergen" name="allergen" placeholder="Allergen" required>
            </div>
        </div>
        <br>
        <button type="submit" class="btn btn-primary" name="addAllergy">Add Allergen</button>
    </form>

    <br><br>


    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <h3>Add a restaurant:</h3>
        <div class="form-row">
            <div class="col">
                <input type="text" class="form-control" id="restaurantName" name="restaurantName" placeholder="Restaurant Name" required>
            </div>
        </div>
        <br>
        <button type="submit" class="btn btn-primary" name="addRestaurant">Add Restaurant</button>
    </form>


    <br><br>

    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
        <h3>Add a food item:</h3>
        <div class="form-row">
            <div class="col">
                <input type="text" class="form-control" id="itemName" name="itemName" placeholder="Name" required>
            </div>
            <div class="col">
                <input type="text" class="form-control" id="price" name="price" placeholder="Price" required>
            </div>
            <div class="col">
                <select class="form-control" id="restaurant" name="restaurant" required>
                    <!-- dropdown list for the restaurant names -->
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
        </div>
        <br>
        <button type="submit" class="btn btn-primary" name="addFoodItem">Add Food Item</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>