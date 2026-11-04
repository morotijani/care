<?php
    include_once ("../system/DatabaseConnector.php");
    
    unset($_SESSION['CTFLHADMIN']);
    $_SESSION['flash_success'] = "You have been signed out safely.";

    redirect(PROOT . 'admin/');
