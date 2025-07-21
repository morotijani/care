<?php
    include_once ("../system/DatabaseConnector.php");
    
    // Unset all session variables
    $_SESSION = array();

    // Destroy the session
    session_destroy();

    // Redirect to login page
    header("Location: index.php");
    exit();
?>