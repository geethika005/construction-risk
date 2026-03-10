<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once 'config.php';

// Handle permit officer review actions (verify/reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_action'])) {
    // Only permit officers may perform review actions
    if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'officer') {
        $_SESSION['app_error'] = 'Unauthorized action';
        header('Location: submit_application.php');
        exit();
    }

    $action = $_POST['review_action'];
    $id = isset($_POST['construction_id']) ? (int)$_POST['construction_id'] : 0;
    if ($id <= 0) {
        $_SESSION['app_error'] = 'Invalid application id';
        header('Location: submit_application.php');
        exit();
    }

    $newStatus = $action === 'verify' ? 'Verified' : ($action === 'reject' ? 'Rejected' : null);
    if ($newStatus === null) {
        $_SESSION['app_error'] = 'Unknown action';
        header('Location: submit_application.php');
        exit();
    }

    $conn = getDBConnection();
    $uStmt = $conn->prepare("UPDATE Construction_Details SET application_status = ? WHERE construction_id = ?");
    $uStmt->bind_param('si', $newStatus, $id);
    if ($uStmt->execute()) {
        $_SESSION['app_success'] = 'Application updated to ' . $newStatus;
    } else {
        $_SESSION['app_error'] = 'Failed to update application: ' . $uStmt->error;
    }
    $uStmt->close();
    closeDBConnection($conn);
    header('Location: submit_application.php');
    exit();
}

// Handle applicant form POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['district'])) {
    $district = isset($_POST['district']) ? trim($_POST['district']) : '';
    $town = isset($_POST['town']) ? trim($_POST['town']) : '';
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

    $errors = [];
    if (empty($district)) $errors[] = 'District is required.';
    if (empty($town)) $errors[] = 'Town is required.';

    if (!empty($errors)) {
        $_SESSION['app_error'] = implode(' ', $errors);
        header('Location: submit_application.php');
        exit();
    }

    $conn = getDBConnection();

    // Check for duplicate application
    $dupStmt = $conn->prepare("SELECT construction_id FROM Construction_Details WHERE user_id = ? AND district = ? AND town = ? AND application_status != 'Rejected' LIMIT 1");
    $dupStmt->bind_param('iss', $user_id, $district, $town);
    $dupStmt->execute();
    $dupResult = $dupStmt->get_result();
    if ($dupResult->num_rows > 0) {
        $_SESSION['app_error'] = 'You already have an active application for ' . htmlspecialchars($district) . ', ' . htmlspecialchars($town) . '. Duplicate applications are not allowed.';
        $dupStmt->close();
        closeDBConnection($conn);
        header('Location: submit_application.php');
        exit();
    }
    $dupStmt->close();
    // Create Construction_Details table if not exists (basic schema matching dashboard expectations)
    $createSql = "CREATE TABLE IF NOT EXISTS Construction_Details (
        construction_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        district VARCHAR(255) NOT NULL,
        town VARCHAR(255) NOT NULL,
        application_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        application_status VARCHAR(50) DEFAULT 'Submitted',
        predicted_overall_risk VARCHAR(50) DEFAULT NULL,
        predicted_flood_risk VARCHAR(50) DEFAULT NULL,
        predicted_landslide_risk VARCHAR(50) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($createSql);

    // Use explicit PHP local date instead of CURRENT_TIMESTAMP
    $currentDate = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO Construction_Details (user_id, district, town, application_date) VALUES (?,?,?,?)");
    $stmt->bind_param('isss', $user_id, $district, $town, $currentDate);
    if ($stmt->execute()) {
        $insertId = $conn->insert_id;

        // Call ML predictor to auto-populate risk fields
        require_once 'ml_predictor.php';
        $predictor = new MLPredictor();
        $pred = $predictor->predictRisks($district, $town);

        $overall = null; $flood = null; $landslide = null;
        if (is_array($pred)) {
            if (isset($pred['risk_assessment'])) {
                $ra = $pred['risk_assessment'];
                $overall = isset($ra['overall_risk']) ? $ra['overall_risk'] : (isset($ra['overall']) ? $ra['overall'] : null);
                $flood = isset($ra['flood_risk']) ? $ra['flood_risk'] : null;
                $landslide = isset($ra['landslide_risk']) ? $ra['landslide_risk'] : null;
            }
        }

        // Update record with predictions (if any)
        $upd = $conn->prepare("UPDATE Construction_Details SET predicted_overall_risk = ?, predicted_flood_risk = ?, predicted_landslide_risk = ? WHERE construction_id = ?");
        $upd->bind_param('sssi', $overall, $flood, $landslide, $insertId);
        $upd->execute();
        $upd->close();

        $_SESSION['app_success'] = 'Application submitted successfully.';
        $stmt->close();
        closeDBConnection($conn);
        // Redirect user back to dashboard after success
        header('Location: dashboard.php');
        exit();
    } else {
        $_SESSION['app_error'] = 'Failed to submit application. ' . $stmt->error;
        $stmt->close();
        closeDBConnection($conn);
        // Redirect back to form on failure
        header('Location: submit_application.php');
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Application - Sovereign Structures</title>
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

        .nav-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }

        .user-name {
            font-size: 14px;
            font-weight: 600;
        }

        .btn-logout {
            color: #fff;
            text-decoration: none;
            padding: 10px 28px;
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: #ef4444;
            border-color: #ef4444;
        }

        /* Main Container */
        .main-wrapper {
            position: relative;
            z-index: 1;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 150px 20px 50px;
        }

        .main-container {
            max-width: 1000px;
            width: 100%;
        }

        .page-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .page-title {
            font-size: 48px;
            font-weight: 900;
            margin-bottom: 15px;
            color: #fff;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }

        .page-subtitle {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.8);
        }

        /* Form Container */
        .form-container {
            background: rgba(55, 56, 57, 0.7);
            backdrop-filter: blur(15px);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 50px 40px;
        }

        .form-section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(59, 130, 246, 0.3);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-icon {
            font-size: 24px;
            color: #3b82f6;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row.full {
            grid-template-columns: 1fr;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            font-size: 15px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #fff;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            background: rgba(0, 0, 0, 0.4);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .form-group select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='rgba(255,255,255,0.4)' stroke-width='2'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 20px;
            padding-right: 45px;
            cursor: pointer;
        }

        .form-group select option {
            background: #1f2937;
            color: #fff;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }

        .required {
            color: #ef4444;
        }

        .helper-text {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 6px;
        }

        .btn-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
        }

        .btn-submit,
        .btn-cancel {
            padding: 16px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-submit {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(59, 130, 246, 0.5);
        }
        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-cancel {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.2);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
            color: #fecaca;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.5);
            color: #a7f3d0;
        }

        .alert-info {
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.5);
            color: #bfdbfe;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-title {
                font-size: 36px;
            }
            
            .form-container {
                padding: 40px 30px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .nav-container {
                padding: 0 20px;
            }

            .btn-group {
                flex-direction: column;
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
            <div class="nav-right">
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span class="user-name" id="userName">Welcome</span>
                </div>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-wrapper">
        <div class="main-container">
            <!-- Header -->
            <div class="page-header">
                <h1 class="page-title">Submit Application</h1>
                <p class="page-subtitle">Provide your project details and location information</p>
            </div>

            <!-- Alert Messages -->
            <div id="alertContainer">
                <?php
                if (isset($_SESSION['app_success'])) {
                    echo '<div class="alert alert-success"><i class="fas fa-check-circle"></i><span>' . htmlspecialchars($_SESSION['app_success']) . '</span></div>';
                    unset($_SESSION['app_success']);
                }
                if (isset($_SESSION['app_error'])) {
                    echo '<div class="alert alert-error"><i class="fas fa-times-circle"></i><span>' . htmlspecialchars($_SESSION['app_error']) . '</span></div>';
                    unset($_SESSION['app_error']);
                }
                ?>
            </div>

            <!-- Form -->
            <form method="POST" action="submit_application.php" class="form-container" id="applicationForm">

                <!-- Location Information Section -->
                <div class="form-section">
                    <h2 class="section-title">
                        <i class="fas fa-map-marker-alt section-icon"></i>
                        Location Information
                    </h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                District <span class="required">*</span>
                            </label>
                            <select name="district" id="district" required>
                                <option value="">-- Select District --</option>
                                <option value="Alappuzha">Alappuzha</option>
                                <option value="Ernakulam">Ernakulam</option>
                                <option value="Idukki">Idukki</option>
                                <option value="Kannur">Kannur</option>
                                <option value="Kasaragod">Kasaragod</option>
                                <option value="Kollam">Kollam</option>
                                <option value="Kottayam">Kottayam</option> 
                                <option value="Kozhikode">Kozhikode</option>
                                <option value="Malappuram">Malappuram</option>
                                <option value="Palakkad">Palakkad</option>
                                <option value="Pathanamthitta">Pathanamthitta</option>
                                <option value="Thiruvananthapuram">Thiruvananthapuram</option>
                                <option value="Thrissur">Thrissur</option>
                                <option value="Wayanad">Wayanad</option>
                

                            </select>
                            <p class="helper-text">Select the district where your project is located</p>
                        </div>

                        <div class="form-group">
                            <label>
                                Town<span class="required">*</span>
                            </label>
                            <select name="town" id="town" required>
                                <option value="">-- Select Town --</option>
                            </select>
                            <p class="helper-text">Select the specific town</p>
                        </div>
                    </div>

                    
                <!-- Action Buttons -->
                <div class="btn-group">
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-paper-plane" id="submitIcon"></i> <span id="submitText">Submit Application</span>
                    </button>
                    <a href="dashboard.php" class="btn-cancel">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Prevent browser back button
        history.pushState(null, null, document.URL);
        window.addEventListener('popstate', function () {
            history.pushState(null, null, document.URL);
        });

        // District and Town data mapping
        const districtTownMap = {
            'Kasaragod': [
                'Manjeshwar',
                'Bekal',
                'Kanhangad',
                'Uppala',
                'Mogral',
                'Cheruvathur',
                'Nileshwaram',
                'Kumbla',
                'Kasaragod',
                'Bandadka'
            ],
            'Kannur': [
                'Thalassery',
                'Mattannur',
                'Payyannur',
                'Kunhimangalam',
                'Peringathur',
                'Taliparamba',
                'Iritty',
                'Panoor',
                'Kannur Town',
                'Valapattanam'
            ],
            'Wayanad': [
                'Mananthavady',
                'Kaniyambetta',
                'Kalpetta',
                'Meppadi',
                'Pulpally',
                'Pozhuthana',
                'Sultan Bathery',
                'Padinjarathara',
                'Thariyode',
                'Vythiri'
            ],
            'Kozhikode': [
                'Koyilandy',
                'Thamarassery',
                'Kappad',
                'Feroke',
                'Kozhikode City',
                'Vadakara',
                'Kuttiady',
                'Ramanattukara',
                'Balussery',
                'Nadapuram'
            ],
            'Malappuram': [
                'Kottakkal',
                'Perinthalmanna',
                'Tirur',
                'Manjeri',
                'Tanur',
                'Nilambur',
                'Malappuram Town',
                'Ponnani',
                'Parappanangadi',
                'Areekode'
            ],
            'Palakkad': [
                'Alathur',
                'Mannarkkad',
                'Nemmara',
                'Ottapalam',
                'Malampuzha',
                'Shornur',
                'Chittur',
                'Palakkad Town',
                'Pattambi',
                'Kollengode'
            ],
            'Thrissur': [
                'Kodungallur',
                'Chalakudy',
                'Guruvayur',
                'Thrissur Town',
                'Kunnamkulam',
                'Wadakkanchery',
                'Chavakkad',
                'Irinjalakuda',
                'Ollur',
                'Anthikkad'
            ],
            'Ernakulam': [
                'Angamaly',
                'Perumbavoor',
                'Kothamangalam',
                'Muvattupuzha',
                'North Paravur',
                'Edappally',
                'Tripunithura',
                'Kochi',
                'Kalamassery',
                'Aluva'
            ],
            'Idukki': [
                'Kattappana',
                'Kumily',
                'Vandiperiyar',
                'Peermade',
                'Rajakkad',
                'Painavu',
                'Nedumkandam',
                'Devikulam',
                'Thodupuzha',
                'Adimali'
            ],
            'Kottayam': [
                'Vaikom',
                'Kuravilangad',
                'Ettumanoor',
                'Kaduthuruthy',
                'Pampady',
                'Changanassery',
                'Mundakayam',
                'Kanjirappally',
                'Pala',
                'Ponkunnam'
            ],
            'Alappuzha': [
                'Haripad',
                'Cherthala',
                'Punnapra',
                'Mavelikkara',
                'Muhamma',
                'Kayamkulam',
                'Mannanchery',
                'Ambalappuzha',
                'Chengannur',
                'Kuttanadu'
            ],
            'Pathanamthitta': [
                'Mallappally',
                'Kozhencherry',
                'Seethathode',
                'Thiruvalla',
                'Aranmula',
                'Pathanamthitta Town',
                'Adoor',
                'Ranni',
                'Pandalam',
                'Konni'
            ],
            'Kollam': [
                'Pathanapuram',
                'Kundara',
                'Anchal',
                'Punalur',
                'Sasthamkotta',
                'Chavara',
                'Kottarakkara',
                'Karunagappally',
                'Kollam City',
                'Paravur'
            ],
            'Thiruvananthapuram': [
                'Nedumangad',
                'Kattakada',
                'Balaramapuram',
                'Kilimanoor',
                'Poovar',
                'Varkala',
                'Kovalam',
                'Neyyattinkara',
                'Attingal',
                'Vizhinjam'
            ]
        };

        // District change event
        document.getElementById('district').addEventListener('change', function() {
            const selectedDistrict = this.value;
            const townSelect = document.getElementById('town');
            
            // Clear existing options except the default
            townSelect.innerHTML = '<option value="">-- Select Town --</option>';
            
            if (selectedDistrict && districtTownMap[selectedDistrict]) {
                const towns = districtTownMap[selectedDistrict];
                towns.forEach(town => {
                    const option = document.createElement('option');
                    option.value = town;
                    option.textContent = town;
                    townSelect.appendChild(option);
                });
                townSelect.disabled = false;
            } else {
                townSelect.disabled = true;
            }
        });

        // Form submission
        document.getElementById('applicationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!this.checkValidity()) {
                showAlert('Please fill in all required fields', 'error');
                return;
            }

            // Disable button to prevent double-submit
            const btn = document.getElementById('submitBtn');
            const icon = document.getElementById('submitIcon');
            const text = document.getElementById('submitText');
            btn.disabled = true;
            icon.className = 'fas fa-spinner fa-spin';
            text.textContent = 'Submitting...';
            
            // Submit the form
            this.submit();
        });

        // Auto-redirect to dashboard if success message is showing
        (function() {
            const successEl = document.querySelector('.alert.alert-success');
            if (successEl) {
                // Build countdown
                let seconds = 4;
                const span = document.createElement('strong');
                span.textContent = ` Redirecting in ${seconds}s...`;
                successEl.appendChild(span);

                const timer = setInterval(function() {
                    seconds--;
                    if (seconds <= 0) {
                        clearInterval(timer);
                        window.location.href = 'dashboard.php';
                    } else {
                        span.textContent = ` Redirecting in ${seconds}s...`;
                    }
                }, 1000);
            }
        })();

        // Show alert function
        function showAlert(message, type = 'info') {
            const alertContainer = document.getElementById('alertContainer');
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'times-circle' : type === 'success' ? 'check-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;
            alertContainer.appendChild(alertDiv);
            
            // Auto-remove alert after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // Set minimum date to today (if an input exists)
        const today = new Date().toISOString().split('T')[0];
        const startInput = document.querySelector('input[name="start_date"]');
        if (startInput) startInput.setAttribute('min', today);
    </script>
</body>
</html>
