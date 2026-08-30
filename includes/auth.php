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
    // Clear the "remember me" persistent cookie.
    if (isset($_COOKIE['admin_remember'])) {
        admin_clear_remember_cookie($_COOKIE['admin_remember']);
    }

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

/**
 * Create a persistent "remember me" cookie by storing a token in the
 * database and setting it in a cookie.
 *
 * @param int    $adminId
 * @param string $selector
 * @param string $validator
 * @return void
 */
function admin_create_remember_token($adminId, $selector, $validator)
{
    global $pdo;

    $hashedValidator = hash('sha256', $validator);
    $expires = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 days

    $stmt = $pdo->prepare(
        "INSERT INTO password_reset_tokens (user_type, user_id, token, expires_at)
         VALUES ('admin', :uid, :token, :expires)"
    );
    $stmt->execute([
        ':uid'     => $adminId,
        ':token'   => $selector . ':' . $hashedValidator,
        ':expires' => $expires,
    ]);
}

/**
 * Set the "remember me" cookie in the browser.
 *
 * @param string $selector
 * @param string $validator
 * @return void
 */
function admin_set_remember_cookie($selector, $validator)
{
    $value = $selector . ':' . $validator;
    setcookie('admin_remember', $value, [
        'expires'  => time() + (30 * 24 * 60 * 60),
        'path'     => '/',
        'domain'   => '',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly'  => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Validate the "remember me" cookie and return the admin id, or false.
 *
 * @return int|false
 */
function admin_validate_remember_cookie()
{
    global $pdo;

    if (empty($_COOKIE['admin_remember'])) {
        return false;
    }

    $parts = explode(':', $_COOKIE['admin_remember'], 2);
    if (count($parts) !== 2) {
        return false;
    }

    $selector          = $parts[0];
    $validator         = $parts[1];
    $hashedValidator   = hash('sha256', $validator);
    $tokenValue        = $selector . ':' . $hashedValidator;

    try {
        $stmt = $pdo->prepare(
            "SELECT id, user_id, expires_at, used
             FROM password_reset_tokens
             WHERE user_type = 'admin' AND token = :token
             LIMIT 1"
        );
        $stmt->execute([':token' => $tokenValue]);
        $row = $stmt->fetch() ?: null;

        if ($row === null) {
            admin_clear_remember_cookie($_COOKIE['admin_remember']);
            return false;
        }

        if ((int) $row['used'] === 1 || strtotime($row['expires_at']) < time()) {
            admin_clear_remember_cookie($_COOKIE['admin_remember']);
            return false;
        }

        return (int) $row['user_id'];
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Remove a remember-me token from the database and clear the cookie.
 *
 * @param string $cookieValue
 * @return void
 */
function admin_clear_remember_cookie($cookieValue)
{
    global $pdo;

    $parts = explode(':', $cookieValue, 2);
    if (count($parts) === 2) {
        $hashedValidator = hash('sha256', $parts[1]);
        $tokenValue      = $parts[0] . ':' . $hashedValidator;
        try {
            $stmt = $pdo->prepare(
                "DELETE FROM password_reset_tokens
                 WHERE user_type = 'admin' AND token = :token"
            );
            $stmt->execute([':token' => $tokenValue]);
        } catch (PDOException $e) {
            // Silently ignore — the cookie will still be cleared.
        }
    }

    if (isset($_COOKIE['admin_remember'])) {
        setcookie('admin_remember', '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'domain'   => '',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly'  => true,
            'samesite' => 'Lax',
        ]);
    }
}

/**
 * Create a password-reset token for an admin.
 *
 * @param int    $adminId
 * @param string $token     The raw random token (hashed before storage).
 * @param int    $ttl       Lifetime in seconds (default 1 hour).
 * @return void
 */
function admin_create_reset_token($adminId, $token, $ttl = 3600)
{
    global $pdo;

    $hashed  = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + $ttl);

    // Invalidate any previous unused tokens for this admin.
    $pdo->prepare(
        "UPDATE password_reset_tokens
         SET used = 1
         WHERE user_type = 'admin' AND user_id = :uid AND used = 0"
    )->execute([':uid' => $adminId]);

    $pdo->prepare(
        "INSERT INTO password_reset_tokens (user_type, user_id, token, expires_at)
         VALUES ('admin', :uid, :token, :expires)"
    )->execute([
        ':uid'     => $adminId,
        ':token'   => $hashed,
        ':expires' => $expires,
    ]);
}

/**
 * Validate a password-reset token for an admin and return the admin id, or false.
 *
 * @param string $token
 * @return int|false
 */
function admin_validate_reset_token($token)
{
    global $pdo;

    $hashed = hash('sha256', $token);

    try {
        $stmt = $pdo->prepare(
            "SELECT id, user_id, expires_at, used
             FROM password_reset_tokens
             WHERE user_type = 'admin' AND token = :token
             LIMIT 1"
        );
        $stmt->execute([':token' => $hashed]);
        $row = $stmt->fetch() ?: null;

        if ($row === null || (int) $row['used'] === 1 || strtotime($row['expires_at']) < time()) {
            return false;
        }

        return (int) $row['user_id'];
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Mark a password-reset token as used for an admin.
 *
 * @param string $token
 * @return void
 */
function admin_mark_reset_token_used($token)
{
    global $pdo;

    $hashed = hash('sha256', $token);
    $pdo->prepare(
        "UPDATE password_reset_tokens SET used = 1
         WHERE user_type = 'admin' AND token = :token"
    )->execute([':token' => $hashed]);
}
