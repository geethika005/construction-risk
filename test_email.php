<?php
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if PHPMailer is installed
$autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    die("<h2>❌ Error: PHPMailer Not Found</h2><p>Please wait for the Render build to finish.</p>");
}

require_once $autoload;

$to = isset($_GET['to']) ? $_GET['to'] : SMTP_USER;

echo "<h2>SMTP Timeout & Encryption Diagnostic</h2>";

// DNS Check
$ip = gethostbyname('smtp.gmail.com');
echo "<strong>DNS Check:</strong> smtp.gmail.com resolved to: <code>$ip</code><br><br>";

if (empty(SMTP_USER) || empty(SMTP_PASS)) {
    echo "<div style='color: red;'>⚠️ CRITICAL: SMTP_USER or SMTP_PASS is empty in Render Env Vars.</div>";
}

echo "<h3>Database Connectivity Test</h3>";
echo "Testing connection to host: <code>" . DB_HOST . "</code>...<br>";
try {
    $testConn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
    if ($testConn->connect_error) {
        echo "<b style='color: red;'>❌ Database Connection Failed:</b> " . $testConn->connect_error . "<br>";
    } else {
        echo "<b style='color: green;'>✅ Database Connection Successful!</b><br>";
        $testConn->close();
    }
} catch (Exception $e) {
    echo "<b style='color: red;'>❌ Database Error:</b> " . $e->getMessage() . "<br>";
}

echo "<h3>SMTP Timeout & Encryption Diagnostic</h3>";

function tryPort($to, $port, $secureType) {
    echo "<h3>Testing Port $port (" . ($secureType ?: 'None') . ")...</h3>";
    echo "<pre style='background: #f4f4f4; padding: 10px; border: 1px solid #ddd; max-height: 250px; overflow: auto;'>";
    
    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = 2;
        $mail->Debugoutput = 'echo';
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = $secureType;
        $mail->Port       = $port;
        $mail->Timeout    = 30; // Increased timeout to 30s
        
        // Remove SMTPOptions to test default routing
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom(SMTP_USER, 'Sovereign Test');
        $mail->addAddress($to);
        $mail->Subject = "Sovereign Test - Port $port";
        $mail->Body    = "Test from port $port at " . date('Y-m-d H:i:s');

        $mail->send();
        echo "</pre>";
        echo "<b style='color: green;'>✅ Port $port Worked!</b><br>";
        return true;
    } catch (Exception $e) {
        echo "</pre>";
        echo "<b style='color: red;'>❌ Port $port Failed:</b> " . $mail->ErrorInfo . "<br>";
        return false;
    }
}

// Try 465 (SMTPS)
if (!tryPort($to, 465, PHPMailer::ENCRYPTION_SMTPS)) {
    // Then try 587 (STARTTLS)
    tryPort($to, 587, PHPMailer::ENCRYPTION_STARTTLS);
}

echo "<br><hr><br><a href='index.php'>Return Home</a>";
?>
