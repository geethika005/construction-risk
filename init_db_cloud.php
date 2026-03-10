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
$conn->query("DROP VIEW IF EXISTS dashboard_stats");
$conn->query("DROP VIEW IF EXISTS pending_applications");
$conn->query("DROP VIEW IF EXISTS application_details");
$conn->query("DROP TABLE IF EXISTS Site_Verification");
$conn->query("DROP TABLE IF EXISTS Construction_Details");

$sql = "CREATE TABLE Construction_Details (
    construction_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    district VARCHAR(50) NOT NULL,
    town VARCHAR(50) NOT NULL,
    average_elevation_m DOUBLE,
    terrain_type VARCHAR(50),
    average_rainfall_mm DOUBLE,
    water_table_depth VARCHAR(50),
    soil_type VARCHAR(50),
    slope_category VARCHAR(50),
    forest_cover_percent DOUBLE,
    land_cover_type VARCHAR(100),
    predicted_flood_risk VARCHAR(50),
    predicted_landslide_risk VARCHAR(50),
    predicted_overall_risk VARCHAR(50),
    application_status ENUM('Submitted', 'Verified', 'Rejected') DEFAULT 'Submitted',
    application_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES User(user_id)
)";
if ($conn->query($sql)) echo "✅ Construction_Details table ready<br>";

// 3. Site_Verification Table
$sql = "CREATE TABLE Site_Verification (
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
    WHERE cd.application_status = 'Submitted'";
if ($conn->query($sql)) echo "✅ pending_applications view ready<br>";

$sql = "DROP VIEW IF EXISTS dashboard_stats";
$conn->query($sql);
$sql = "CREATE VIEW dashboard_stats AS
    SELECT 
        (SELECT COUNT(*) FROM Construction_Details WHERE application_status = 'Submitted') as pending_count,
        (SELECT COUNT(*) FROM Construction_Details WHERE application_status = 'Verified') as verified_count,
        (SELECT COUNT(*) FROM Construction_Details WHERE application_status = 'Rejected') as rejected_count,
        (SELECT COUNT(*) FROM User WHERE role = 'Officer' AND is_verified = 0) as pending_officers";
if ($conn->query($sql)) echo "✅ dashboard_stats view ready<br>";

// 6. Create or Update Initial Admin
$admin_email = 'jijithannickal@gmail.com';
$admin_pass = 'Ji@123456';

// Check if this specific email exists
$checkUser = $conn->query("SELECT * FROM User WHERE email = '$admin_email'");

if ($checkUser->num_rows > 0) {
    // User exists, force them to be a verified Admin
    $sql = "UPDATE User SET role = 'Admin', password = '$admin_pass', is_verified = 1 WHERE email = '$admin_email'";
    if ($conn->query($sql)) echo "✅ Account $admin_email updated to Admin status.<br>";
} else {
    // User doesn't exist, create new Admin
    $sql = "INSERT INTO User (name, email, password, role, is_verified) 
            VALUES ('Admin User', '$admin_email', '$admin_pass', 'Admin', 1)";
    if ($conn->query($sql)) echo "✅ New Admin created: $admin_email / $admin_pass<br>";
}

// Also ensure any existing generic "Admin" role is updated
$conn->query("UPDATE User SET is_verified = 1 WHERE role = 'Admin'");

echo "<br>🎉 **All Set!** Your cloud database is ready. <br>";
echo "<a href='index.php'>Go to Website</a>";

closeDBConnection($conn);
?>
