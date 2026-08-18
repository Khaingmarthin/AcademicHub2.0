<?php
/**
 * Student Timetable (protected).
 *
 * Placeholder page for the authenticated student's class timetable.
 * The full timetable view is scheduled for Day 7; this page only confirms
 * the student is authenticated and shows their classroom for context.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/student-auth.php';

student_require_login();

$pageTitle = 'My Timetable';

$ucsStudent = student_current_user();
$ucsDetails = null;

try {
    $ucsStmt = $pdo->prepare(
        "SELECT c.classroom_name, c.year_level,
                m.name AS major_name,
                ay.year_name AS academic_year
         FROM students s
         JOIN classrooms c ON c.id = s.classroom_id
         JOIN majors m ON m.id = c.major_id
         JOIN academic_years ay ON ay.id = c.academic_year_id
         WHERE s.id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsStudent['id']]);
    $ucsDetails = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsDetails = null;
}

require_once __DIR__ . '/../includes/header.php';
?>
<main class="flex-1 bg-slate-50">
    <section class="py-12 sm:py-16" aria-labelledby="student-timetable-heading">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600">Student Portal</p>
            <h1 id="student-timetable-heading" class="mt-2 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">My Timetable</h1>

            <?php if ($ucsDetails !== null): ?>
                <p class="mt-3 text-base leading-7 text-gray-600">
                    Timetable for <?php echo htmlspecialchars($ucsDetails['classroom_name']); ?> (<?php echo htmlspecialchars($ucsDetails['major_name']); ?>, <?php echo htmlspecialchars($ucsDetails['year_level']); ?>).
                </p>
            <?php endif; ?>

            <div class="mt-8 rounded-2xl bg-white px-6 py-12 text-center shadow-sm ring-1 ring-gray-100 sm:px-8">
                <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 ring-1 ring-blue-100" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 2v4M16 2v4M3 10h18"></path>
                        <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                    </svg>
                </span>
                <h2 class="mt-5 text-xl font-bold tracking-tight text-gray-900">Timetable Coming Soon</h2>
                <p class="mx-auto mt-3 max-w-md text-base leading-7 text-gray-500">
                    Your class timetable will be available here. Please check back soon.
                </p>
                <a href="<?php echo htmlspecialchars(BASE_URL . '/student-dashboard.php'); ?>" class="mt-7 inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 transition-colors hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </section>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>