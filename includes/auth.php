<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Base URL for generating absolute paths in links and redirects
if (!defined('BASE_URL')) {
    // Adjust this if the app runs in a different subdirectory
    define('BASE_URL', '/soft_vehicle_management');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function loginUser($username, $password, $conn) {
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Plain text password check (replace with password_hash in production)
        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            return true;
        }
    }
    return false;
}

function checkAuth() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/index.php");
        exit();
    }
}

function logoutUser() {
    session_destroy();
    header("Location: " . BASE_URL . "/index.php");
    exit();
}