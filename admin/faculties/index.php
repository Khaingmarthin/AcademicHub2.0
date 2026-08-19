<?php
/**
 * Admin Faculties - legacy index.
 *
 * Faculties and Departments are managed together in the combined
 * "Faculties & Departments" module. This page redirects there.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';

admin_require_login();

header('Location: ' . ROOT_URL . '/admin/faculties-departments/index.php');
exit;