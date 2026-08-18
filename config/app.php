<?php
// Application Configuration

// Local standard timezone (Myanmar Standard Time, UTC+06:30)
date_default_timezone_set('Asia/Yangon');

define('APP_NAME', 'UCSMTLA Academic Hub');
define('BASE_URL', 'http://localhost/ucsmtlaAcademichub/pages'); // Adjust based on your setup

// Project root URL (parent of BASE_URL). Used for handlers in the actions/
// folder, which lives outside the public web root.
define('ROOT_URL', rtrim(dirname(BASE_URL), '/'));

// Upload Paths
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
?>
