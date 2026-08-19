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