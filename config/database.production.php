<?php

return [
    'host' => 'localhost', // Most shared hosts use localhost
    'username' => 'printwise_user', // Replace with your actual database username
    'password' => 'your_password_here', // Replace with your actual database password
    'database' => 'printwise_db', // Replace with your actual database name
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
