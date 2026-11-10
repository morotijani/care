<?php

    // AUTO LOAD VENDOR FILES
    require __DIR__ . '/vendor/autoload.php';

    // LOAD DOTENV
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
