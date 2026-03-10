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

// Get application details - REMOVED Risk_Assessment JOIN
$conn = getDBConnection();

if ($user_role === 'Applicant') {
    $stmt = $conn->prepare("
        SELECT cd.*, u.name as applicant_name
        FROM Construction_Details cd
        JOIN User u ON cd.user_id = u.user_id
        WHERE cd.construction_id = ? AND cd.user_id = ? AND cd.application_status = 'Verified'
    ");
    $stmt->bind_param("ii", $app_id, $user_id);
} else {
    $stmt = $conn->prepare("
        SELECT cd.*, u.name as applicant_name
        FROM Construction_Details cd
        JOIN User u ON cd.user_id = u.user_id
        WHERE cd.construction_id = ? AND cd.application_status = 'Verified'
    ");
    $stmt->bind_param("i", $app_id);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Report not found or not yet available';
    header('Location: dashboard.php');
    exit();
}

$data = $result->fetch_assoc();
$stmt->close();
closeDBConnection($conn);

// Generate recommendations based on risks
function getRecommendations($flood_risk, $landslide_risk, $overall_risk) {
    $recommendations = [];
    
    if ($overall_risk === 'High') {
        $recommendations[] = "⚠️ This site requires immediate attention and comprehensive mitigation measures.";
    }
    
    if ($flood_risk === 'High') {
        $recommendations[] = "Install proper drainage systems and elevate ground floor levels.";
        $recommendations[] = "Avoid construction in low-lying areas prone to waterlogging.";
        $recommendations[] = "Implement flood-resistant building materials and design.";
    } elseif ($flood_risk === 'Medium') {
        $recommendations[] = "Ensure adequate drainage and rainwater harvesting systems.";
        $recommendations[] = "Consider raising foundation levels above flood-prone areas.";
    }
    
    if ($landslide_risk === 'High') {
        $recommendations[] = "Conduct detailed geotechnical investigation before construction.";
        $recommendations[] = "Implement slope stabilization measures (retaining walls, terracing).";
        $recommendations[] = "Avoid construction on steep slopes (>15°).";
        $recommendations[] = "Install proper soil erosion control systems.";
    } elseif ($landslide_risk === 'Medium') {
        $recommendations[] = "Perform soil stability tests before construction.";
        $recommendations[] = "Plant deep-rooted vegetation to prevent soil erosion.";
    }
    
    if ($overall_risk === 'Low') {
        $recommendations[] = "✓ Site is suitable for construction with standard safety measures.";
        $recommendations[] = "Follow Kerala State Building Code guidelines.";
    }
    
    $recommendations[] = "Obtain necessary construction permits from local authorities.";
    $recommendations[] = "Consult with structural engineers for site-specific design.";
    
    return $recommendations;
}

$recommendations = getRecommendations(
    $data['predicted_flood_risk'], 
    $data['predicted_landslide_risk'], 
    $data['predicted_overall_risk']
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Risk Assessment Report - Sovereign Structures</title>
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {width: 50px; height: 50px; border-radius: 50%;}
        .btn-back, .btn-print {
            padding: 8px 20px;
            background: transparent;
            color: #fff;
            text-decoration: none;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px;
            transition: all 0.3s;
            margin-left: 10px;
        }
        .btn-back:hover, .btn-print:hover {background: rgba(255, 255, 255, 0.1);}
        .report-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 40px;
        }
        .report-card {
            background: rgba(31, 41, 55, 0.6);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 50px;
        }
        .report-header {
            text-align: center;
            padding-bottom: 30px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 40px;
        }
        .report-title {
            font-size: 32px;
            font-weight: 900;
            margin-bottom: 10px;
        }
        .report-subtitle {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.7);
        }
        .report-id {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            padding: 8px 20px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 15px;
            font-weight: 700;
        }
        .section {margin-bottom: 40px;}
        .section-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .info-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        .info-label {
            color: rgba(255, 255, 255, 0.6);
            font-weight: 600;
        }
        .info-value {font-weight: 600;}
        .risk-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin: 30px 0;
        }
        .risk-card {
            background: rgba(0, 0, 0, 0.3);
            padding: 25px;
            border-radius: 10px;
            text-align: center;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }
        .risk-card-title {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 10px;
        }
        .risk-badge {
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 16px;
            font-weight: 700;
            display: inline-block;
        }
        .risk-high {background: rgba(239, 68, 68, 0.2); color: #fca5a5;}
        .risk-medium {background: rgba(245, 158, 11, 0.2); color: #fbbf24;}
        .risk-low {background: rgba(16, 185, 129, 0.2); color: #6ee7b7;}
        .recommendations {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.3);
            padding: 30px;
            border-radius: 10px;
        }
        .recommendations ul {
            list-style: none;
            padding: 0;
        }
        .recommendations li {
            padding: 12px 0;
            padding-left: 30px;
            position: relative;
            line-height: 1.6;
        }
        .recommendations li::before {
            content: '▸';
            position: absolute;
            left: 10px;
            color: #60a5fa;
            font-weight: bold;
        }
        .footer-note {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
        }
        @media print {
            body {background: #fff; color: #000;}
            .navbar, .btn-back, .btn-print {display: none;}
            .report-card {border: 1px solid #ddd;}
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div style="display: flex; align-items: center; gap: 15px;">
                <img src="Screenshot 2026-02-21 222153.png" class="logo">
                <div style="font-weight: 700;">SOVEREIGN STRUCTURES</div>
            </div>
            <div>
                <a href="application_details.php?id=<?php echo $data['construction_id']; ?>" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <a href="javascript:window.print()" class="btn-print">
                    <i class="fas fa-print"></i> Print
                </a>
            </div>
        </div>
    </nav>

    <div class="report-container">
        <div class="report-card">
            <!-- Header -->
            <div class="report-header">
                <h1 class="report-title">Construction Risk Assessment Report</h1>
                <p class="report-subtitle">Sovereign Structures - Government of Kerala</p>
                <div class="report-id">
                    Application ID: #<?php echo str_pad($data['construction_id'], 5, '0', STR_PAD_LEFT); ?>
                </div>
            </div>

            <!-- Applicant Info -->
            <div class="section">
                <h2 class="section-title"><i class="fas fa-user"></i> Applicant Information</h2>
                <div class="info-row">
                    <div class="info-label">Name:</div>
                    <div class="info-value"><?php echo htmlspecialchars($data['applicant_name']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Application Date:</div>
                    <div class="info-value"><?php echo date('d F Y', strtotime($data['application_date'])); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Location:</div>
                    <div class="info-value"><?php echo htmlspecialchars($data['district']) . ', ' . htmlspecialchars($data['town']); ?></div>
                </div>
            </div>

            <!-- Risk Summary -->
            <div class="section">
                <h2 class="section-title"><i class="fas fa-exclamation-triangle"></i> Risk Assessment Summary</h2>
                <div class="risk-summary">
                    <div class="risk-card">
                        <div class="risk-card-title">Flood Risk</div>
                        <span class="risk-badge risk-<?php echo strtolower($data['predicted_flood_risk']); ?>">
                            <?php echo $data['predicted_flood_risk']; ?>
                        </span>
                    </div>
                    <div class="risk-card">
                        <div class="risk-card-title">Landslide Risk</div>
                        <span class="risk-badge risk-<?php echo strtolower($data['predicted_landslide_risk']); ?>">
                            <?php echo $data['predicted_landslide_risk']; ?>
                        </span>
                    </div>
                    <div class="risk-card">
                        <div class="risk-card-title">Overall Risk</div>
                        <span class="risk-badge risk-<?php echo strtolower($data['predicted_overall_risk']); ?>">
                            <?php echo $data['predicted_overall_risk']; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Environmental Data -->
            <div class="section">
                <h2 class="section-title"><i class="fas fa-leaf"></i> Site Environmental Data</h2>
                <div class="info-row">
                    <div class="info-label">Average Elevation:</div>
                    <div class="info-value"><?php echo $data['average_elevation_m']; ?> meters</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Terrain Type:</div>
                    <div class="info-value"><?php echo htmlspecialchars($data['terrain_type']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Average Rainfall:</div>
                    <div class="info-value"><?php echo $data['average_rainfall_mm']; ?> mm/year</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Soil Type:</div>
                    <div class="info-value"><?php echo htmlspecialchars($data['soil_type']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Slope Category:</div>
                    <div class="info-value"><?php echo htmlspecialchars($data['slope_category']); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Land Cover:</div>
                    <div class="info-value"><?php echo htmlspecialchars($data['land_cover_type']); ?></div>
                </div>
            </div>

            <!-- Recommendations -->
            <div class="section">
                <h2 class="section-title"><i class="fas fa-lightbulb"></i> Recommendations & Safety Measures</h2>
                <div class="recommendations">
                    <ul>
                        <?php foreach ($recommendations as $rec): ?>
                            <li><?php echo htmlspecialchars($rec); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="footer-note">
                <strong><i class="fas fa-info-circle"></i> Important Note:</strong> This assessment is based on machine learning predictions using geographical and environmental data. It is recommended to conduct a detailed site survey and consult with certified structural engineers before commencing construction. This report is valid for 6 months from the date of issue.
            </div>
        </div>
    </div>
</body>
</html>
