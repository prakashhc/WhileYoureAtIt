<?php
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

//WORKING PROPERLY NOW -- SHOULD FETCH THE USER'S FIRST NAME
$stmt = $conn->prepare("SELECT first_name FROM User WHERE email = :email");
$stmt->bindParam(':email', $_SESSION['user_ID']);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result !== false && isset($result['first_name'])) {
    $firstName = $result['first_name'];
} else {
    $firstName = "User";
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
    <h1>Welcome, <?php echo $firstName; ?></h1>
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
        <h1>While You're At It!</h1>
    </div>

    <h2 id="IndexHeader">Overview</h2>
    <p id="IndexText">Imagine your days as a student: constant lectures, homework assignments, projects, and exams.
        Now, when you are at your dorm or apartment, you'll be in the zone until you hear your stomach
        grumble. Then, you know that you'll have to spend at least an hour walking across campus,
        ordering a meal, waiting for it to be prepared, and then finally spending time to eat it. With
        "While You're At It", the time spent walking to your favorite restaurants and waiting for your
        meal to get prepared is cut, leaving students to spend more time studying and less time running
        around campus.</p>

    <h2 id="IndexHeader">How It Works</h2>
    <p id="IndexText">How does it work? A user creates a post that is only visible to the people of their building. The
        post includes the fast food location they are going to or the name of the home-cooked meal they
        are preparing in their apartment. Within seconds, people on the app receive a notification and are
        prompted if they want anything from that particular restaurant or if they would like the
        home-cooked dish to be prepared for them.</p>

    <h2 id="IndexHeader">Key Features</h2>
    <ul id="IndexText">
        <h4>User Information</h4>
        <li id="IndexListItem">Most apps enforce account creation, including ours. Account creation is a major aspect of our
            service since we need to collect information about your location and how to contact you. Upon
            installing our app, we ask for all users to create an account with us, in which users use a unique
            email, have a password, and enter their residence information. By using the residence
            information, we can verify if a user lives in a large apartment or a dormitory, store their address
            for future orders, and strictly enforce that users in the same residential building only place orders
            with others from the same building.</li>
        <h4>Dynamic Roles</h4>
        <li id="IndexListItem">Dynamic roles refer to how a user can be both a customer and a worker, allowing for easy
            accessibility. The role of the user is easily implemented on the landing page, where there will be
            three navigation sections: courier (delivery or preparing a home-cooked meal), customer
            (includes the order forum as specified below), and settings (allows for users to edit their user
            information).</li>
        <h4>Order Forum</h4>
        <li id="IndexListItem">In today's generation, people love simple, straightforward designs. Our application will have a
            clean-cut design and an easy-to-use forum tab in which users can create posts for their building.
            These posts indicate whether a user is going to pick up a takeout order or is preparing a
            home-cooked meal. These posts would have an associated date and time, so orders can only be active for a given amount of time.</li>
        <h4>Food Safety</h4>
        <li id="IndexListItem">"While You're At It" heavily prioritizes food safety, so we included an allergies section. This
            feature forces all users to add a list of their allergies and
            requires that all home-cooked meals have a list of all allergens that are in their meals. By having
            information about both parties' allergies, the app will warn users with red text about allergens in the home-cooked meals.</li>
    </ul>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>