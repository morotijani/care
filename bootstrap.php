<?php
    
    // AUTO LOAD VENDOR FILES
    require dirname(__DIR__)  . '/levina/vendor/autoload.php';

    
    // LOAD DOTENV
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
