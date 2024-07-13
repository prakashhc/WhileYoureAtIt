<?php
require_once 'connection.php';

// check if the user is logged in
if (!isset($_SESSION['user_ID'])) {header("Location: signin.php"); exit;}

//check if the user is an admin
$stmt = $conn->prepare("SELECT * FROM Admins WHERE email = :email");
$stmt->bindParam(':email', $_SESSION['user_ID']);
$stmt->execute();
$isAdmin = $stmt->rowCount() > 0;

// function to get the user's building ID
function getUserBuildingID( $userEmail) {
    global $conn;
    $stmt = $conn->prepare("SELECT buildingID_id FROM User WHERE email = :userEmail");

    $stmt->bindParam(':userEmail', $userEmail);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ? $result['buildingID_id'] :null;
}

// retrieve the user's building
$userBuildingID = getUserBuildingID($_SESSION['user_ID']);

// calculate the time to get posts from the last 12 hours
$postDuration = date('Y-m-d H:i:s', strtotime('-12 hours'));

// get posts from the user's building in the last 12 hours
$stmt = $conn->prepare("SELECT p.postID, p.datePosted, p.email, p.type, hc.Dish, ff.restaurant
    FROM Post p LEFT JOIN Home_Cooked hc ON p.postID = hc.postID LEFT JOIN Fast_Food ff ON p.postID = ff.postID LEFT JOIN User u ON p.email = u.email
    WHERE u.buildingID_id = :buildingID AND p.datePosted >= :postDuration
    ORDER BY p.datePosted DESC");

$stmt->bindParam(':buildingID', $userBuildingID);

$stmt->bindParam(':postDuration', $postDuration);

$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// get the user's allergies
$stmt = $conn->prepare("
SELECT Allergen 
FROM User_Allergies 
WHERE email = :email");
$stmt->bindParam(':email', $_SESSION['user_ID']);
$stmt->execute();
$userAllergies = $stmt->fetchAll(PDO::FETCH_COLUMN);

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Order - While You're At It</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .user-allergen {color: red;}
    </style>
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

                    <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="admin_settings.php">Admin Settings</a>
                    </li>

                <?php endif; ?>

                </ul>

                <!-- Logout button -->
                <form class="d-flex" role="search">
                    <a href="signout.php" class="btn btn-outline-success">Logout</a>
                </form>
            </div>
        </div>
    </nav>
    <!-- End of navigation bar -->

    <div class="posts-container">
        <?php if (!empty($posts)): ?>
            <?php foreach ($posts as $post): ?>
                <div class="post-card">
                    <h4><?php echo $post['type']; ?></h4>

                    <p>Date Posted: <?php echo $post['datePosted']; ?></p>

                    <p>Email: <?php echo $post['email']; ?></p>

                    <?php if ($post['type'] === 'Home_Cooked'): ?>
                        <p>Dish: <?php echo $post['Dish']; ?></p>
                        <?php

                        // get any allergens associated with the home-cooked dish
                        $stmt = $conn->prepare("SELECT Allergen
                            FROM Food_Allergies
                            WHERE PostID = :postID");

                        $stmt->bindParam(':postID', $post['postID']);
                        $stmt->execute();
                        $allergens = $stmt->fetchAll(PDO::FETCH_COLUMN);

                        if (!empty($allergens)) {
                            echo '<p>Allergens: ';
                            
                            // loop through each allergen
                            foreach ($allergens as $allergen) {

                                // check if the allergen is in the user's allergies and if it is then color the text red

                                $isUserAllergen = in_array($allergen, $userAllergies);
                                $allergenClass = $isUserAllergen ? 'user-allergen' : '';

                                echo '<span class="' . $allergenClass . '">' . $allergen . '</span>';
                                if (next($allergens) !== false) {
                                    echo ', ';
                                }
                            }
                            echo '</p>';
                        } else {
                            echo '<p>No allergens associated.</p>';
                        }
                        ?>
                    <?php else: ?>
                        <p>Restaurant: <?php echo $post['restaurant']; ?></p>
                    <?php endif; ?>
                    <button class="order-btn" data-post-id="<?php echo $post['postID']; ?>">Order</button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No posts found for your building in the last 12 hours.</p>
        <?php endif; ?>
    </div>

    <!-- javaScript for handling order button clicks -->
    <script>
        const orderButtons = document.querySelectorAll('.order-btn');

        orderButtons.forEach(btn => {

            btn.addEventListener('click', () => {
                const postID = btn.dataset.postId;
                // get the food items and open the food items popup
                getFoodItems(postID);
            });
        });

        // get the food items from the database
        function getFoodItems(postID) {
            fetch(`get_food_items.php?postID=${postID}`).then(response => response.json()).then(foodItems => {displayFoodItems(postID, foodItems);}).catch(error => {console.error('Error fetching food items:', error);});
        }

        // function to display a modal window with food items for the post
        function displayFoodItems(postID, foodItems) {
            const modal = document.createElement('div');
            modal.classList.add('modal');

            modal.style.display = 'block';
            modal.style.position = 'fixed';

            modal.style.top = '50%';

            modal.style.left = '50%';
            
            modal.style.transform = 'translate(-50%, -50%)';
            modal.style.zIndex = '1000';
            
            // create a form for selecting food items the user wants
            const foodItemForm = document.createElement('form');
            foodItemForm.id = 'foodItemsForm';
            foodItemForm.classList.add('modal-content');
            foodItemForm.style.padding = '20px';

            foodItemForm.style.backgroundColor = '#fff';
            foodItemForm.style.borderRadius = '8px';
            foodItemForm.style.boxShadow = '0 4px 4px rgba(0, 0, 0, 0.4)';

            foodItemForm.innerHTML = '<h5>Select Food Items:</h5>';

            // add a checkbox for each food item
            foodItems.forEach(item => {
                const label = document.createElement('label');
            label.classList.add('food-item-label');
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.name = 'foodItems[]';
                checkbox.value = item.foodID;

                label.appendChild(checkbox);
                label.appendChild(document.createTextNode(`${item.name} - $${item.price}`));
                foodItemForm.appendChild(label);
            foodItemForm.appendChild(document.createElement('br'));
            });

            // add a submit button to save the order
            const submitButton = document.createElement('button');
            submitButton.type = 'submit';

            submitButton.textContent = 'Submit Order';
            submitButton.style.marginTop = '20px';
            foodItemForm.appendChild(submitButton);

            // add the form to the modal window
            modal.appendChild(foodItemForm);
            document.body.appendChild(modal);

            // check if the user clicked outside the form and close the modal window if they did
            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                    document.body.removeChild(modal);
                }
            });

            // form submission
            foodItemForm.addEventListener('submit', function(event) {

                event.preventDefault();
                const formData = new FormData(foodItemForm);
                formData.append('postID', postID);

                saveSelectedFoodItems(postID, formData);
                modal.style.display = 'none'; // Close the modal window after submitting
                document.body.removeChild(modal);
            });
        }

        // function to save the selected food items to the Order_FoodItem table
        function saveSelectedFoodItems(postID, formData) {
            fetch('save_order_fooditem.php', {
                method: 'POST',
                body: formData,}).then(response => response.text()).then(data => {
            console.log(data);
                alert('Order saved successfully!');})
            .catch(error => {
                console.error('Error saving order:', error);
            });
        }
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
