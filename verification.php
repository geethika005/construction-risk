<?php
require_once 'config.php';
requireLogin();
requireRole('Officer');

$officer_id = $_SESSION['user_id'];
$officer_name = $_SESSION['user_name'];

// Get pending applications
$conn = getDBConnection();
$result = $conn->query("
    SELECT 
        cd.*,
        u.name as applicant_name,
        u.email as applicant_email
    FROM Construction_Details cd
    JOIN User u ON cd.user_id = u.user_id
    WHERE cd.application_status = 'Submitted'
    ORDER BY cd.application_date ASC
");
$pending_applications = $result;

// Get statistics
$stats_result = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM Construction_Details WHERE application_status = 'Submitted') as pending,
        (SELECT COUNT(*) FROM Site_Verification WHERE verified_by = $officer_id) as verified_by_me,
        (SELECT COUNT(*) FROM Construction_Details WHERE application_status = 'Verified') as total_verified,
        (SELECT COUNT(*) FROM Construction_Details WHERE predicted_overall_risk = 'High' AND application_status = 'Submitted') as high_risk
");
$stats = $stats_result->fetch_assoc();

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Dashboard - Sovereign Structures</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a0a0a, #232222);
            color: #fff;
            min-height: 100vh;
        }

        /* Navigation */
        .navbar {
            background: rgba(27, 28, 28, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px 0;
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
            width: 60px;
            height: 60px;
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
        }

        .brand-subtitle {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.8);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981, #059669);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            font-size: 15px;
        }

        .user-role {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
        }

        .btn-logout {
            padding: 10px 25px;
            background: transparent;
            color: #fff;
            text-decoration: none;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: #ef4444;
        }

        /* Main Content */
        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px;
        }

        .page-header {
            margin-bottom: 40px;
        }

        .page-title {
            font-size: 36px;
            font-weight: 900;
            margin-bottom: 10px;
        }

        .page-subtitle {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(31, 41, 55, 0.6);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 25px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: #10b981;
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 15px;
        }

        .icon-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .icon-green { background: linear-gradient(135deg, #10b981, #059669); }
        .icon-purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .icon-red { background: linear-gradient(135deg, #ef4444, #dc2626); }

        .stat-value {
            font-size: 32px;
            font-weight: 900;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
        }

        /* Applications Section */
        .applications-section {
            background: rgba(31, 41, 55, 0.6);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 30px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .applications-table {
            width: 100%;
            border-collapse: collapse;
        }

        .applications-table thead {
            background: rgba(0, 0, 0, 0.3);
        }

        .applications-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.9);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .applications-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .applications-table tbody tr {
            transition: all 0.3s;
        }

        .applications-table tbody tr:hover {
            background: rgba(16, 185, 129, 0.1);
        }

        .risk-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .risk-low {
            background: rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
        }

        .risk-medium {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }

        .risk-high {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }

        .btn-verify {
            padding: 8px 20px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            text-decoration: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.5);
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .priority-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .priority-high {
            background: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
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
            <div class="nav-right">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($officer_name, 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($officer_name); ?></div>
                        <div class="user-role">Permit Officer</div>
                    </div>
                </div>
                <a href="logout.php" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Verification Dashboard</h1>
            <p class="page-subtitle">Review and verify construction risk assessment applications</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-blue">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['verified_by_me']; ?></div>
                <div class="stat-label">Verified by Me</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-purple">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_verified']; ?></div>
                <div class="stat-label">Total Verified</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-red">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['high_risk']; ?></div>
                <div class="stat-label">High Risk Pending</div>
            </div>
        </div>

        <!-- Pending Applications -->
        <div class="applications-section">
            <h2 class="section-title">
                <i class="fas fa-clipboard-list"></i> Pending Applications
            </h2>

            <?php if ($pending_applications->num_rows > 0): ?>
                <table class="applications-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Applicant</th>
                            <th>Location</th>
                            <th>Submitted</th>
                            <th>Risk Level</th>
                            <th>Priority</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($app = $pending_applications->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo str_pad($app['construction_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($app['applicant_name']); ?></strong><br>
                                    <small style="color: rgba(255,255,255,0.6);"><?php echo htmlspecialchars($app['applicant_email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($app['district']) . ', ' . htmlspecialchars($app['town']); ?></td>
                                <td><?php echo date('d M Y', strtotime($app['application_date'])); ?></td>
                                <td>
                                    <?php 
                                    $risk = $app['predicted_overall_risk'];
                                    $riskClass = strtolower($risk);
                                    ?>
                                    <span class="risk-badge risk-<?php echo $riskClass; ?>">
                                        <?php echo $risk; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($risk === 'High'): ?>
                                        <span class="priority-badge priority-high">
                                            <i class="fas fa-exclamation"></i> Urgent
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="verify_application.php?id=<?php echo $app['construction_id']; ?>" class="btn-verify">
                                        <i class="fas fa-clipboard-check"></i> Verify
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-double"></i>
                    <h3>All Caught Up!</h3>
                    <p>No pending applications to review at this time.</p>
                </div>
            <?php endif; ?>
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
    </script>
</body>
</html>
