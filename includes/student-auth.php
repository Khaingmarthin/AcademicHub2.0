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
 * @return bool
 */
function student_is_logged_in()
{
    return isset($_SESSION['student_id'])
        && $_SESSION['student_id'] !== ''
        && is_numeric($_SESSION['student_id']);
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