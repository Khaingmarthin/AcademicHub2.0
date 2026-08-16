<?php
/**
 * Public Departments route.
 *
 * Departments are part of the unified Faculties & Departments module
 * (pages/faculties.php). This route redirects to that module and opens the
 * Departments tab directly, so there is a single public directory page and
 * no duplicated content.
 */
require_once '../config/app.php';

$ucsTarget = BASE_URL . '/faculties.php#departments';
header('Location: ' . $ucsTarget, true, 301);
exit;