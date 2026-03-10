<?php
require_once 'config.php';

// This script initializes the database tables on the cloud server
// FOR SECURITY: You should delete this file after running it once!

$conn = getDBConnection();

echo "<h2>Database Initialization</h2>";

// 1. User Table
$sql = "CREATE TABLE IF NOT EXISTS User (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Officer', 'Applicant') NOT NULL,
    spark_pen VARCHAR(20) DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
if ($conn->query($sql)) echo "✅ User table ready<br>";

// 2. Construction_Details Table
$sql = "CREATE TABLE IF NOT EXISTS Construction_Details (
    construction_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    district VARCHAR(50) NOT NULL,
    town VARCHAR(50) NOT NULL,
    avg_elevation_m DOUBLE,
    terrain_type VARCHAR(50),
    avg_rainfall_mm DOUBLE,
    water_table_depth VARCHAR(50),
    soil_type VARCHAR(50),
    slope_category VARCHAR(50),
    forest_cover_percent DOUBLE,
    land_use_type VARCHAR(100),
    predicted_flood_risk VARCHAR(50),
    predicted_landslide_risk VARCHAR(50),
    predicted_overall_risk VARCHAR(50),
    status ENUM('Pending', 'Verified', 'Rejected') DEFAULT 'Pending',
    submission_date DATE,
    FOREIGN KEY (user_id) REFERENCES User(user_id)
)";
if ($conn->query($sql)) echo "✅ Construction_Details table ready<br>";

// 3. Site_Verification Table
$sql = "CREATE TABLE IF NOT EXISTS Site_Verification (
    verification_id INT AUTO_INCREMENT PRIMARY KEY,
    construction_id INT NOT NULL,
    verified_by INT NOT NULL,
    verification_status ENUM('Verified', 'Rejected') NOT NULL,
    remarks TEXT,
    verification_date DATE,
    FOREIGN KEY (construction_id) REFERENCES Construction_Details(construction_id),
    FOREIGN KEY (verified_by) REFERENCES User(user_id)
)";
if ($conn->query($sql)) echo "✅ Site_Verification table ready<br>";

// 4. Notifications Table
$sql = "CREATE TABLE IF NOT EXISTS Notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES User(user_id)
)";
if ($conn->query($sql)) echo "✅ Notifications table ready<br>";

// 5. Create Views
$sql = "DROP VIEW IF EXISTS application_details";
$conn->query($sql);
$sql = "CREATE VIEW application_details AS
    SELECT 
        cd.*, 
        u.name as applicant_name, 
        u.email as applicant_email,
        sv.remarks, 
        sv.verification_date,
        v_u.name as verified_by_name
    FROM Construction_Details cd
    JOIN User u ON cd.user_id = u.user_id
    LEFT JOIN Site_Verification sv ON cd.construction_id = sv.construction_id
    LEFT JOIN User v_u ON sv.verified_by = v_u.user_id";
if ($conn->query($sql)) echo "✅ application_details view ready<br>";

$sql = "DROP VIEW IF EXISTS pending_applications";
$conn->query($sql);
$sql = "CREATE VIEW pending_applications AS
    SELECT cd.*, u.name as applicant_name
    FROM Construction_Details cd
    JOIN User u ON cd.user_id = u.user_id
    WHERE cd.status = 'Pending'";
if ($conn->query($sql)) echo "✅ pending_applications view ready<br>";

$sql = "DROP VIEW IF EXISTS dashboard_stats";
$conn->query($sql);
$sql = "CREATE VIEW dashboard_stats AS
    SELECT 
        (SELECT COUNT(*) FROM Construction_Details WHERE status = 'Pending') as pending_count,
        (SELECT COUNT(*) FROM Construction_Details WHERE status = 'Verified') as verified_count,
        (SELECT COUNT(*) FROM Construction_Details WHERE status = 'Rejected') as rejected_count,
        (SELECT COUNT(*) FROM User WHERE role = 'Officer' AND is_verified = 0) as pending_officers";
if ($conn->query($sql)) echo "✅ dashboard_stats view ready<br>";

// 6. Create or Update Initial Admin
$admin_email = 'jijithannickal@gmail.com';
$admin_pass = 'Ji@123456';

$checkAdmin = $conn->query("SELECT * FROM User WHERE role = 'Admin' OR email = '$admin_email'");
if ($checkAdmin->num_rows == 0) {
    $sql = "INSERT INTO User (name, email, password, role, is_verified) 
            VALUES ('Admin User', '$admin_email', '$admin_pass', 'Admin', 1)";
    if ($conn->query($sql)) echo "✅ Default Admin created (User: $admin_email, Pass: $admin_pass)<br>";
} else {
    $sql = "UPDATE User SET email = '$admin_email', password = '$admin_pass', is_verified = 1 WHERE role = 'Admin' LIMIT 1";
    if ($conn->query($sql)) echo "✅ Admin credentials updated to: $admin_email / $admin_pass<br>";
}

echo "<br>🎉 **All Set!** Your cloud database is ready. <br>";
echo "<a href='index.php'>Go to Website</a>";

closeDBConnection($conn);
?>
