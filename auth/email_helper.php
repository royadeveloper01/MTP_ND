<?php
// auth/email_helper.php
// Email helper functions for password reset

/**
 * Send password reset email to user using PHPMailer
 * 
 * @param string $email User's email address
 * @param string $name User's first name
 * @param string $reset_link Password reset link
 * @return bool True if email was sent successfully, false otherwise
 */
function send_password_reset_email($email, $name, $reset_link) {
    // Check if PHPMailer is installed
    $vendor_autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($vendor_autoload)) {
        error_log("PHPMailer not installed. Please run 'composer install' in the project root.");
        return false;
    }
    
    // Load PHPMailer classes
    require_once $vendor_autoload;
    
    // --- Production-Ready Email Configuration ---
    // Load from environment variables. See .env.example for details.
    $smtp_host = getenv('SMTP_HOST');
    $smtp_port = getenv('SMTP_PORT') ?: 587;
    $smtp_username = getenv('SMTP_USER');
    $smtp_password = getenv('SMTP_PASS');
    $from_email = getenv('SMTP_FROM_EMAIL') ?: $smtp_username;
    $from_name = getenv('SMTP_FROM_NAME') ?: 'MTP Store';

    // Validate that essential environment variables are set
    if (empty($smtp_host) || empty($smtp_username) || empty($smtp_password)) {
        error_log('SMTP configuration is incomplete. Please set SMTP_HOST, SMTP_USER, and SMTP_PASS in your .env file.');
        // In a production environment, you wouldn't want to expose the reset link.
        return false;
    }

    $subject = 'Password Reset Request - ' . $from_name;

    // Load email CSS from a separate file for better organization.
    // It's injected into a <style> block because most email clients do not support external stylesheets.
    $email_css = '';
    $css_path = __DIR__ . '/../assets/css/email_template.css';
    if (file_exists($css_path)) {
        $email_css = file_get_contents($css_path);
    }
    
    // Email body HTML
    $email_body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <style>" . $email_css . "</style>
    </head>
    <body>
        <div class='container'>
            <h2>Password Reset Request</h2>
            <p>Hello " . htmlspecialchars($name) . ",</p>
            <p>You have requested to reset your password for your MTP Store account.</p>
            <p>Click the button below to reset your password:</p>
            <p><a href='" . htmlspecialchars($reset_link) . "' class='button'>Reset Password</a></p>
            <p>Or copy and paste this link into your browser:</p>
            <p style='word-break: break-all; color: #666;'>" . htmlspecialchars($reset_link) . "</p>
            <p><strong>This link will expire in 1 hour.</strong></p>
            <p>If you did not request this password reset, please ignore this email.</p>
            <div class='footer'>
                <p>Best regards,<br>MTP Store Team</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_username;
        $mail->Password   = $smtp_password; // This will now use the value from getenv()
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtp_port;
        $mail->CharSet    = 'UTF-8';
        
        // Enable verbose debug output (optional, for testing). Set SMTP_DEBUG=1 in env to enable.
        if (getenv('SMTP_DEBUG') === '1') {
            $mail->SMTPDebug  = 2;
            $mail->Debugoutput = 'error_log';
        }
        
        // Recipients
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($email, $name);
        $mail->addReplyTo($from_email, $from_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $email_body;
        
        // Plain text alternative
        $mail->AltBody = "Hello $name,\n\nYou have requested to reset your password for your MTP Store account.\n\nClick this link to reset your password:\n$reset_link\n\nThis link will expire in 1 hour.\n\nIf you did not request this password reset, please ignore this email.\n\nBest regards,\nMTP Store Team";
        
        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        // Log error with context for debugging and monitoring
        error_log("Failed to send email to $email: " . $mail->ErrorInfo);
        return false;
    }
}
