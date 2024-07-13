
<?php
    session_start();

    //destroy current session and redirect user to signin page

    $_SESSION = array();
    session_destroy();

    header("Location: signin.php");
    exit;
?>
