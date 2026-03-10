<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Get application ID
$app_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($app_id === 0) {
    header('Location: dashboard.php');
    exit();
}

// Get application details
$conn = getDBConnection();

// Check if user has permission to view this application
if ($user_role === 'Applicant') {
    $stmt = $conn->prepare("
        SELECT cd.*, u.name as applicant_name, u.email as applicant_email
        FROM Construction_Details cd
        JOIN User u ON cd.user_id = u.user_id
        WHERE cd.construction_id = ? AND cd.user_id = ?
    ");
    $stmt->bind_param("ii", $app_id, $user_id);
} else {
    // Officers and Admins can view all applications
    $stmt = $conn->prepare("
        SELECT cd.*, u.name as applicant_name, u.email as applicant_email
        FROM Construction_Details cd
        JOIN User u ON cd.user_id = u.user_id
        WHERE cd.construction_id = ?
    ");
    $stmt->bind_param("i", $app_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Application not found or access denied';
    header('Location: dashboard.php');
    exit();
}

$app = $result->fetch_assoc();
$stmt->close();

// Get verification details if exists
$stmt = $conn->prepare("
    SELECT sv.*, u.name as officer_name
    FROM Site_Verification sv
    JOIN User u ON sv.verified_by = u.user_id
    WHERE sv.construction_id = ?
    ORDER BY sv.verification_date DESC
    LIMIT 1
");
$stmt->bind_param("i", $app_id);
$stmt->execute();
$verification = $stmt->get_result()->fetch_assoc();
$stmt->close();

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details - Sovereign Structures</title>
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
        .page-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .page-title {font-size: 32px; font-weight: 900;}
        .status-badge {
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 700;
        }
        .status-submitted {background: rgba(59, 130, 246, 0.2); color: #60a5fa;}
        .status-verified {background: rgba(16, 185, 129, 0.2); color: #6ee7b7;}
        .status-rejected {background: rgba(239, 68, 68, 0.2); color: #fca5a5;}
        .content-grid {display: grid; grid-template-columns: 2fr 1fr; gap: 30px;}
        .section {
            background: rgba(31, 41, 55, 0.6);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
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
        .info-value {font-size: 16px; font-weight: 600;}
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
        .verification-box {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 20px;
            border-radius: 10px;
            margin-top: 15px;
        }
        .verification-box.rejected {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
        }
        .btn-report {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s;
        }
        .btn-report:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
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
            <a href="<?php echo $user_role === 'Applicant' ? 'dashboard.php' : ($user_role === 'Officer' ? 'verification.php' : 'admin_dashboard.php'); ?>" class="btn-back">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </nav>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Application #<?php echo str_pad($app['construction_id'], 5, '0', STR_PAD_LEFT); ?></h1>
            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $app['application_status'])); ?>">
                <?php echo ($user_role === 'Applicant' && $app['application_status'] === 'Verified') ? 'Approved' : $app['application_status']; ?>
            </span>
        </div>

        <div class="content-grid">
            <div>
                <!-- Applicant Info -->
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-user"></i> Applicant Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Name</div>
                            <div class="info-value"><?php echo htmlspecialchars($app['applicant_name']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email</div>
                            <div class="info-value"><?php echo htmlspecialchars($app['applicant_email']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Submission Date</div>
                            <div class="info-value"><?php echo date('d M Y', strtotime($app['application_date'])); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Location -->
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-map-marker-alt"></i> Location Details</h2>
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
                    <?php if ($app['application_status'] === 'Verified' || $app['application_status'] === 'Rejected'): ?>
                    <!-- Environmental Data -->
                    <div class="section">
                        <h2 class="section-title"><i class="fas fa-leaf"></i> Environmental Data</h2>
                
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Average Elevation</div>
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
                            <div class="info-label">Soil Type</div>
                            <div class="info-value"><?php echo htmlspecialchars($app['soil_type']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Slope</div>
                            <div class="info-value"><?php echo htmlspecialchars($app['slope_category']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Land Cover</div>
                            <div class="info-value"><?php echo htmlspecialchars($app['land_cover_type']); ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($verification): ?>
                <!-- Verification Details -->
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-clipboard-check"></i> Verification Details</h2>
                    <div class="verification-box <?php echo $verification['verification_status'] === 'Rejected' ? 'rejected' : ''; ?>">
                        <div style="margin-bottom: 15px;">
                            <div class="info-label">Verified By</div>
                            <div class="info-value"><?php echo htmlspecialchars($verification['officer_name']); ?></div>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div class="info-label">Verification Date</div>
                            <div class="info-value"><?php echo date('d M Y', strtotime($verification['verification_date'])); ?></div>
                        </div>
                        <div>
                            <div class="info-label">Officer Remarks</div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($verification['remarks'])); ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div>
                
               <!-- Risk Assessment -->
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-exclamation-triangle"></i> Risk Assessment</h2>
                    
                    <?php if ($app['application_status'] === 'Verified' || $app['application_status'] === 'Rejected'): ?>
                        <!-- Show risks only if verified or rejected -->
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
                    <?php else: ?>
                        <!-- Show pending message if still submitted -->
                        <div style="text-align: center; padding: 30px 20px; color: rgba(255,255,255,0.6);">
                            <i class="fas fa-hourglass-half" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i>
                            <p><strong>Risk Assessment Pending</strong></p>
                            <p style="font-size: 14px; margin-top: 10px;">Your application is under review. Risk assessment will be available after officer verification.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($app['application_status'] === 'Verified'): ?>
                <!-- View Report Button -->
                <a href="report.php?id=<?php echo $app['construction_id']; ?>" class="btn-report">
                    <i class="fas fa-file-pdf"></i> View Full Report
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
