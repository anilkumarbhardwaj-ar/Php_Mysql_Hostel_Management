<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mail.php';

function sendWelcomeEmail($name, $email, $password, $role) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, $name);
        
        // Content
        $mail->isHTML(true);
        $emailTemplate = getWelcomeEmailTemplate($name, $email, $password, $role);
        $mail->Subject = $emailTemplate['subject'];
        $mail->Body = $emailTemplate['body'];
        
        // Send email
        $mail->send();
        return ['success' => true, 'message' => 'Welcome email sent successfully'];
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        return ['success' => false, 'message' => 'Failed to send welcome email: ' . $mail->ErrorInfo];
    }
}

function sendPasswordResetEmail($name, $email, $resetLink) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = "Password Reset Request";
        
        $body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                .button { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Password Reset Request</h2>
                </div>
                <div class='content'>
                    <p>Dear " . htmlspecialchars($name) . ",</p>
                    <p>We received a request to reset your password. Click the button below to reset your password:</p>
                    <p style='text-align: center;'>
                        <a href='" . htmlspecialchars($resetLink) . "' class='button'>Reset Password</a>
                    </p>
                    <p>If you did not request this password reset, please ignore this email or contact support if you have concerns.</p>
                    <p>This password reset link will expire in 1 hour.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message, please do not reply.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->Body = $body;
        
        // Send email
        $mail->send();
        return ['success' => true, 'message' => 'Password reset email sent successfully'];
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        return ['success' => false, 'message' => 'Failed to send password reset email: ' . $mail->ErrorInfo];
    }
}
?> 