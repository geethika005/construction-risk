<?php
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if PHPMailer is installed
$autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    die("<h2>❌ Error: PHPMailer Not Found</h2><p>Composer dependencies have not been installed on the server yet. Please wait for the Render build to finish or check the build logs for errors.</p>");
}

require_once $autoload;

$to = isset($_GET['to']) ? $_GET['to'] : SMTP_USER;

echo "<h2>Detailed SMTP Connectivity Test</h2>";

if (empty(SMTP_USER) || empty(SMTP_PASS)) {
    echo "<div style='color: red;'>⚠️ CRITICAL: SMTP_USER or SMTP_PASS is empty. Have you added them to Render Environment Variables?</div>";
}

echo "Attempting to send a test email to: <strong>$to</strong>...<br><br>";
echo "<strong>Debug Log:</strong><br><pre style='background: #f4f4f4; padding: 15px; border: 1px solid #ddd;'>";

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = 2; // Enable verbose debug output
    $mail->Debugoutput = 'echo';
    
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;

    // Recipients
    $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
    $mail->addAddress($to);

    // Content
    $mail->isHTML(true);
    $mail->Subject = "Sovereign Structures - Detailed SMTP Test";
    $mail->Body    = "<h3>Success!</h3><p>Your SMTP debug test was successful.</p>";

    $mail->send();
    echo "</pre>";
    echo "<div style='color: green; font-weight: bold; margin-top: 20px;'>✅ FINAL RESULT: Success! Email sent.</div>";
} catch (Exception $e) {
    echo "</pre>";
    echo "<div style='color: red; font-weight: bold; margin-top: 20px;'>❌ FINAL RESULT: Failed!</div>";
    echo "<p>Detailed Error: " . $mail->ErrorInfo . "</p>";
}

echo "<br><a href='index.php'>Return Home</a>";
?>
