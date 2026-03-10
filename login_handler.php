<?php
session_start();
require_once 'config.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $user_type = isset($_POST['user_type']) ? trim($_POST['user_type']) : '';
    
    // Validate input
    if (empty($email) || empty($password) || empty($user_type)) {
        $_SESSION['error'] = 'Please fill in all required fields';
        header('Location: login.php?type=' . urlencode($user_type));
        exit();
    }
    
    // Get database connection
    $conn = getDBConnection();
    
    // Query user from database
    $stmt = $conn->prepare("SELECT user_id, email, password, role, name, is_verified FROM User WHERE email = ? AND role = ?");
    $stmt->bind_param("ss", $email, $user_type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Invalid email or password';
        $stmt->close();
        closeDBConnection($conn);
        header('Location: login.php?type=' . urlencode($user_type));
        exit();
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Verify password (adjust based on your password hashing method)
    // If passwords are plain text (not recommended):
    if ($user && $password === $user['password']) {
        // Check if officer is verified
        if ($user['role'] === 'Officer') {
            if ($user['is_verified'] == 0) {
                $_SESSION['error'] = 'Your account is pending admin verification. Please wait for approval.';
                header('Location: login.php?type=' . urlencode($user_type));
                exit();
            } else if ($user['is_verified'] == -1) {
                $_SESSION['error'] = 'Your registration as a Permit Officer has been rejected. Please contact the administrator.';
                header('Location: login.php?type=' . urlencode($user_type));
                exit();
            }
        }
        
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        
        // Redirect based on role
        switch ($user['role']) {
            case 'Applicant':
                header('Location: dashboard.php');
                break;
            case 'Officer':
                header('Location: verification.php');
                break;
            case 'Admin':
                header('Location: admin_dashboard.php');
                break;
            default:
                header('Location: index.php');
        }
        exit();
    } else {
        $_SESSION['error'] = 'Invalid email or password';
        closeDBConnection($conn);
        header('Location: login%20(1).php');
        exit();
    }
}

// If GET request, redirect to login form
header('Location: login%20(1).php');
exit();
?>
