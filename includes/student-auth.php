<?php
/**
 * Student authentication helpers.
 *
 * Provides reusable session checks for protected student pages (Dashboard,
 * Profile, Timetable). Only the authenticated student's id (and non-sensitive
 * display details) are stored in the session; passwords are never kept.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}

// The student session is always a browser-session cookie (lifetime 0) so it
// expires when the browser is closed, and it is restricted to HTTP-only,
// same-site delivery so a stale cookie from a previous student cannot be
// replayed from cross-site or script contexts.
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

// A student session is invalidated after this many seconds of inactivity so a
// session left behind by a previous student (for example one restored by the
// browser after a restart) can never present that student as authenticated.
if (!defined('UCS_STUDENT_SESSION_TIMEOUT')) {
    define('UCS_STUDENT_SESSION_TIMEOUT', 1800);
}

/**
 * Whether a student is currently authenticated.
 *
 * The authenticated state is determined solely by the server-side session.
 * A valid session must contain the authenticated student id (a positive
 * integer) together with the paired name/email fields written at login.
 * Partial or stale sessions are therefore never treated as authenticated,
 * so the public header renders the logged-out state for them.
 *
 * @return bool
 */
function student_is_logged_in()
{
    // A student session must carry a fresh activity marker written by the
    // login handler. Sessions without one (e.g. a previous student whose
    // session predates this marker) or sessions idle beyond the inactivity
    // window are treated as logged out and their identity is cleared, so a
    // stale session can never be presented as the current student on the
    // public site again.
    if (isset($_SESSION['student_id']) || isset($_SESSION['student_name'])) {
        $lastActivity = (int) ($_SESSION['student_last_activity'] ?? 0);
        if ($lastActivity <= 0 || (time() - $lastActivity) > UCS_STUDENT_SESSION_TIMEOUT) {
            unset(
                $_SESSION['student_id'],
                $_SESSION['student_name'],
                $_SESSION['student_email'],
                $_SESSION['student_last_activity']
            );
            return false;
        }
    }

    $studentId = filter_var($_SESSION['student_id'] ?? null, FILTER_VALIDATE_INT);

    if ($studentId !== false
        && $studentId > 0
        && isset($_SESSION['student_name'])
        && isset($_SESSION['student_email'])) {
        // Refresh the activity marker on every valid authenticated request.
        $_SESSION['student_last_activity'] = time();
        return true;
    }

    return false;
}

/**
 * Return the authenticated student's session details, or null when logged out.
 *
 * @return array{id:int,name:string,email:string}|null
 */
function student_current_user()
{
    if (!student_is_logged_in()) {
        return null;
    }

    return [
        'id'    => (int) $_SESSION['student_id'],
        'name'  => (string) ($_SESSION['student_name'] ?? ''),
        'email' => (string) ($_SESSION['student_email'] ?? ''),
    ];
}

/**
 * Protect a student page: redirect unauthenticated visitors to Student Login.
 *
 * @return void
 */
function student_require_login()
{
    if (!student_is_logged_in()) {
        header('Location: ' . BASE_URL . '/student-login.php');
        exit;
    }
}

/**
 * Return the CSRF token for the student area, generating one on first use.
 *
 * Used to protect state-changing student forms (e.g. alumni self-management)
 * from cross-site request forgery. The token lives in the session.
 *
 * @return string
 */
function student_csrf_token()
{
    if (empty($_SESSION['student_csrf_token']) || !is_string($_SESSION['student_csrf_token'])) {
        $_SESSION['student_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['student_csrf_token'];
}

/**
 * Verify a submitted CSRF token against the student session token.
 *
 * @param mixed $token
 * @return bool
 */
function student_csrf_verify($token)
{
    $stored = (string) ($_SESSION['student_csrf_token'] ?? '');
    return $stored !== '' && is_string($token) && $token !== '' && hash_equals($stored, $token);
}

/**
 * Destroy the student session and return to the Student Login page.
 *
 * @return void
 */
function student_logout()
{
    // Clear the "remember me" persistent cookie.
    if (isset($_COOKIE['student_remember'])) {
        student_clear_remember_cookie($_COOKIE['student_remember']);
    }

    unset(
        $_SESSION['student_id'],
        $_SESSION['student_name'],
        $_SESSION['student_email'],
        $_SESSION['student_login_email'],
        $_SESSION['student_login_errors']
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

    header('Location: ' . BASE_URL . '/student-login.php');
    exit;
}

/**
 * Create a persistent "remember me" cookie by storing a token in the
 * database and setting it in a cookie.
 *
 * @param int    $studentId
 * @param string $selector
 * @param string $validator
 * @return void
 */
function student_create_remember_token($studentId, $selector, $validator)
{
    global $pdo;

    $hashedValidator = hash('sha256', $validator);
    $expires = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 days

    $stmt = $pdo->prepare(
        "INSERT INTO password_reset_tokens (user_type, user_id, token, expires_at)
         VALUES ('student', :uid, :token, :expires)"
    );
    $stmt->execute([
        ':uid'     => $studentId,
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
function student_set_remember_cookie($selector, $validator)
{
    $value = $selector . ':' . $validator;
    setcookie('student_remember', $value, [
        'expires'  => time() + (30 * 24 * 60 * 60),
        'path'     => '/',
        'domain'   => '',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly'  => true,
        'samesite' => 'Lax',
    ]);
}

/**
 * Validate the "remember me" cookie and return the student id, or false.
 *
 * @return int|false
 */
function student_validate_remember_cookie()
{
    global $pdo;

    if (empty($_COOKIE['student_remember'])) {
        return false;
    }

    $parts = explode(':', $_COOKIE['student_remember'], 2);
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
             WHERE user_type = 'student' AND token = :token
             LIMIT 1"
        );
        $stmt->execute([':token' => $tokenValue]);
        $row = $stmt->fetch() ?: null;

        if ($row === null) {
            student_clear_remember_cookie($_COOKIE['student_remember']);
            return false;
        }

        if ((int) $row['used'] === 1 || strtotime($row['expires_at']) < time()) {
            student_clear_remember_cookie($_COOKIE['student_remember']);
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
function student_clear_remember_cookie($cookieValue)
{
    global $pdo;

    $parts = explode(':', $cookieValue, 2);
    if (count($parts) === 2) {
        $hashedValidator = hash('sha256', $parts[1]);
        $tokenValue      = $parts[0] . ':' . $hashedValidator;
        try {
            $stmt = $pdo->prepare(
                "DELETE FROM password_reset_tokens
                 WHERE user_type = 'student' AND token = :token"
            );
            $stmt->execute([':token' => $tokenValue]);
        } catch (PDOException $e) {
            // Silently ignore — the cookie will still be cleared.
        }
    }

    if (isset($_COOKIE['student_remember'])) {
        setcookie('student_remember', '', [
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
 * Create a password-reset token for a student.
 *
 * @param int    $studentId
 * @param string $token     The raw random token (hashed before storage).
 * @param int    $ttl       Lifetime in seconds (default 1 hour).
 * @return void
 */
function student_create_reset_token($studentId, $token, $ttl = 3600)
{
    global $pdo;

    $hashed  = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + $ttl);

    // Invalidate any previous unused tokens for this student.
    $pdo->prepare(
        "UPDATE password_reset_tokens
         SET used = 1
         WHERE user_type = 'student' AND user_id = :uid AND used = 0"
    )->execute([':uid' => $studentId]);

    $pdo->prepare(
        "INSERT INTO password_reset_tokens (user_type, user_id, token, expires_at)
         VALUES ('student', :uid, :token, :expires)"
    )->execute([
        ':uid'     => $studentId,
        ':token'   => $hashed,
        ':expires' => $expires,
    ]);
}

/**
 * Validate a password-reset token for a student and return the student id, or false.
 *
 * @param string $token
 * @return int|false
 */
function student_validate_reset_token($token)
{
    global $pdo;

    $hashed = hash('sha256', $token);

    try {
        $stmt = $pdo->prepare(
            "SELECT id, user_id, expires_at, used
             FROM password_reset_tokens
             WHERE user_type = 'student' AND token = :token
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
 * Mark a password-reset token as used for a student.
 *
 * @param string $token
 * @return void
 */
function student_mark_reset_token_used($token)
{
    global $pdo;

    $hashed = hash('sha256', $token);
    $pdo->prepare(
        "UPDATE password_reset_tokens SET used = 1
         WHERE user_type = 'student' AND token = :token"
    )->execute([':token' => $hashed]);
}