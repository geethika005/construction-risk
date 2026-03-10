<?php
require_once 'config.php';
requireLogin();
requireRole('Officer');

$officer_id = $_SESSION['user_id'];
// Helper: ensure a column exists (works on older MySQL versions without IF NOT EXISTS)
function ensureColumnExists($conn, $table, $column, $definition) {
    $tableEsc = $conn->real_escape_string($table);
    $colEsc = $conn->real_escape_string($column);
    $q = "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '".$tableEsc."' AND COLUMN_NAME = '".$colEsc."'";
    $res = $conn->query($q);
    if ($res) {
        $row = $res->fetch_assoc();
        if (empty($row) || intval($row['c']) === 0) {
            $sql = "ALTER TABLE `".$tableEsc."` ADD COLUMN `".$colEsc."` " . $definition;
            $conn->query($sql);
        }
    }
}
// Determine application id from request (POST or GET)
$app_id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
// Handle verification submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? '';
    $remarks = $_POST['remarks'] ?? '';

    if (in_array($status, ['Verified', 'Rejected'])) {
        $conn = getDBConnection();

        // Ensure all environmental columns exist
        ensureColumnExists($conn, 'Construction_Details', 'average_elevation_m', 'DOUBLE NULL');
        ensureColumnExists($conn, 'Construction_Details', 'terrain_type', 'VARCHAR(255) NULL');
        ensureColumnExists($conn, 'Construction_Details', 'average_rainfall_mm', 'DOUBLE NULL');
        ensureColumnExists($conn, 'Construction_Details', 'water_table_depth', 'VARCHAR(255) NULL');
        ensureColumnExists($conn, 'Construction_Details', 'soil_type', 'VARCHAR(255) NULL');
        ensureColumnExists($conn, 'Construction_Details', 'slope_category', 'VARCHAR(255) NULL');
        ensureColumnExists($conn, 'Construction_Details', 'land_cover_type', 'VARCHAR(255) NULL');
        ensureColumnExists($conn, 'Construction_Details', 'forest_cover_percent', 'DOUBLE NULL');
        // Ensure prediction columns exist
        ensureColumnExists($conn, 'Construction_Details', 'predicted_flood_risk', 'VARCHAR(50) NULL');
        ensureColumnExists($conn, 'Construction_Details', 'predicted_landslide_risk', 'VARCHAR(50) NULL');
        ensureColumnExists($conn, 'Construction_Details', 'predicted_overall_risk', 'VARCHAR(50) NULL');
        


        // ALWAYS fetch ALL environmental values AND predictions from ML model for accuracy
        $stmtChk = $conn->prepare("SELECT district, town FROM Construction_Details WHERE construction_id = ?");
        $stmtChk->bind_param('i', $app_id);
        $stmtChk->execute();
        $r = $stmtChk->get_result()->fetch_assoc();
        $stmtChk->close();

        if ($r) {
            require_once 'ml_predictor.php';
            $predictor = new MLPredictor();
            $pred = $predictor->predictRisks($r['district'], $r['town']);

            $env = $pred['environmental_data'] ?? [];
            $risk = $pred['risk_assessment'] ?? [];

            // Extract all environmental values from ML predictions
            $elevation = isset($env['avg_elevation_m']) ? (int)$env['avg_elevation_m'] : null;
            $terrain = $env['terrain_type'] ?? null;
            $rainfall = isset($env['avg_rainfall_mm']) ? (int)$env['avg_rainfall_mm'] : null;
            $waterTable = $env['water_table_depth'] ?? null;
            $soil = $env['soil_type'] ?? null;
            $slope = $env['slope_category'] ?? null;
            $landUse = $env['land_use_type'] ?? null;
            $forest = isset($env['forest_cover_percent']) ? (int)$env['forest_cover_percent'] : null;

            // Extract risk predictions from ML model
            $floodRisk = $risk['flood_risk'] ?? 'Unknown';
            $landslideRisk = $risk['landslide_risk'] ?? 'Unknown';
            $overallRisk = $risk['overall_risk'] ?? 'Unknown';

            // Update ALL environmental fields AND predictions with ML model data
            $upd = $conn->prepare("UPDATE Construction_Details SET 
                average_elevation_m = ?, 
                terrain_type = ?, 
                average_rainfall_mm = ?, 
                water_table_depth = ?, 
                soil_type = ?, 
                slope_category = ?, 
                land_cover_type = ?, 
                forest_cover_percent = ?,
                predicted_flood_risk = ?,
                predicted_landslide_risk = ?,
                predicted_overall_risk = ?
            WHERE construction_id = ?");

            if ($upd) {
                $upd->bind_param('isdssssssssi', $elevation, $terrain, $rainfall, $waterTable, $soil, $slope, $landUse, $forest, $floodRisk, $landslideRisk, $overallRisk, $app_id);
                $upd->execute();
                $upd->close();
            }
        }

        // Fetch applicant email and name to send notification
        $applicantEmail = '';
        $applicantName = '';
        $stmtApp = $conn->prepare("SELECT u.email, u.name FROM Construction_Details cd JOIN User u ON cd.user_id = u.user_id WHERE cd.construction_id = ?");
        $stmtApp->bind_param('i', $app_id);
        $stmtApp->execute();
        $resApp = $stmtApp->get_result();
        if ($resApp && $resApp->num_rows > 0) {
            $rowApp = $resApp->fetch_assoc();
            $applicantEmail = $rowApp['email'];
            $applicantName = $rowApp['name'];
        }
        $stmtApp->close();

        // Insert verification record (Redundant risk columns removed)
        $stmt = $conn->prepare("INSERT INTO Site_Verification (construction_id, verified_by, verification_status, remarks, verification_date) VALUES (?, ?, ?, ?, CURDATE())");
        $stmt->bind_param("iiss", $app_id, $officer_id, $status, $remarks);
        $stmt->execute();
        $stmt->close();

        // Update application status
        $stmt = $conn->prepare("UPDATE Construction_Details SET application_status = ? WHERE construction_id = ?");
        $stmt->bind_param("si", $status, $app_id);
        $stmt->execute();
        $stmt->close();

        // Send email notification to the applicant
        if (!empty($applicantEmail)) {
            $displayStatus = ($status === 'Verified') ? 'Approved' : $status;
            $subject = "Construction Permit Application - " . $displayStatus;
            $message = "Dear $applicantName,\n\n";
            $message .= "We are writing to inform you that your construction permit application (ID: #" . str_pad($app_id, 5, '0', STR_PAD_LEFT) . ") has been reviewed.\n\n";
            $message .= "Application Status: $displayStatus\n";
            $message .= "Officer Remarks: $remarks\n\n";
            if ($status === 'Verified') {
                $message .= "Congratulations! Your application has been APPROVED. You may proceed with your construction activities as per the approved plan.\n";
            } else {
                $message .= "Unfortunately, your application has been REJECTED. Please review the officer's remarks and address the concerns before reapplying.\n";
            }
            $message .= "\nPlease log in to the Sovereign Structures portal to view the full details.\n\nRegards,\nSovereign Structures - Construction Permit Department";
            @mail($applicantEmail, $subject, $message, "From: noreply@sovereignstructures.in");
        }

        closeDBConnection($conn);

        $_SESSION['success'] = "Application $status successfully!";
        header('Location: verification.php');
        exit();
    }
}

// Load application data for display (GET ?id=...)
$app_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($app_id <= 0) {
    $_SESSION['error'] = 'Application not found';
    header('Location: verification.php');
    exit();
}

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT cd.*, u.name AS applicant_name, u.email AS applicant_email FROM Construction_Details cd LEFT JOIN User u ON cd.user_id = u.user_id WHERE cd.construction_id = ?");
$stmt->bind_param('i', $app_id);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows === 0) {
    $stmt->close();
    closeDBConnection($conn);
    $_SESSION['error'] = 'Application not found';
    header('Location: verification.php');
    exit();
}

$app = $result->fetch_assoc();
$stmt->close();

// ALWAYS populate ALL environmental fields AND predictions from ML model for accuracy
$conn2 = getDBConnection();
// Ensure all environmental columns exist
ensureColumnExists($conn2, 'Construction_Details', 'average_elevation_m', 'DOUBLE NULL');
ensureColumnExists($conn2, 'Construction_Details', 'terrain_type', 'VARCHAR(255) NULL');
ensureColumnExists($conn2, 'Construction_Details', 'average_rainfall_mm', 'DOUBLE NULL');
ensureColumnExists($conn2, 'Construction_Details', 'water_table_depth', 'VARCHAR(255) NULL');
ensureColumnExists($conn2, 'Construction_Details', 'soil_type', 'VARCHAR(255) NULL');
ensureColumnExists($conn2, 'Construction_Details', 'slope_category', 'VARCHAR(255) NULL');
ensureColumnExists($conn2, 'Construction_Details', 'land_cover_type', 'VARCHAR(255) NULL');
ensureColumnExists($conn2, 'Construction_Details', 'forest_cover_percent', 'DOUBLE NULL');
// Ensure prediction columns exist
ensureColumnExists($conn2, 'Construction_Details', 'predicted_flood_risk', 'VARCHAR(50) NULL');
ensureColumnExists($conn2, 'Construction_Details', 'predicted_landslide_risk', 'VARCHAR(50) NULL');
ensureColumnExists($conn2, 'Construction_Details', 'predicted_overall_risk', 'VARCHAR(50) NULL');

require_once 'ml_predictor.php';
$predictor = new MLPredictor();
$pred = $predictor->predictRisks($app['district'], $app['town']);

$env = $pred['environmental_data'] ?? [];
$risk = $pred['risk_assessment'] ?? [];

// Extract all environmental values from ML predictions
$elevation = isset($env['avg_elevation_m']) ? (int)$env['avg_elevation_m'] : null;
$terrain = $env['terrain_type'] ?? null;
$rainfall = isset($env['avg_rainfall_mm']) ? (int)$env['avg_rainfall_mm'] : null;
$waterTable = $env['water_table_depth'] ?? null;
$soil = $env['soil_type'] ?? null;
$slope = $env['slope_category'] ?? null;
$landUse = $env['land_use_type'] ?? null;
$forest = isset($env['forest_cover_percent']) ? (int)$env['forest_cover_percent'] : null;

// Extract risk predictions from ML model
$floodRisk = $risk['flood_risk'] ?? 'Unknown';
$landslideRisk = $risk['landslide_risk'] ?? 'Unknown';
$overallRisk = $risk['overall_risk'] ?? 'Unknown';

// Update ALL environmental fields AND predictions with ML model data
$upd = $conn2->prepare("UPDATE Construction_Details SET 
    average_elevation_m = ?, 
    terrain_type = ?, 
    average_rainfall_mm = ?, 
    water_table_depth = ?, 
    soil_type = ?, 
    slope_category = ?, 
    land_cover_type = ?, 
    forest_cover_percent = ?,
    predicted_flood_risk = ?,
    predicted_landslide_risk = ?,
    predicted_overall_risk = ?
WHERE construction_id = ?");

if ($upd) {
    $upd->bind_param('isdssssssssi', $elevation, $terrain, $rainfall, $waterTable, $soil, $slope, $landUse, $forest, $floodRisk, $landslideRisk, $overallRisk, $app['construction_id']);
    $upd->execute();
    $upd->close();
}

// Refresh $app values from DB to display all updated ML data
// Use the same JOIN to keep applicant information
$stmt2 = $conn2->prepare("SELECT cd.*, u.name AS applicant_name, u.email AS applicant_email FROM Construction_Details cd LEFT JOIN User u ON cd.user_id = u.user_id WHERE cd.construction_id = ?");
$stmt2->bind_param('i', $app['construction_id']);
$stmt2->execute();
$res2 = $stmt2->get_result();
if ($res2 && $res2->num_rows > 0) {
    $app = $res2->fetch_assoc();
}
$stmt2->close();
closeDBConnection($conn2);

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Application - Sovereign Structures</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {margin: 0; padding: 0; box-sizing: border-box;}
        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #0a0a0a, #232222);
            color: #fff;
            min-height: 100vh;
        }
        .navbar {
            background: rgba(27, 28, 28, 0.95);
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {width: 50px; height: 50px; border-radius: 50%;}
        .brand-title {font-size: 16px; font-weight: 700;}
        .btn-back {
            padding: 8px 20px;
            background: transparent;
            color: #fff;
            text-decoration: none;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px;
            transition: all 0.3s;
        }
        .btn-back:hover {background: rgba(255, 255, 255, 0.1);}
        .main-content {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 40px;
        }
        .page-header {margin-bottom: 30px;}
        .page-title {font-size: 32px; font-weight: 900; margin-bottom: 10px;}
        .content-grid {display: grid; grid-template-columns: 2fr 1fr; gap: 30px;}
        .section {
            background: rgba(31, 41, 55, 0.6);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 30px;
        }
        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .info-grid {display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;}
        .info-item {margin-bottom: 15px;}
        .info-label {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 16px;
            font-weight: 600;
        }
        .risk-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 700;
            display: inline-block;
        }
        .risk-high {background: rgba(239, 68, 68, 0.2); color: #fca5a5;}
        .risk-medium {background: rgba(245, 158, 11, 0.2); color: #fbbf24;}
        .risk-low {background: rgba(16, 185, 129, 0.2); color: #6ee7b7;}
        .form-group {margin-bottom: 20px;}
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .form-group textarea {
            width: 100%;
            padding: 12px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            color: #fff;
            font-family: inherit;
            min-height: 120px;
        }
        .form-group textarea:focus {
            outline: none;
            border-color: #10b981;
        }
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-approve {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
        }
        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }
        .btn-reject {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: #fff;
        }
        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
        }
        @media (max-width: 1024px) {
            .content-grid {grid-template-columns: 1fr;}
            .info-grid {grid-template-columns: 1fr;}
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div style="display: flex; align-items: center; gap: 15px;">
                <img src="Screenshot 2026-02-21 222153.png" class="logo">
                <div class="brand-title">SOVEREIGN STRUCTURES</div>
            </div>
            <a href="verification.php" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Verify Application #<?php echo str_pad($app['construction_id'], 5, '0', STR_PAD_LEFT); ?></h1>
        </div>

        <div class="content-grid">
            <div>
                <!-- Applicant Info -->
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-user"></i> Applicant Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Name</div>
                            <div class="info-value"><?php echo !empty($app['applicant_name']) ? htmlspecialchars($app['applicant_name']) : '-'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo !empty($app['applicant_email']) ? htmlspecialchars($app['applicant_email']) : '-'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Submission Date</div>
                            <div class="info-value"><?php echo date('d M Y', strtotime($app['application_date'])); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Location -->
                <div class="section" style="margin-top: 20px;">
                    <h2 class="section-title"><i class="fas fa-map-marker-alt"></i> Location</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">District</div>
                            <div class="info-value"><?php echo htmlspecialchars($app['district']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Town</div>
                            <div class="info-value"><?php echo htmlspecialchars($app['town']); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Environmental Data -->
                <div class="section" style="margin-top: 20px;">
                    <h2 class="section-title"><i class="fas fa-leaf"></i> Auto-Predicted Environmental Data</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Elevation</div>
                            <div class="info-value"><?php echo $app['average_elevation_m']; ?> m</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Terrain Type</div>
                            <div class="info-value"><?php echo htmlspecialchars($app['terrain_type']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Average Rainfall</div>
                            <div class="info-value"><?php echo $app['average_rainfall_mm']; ?> mm/year</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Water Table Depth</div>
                            <div class="info-value"><?php echo htmlspecialchars($app['water_table_depth']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Soil Type</div>
                            <div class="info-value"><?php echo htmlspecialchars($app['soil_type']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Slope</div>
                            <div class="info-value"><?php echo !empty($app['slope_category']) ? htmlspecialchars($app['slope_category']) : '-'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Land Cover</div>
                            <div class="info-value"><?php echo !empty($app['land_cover_type']) ? htmlspecialchars($app['land_cover_type']) : '-'; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Forest Cover</div>
                            <div class="info-value"><?php echo !empty($app['forest_cover_percent']) ? (htmlspecialchars($app['forest_cover_percent']) . '%') : '-'; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Verification Form -->
            <div>
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-exclamation-triangle"></i> Predicted Risks</h2>
                    <div style="margin-bottom: 20px;">
                        <div class="info-label">Flood Risk</div>
                        <span class="risk-badge risk-<?php echo strtolower($app['predicted_flood_risk']); ?>">
                            <?php echo $app['predicted_flood_risk']; ?>
                        </span>
                    </div>
                    <div style="margin-bottom: 20px;">
                        <div class="info-label">Landslide Risk</div>
                        <span class="risk-badge risk-<?php echo strtolower($app['predicted_landslide_risk']); ?>">
                            <?php echo $app['predicted_landslide_risk']; ?>
                        </span>
                    </div>
                    <div>
                        <div class="info-label">Overall Risk</div>
                        <span class="risk-badge risk-<?php echo strtolower($app['predicted_overall_risk']); ?>">
                            <?php echo $app['predicted_overall_risk']; ?>
                        </span>
                    </div>
                </div>

                <form method="POST" style="margin-top: 20px;">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($app['construction_id']); ?>">
                    <div class="section">
                        <h2 class="section-title"><i class="fas fa-clipboard-check"></i> Verification</h2>
                        
                        <div class="form-group">
                            <label>Officer Remarks *</label>
                            <textarea name="remarks" required placeholder="Enter your site verification remarks..."></textarea>
                        </div>

                        <div class="action-buttons">
                            <button type="submit" name="status" value="Verified" class="btn btn-approve">
                                <i class="fas fa-check-circle"></i> Approve
                            </button>
                            <button type="submit" name="status" value="Rejected" class="btn btn-reject">
                                <i class="fas fa-times-circle"></i> Reject
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
