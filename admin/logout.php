<?php
    session_start();

    // Save last visited page in a cookie (valid for 5 minutes)
    if (isset($_SERVER['HTTP_REFERER'])) {
        setcookie("redirect_after_login", $_SERVER['HTTP_REFERER'], time() + 300, "/");
    }

    $_SESSION = array();
    session_destroy();

    header("Location: index.php");
    exit;
?>