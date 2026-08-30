<?php
/**
 * Email sending service using PHPMailer with SMTP.
 *
 * Uses PHPMailer to send HTML emails via a configured SMTP server.
 * Falls back to PHP's built-in mail() if SMTP is not configured.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

// Load Composer autoloader if not already loaded.
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $autoloadPaths = [
        __DIR__ . '/../../vendor/autoload.php',
        dirname(__DIR__, 2) . '/vendor/autoload.php',
    ];
    $loaded = false;
    foreach ($autoloadPaths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $loaded = true;
            break;
        }
    }
    if (!$loaded) {
        error_log('[Email Service] Composer autoload not found. Emails will not be sent.');
    }
}

// Load SMTP config if it exists.
$smtpConfigFile = __DIR__ . '/../../config/smtp.php';
if (file_exists($smtpConfigFile)) {
    require_once $smtpConfigFile;
}

/**
 * Send an HTML email via SMTP (or fallback to mail()).
 *
 * @param string $to      Recipient email address.
 * @param string $subject Email subject line.
 * @param string $html    HTML body content.
 * @param string $text    Optional plain-text fallback.
 * @return bool True on success, false on failure.
 */
function ucs_send_email($to, $subject, $html, $text = '')
{
    // If PHPMailer class is not available, fall back to mail().
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return ucs_send_email_fallback($to, $subject, $html, $text);
    }

    // If SMTP is not configured, fall back to mail().
    if (!defined('SMTP_HOST') || SMTP_HOST === '' || !defined('SMTP_USERNAME') || SMTP_USERNAME === '') {
        return ucs_send_email_fallback($to, $subject, $html, $text);
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet = 'UTF-8';

        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;

        if (defined('SMTP_ENCRYPTION') && SMTP_ENCRYPTION === 'ssl') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 465;
        } else {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;
        }

        // Sender
        $fromEmail = defined('SMTP_FROM_EMAIL') && SMTP_FROM_EMAIL !== ''
            ? SMTP_FROM_EMAIL
            : SMTP_USERNAME;
        $fromName  = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : (APP_NAME ?? 'Academic Hub');

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = $text !== '' ? $text : strip_tags($html);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('[Email Service] PHPMailer error: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Fallback: Send email using PHP's built-in mail() function.
 *
 * @param string $to
 * @param string $subject
 * @param string $html
 * @param string $text
 * @return bool
 */
function ucs_send_email_fallback($to, $subject, $html, $text = '')
{
    if ($text === '') {
        $text = strip_tags($html);
    }

    $boundary  = md5(uniqid(time()));
    $fromEmail = 'noreply@' . parse_url(BASE_URL, PHP_URL_HOST);
    $fromName  = APP_NAME ?? 'Academic Hub';

    $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>\r\n";
    $headers .= "Reply-To: {$fromEmail}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $body  = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
    $body .= quoted_printable_encode($text) . "\r\n\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
    $body .= quoted_printable_encode($html) . "\r\n\r\n";
    $body .= "--{$boundary}--";

    return @mail($to, $subject, $body, $headers);
}
