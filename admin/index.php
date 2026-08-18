<?php
/**
 * Redirect the bare /admin/ URL to the admin dashboard.
 */
require_once __DIR__ . '/../config/app.php';

header('Location: ' . ROOT_URL . '/admin/dashboard.php');
exit;
