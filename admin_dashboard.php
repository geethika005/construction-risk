<?php
require_once 'config.php';
requireLogin();
requireRole('Admin');

$admin_name = $_SESSION['user_name'];

// Get comprehensive statistics
$conn = getDBConnection();

// Overall stats
$overall = $conn->query("
    SELECT 
        COUNT(*) as total_applications,
        SUM(CASE WHEN application_status = 'Submitted' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN application_status = 'Verified' THEN 1 ELSE 0 END) as verified,
        SUM(CASE WHEN application_status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
        SUM(CASE WHEN predicted_overall_risk = 'High' THEN 1 ELSE 0 END) as high_risk,
        SUM(CASE WHEN predicted_overall_risk = 'Medium' THEN 1 ELSE 0 END) as medium_risk,
        SUM(CASE WHEN predicted_overall_risk = 'Low' THEN 1 ELSE 0 END) as low_risk
    FROM Construction_Details
")->fetch_assoc();

// User stats
$users = $conn->query("
    SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'Applicant' THEN 1 ELSE 0 END) as applicants,
        SUM(CASE WHEN role = 'Officer' THEN 1 ELSE 0 END) as officers,
        SUM(CASE WHEN role = 'Admin' THEN 1 ELSE 0 END) as admins
    FROM User
")->fetch_assoc();

// District-wise stats
$district_stats = $conn->query("
    SELECT 
        district,
        COUNT(*) as count,
        SUM(CASE WHEN predicted_overall_risk = 'High' THEN 1 ELSE 0 END) as high_risk_count
    FROM Construction_Details
    GROUP BY district
    ORDER BY count DESC
    LIMIT 10
");

// Recent applications
$recent = $conn->query("
    SELECT 
        cd.construction_id,
        cd.district,
        cd.town,
        cd.application_date,
        cd.predicted_overall_risk,
        cd.application_status,
        u.name as applicant_name
    FROM Construction_Details cd
    JOIN User u ON cd.user_id = u.user_id
    ORDER BY cd.application_date DESC
    LIMIT 10
");

// Pending officers
$pending_officers = $conn->query("
    SELECT user_id, name, email, spark_pen, created_at 
    FROM User 
    WHERE role = 'Officer' AND is_verified = 0
    ORDER BY created_at DESC
");

closeDBConnection($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sovereign Structures</title>
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
            backdrop-filter: blur(10px);
            padding: 20px 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        .nav-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 0 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-section {display: flex; align-items: center; gap: 20px;}
        .logo {width: 60px; height: 60px; border-radius: 50%;}
        .brand-title {font-size: 18px; font-weight: 700;}
        .brand-subtitle {font-size: 11px; color: rgba(255, 255, 255, 0.8);}
        .user-avatar {
            width: 45px; height: 45px; border-radius: 50%;
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; font-weight: 700;
        }
        .nav-right {display: flex; align-items: center; gap: 25px;}
        .user-info {display: flex; align-items: center; gap: 15px;}
        .btn-logout {
            padding: 10px 25px; background: transparent; color: #fff;
            text-decoration: none; border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 6px; font-weight: 600; transition: all 0.3s;
        }
        .btn-logout:hover {background: rgba(239, 68, 68, 0.2); border-color: #ef4444;}
        .main-content {max-width: 1600px; margin: 0 auto; padding: 40px;}
        .page-title {font-size: 36px; font-weight: 900; margin-bottom: 10px;}
        .page-subtitle {font-size: 16px; color: rgba(255, 255, 255, 0.7); margin-bottom: 40px;}
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
            border-color: #f59e0b;
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);
        }
        .stat-icon {
            width: 50px; height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 15px;
        }
        .icon-orange {background: linear-gradient(135deg, #f59e0b, #ea580c);}
        .icon-blue {background: linear-gradient(135deg, #3b82f6, #2563eb);}
        .icon-green {background: linear-gradient(135deg, #10b981, #059669);}
        .icon-red {background: linear-gradient(135deg, #ef4444, #dc2626);}
        .icon-purple {background: linear-gradient(135deg, #8b5cf6, #7c3aed);}
        .stat-value {font-size: 32px; font-weight: 900; margin-bottom: 5px;}
        .stat-label {font-size: 14px; color: rgba(255, 255, 255, 0.7);}
        .content-grid {display: grid; grid-template-columns: 2fr 1fr; gap: 30px;}
        .section {
            background: rgba(31, 41, 55, 0.6);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 30px;
        }
        .section-title {font-size: 20px; font-weight: 700; margin-bottom: 20px;}
        table {width: 100%; border-collapse: collapse;}
        th {
            padding: 12px; text-align: left; font-weight: 600;
            background: rgba(0, 0, 0, 0.3);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }
        td {padding: 12px; border-bottom: 1px solid rgba(255, 255, 255, 0.05);}
        .risk-badge {
            padding: 4px 10px; border-radius: 12px;
            font-size: 11px; font-weight: 600;
        }
        .risk-high {background: rgba(239, 68, 68, 0.2); color: #fca5a5;}
        .risk-medium {background: rgba(245, 158, 11, 0.2); color: #fbbf24;}
        .risk-low {background: rgba(16, 185, 129, 0.2); color: #6ee7b7;}
        .status-badge {
            padding: 4px 10px; border-radius: 12px;
            font-size: 11px; font-weight: 600;
        }
        .status-submitted {background: rgba(59, 130, 246, 0.2); color: #60a5fa;}
        .status-verified {background: rgba(16, 185, 129, 0.2); color: #6ee7b7;}
        .btn-approve:hover {
            background: rgba(16, 185, 129, 0.4);
        }
        .btn-reject {
            padding: 6px 12px;
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.5);
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.3s;
        }
        .btn-reject:hover {
            background: rgba(239, 68, 68, 0.4);
        }
        .pending-badge {
            background: #ef4444;
            color: #fff;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 800;
            margin-left: 8px;
            vertical-align: middle;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #6ee7b7;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
        }
        @media (max-width: 1200px) {.content-grid {grid-template-columns: 1fr;}}
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo-section">
                <img src="Screenshot 2026-02-21 222153.png" class="logo">
                <div>
                    <div class="brand-title">SOVEREIGN STRUCTURES</div>
                    <div class="brand-subtitle">Construction dept</div>
                </div>
            </div>
            <div class="nav-right">
                <div class="user-info">
                    <div class="user-avatar"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
                    <div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($admin_name); ?></div>
                        <div style="font-size: 12px; color: rgba(255,255,255,0.6);">Administrator</div>
                    </div>
                </div>
                <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="main-content">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-times-circle"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <h1 class="page-title">Admin Dashboard</h1>
        <p class="page-subtitle">System overview and analytics</p>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-orange"><i class="fas fa-file-alt"></i></div>
                <div class="stat-value"><?php echo $overall['total_applications']; ?></div>
                <div class="stat-label">Total Applications</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-blue"><i class="fas fa-clock"></i></div>
                <div class="stat-value"><?php echo $overall['pending']; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-green"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?php echo $overall['verified']; ?></div>
                <div class="stat-label">Verified</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-red"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-value"><?php echo $overall['high_risk']; ?></div>
                <div class="stat-label">High Risk</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-purple"><i class="fas fa-users"></i></div>
                <div class="stat-value"><?php echo $users['total_users']; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-blue"><i class="fas fa-user"></i></div>
                <div class="stat-value"><?php echo $users['applicants']; ?></div>
                <div class="stat-label">Applicants</div>
            </div>
            <div class="stat-card" style="position: relative;">
                <div class="stat-icon icon-green"><i class="fas fa-user-shield"></i></div>
                <div class="stat-value">
                    <?php echo $users['officers']; ?>
                    <?php if ($pending_officers && $pending_officers->num_rows > 0): ?>
                        <span class="pending-badge"><?php echo $pending_officers->num_rows; ?> PENDING</span>
                    <?php endif; ?>
                </div>
                <div class="stat-label">Officers</div>
            </div>
        </div>

        <div class="content-grid">
            <div class="section">
                <h2 class="section-title"><i class="fas fa-clock"></i> Recent Applications</h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Applicant</th>
                            <th>Location</th>
                            <th>Date</th>
                            <th>Risk</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($app = $recent->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo str_pad($app['construction_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo htmlspecialchars($app['applicant_name']); ?></td>
                                <td><?php echo htmlspecialchars($app['district']); ?></td>
                                <td><?php echo date('d M Y', strtotime($app['application_date'])); ?></td>
                                <td>
                                    <span class="risk-badge risk-<?php echo strtolower($app['predicted_overall_risk']); ?>">
                                        <?php echo $app['predicted_overall_risk']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($app['application_status']); ?>">
                                        <?php echo $app['application_status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if ($pending_officers && $pending_officers->num_rows > 0): ?>
            <div class="section" style="grid-column: 1 / -1;">
                <h2 class="section-title"><i class="fas fa-user-shield"></i> Pending Officer Verifications</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>SPARK PEN</th>
                            <th>Reg. Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($officer = $pending_officers->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($officer['name']); ?></td>
                                <td><?php echo htmlspecialchars($officer['email']); ?></td>
                                <td><strong><?php echo htmlspecialchars($officer['spark_pen']); ?></strong></td>
                                <td><?php echo date('d M Y', strtotime($officer['created_at'])); ?></td>
                                <td>
                                    <form method="POST" action="verify_officer.php" style="display:inline; margin-left: 5px;">
                                        <input type="hidden" name="officer_id" value="<?php echo $officer['user_id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn-approve">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="verify_officer.php" style="display:inline; margin-left: 5px;">
                                        <input type="hidden" name="officer_id" value="<?php echo $officer['user_id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn-reject">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <div class="section">
                <h2 class="section-title"><i class="fas fa-map-marker-alt"></i> District Stats</h2>
                <table>
                    <thead>
                        <tr>
                            <th>District</th>
                            <th>Total</th>
                            <th>High Risk</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($district = $district_stats->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($district['district']); ?></td>
                                <td><strong><?php echo $district['count']; ?></strong></td>
                                <td>
                                    <?php if ($district['high_risk_count'] > 0): ?>
                                        <span class="risk-badge risk-high"><?php echo $district['high_risk_count']; ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
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
    </script>
</body>
</html>
