<?php
require_once 'config.php';
require_once 'includes/mailer.php';

// This script helps you test if your Gmail App Password is working on Render.
// Access it via: https://your-site.onrender.com/test_email.php?to=your-email@example.com

$to = isset($_GET['to']) ? $_GET['to'] : SMTP_USER;

if (empty($to)) {
    die("Error: Please provide a recipient email in the URL, e.g., ?to=yourname@mail.com");
}

echo "<h2>Email Connectivity Test</h2>";
echo "Attempting to send a test email to: <strong>$to</strong>...<br><br>";

$subject = "Sovereign Structures - SMTP Test";
$body = "<h3>Success!</h3><p>If you are reading this, your Gmail SMTP connection is working perfectly on Render.com.</p>";

if (sendEmail($to, $subject, $body)) {
    echo "<div style='color: green; font-weight: bold;'>✅ SUCCESS: Email sent successfully!</div>";
    echo "<p>Check your inbox (and spam folder) for the test message.</p>";
} else {
    echo "<div style='color: red; font-weight: bold;'>❌ FAILED: Could not send email.</div>";
    echo "<p>Please check your Render Environment Variables (SMTP_USER and SMTP_PASS).</p>";
    echo "<p>Current SMTP User: " . SMTP_USER . "</p>";
}

echo "<br><a href='index.php'>Return Home</a>";
?>
