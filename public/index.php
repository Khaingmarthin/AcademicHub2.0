<?php
// Main entry point for the public website
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../includes/header.php';
?>

<main class="container mx-auto mt-8 px-4">
    <h1 class="text-4xl font-bold text-center text-blue-800">Welcome to UCSMTLA Academic Hub</h1>
    <p class="mt-4 text-center text-gray-600">The central hub for all university information, news, and academic resources.</p>
</main>

<?php
require_once '../includes/footer.php';
?>
