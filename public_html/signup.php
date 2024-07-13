<?php
require_once 'signup-connection.php';

// Check if the user is already logged in
if (isset($_SESSION['user_ID'])) {
    header("Location: index.php");
    exit;
}

$emailExistsError = "";

// Get the allergens to display the checklist
$stmt = $conn->prepare("SELECT Allergen FROM Allergy");
$stmt->execute();
$allergens = $stmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $buildingId = $_POST['building'];

    // Check for errors
    $errors = array();
    if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword) || empty($buildingId)) {
        $errors[] = "All fields are required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    // Check if email already exists
    $stmt = $conn->prepare("SELECT * FROM User WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $emailExistsError = "Email already exists.";
        $errors[] = $emailExistsError;
    }

    // If there are no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Prepare the SQL statement to insert the new user
        $stmt = $conn->prepare("INSERT INTO User (email, password, first_name, last_name, buildingID_id) VALUES (:email, :password, :firstName, :lastName, :buildingId)");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':firstName', $firstName);
        $stmt->bindParam(':lastName', $lastName);
        $stmt->bindParam(':buildingId', $buildingId);

        // Execute the statement
        if ($stmt->execute()) {
            // Set the user's email in the session
            $_SESSION['user_ID'] = $email;

            // Add user allergies if provided
            if (isset($_POST['allergies'])) {
                $allergies = $_POST['allergies'];
                foreach ($allergies as $allergen) {
                    $stmt = $conn->prepare("INSERT INTO User_Allergies (Allergen, email) VALUES (:allergen, :email)");
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':allergen', $allergen);
                    $stmt->execute();
                }
            }

            // Redirect to index.php after successful registration
            header("Location: index.php");
            exit;
        } else {
            $errors[] = "Error occurred during registration.";
        }
    }
}

// Get the list of buildings
$stmt = $conn->prepare("SELECT id, address, city, state, zipCode, buildingName FROM webapp_building");
$stmt->execute();
$buildings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Sign Up - While You're At It</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <!-- Bootstrap Navigation bar -->
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container-fluid">
            <!-- Title -->
            <a class="navbar-brand">While You're At It</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>
    <!-- End of Bootstrap nav -->

    <div class="IndexTitle">
        <h1>SIGN UP</h1>
        <br>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
            <h5>Personal Information</h5>
            <br>
            <label for="firstName">First Name:</label>
            <input type="text" id="firstName" name="firstName" placeholder="First Name" value="<?php echo isset($firstName) ? $firstName : ''; ?>" required>
            <br><br>
            <label for="lastName">Last Name:</label>
            <input type="text" id="lastName" name="lastName" placeholder="Last Name" value="<?php echo isset($lastName) ? $lastName : ''; ?>" required>
            <br><br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" placeholder="Email" value="<?php echo isset($email) ? $email : ''; ?>" required>
            <?php if ($emailExistsError): ?>
                <div class="alert alert-danger" role="alert"><?php echo $emailExistsError; ?></div>
            <?php endif; ?>
            <br><br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" placeholder="Password" required>
            <br><br>
            <label for="confirmPassword">Retype Password:</label>
            <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Password" required>
            <br><br>
            <h5>Location Information</h5>
            <br>
            <label for="building">Building:</label>
            <select id="building" name="building" required>
    <option value="">Select a building</option>
    <?php foreach ($buildings as $building): ?>
        <option value="<?php echo $building['id']; ?>">
            <?php echo $building['address'] . ', ' . $building['city'] . ', ' . $building['state'] . ' ' . $building['zipCode'] . ' (' . $building['buildingName'] . ')'; ?>
        </option>
    <?php endforeach; ?>
</select>

            <br><br>

            <h5>Allergies</h5>
            <br>
            <?php foreach ($allergens as $allergen): ?>
                <label>
                    <input type="checkbox" name="allergies[]" value="<?php echo $allergen; ?>">
                    <?php echo $allergen; ?>
                </label>
                <br>
            <?php endforeach; ?>
            <br>

            <button type="submit">Sign up</button>
        </form>
        <br><br>
        <p>Already have an account? Login <a href="signin.php">here</a></p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
