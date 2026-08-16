<?php
// Application Configuration

define('APP_NAME', 'UCSMTLA Academic Hub');
define('BASE_URL', 'http://localhost/ucsmtlaAcademichub/public'); // Adjust based on your setup

// Project root URL (parent of BASE_URL). Used for handlers in the actions/
// folder, which lives outside the public web root.
define('ROOT_URL', rtrim(dirname(BASE_URL), '/'));

// Upload Paths
define('UPLOAD_DIR', __DIR__ . '/../public/assets/uploads/');
?>
