<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function getUserName() {
    return isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '';
}

// Debug function
function debug($var) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}
?> 