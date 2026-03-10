<?php
session_start();
require_once 'config.php';
requireLogin();
requireRole('Admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['officer_id']) && isset($_POST['action'])) {
    $officer_id = (int)$_POST['officer_id'];
    $action = $_POST['action'];
    
    if ($officer_id > 0) {
        $conn = getDBConnection();
        
        if ($action === 'approve') {
            // Get officer details for email
            $stmtOff = $conn->prepare("SELECT name, email FROM User WHERE user_id = ?");
            $stmtOff->bind_param("i", $officer_id);
            $stmtOff->execute();
            $resOff = $stmtOff->get_result();
            if ($rowOff = $resOff->fetch_assoc()) {
                $officerName = $rowOff['name'];
                $officerEmail = $rowOff['email'];
                
                // Send approval email
                $subject = "Your Officer Account has been Approved";
                $message = "Dear $officerName,\n\nCongratulations! Your registration as a Permit Officer has been approved by the administrator.\n\nYou can now log in to the dashboard using your credentials to begin reviewing construction applications.\n\nRegards,\nSovereign Structures Administration";
                @mail($officerEmail, $subject, $message, "From: noreply@sovereignstructures.in");
            }
            $stmtOff->close();

            // Update the officer to be verified
            $stmt = $conn->prepare("UPDATE User SET is_verified = 1 WHERE user_id = ? AND role = 'Officer'");
            $stmt->bind_param("i", $officer_id);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Officer approved successfully.";
            }
            $stmt->close();
        } else if ($action === 'reject') {
            // Get officer details for email
            $stmtOff = $conn->prepare("SELECT name, email FROM User WHERE user_id = ?");
            $stmtOff->bind_param("i", $officer_id);
            $stmtOff->execute();
            $resOff = $stmtOff->get_result();
            if ($rowOff = $resOff->fetch_assoc()) {
                $officerName = $rowOff['name'];
                $officerEmail = $rowOff['email'];
                
                // Send rejection email
                $subject = "Officer Registration Status Update";
                $message = "Dear $officerName,\n\nWe regret to inform you that your registration as a Permit Officer has been declined.\n\nIf you believe this is a mistake, please contact the system administrator.\n\nRegards,\nSovereign Structures Administration";
                @mail($officerEmail, $subject, $message, "From: noreply@sovereignstructures.in");
            }
            $stmtOff->close();

            // Mark officer as rejected (is_verified = -1)
            $stmt = $conn->prepare("UPDATE User SET is_verified = -1 WHERE user_id = ? AND role = 'Officer'");
            $stmt->bind_param("i", $officer_id);
            if ($stmt->execute()) {
                $_SESSION['error'] = "Officer registration rejected.";
            }
            $stmt->close();
        }
        
        closeDBConnection($conn);
    }
}

// Redirect back to admin dashboard
header("Location: admin_dashboard.php");
exit();
?>
