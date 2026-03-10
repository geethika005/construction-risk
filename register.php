<?php
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
    <title>Register - Sovereign Structures</title>
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

        /* Register Container */
        .register-wrapper {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 150px 20px 50px;
        }

        .register-container {
            max-width: 1200px;
            width: 100%;
        }

        .register-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .register-title {
            font-size: 48px;
            font-weight: 900;
            margin-bottom: 15px;
            color: #fff;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }

        .register-subtitle {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.8);
        }

        /* User Type Selection */
        .user-type-grid {
            display: grid;
            /* two fixed columns so cards remain together and centered */
            grid-template-columns: repeat(2, 0fr);
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


        .user-type-icon {
            width: 50px;
            height: 50px;
            margin: 0 auto 20px;
            border-radius: 50%;
            overflow: hidden;
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


            font-size: 22px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #fff;
        }

        .user-type-desc {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Register Form */
        .register-form-container {
            max-width: 600px;
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

        .form-group {
            margin-bottom: 25px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            font-size: 15px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #fff;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3b82f6;
            background: rgba(0, 0, 0, 0.4);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .form-group select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.4)' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 20px;
            padding-right: 45px;
        }

        .form-group select option {
            background: #1f2937;
            color: #fff;
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
            box-shadow: 0 12px 30px rgba(26, 39, 59, 0.5);
        }
        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        .password-wrap {
            position: relative;
        }
        .password-wrap input {
            padding-right: 48px;
        }
        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255,255,255,0.5);
            cursor: pointer;
            font-size: 16px;
            padding: 0;
            line-height: 1;
        }
        .password-toggle:hover { color: #fff; }
        .email-hint {
            font-size: 12px;
            color: rgba(255,255,255,0.55);
            margin-top: 5px;
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

        .password-requirements {
            display: none;
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            margin-bottom: 20px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.8);
        }

        .field-error {
            color: #ef4444;
            font-size: 12px;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .input-error {
            border-color: #ef4444 !important;
        }

        .required { color: #ef4444; }

        .register-form-container {
            display: none;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .user-type-grid {
                grid-template-columns: 1fr;
                max-width: 200px;
                margin: 0 auto 40px;
                gap: 15px;
            }
        }

        @media (max-width: 768px) {
            .register-title {
                font-size: 36px;
            }
            
            .register-form-container {
                padding: 40px 30px;
            }

            .form-row {
                grid-template-columns: 1fr;
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

    <!-- Register Section -->
    <div class="register-wrapper">
        <div class="register-container">
            <!-- Header -->
            <div class="register-header">
                <h1 class="register-title">Create Account</h1>
                <p class="register-subtitle">Select your user type and register to get started</p>
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
            </div>

            <!-- Registration Form -->
            <div class="register-form-container">
                <div class="form-header">
                    <h2 class="form-title" id="formTitle">Register as Applicant</h2>
                    <p class="form-subtitle">Enter your details to create an account</p>
                </div>

                <!-- Display errors if any -->
                <?php
                if (isset($_SESSION['errors']) && isset($_SESSION['errors']['general'])) {
                    echo '<div class="alert-error">';
                    echo '<i class="fas fa-times-circle"></i>';
                    echo '<span>' . htmlspecialchars($_SESSION['errors']['general']) . '</span>';
                    echo '</div>';
                    // We don't unset here because we might need other errors under fields
                }
                ?>

                <!-- Display success message if any -->
                <?php
                if (isset($_SESSION['success'])) {
                    echo '<div class="alert-success">';
                    echo '<i class="fas fa-check-circle"></i>';
                    echo '<span>' . htmlspecialchars($_SESSION['success']) . '</span>';
                    echo '</div>';
                    unset($_SESSION['success']);
                }
                ?>

                <form method="POST" action="register_handler.php" id="registerForm" autocomplete="off">
                    <input type="hidden" name="user_type" id="userType" value="Applicant">

                    <div class="form-group">
                        <label>Full Name <span class="required">*</span></label>
                        <input type="text" name="name" placeholder="Enter your full name" required 
                               value="<?php echo isset($_SESSION['old_input']['name']) ? htmlspecialchars($_SESSION['old_input']['name']) : ''; ?>"
                               class="<?php echo (isset($_SESSION['errors']['name'])) ? 'input-error' : ''; ?>">
                        <?php if (isset($_SESSION['errors']['name'])): ?>
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['errors']['name']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" id="emailInput" placeholder="Enter your email address" required 
                               value="<?php echo isset($_SESSION['old_input']['email']) ? htmlspecialchars($_SESSION['old_input']['email']) : ''; ?>"
                               class="<?php echo (isset($_SESSION['errors']['email'])) ? 'input-error' : ''; ?>">
                        <p class="email-hint" id="emailHint">Use your personal email address</p>
                        <?php if (isset($_SESSION['errors']['email'])): ?>
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['errors']['email']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group" id="sparkPenGroup" style="display: none;">
                        <label>SPARK PEN *</label>
                        <input type="text" name="spark_pen" id="sparkPenInput" placeholder="Enter your 6-digit PEN" pattern="\d{6}" maxlength="6" 
                               value="<?php echo isset($_SESSION['old_input']['spark_pen']) ? htmlspecialchars($_SESSION['old_input']['spark_pen']) : ''; ?>"
                               title="SPARK PEN must be exactly 6 digits" class="<?php echo (isset($_SESSION['errors']['spark_pen'])) ? 'input-error' : ''; ?>">
                        <p style="font-size: 13px; color: rgba(255,255,255,0.7); margin-top: 5px;">Required for Government Officers</p>
                        <?php if (isset($_SESSION['errors']['spark_pen'])): ?>
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['errors']['spark_pen']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Set your password *</label>
                        <div class="password-wrap">
                            <input type="password" name="password" id="password" placeholder="Enter a strong password" required class="<?php echo (isset($_SESSION['errors']['password'])) ? 'input-error' : ''; ?>">
                            <button type="button" class="password-toggle" onclick="togglePassword()" title="Show/Hide password">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        <?php if (isset($_SESSION['errors']['password'])): ?>
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['errors']['password']; ?></div>
                        <?php endif; ?>
                        
                        <div class="password-requirements" id="passwordRequirements">
                            <strong>Password Requirements:</strong>
                            <ul>
                                <li>Minimum 8 characters</li>
                                <li>At least one uppercase letter (A-Z)</li>
                                <li>At least one lowercase letter (a-z)</li>
                                <li>At least one number (0-9)</li>
                                <li>At least one special character (!@#$%^&*)</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Confirm Password *</label>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter your password" required class="<?php echo (isset($_SESSION['errors']['confirm_password'])) ? 'input-error' : ''; ?>">
                        <?php if (isset($_SESSION['errors']['confirm_password'])): ?>
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['errors']['confirm_password']; ?></div>
                        <?php endif; ?>
                    </div>

                    <?php 
                        unset($_SESSION['errors']); 
                        // Note: we don't unset old_input here because we need it for JS below, 
                        // we'll unset it at the end of the script or on successful submission.
                    ?>

                    <div class="form-group" style="display: flex; align-items: center; margin-bottom: 25px;">
                        <input type="checkbox" name="terms" id="terms" required style="width: auto; margin: 0; cursor: pointer;">
                        <label for="terms" style="display: inline; margin-left: 12px; cursor: pointer;">
                            I agree to the <a href="#" style="color: #3b82f6;">Terms & Conditions</a>
                        </label>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-user-plus" id="submitIcon"></i> <span id="submitText">Create Account</span>
                    </button>
                </form>

                <div class="form-footer">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
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

        function selectUserType(el, type) {
            // Remove active class from all cards
            document.querySelectorAll('.user-type-card').forEach(card => {
                card.classList.remove('active');
            });

            // Add active class to clicked card
            el.classList.add('active');

            // Normalize type key
            const key = String(type).toLowerCase();

            // Update form title and hidden input
            const titles = {
                'applicant': 'Register as Applicant',
                'officer': 'Register as Permit Officer'
            };

            const userTypes = {
                'applicant': 'Applicant',
                'officer': 'Officer'
            };

            document.getElementById('formTitle').textContent = titles[key] || 'Register';
            document.getElementById('userType').value = userTypes[key] || '';
            
            // Update email hint
            const emailInput = document.getElementById('emailInput');
            const emailHint = document.getElementById('emailHint');
            if (key === 'officer') {
                
                emailHint.textContent = 'Officers must use an official @gov.in email address';
                emailHint.style.color = '#f59e0b';
            } else {
                
                emailHint.textContent = 'Use your personal email address';
                emailHint.style.color = 'rgba(255,255,255,0.55)';
            }
            
            // Toggle SPARK PEN field visibility
            const sparkPenGroup = document.getElementById('sparkPenGroup');
            const sparkPenInput = document.getElementById('sparkPenInput');
            if (key === 'officer') {
                sparkPenGroup.style.display = 'block';
                sparkPenInput.required = true;
            } else {
                sparkPenGroup.style.display = 'none';
                sparkPenInput.required = false;
                sparkPenInput.value = '';
            }

            // Show form if not already visible
            const formContainer = document.querySelector('.register-form-container');
            if (formContainer) {
                formContainer.style.display = 'block';
                // Scroll to form smoothly
                formContainer.scrollIntoView({ behavior: 'smooth' });
            }
        }

        // Show password requirements on focus/click
        const passInput = document.getElementById('password');
        const requirements = document.getElementById('passwordRequirements');
        
        if (passInput && requirements) {
            passInput.addEventListener('focus', () => {
                requirements.style.display = 'block';
            });
            
            passInput.addEventListener('click', () => {
                requirements.style.display = 'block';
            });
        }

        // Toggle confirm password visibility
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Validate password on change
        document.getElementById('password').addEventListener('input', validatePassword);
        document.getElementById('confirm_password').addEventListener('input', validatePassword);

        function validatePassword() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                document.getElementById('confirm_password').style.borderColor = '#ef4444';
            } else if (password.length > 0) {
                document.getElementById('confirm_password').style.borderColor = 'rgba(255, 255, 255, 0.2)';
            }
        }

        // Prevent double-submit: disable button and show loading on submit
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('submitBtn');
            const icon = document.getElementById('submitIcon');
            const text = document.getElementById('submitText');
            btn.disabled = true;
            icon.className = 'fas fa-spinner fa-spin';
            text.textContent = 'Creating Account...';
        });

        // If there are errors or old input, show the form automatically
        window.addEventListener('DOMContentLoaded', () => {
            const hasStatus = <?php echo (isset($_SESSION['errors']) || isset($_GET['error']) || isset($_SESSION['old_input'])) ? 'true' : 'false'; ?>;
            const oldType = "<?php echo isset($_SESSION['old_input']['user_type']) ? strtolower($_SESSION['old_input']['user_type']) : ''; ?>";
            
            if (hasStatus && oldType) {
                // Find matching card and trigger click
                document.querySelectorAll('.user-type-card').forEach(card => {
                    if (card.getAttribute('onclick').includes("'" + oldType + "'")) {
                        selectUserType(card, oldType);
                    }
                });
            }
            <?php unset($_SESSION['old_input']); ?>
        });
    </script>
</body>
</html>
