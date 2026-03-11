<?php
// ==========================================================
// DATABASE CONFIGURATION
// ==========================================================
// Connection settings for MySQL database
// ==========================================================

// Database credentials (with Environment Variable support for Cloud)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: ''); // Use Render Env Var for production
define('DB_NAME', getenv('DB_NAME') ?: 'construction_risk_db');
define('DB_PORT', getenv('DB_PORT') ?: '3306'); // Default MySQL port for local, will use Render Env Var for cloud

// SMTP Configuration (for Emails)
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_USER', getenv('SMTP_USER') ?: ''); // Set in Render Env Var
define('SMTP_PASS', getenv('SMTP_PASS') ?: ''); // Set in Render Env Var (App Password)
define('SMTP_PORT', getenv('SMTP_PORT') ?: '465');
define('SMTP_FROM_NAME', 'Sovereign Structures');

// Create database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Close database connection
function closeDBConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global Cache Control to prevent back-button from loading cached pages
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: 0');

// Set default timezone
date_default_timezone_set('Asia/Kolkata');

// Base URL configuration (Auto-detects localhost vs Cloud)
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$isLocal = ($host === 'localhost' || $host === '127.0.0.1');

if ($isLocal) {
    define('BASE_URL', $protocol . '://' . $host . '/mini project/');
} else {
    // On Render, we usually just need the root
    define('BASE_URL', $protocol . '://' . $host . '/');
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

// Redirect if not authorized
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: ' . BASE_URL . 'unauthorized.php');
        exit();
    }
}
?>
