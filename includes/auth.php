<?php
/**
 * Admin authentication helpers.
 *
 * Provides reusable session checks for protected admin pages (Dashboard,
 * Profile and all admin modules). Only the authenticated admin's id (and
 * non-sensitive display details) are stored in the session; passwords are
 * never kept.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}

// The admin session uses the same hardening as the student session: a
// browser-session cookie (lifetime 0), restricted to HTTP-only, same-site
// delivery so a stale cookie from a previous admin cannot be replayed from
// cross-site or script contexts.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => (string) (ini_get('session.cookie_path') ?: '/'),
        'domain'   => (string) (ini_get('session.cookie_domain') ?: ''),
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// An admin session is invalidated after this many seconds of inactivity.
if (!defined('ADMIN_SESSION_TIMEOUT')) {
    define('ADMIN_SESSION_TIMEOUT', 1800);
}

/**
 * Whether an admin is currently authenticated.
 *
 * @return bool
 */
function admin_is_logged_in()
{
    // Sessions without a fresh activity marker or idle beyond the inactivity
    // window are treated as logged out and their identity is cleared.
    if (isset($_SESSION['admin_id']) || isset($_SESSION['admin_name'])) {
        $lastActivity = (int) ($_SESSION['admin_last_activity'] ?? 0);
        if ($lastActivity <= 0 || (time() - $lastActivity) > ADMIN_SESSION_TIMEOUT) {
            unset(
                $_SESSION['admin_id'],
                $_SESSION['admin_name'],
                $_SESSION['admin_email'],
                $_SESSION['admin_last_activity']
            );
            return false;
        }
    }

    $adminId = filter_var($_SESSION['admin_id'] ?? null, FILTER_VALIDATE_INT);

    if ($adminId !== false
        && $adminId > 0
        && isset($_SESSION['admin_name'])
        && isset($_SESSION['admin_email'])) {
        // Refresh the activity marker on every valid authenticated request.
        $_SESSION['admin_last_activity'] = time();
        return true;
    }

    return false;
}

/**
 * Return the authenticated admin's session details, or null when logged out.
 *
 * @return array{id:int,name:string,email:string}|null
 */
function admin_current_user()
{
    if (!admin_is_logged_in()) {
        return null;
    }

    return [
        'id'    => (int) $_SESSION['admin_id'],
        'name'  => (string) ($_SESSION['admin_name'] ?? ''),
        'email' => (string) ($_SESSION['admin_email'] ?? ''),
    ];
}

/**
 * Protect an admin page: redirect unauthenticated visitors to Admin Login.
 *
 * @return void
 */
function admin_require_login()
{
    if (!admin_is_logged_in()) {
        header('Location: ' . ROOT_URL . '/admin/login.php');
        exit;
    }
}

/**
 * Return the CSRF token for the admin area, generating one on first use.
 *
 * The token lives in the session so it survives the login redirect, and it
 * is used to protect state-changing forms (e.g. the login form) from
 * cross-site request forgery.
 *
 * @return string
 */
function admin_csrf_token()
{
    if (empty($_SESSION['admin_csrf_token']) || !is_string($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['admin_csrf_token'];
}

/**
 * Verify a submitted CSRF token against the session token.
 *
 * @param mixed $token
 * @return bool
 */
function admin_csrf_verify($token)
{
    $stored = (string) ($_SESSION['admin_csrf_token'] ?? '');
    return $stored !== '' && is_string($token) && $token !== '' && hash_equals($stored, $token);
}

/**
 * Destroy the admin session and return to the Admin Login page.
 *
 * @return void
 */
function admin_logout()
{
    unset(
        $_SESSION['admin_id'],
        $_SESSION['admin_name'],
        $_SESSION['admin_email'],
        $_SESSION['admin_login_email'],
        $_SESSION['admin_login_errors']
    );

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $ucsParams = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $ucsParams['path'],
            $ucsParams['domain'],
            $ucsParams['secure'],
            $ucsParams['httponly']
        );
    }

    session_destroy();

    header('Location: ' . ROOT_URL . '/admin/login.php');
    exit;
}
