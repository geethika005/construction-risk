<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    if ($role === 'Applicant') header('Location: dashboard.php');
    elseif ($role === 'Officer') header('Location: verification.php');
    elseif ($role === 'Admin') header('Location: admin_dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sovereign Structures</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #fff;
            min-height: 100vh;
            background-image: url('AESTHETIC HD WALLPAPERS _ DARK BUILDING WALLPAPERS🩶.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.85) 0%, rgba(0, 0, 0, 0.75) 100%);
            z-index: 0;
        }

        /* Navigation */
        .navbar {
            background: transparent;
            backdrop-filter: blur(7px);
            padding: 20px 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            width: 70px;
            height: 70px;
            border-radius: 50%;
        }

        .brand-info {
            display: flex;
            flex-direction: column;
        }

        .brand-title {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .brand-subtitle {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.8);
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .nav-back {
            display: flex;
            gap: 15px;
        }

        .btn-back {
            color: #fff;
            text-decoration: none;
            padding: 10px 28px;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-back:hover {
            background: #fff;
            color: #111827;
            border-color: #fff;
        }

        /* Login Container */
        .login-wrapper {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 150px 20px 50px;
        }

        .login-container {
            max-width: 1200px;
            width: 100%;
        }

        .login-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .login-title {
            font-size: 48px;
            font-weight: 900;
            margin-bottom: 15px;
            color: #fff;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }

        .login-subtitle {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.8);
        }

        /* User Type Selection */
        .user-type-grid {
            display: grid;
            grid-template-columns: repeat(3, 0fr);
            gap: 40px;   /* gap between circles */
            margin: 0 auto 60px; /* auto margins center the grid container */
            justify-content: center; /* center the grid items horizontally */
            align-items: center; /* center the grid items vertically */
        }

        .user-type-card {
            background: rgba(16, 19, 24, 0.6);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.1);
            /* make cards circular */
            width: 200px;
            height: 200px;
            border-radius: 50%;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .user-type-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(58, 59, 60, 0.3);
        }

        .user-type-card.active {
            box-shadow: 0 15px 40px rgba(41, 43, 45, 0.4);
        }

        /* Applicant - Blue */
        .user-type-card:has(.icon-applicant):hover {
            border-color: #3b82f6;
        }

        .user-type-card:has(.icon-applicant).active {
            border-color: #3b82f6;
            background: rgba(59, 130, 246, 0.2);
        }

        /* Officer - Green */
        .user-type-card:has(.icon-officer):hover {
            border-color: #10b981;
        }

        .user-type-card:has(.icon-officer).active {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.2);
        }

        /* Admin - Orange */
        .user-type-card:has(.icon-admin):hover {
            border-color: #f59e0b;
        }

        .user-type-card:has(.icon-admin).active {
            border-color: #f59e0b;
            background: rgba(245, 158, 11, 0.2);
        }

        .user-type-icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 20px;
            border-radius: 50%;
            overflow: hidden; /* clip any square content inside */
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 35px;
            color: #fff;
        }

        .icon-applicant {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }

        .icon-officer {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .icon-admin {
            background: linear-gradient(135deg, #f59e0b, #ea580c);
        }

        .user-type-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #fff;
        }

        .user-type-desc {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Login Form */
        .login-form-container {
            display: none;
            max-width: 500px;
            margin: 0 auto;
            background: rgba(55, 56, 57, 0.7);
            backdrop-filter: blur(15px);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 50px 40px;
        }

        .form-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .form-title {
            font-size: 28px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 10px;
        }

        .form-subtitle {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
            color: #fecaca;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.5);
            color: #a7f3d0;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.5);
            color: #bfdbfe;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            font-size: 15px;
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #fff;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            background: rgba(0, 0, 0, 0.4);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(59, 130, 246, 0.5);
        }

        .form-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .form-footer p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }

        .form-footer a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .form-footer a:hover {
            color: #60a5fa;
        }

        .demo-credentials {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.8);
        }

        .demo-credentials strong {
            color: #60a5fa;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .user-type-grid {
                grid-template-columns: 1fr;
                max-width: 200px; /* match circle width for single column */
                margin: 0 auto 40px;
                gap: 12px;
            }
        }

        @media (max-width: 768px) {
            .login-title {
                font-size: 36px;
            }
            
            .login-form-container {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo-section">
                <img src="Screenshot 2026-02-21 222153.png" class="logo">
                <div class="brand-info">
                    <div class="brand-title">SOVEREIGN STRUCTURES</div>
                    <div class="brand-subtitle">Construction dept</div>
                </div>
            </div>
            <div class="nav-back">
                <a href="index.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </div>
        </div>
    </nav>

    <!-- Login Section -->
    <div class="login-wrapper">
        <div class="login-container">
            <!-- Header -->
            <div class="login-header">
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Select your user type to continue</p>
            </div>

            <!-- User Type Selection -->
            <div class="user-type-grid">
                <div class="user-type-card" onclick="selectUserType(this,'applicant')">
                    <div class="user-type-icon icon-applicant">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3 class="user-type-title">Applicant</h3>
                   
                </div>

                <div class="user-type-card" onclick="selectUserType(this,'officer')">
                    <div class="user-type-icon icon-officer">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3 class="user-type-title">Permit Officer</h3>
                    
                </div>

                <div class="user-type-card" onclick="selectUserType(this,'admin')">
                    <div class="user-type-icon icon-admin">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <h3 class="user-type-title">Admin</h3>
                    
                </div>
            </div>

            <!-- Login Form -->
            <div class="login-form-container">
                <div class="form-header">
                    <h2 class="form-title" id="formTitle">Applicant Login</h2>
                    <p class="form-subtitle">Enter your credentials to access your account</p>
                </div>

                <!-- Error Message Display -->
                <?php
                if (isset($_SESSION['error'])) {
                    echo '<div class="alert-error">';
                    echo '<i class="fas fa-times-circle"></i>';
                    echo '<span>' . htmlspecialchars($_SESSION['error']) . '</span>';
                    echo '</div>';
                    unset($_SESSION['error']);
                }
                if (isset($_SESSION['success'])) {
                    echo '<div class="alert-success">';
                    echo '<i class="fas fa-check-circle"></i>';
                    echo '<span>' . htmlspecialchars($_SESSION['success']) . '</span>';
                    echo '</div>';
                    unset($_SESSION['success']);
                }
                ?>

                <form method="POST" action="login.php">
                    <input type="hidden" name="user_type" id="userType" value="Applicant">

                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" placeholder="Enter your email" required>
                    </div>

                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>

               

                <div class="form-footer">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Prevent browser back button
        function preventBack() { window.history.forward(); }
        setTimeout("preventBack()", 0);
        window.onunload = function () { null };
        
        history.pushState(null, null, document.URL);
        window.addEventListener('popstate', function () {
            history.pushState(null, null, document.URL);
            preventBack();
        });

        function selectUserType(el, type, hideAlerts = true) {
            // Remove active class from all cards
            document.querySelectorAll('.user-type-card').forEach(card => {
                card.classList.remove('active');
            });

            // Add active class to selected card
            el.classList.add('active');

            // Normalize key
            const key = String(type).toLowerCase();

            // Hide any existing error/success messages
            if (hideAlerts) {
                document.querySelectorAll('.alert-error, .alert-success, .alert-info').forEach(alert => {
                    alert.style.display = 'none';
                });
            }

            // Update form title and hidden input
            const titles = {
                'applicant': 'Applicant Login',
                'officer': 'Permit Officer Login',
                'admin': 'Admin Login'
            };

            const userTypes = {
                'applicant': 'Applicant',
                'officer': 'Officer',
                'admin': 'Admin'
            };

            document.getElementById('formTitle').textContent = titles[key] || 'Login';
            document.getElementById('userType').value = userTypes[key] || '';

            // Show form if not already visible
            const formContainer = document.querySelector('.login-form-container');
            if (formContainer) {
                formContainer.style.display = 'block';
                // Scroll to form smoothly
                formContainer.scrollIntoView({ behavior: 'smooth' });
            }
        }

        // Auto-select tab if 'type' is in URL
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const type = urlParams.get('type');
            if (type) {
                const cards = document.querySelectorAll('.user-type-card');
                cards.forEach(card => {
                    if (card.getAttribute('onclick').includes("'" + type.toLowerCase() + "'")) {
                        selectUserType(card, type, false);
                    }
                });
            }

            // If there are errors or success messages, show the form automatically
            const hasStatus = <?php echo (isset($_SESSION['error']) || isset($_SESSION['success'])) ? 'true' : 'false'; ?>;
            const userType = document.getElementById('userType').value;
            
            if (hasStatus && userType) {
                const formContainer = document.querySelector('.login-form-container');
                if (formContainer) formContainer.style.display = 'block';
            }
        });
    </script>
</body>
</html>
