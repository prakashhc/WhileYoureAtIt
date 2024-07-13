<!--REQUIRES LOGIN-->

<?php


require_once 'profile-connection.php';

// check if the user is logged in
if (!isset($_SESSION['user_ID'])) {
    // redirect to the sign-in page if they're not logged in
    header("Location: signin.php");
    exit;
}

// check if the user is an admin
$stmt = $conn->prepare("SELECT * FROM Admins WHERE email = :email");
$stmt->bindParam(':email', $_SESSION['user_ID']);
$stmt->execute();
$isAdmin = $stmt->rowCount() > 0;

// get the list of buildings
$stmt = $conn->prepare("SELECT id, address, city, state, zipCode, buildingName FROM webapp_building");
$stmt->execute();
$buildings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// get user information from the database
$stmt = $conn->prepare("SELECT first_name, last_name, email, buildingID_id FROM User WHERE email = :email");
$stmt->bindParam(':email', $_SESSION['user_ID']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);


// get the list of allergens
$stmt = $conn->prepare("SELECT Allergen FROM Allergy");
$stmt->execute();
$allergens = $stmt->fetchAll(PDO::FETCH_COLUMN);

// get the user's existing allergies
$stmt = $conn->prepare("SELECT Allergen FROM User_Allergies WHERE email = :email");
$stmt->bindParam(':email', $_SESSION['user_ID']);
$stmt->execute();
$userAllergies = $stmt->fetchAll(PDO::FETCH_COLUMN);

?>


<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styleDeliver.css">
    <title>While You're At It</title>
</head>
<body>
<!--Bootstrap Navigation bar-->
      <nav class="navbar navbar-expand-lg bg-body-tertiary">
      <div class="container-fluid">

          <!--Title-->
        <a class="navbar-brand" href="#">While You're At It</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
              
              <!--Home-->
              <li class="nav-item">
                <a class="nav-link active" aria-current="page" href="index">Home</a>
              </li>

              <!--Deliver-->
            <li class="nav-item">
              <a class="nav-link active" aria-current="page" href="deliver">Deliver</a>
            </li>

              <!--Order-->
              <li class="nav-item">
                <a class="nav-link" aria-current="page" href="order">Order</a>
              </li>

                <!--Profile-->
                <li class="nav-item">
                    <a class="nav-link" aria-current="page" href="profile">Profile</a>
                  </li>         
                  
                    <!-- Checks if is admin --> 
                    <?php if ($isAdmin): ?>
                    <li class="nav-item">
                        <a class="nav-link" aria-current="page" href="admin_settings.php">Admin Settings</a>
                    </li>

                <?php endif; ?>
                                  
            </ul>

          <form class="d-flex" role="search">
            <a href="signout" class="btn btn-outline-success">Logout</a>
          </form>
        </div>
      </div>
    </nav>
    <!--end of bootstrap nav-->

    <h1>Settings</h1>
    <h6>Edit your Information Below and click update to save</h6><br><br>
    <form method="post" action="">
        <label for="first_name">First Name:</label>
        <input type="text" id="first_name" name="first_name" placeholder="<?php echo $user['first_name']; ?>" value="<?php echo $user['first_name']; ?>"><br><br>

        <label for="last_name">Last Name:</label>
        <input type="text" id="last_name" name="last_name" placeholder="<?php echo $user['last_name']; ?>" value="<?php echo $user['last_name']; ?>"><br><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" placeholder="Password"><br><br>

        <label for="confirmPassword">Confirm Password:</label>
        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm Password"><br><br>

        <label for="building">Building:</label>
        <select id="building" name="building">
            <option value="">Select a building</option>
            <?php foreach ($buildings as $building): ?>
                <option value="<?php echo $building['id']; ?>" <?php echo ($building['id'] == $user['buildingID_id']) ? 'selected' : ''; ?>>
                    <?php echo $building['address'] . ', ' . $building['city'] . ', ' . $building['state'] . ' ' . $building['zipCode'] . ' (' . $building['buildingName'] . ')'; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <br><br>


        <h5>Allergies</h5>
        <br>
        <?php foreach ($allergens as $allergen): ?>
            <label>
                <input type="checkbox" name="allergies[]" value="<?php echo $allergen; ?>" <?php echo in_array($allergen, $userAllergies) ? 'checked' : ''; ?>>
                <?php echo $allergen; ?>
            </label>
            <br>
        <?php endforeach; ?>
        <br>

        <input type="submit" value="Update">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
