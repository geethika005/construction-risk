<?php
session_start();
require_once 'config.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $user_type = isset($_POST['user_type']) ? trim($_POST['user_type']) : '';
    $spark_pen = isset($_POST['spark_pen']) ? trim($_POST['spark_pen']) : '';
    
    // Save old input for UX persistence (don't save password)
    $_SESSION['old_input'] = [
        'name' => $name,
        'email' => $email,
        'user_type' => $user_type,
        'spark_pen' => $spark_pen
    ];

    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors['name'] = 'Full name is required';
    } else if (strlen($name) < 3) {
        $errors['name'] = 'Name is too short';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Valid email is required';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } else if (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (empty($user_type)) {
        $errors['user_type'] = 'User type is required';
    } else if ($user_type === 'Officer') {
        if (!preg_match('/@gov\.in$/i', $email)) {
            $errors['email'] = 'Officer registration requires a valid @gov.in email address';
        }
        if (empty($spark_pen) || !preg_match('/^\d{6}$/', $spark_pen)) {
            $errors['spark_pen'] = 'A valid 6-digit SPARK PEN is required for Officer registration';
        }
    }
    
    // Password strength validation
    if (!empty($password) && !isset($errors['password'])) {
        $strengthErrors = [];
        if (!preg_match('/[A-Z]/', $password)) $strengthErrors[] = 'one uppercase letter';
        if (!preg_match('/[a-z]/', $password)) $strengthErrors[] = 'one lowercase letter';
        if (!preg_match('/[0-9]/', $password)) $strengthErrors[] = 'one number';
        if (!preg_match('/[!@#$%^&*]/', $password)) $strengthErrors[] = 'one special character';
        
        if (!empty($strengthErrors)) {
            $errors['password'] = 'Password must contain at least ' . implode(', ', $strengthErrors);
        }
    }
    
    // If there are errors, redirect back with error messages
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: register.php');
        exit();
    }
    
    // Get database connection
    $conn = getDBConnection();
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT user_id FROM User WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['errors'] = ['email' => 'Email already registered. Please use a different email or login.'];
        $stmt->close();
        closeDBConnection($conn);
        header('Location: register.php');
        exit();
    }
    $stmt->close();
    
    // Set verified status
    $is_verified = ($user_type === 'Officer') ? 0 : 1;

    // Use provided full name
    $full_name = $name;

    // Insert new user into database
    $stmt = $conn->prepare("
        INSERT INTO User (name, email, password, role, spark_pen, is_verified, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->bind_param("sssssi", 
        $full_name,
        $email,
        $password,
        $user_type,
        $spark_pen,
        $is_verified
    );
    
    if ($stmt->execute()) {
        // Clear old input on success
        unset($_SESSION['old_input']);
        if ($user_type === 'Officer') {
            // Notify Admin
            $adminEmail = 'jijithannickal@gmail.com'; // Default admin email for the system
            $subject = 'New Officer Registration Approval Required';
            $message = "A new Permit Officer ($full_name, PEN: $spark_pen) has registered and is pending your approval.\nLog in to the Admin Dashboard to verify their account.";
            // In a real system, send email here. For this miniproject, we'll try to mail
            @mail($adminEmail, $subject, $message, "From: noreply@sovereignstructures.in");
            
            $_SESSION['success'] = 'Registration successful! Your account is pending admin verification before you can login.';
        } else {
            // Welcome email for Applicants
            $subject = "Welcome to Sovereign Structures!";
            $message = "Dear $full_name,\n\nWelcome to Sovereign Structures! Your account has been successfully created.\n\nYou can now log in to the portal to submit your construction permit applications and track their status.\n\nRegards,\nSovereign Structures Team";
            @mail($email, $subject, $message, "From: noreply@sovereignstructures.in");

            $_SESSION['success'] = 'Registration successful! Please login with your credentials.';
        }
        
        $stmt->close();
        closeDBConnection($conn);
        header('Location: login%20(1).php?type=' . urlencode($user_type));
        exit();
    } else {
        $_SESSION['errors'] = ['general' => 'Registration failed. Please try again.'];
        $stmt->close();
        closeDBConnection($conn);
        header('Location: register.php');
        exit();
    }
}

// If GET request, redirect to register form
header('Location: register.php');
exit();
?>
