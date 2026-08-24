<?php
/**
 * Email sending service using PHP mail().
 *
 * Provides a simple wrapper around PHP's built-in mail() function with
 * proper MIME headers for HTML emails. Intended for lightweight notification
 * sending; not suitable for high-volume or mission-critical delivery.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

/**
 * Send an HTML email.
 *
 * @param string $to      Recipient email address.
 * @param string $subject Email subject line.
 * @param string $html    HTML body content.
 * @param string $text    Optional plain-text fallback.
 * @return bool True on success, false on failure.
 */
function ucs_send_email($to, $subject, $html, $text = '')
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
