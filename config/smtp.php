<?php
/**
 * SMTP Configuration for PHPMailer.
 *
 * Configure your SMTP server details below. This file is used by the
 * email-service to send emails via SMTP instead of PHP's mail().
 *
 * Gmail SMTP settings:
 *   Host:     smtp.gmail.com
 *   Port:     587 (TLS) or 465 (SSL)
 *   Username: your-email@gmail.com
 *   Password: your-app-password (NOT your regular Gmail password)
 *   Encryption: tls
 *
 * NOTE: For Gmail, you must create an "App Password" at:
 *       https://myaccount.google.com/apppasswords
 *       (Enable 2-Factor Authentication first, then generate an app password)
 */

// SMTP Server Settings
define('SMTP_HOST', 'smtp.gmail.com');       // SMTP server host
define('SMTP_PORT', 587);                     // SMTP port (587 for TLS, 465 for SSL)
define('SMTP_USERNAME', 'khaingsan2022@gmail.com');                  // Your email address (e.g., your-email@gmail.com)
define('SMTP_PASSWORD', 'didsfuwpdrknnwmi');                  // Your app password (NOT your Gmail password)
define('SMTP_FROM_EMAIL', 'khaingsan2022@gmail.com');                // From email address
define('SMTP_FROM_NAME', 'Academic Hub'); // From name
define('SMTP_ENCRYPTION', 'tls');             // Encryption: 'tls' or 'ssl'
