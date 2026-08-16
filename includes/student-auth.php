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

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
    $studentId = filter_var($_SESSION['student_id'] ?? null, FILTER_VALIDATE_INT);

    return $studentId !== false
        && $studentId > 0
        && isset($_SESSION['student_name'])
        && isset($_SESSION['student_email']);
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