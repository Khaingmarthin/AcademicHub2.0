<?php
// Main entry point for the public website
require_once '../config/app.php';
require_once '../includes/database.php';
require_once '../includes/header.php';
?>

<main class="flex-1">
    <?php include __DIR__ . '/sections/home-hero.php'; ?>
    <?php include __DIR__ . '/sections/home-stats.php'; ?>
    <?php include __DIR__ . '/sections/home-about.php'; ?>
    <?php include __DIR__ . '/sections/home-programmes.php'; ?>
    <?php include __DIR__ . '/sections/home-faculties.php'; ?>
    <?php include __DIR__ . '/sections/home-announcements.php'; ?>
    <?php include __DIR__ . '/sections/home-campus-life.php'; ?>
    <?php include __DIR__ . '/sections/home-student-life.php'; ?>
    <?php include __DIR__ . '/sections/home-admissions.php'; ?>
    <?php include __DIR__ . '/sections/home-location-contact.php'; ?>
</main>

<?php
require_once '../includes/footer.php';
?>
