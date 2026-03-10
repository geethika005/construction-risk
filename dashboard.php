<?php
require_once 'config.php';
requireLogin();
requireRole('Applicant');

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get user's applications
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT * FROM Construction_Details 
    WHERE user_id = ? 
    ORDER BY application_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$applications = $stmt->get_result();
$stmt->close();

// Get statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN application_status = 'Submitted' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN application_status = 'Verified' THEN 1 ELSE 0 END) as verified,
        SUM(CASE WHEN application_status = 'Rejected' THEN 1 ELSE 0 END) as rejected
    FROM Construction_Details 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();
closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sovereign Structures</title>
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
            background: linear-gradient(135deg, #3b82f6, #2563eb);
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
            border-color: #3b82f6;
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.3);
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
        .icon-yellow { background: linear-gradient(135deg, #f59e0b, #ea580c); }
        .icon-green { background: linear-gradient(135deg, #10b981, #059669); }
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

        /* Action Button */
        .action-section {
            margin-bottom: 40px;
        }

        .btn-primary {
            padding: 16px 40px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(59, 130, 246, 0.5);
        }

        /* Applications Table */
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
            background: rgba(59, 130, 246, 0.1);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-submitted {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .status-verified {
            background: rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
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

        .btn-view {
            padding: 8px 16px;
            background: transparent;
            color: #60a5fa;
            text-decoration: none;
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 6px;
            font-size: 13px;
            transition: all 0.3s;
        }

        .btn-view:hover {
            background: rgba(59, 130, 246, 0.2);
            border-color: #3b82f6;
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

            .applications-table {
                font-size: 14px;
            }

            .applications-table th,
            .applications-table td {
                padding: 10px;
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
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                        <div class="user-role">Applicant</div>
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
            <h1 class="page-title">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h1>
            <p class="page-subtitle">Manage your construction risk assessment applications</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-blue">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Applications</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-yellow">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-green">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['verified']; ?></div>
                <div class="stat-label">Approved</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-red">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-value"><?php echo $stats['rejected']; ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>

        <!-- New Application Button -->
        <div class="action-section">
            <a href="submit_application.php" class="btn-primary">
                <i class="fas fa-plus-circle"></i> Submit New Application
            </a>
        </div>

        <!-- Applications List -->
        <div class="applications-section">
            <h2 class="section-title">
                <i class="fas fa-list"></i> My Applications
            </h2>

            <?php if ($applications->num_rows > 0): ?>
                <table class="applications-table">
                    <thead>
                        <tr>
                            <th>Application ID</th>
                            <th>Location</th>
                            <th>Submission Date</th>
                            <th>Overall Risk</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($app = $applications->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo str_pad($app['construction_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($app['district']) . ', ' . htmlspecialchars($app['town']); ?></td>
                                <td><?php echo date('d M Y', strtotime($app['application_date'])); ?></td>
                                <td>
                                    <?php 
                                    if ($app['application_status'] === 'Verified' || $app['application_status'] === 'Rejected') {
                                        $risk = $app['predicted_overall_risk'];
                                        $riskClass = strtolower($risk);
                                        ?>
                                        <span class="risk-badge risk-<?php echo $riskClass; ?>">
                                            <?php echo $risk; ?>
                                        </span>
                                        <?php
                                    } else {
                                        // Status is "Submitted" - still pending
                                        ?>
                                        <span class="status-badge status-submitted">
                                            Pending Review
                                        </span>
                                        <?php
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $status = $app['application_status'];
                                    $displayStatus = $status === 'Verified' ? 'Approved' : $status;
                                    $statusClass = strtolower(str_replace(' ', '-', $status));
                                    ?>
                                    <span class="status-badge status-<?php echo $statusClass; ?>">
                                        <?php echo $displayStatus; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="application_details.php?id=<?php echo $app['construction_id']; ?>" class="btn-view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Applications Yet</h3>
                    <p>You haven't submitted any applications. Click the button above to get started!</p>
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
