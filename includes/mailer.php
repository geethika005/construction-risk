<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Sends an email using PHPMailer and SMTP.
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email content (HTML supported)
 * @param bool $isHtml Whether the body is HTML
 * @return bool True on success, False on failure
 */
function sendEmail($to, $subject, $body, $isHtml = true) {
    // Check if PHPMailer is installed (Cloud vs Local)
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    
    if (!file_exists($autoload)) {
        error_log("PHPMailer not found. Is composer installed? Falling back to mail().");
        return @mail($to, $subject, strip_tags($body), "From: " . SMTP_USER);
    }

    require_once $autoload;

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        // Use SMTPS for port 465, STARTTLS for others
        $mail->SMTPSecure = (SMTP_PORT == 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
