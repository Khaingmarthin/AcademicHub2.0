<?php
/**
 * Admin Logout.
 *
 * Destroys the admin session, clears the authentication state and returns to
 * the Admin Login page. Any later attempt to open a protected admin page will
 * be redirected to Admin Login again.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';

admin_logout();
