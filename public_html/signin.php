<?php

require_once 'connection.php';


//check if the user is already logged in
if (isset($_SESSION['user_ID'])) {
    // redirect to the index page
    header("Location: index.php");
    exit;
}

// handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = $_POST['email'];
    $password = $_POST['password'];


    $stmt = $conn->prepare("
    SELECT email, password 
    FROM User 
    WHERE email=:email");

    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // verify the password
    if ($result && password_verify($password, $result['password'])) {


        // set the user ID in the session
        $_SESSION['user_ID'] = $result['id'];


        header("Location: index.php"); //if successful login info, redirect to index
        exit;
    } 
    
    else {
        $error = "Invalid email or password.";
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
    <title>Sign In - While You're At It</title>
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
    <!-- end of bootstrap nav -->

    <div class="IndexTitle">
        <h1>SIGN IN</h1>
        <br>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
            <label for="email">Email:</label>
            <input type="text" id="email" name="email" placeholder="Email" required>
            <br><br>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" placeholder="Password" required>
            <br><br>
            <button type="submit">Login</button>
        </form>
        <br><br>
        <p>Don't have an account? Sign up <a href="signup.php">here</a></p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>