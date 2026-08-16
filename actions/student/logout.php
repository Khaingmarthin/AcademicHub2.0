<?php
/**
 * Student Logout handler.
 *
 * Clears the student session data, destroys the session and returns to the
 * public website. Any later attempt to open a protected student page will be
 * redirected to Student Login again.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/student-auth.php';

student_logout();