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
    <title>Location-Based Construction Risk Assessment</title>
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
            max-width: 2000px;
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display:flex;
            align-items: center;
            gap: 20px;
        }

        .logo {
            width: 100px;
            height: 100px;
            border-radius: 100%;
        }

        .brand-info {
            display: flex;
            flex-direction: column;
        }

        .brand-title {
            font-size: 20px;
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

        .nav-menu {
            display: flex;
            gap: 40px;
        }

        .nav-menu a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s;
            position: relative;
        }

        .nav-menu a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: #fff;
            transition: width 0.3s;
        }

        .nav-menu a:hover::after {
            width: 100%;
        }

        .nav-menu a:hover {
            color: #fff;
        }

        .nav-actions {
            display: flex;
            gap: 15px;
        }

        .btn-login {
            color: #fff;
            text-decoration: none;
            padding: 10px 28px;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-login:hover {
            background: #fff;
            color: #111827;
            border-color: #fff;
        }

        /* Hero Section */
        .hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: url('AESTHETIC HD WALLPAPERS _ DARK BUILDING WALLPAPERS🩶.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, 
                rgba(0, 0, 0, 0.75) 0%,
                rgba(0, 0, 0, 0.65) 100%);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 0 40px;
            margin-top: 80px;
        }

        .hero-logo {
            width: 180px;
            height: 180px;
            margin: 0 auto 40px;
            animation: fadeIn 1s ease-out;
        }

        .hero-tagline {
            font-size: 16px;
            letter-spacing: 4px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 20px;
            animation: fadeIn 1s ease-out 0.2s both;
        }

        .hero-title {
            font-size: 72px;
            font-weight: 900;
            margin-bottom: 30px;
            color: #fff;
            line-height: 1.1;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            animation: slideUp 1s ease-out 0.4s both;
        }

        .hero-subtitle {
            font-size: 22px;
            margin-bottom: 50px;
            color: rgba(255, 255, 255, 0.95);
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
            animation: slideUp 1s ease-out 0.6s both;
        }

        .hero-buttons {
            display: flex;
            gap: 25px;
            justify-content: center;
            animation: fadeIn 1s ease-out 0.8s both;
        }

        .btn-primary {
            background: transparent;
            color: #fff;
            padding: 18px 50px;
            text-decoration: none;
            border: 2px solid rgba(255, 255, 255, 0.6);
            border-radius: 8px;
            font-size: 17px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            box-shadow: 0 8px 25px rgba(17, 18, 19, 0.4);
        }

        .btn-primary:hover {
            background: #898d96;
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(13, 13, 14, 0.6);
        }

        .btn-secondary {
            background: transparent;
            color: #fff;
            padding: 18px 50px;
            text-decoration: none;
            border: 2px solid rgba(255, 255, 255, 0.6);
            border-radius: 8px;
            font-size: 17px;
            font-weight: 700;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            border-color: #fff;
            background: #898d96;
            transform: translateY(-3px);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Features Section */
        .features {
            padding: 120px 0;
            background:linear-gradient(135deg, #0a0a0a, #232222);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 40px;
        }

        .section-header {
            text-align: center;
            margin-bottom: 80px;
        }

        .section-title {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 20px;
            color: #fff;
        }

        .section-subtitle {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.7);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 35px;
        }

        .feature-card {
            background:transparent;
            padding: 50px 35px;
            border-radius: 16px;
            text-align: center;
            border: 2px solid rgba(59, 130, 246, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: transparent;
            opacity: 0;
            transition: opacity 0.4s;
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-card:hover {
            transform: translateY(-15px);
            border-color: #3b82f6;
            box-shadow: 0 20px 60px rgba(59, 130, 246, 0.3);
        }

        .feature-icon {
            width: 90px;
            height: 90px;
            margin: 0 auto 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            color: #fff;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.15) rotate(360deg);
        }

        .icon-blue {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4);
        }

        .icon-green {
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
        }

        .icon-orange {
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.4);
        }

        .icon-purple {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.4);
        }

        .feature-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #fff;
        }

        .feature-text {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.7;
        }

        /* Process Section */
        .process {
            padding: 120px 0;
            background: #1b1c1c;
        }

        .process-steps {
            display: flex;
            justify-content: space-between;
            max-width: 1300px;
            margin: 0 auto;
        }

        .step {
            text-align: center;
            flex: 1;
            position: relative;
        }

        .step-number {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #0a0b0b, #000000);
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 900;
            margin: 0 auto 25px;
            box-shadow: 0 10px 35px rgba(88, 90, 94, 0.5);
            transition: all 0.5s;
        }

        .step:hover .step-number {
            transform: scale(1.2) rotate(360deg);
            box-shadow: 0 15px 50px rgba(17, 18, 18, 0.7);
        }

        .step-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 12px;
            color: #fff;
        }

        .step-text {
            font-size: 15px;
            color: rgba(255, 255, 255, 0.75);
        }

        .step::after {
            content: '→';
            position: absolute;
            top: 45px;
            right: -35px;
            font-size: 32px;
            color: rgba(13, 13, 13, 0.6);
            font-weight: bold;
        }

        .step:last-child::after {
            display: none;
        }

        /* Stats Section */
        .stats {
            padding: 100px 0;
            background:linear-gradient(135deg, #0a0a0a, #232222);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 70px;
            max-width: 1300px;
            margin: 0 auto;
        }

        .stat {
            text-align: center;
            transition: all 0.3s;
        }

        .stat:hover {
            transform: translateY(-12px);
        }

        .stat-number {
            font-size: 64px;
            font-weight: 900;
            margin-bottom: 12px;
            color: #fff;
            text-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }

        .stat-label {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.95);
            font-weight: 500;
        }

        /* Footer */
        .footer {
            background: #1b1c1c;
            padding: 80px 0 40px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 60px;
            max-width: 1400px;
            margin: 0 auto 50px;
            padding: 0 40px;
        }

        .footer-brand {
            display: flex;
            flex-direction: column;
        }

        .footer-logo {
            width: 100px;
            height: 100px;
            margin-bottom: 25px;
            border-radius: 100%;
        }

        .footer-brand h3 {
            font-size: 24px;
            margin-bottom: 15px;
            color: #fff;
        }

        .footer-brand p {
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.8;
            font-size: 15px;
        }

        .footer-section h4 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 25px;
            color: #fff;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section li {
            margin-bottom: 15px;
        }

        .footer-section a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 15px;
            transition: all 0.3s;
        }

        .footer-section a:hover {
            color: #3b82f6;
            padding-left: 5px;
        }

        .footer-bottom {
            text-align: center;
            padding-top: 40px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.6);
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            .hero-title {
                font-size: 42px;
            }
            .features-grid {
                grid-template-columns: 1fr;
            }
            .process-steps {
                flex-direction: column;
                gap: 50px;
            }
            .step::after {
                display: none;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .footer-grid {
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
                <img src="Screenshot 2026-02-21 222153.png"  class="logo">
                <div class="brand-info">
                    <div class="brand-title">SOVEREIGN STRUCTURES</div>
                    <div class="brand-subtitle">Construction dept</div>
                </div>
            </div>
            <div class="nav-menu">
                <a href="index.php">Home</a>
                <a href="#features">About</a>
                <a href="#contact">Contact</a>
            </div>
            <div class="nav-actions">
                
                <a href="login.php" class="btn-login">Login</a>
            </div>
            
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            
            
            <h2 class="hero-title">Location-Based Construction Risk Assessment</h2>
            <p class="hero-subtitle">An integrated e-governance platform for evaluating environmental and geographical risks across Kerala</p>
            <div class="hero-buttons">
                <a href="register.php" class="btn-primary">
                    <i class="fas fa-arrow-right"></i> Register Now
                </a>
               
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">System Overview</h2>
                
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon icon-blue">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    
                    <p class="feature-text">Submit construction site location with just District and Town selection</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon icon-green">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h3 class="feature-title">Site Verification</h3>
                    <p class="feature-text">Permit officers verify auto-predicted data and conduct on-site assessments</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon icon-orange">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Risk Assessment</h3>
                    <p class="feature-text">Machine learning generates risk levels and safety recommendations</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon icon-purple">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3 class="feature-title">Admin Monitoring</h3>
                    <p class="feature-text">Comprehensive dashboard for tracking applications and analytics</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Process -->
    <section class="process">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Application Process</h2>
                <p class="section-subtitle">Simple and transparent workflow</p>
            </div>
            <div class="process-steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3 class="step-title">Register</h3>
                    <p class="step-text">Create an account as an applicant</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3 class="step-title">Submit Details</h3>
                    <p class="step-text">Fill construction site and environmental data</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3 class="step-title">Verification</h3>
                    <p class="step-text">Officers review and verify application</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3 class="step-title">Get Report</h3>
                    <p class="step-text">Receive assessment and recommendations</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="stats">
        <div class="stats-grid">
            <div class="stat">
                <div class="stat-number">140+</div>
                <div class="stat-label">Locations Covered</div>
            </div>
            <div class="stat">
                <div class="stat-number">14</div>
                <div class="stat-label">Districts</div>
            </div>
            <div class="stat">
                <div class="stat-number">3</div>
                <div class="stat-label">Risk Categories</div>
            </div>
            <div class="stat">
                <div class="stat-number">24/7</div>
                <div class="stat-label">Online Access</div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer" id="contact">
        <div class="footer-grid">
            <div class="footer-brand">
                <img src="Screenshot 2026-02-21 222153.png" alt="Logo" class="footer-logo">
                <h3>SOVEREIGN STRUCTURES</h3>
                <p>Construction Dept<br>Permit Granting<br>Building safer communities through advanced risk assessment</p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="#features">Features</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Services</h4>
                <ul>
                    <li><a href="#">Risk Assessment</a></li>
                    <li><a href="#">Site Verification</a></li>
                    <li><a href="#">Consultation</a></li>
                    <li><a href="#">Reports</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Contact</h4>
                <ul>
                    <li><a href="mailto:lsgd@kerala.gov.in">sovereignstructures.in</a></li>
                    <li><a href="tel:+914712518000">+91 471 2518000</a></li>
                    <li><a href="#">Thiruvananthapuram, Kerala</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 ConstructRisk Solutions - Government of Kerala. All rights reserved.</p>
        </div>
    </footer>
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
    </script>
</body>
</html>
