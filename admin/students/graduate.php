<?php
/**
 * Admin Students module - graduation confirmation form.
 *
 * Shows the selected student's academic context and asks the admin to
 * confirm graduation with a graduation year. This is the official,
 * admin-controlled graduation decision; it never happens automatically.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/student-validation.php';

admin_require_login();

$pageTitle    = 'Confirm Graduation';
$pageSubtitle = 'Officially mark a student as graduated.';
$activeNav    = 'students';

$ucsId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

try {
    $ucsStmt = $pdo->prepare(
        "SELECT s.id, s.student_id, s.roll_number, s.name, s.email,
                s.status, s.student_status, s.graduation_year,
                c.classroom_name, c.year_level, c.section,
                COALESCE(m.name, '') AS major_name,
                ay.year_name AS academic_year, ay.end_date
         FROM students s
         JOIN classrooms c ON c.id = s.classroom_id
         LEFT JOIN majors m ON m.id = c.major_id
         JOIN academic_years ay ON ay.id = c.academic_year_id
         WHERE s.id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsStudent = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsStudent = null;
}

if ($ucsStudent === null || $ucsId === false || $ucsId < 1) {
    student_flash('error', 'Student not found.');
    header('Location: ' . ROOT_URL . '/admin/students/index.php');
    exit;
}

if ((int) $ucsStudent['status'] !== 1) {
    student_flash('error', 'Only active student accounts can be marked as graduated.');
    header('Location: ' . ROOT_URL . '/admin/students/index.php');
    exit;
}

if ($ucsStudent['student_status'] === 'graduated') {
    student_flash('error', 'This student is already marked as graduated.');
    header('Location: ' . ROOT_URL . '/admin/students/index.php');
    exit;
}

if (trim((string) $ucsStudent['year_level']) !== 'Fifth Year') {
    student_flash('error', 'Only Fifth Year students can be marked as graduated.');
    header('Location: ' . ROOT_URL . '/admin/students/index.php');
    exit;
}

$ucsErrors = $_SESSION['student_errors'] ?? [];
unset($_SESSION['student_errors']);

$ucsOld = $_SESSION['student_old'] ?? null;
unset($_SESSION['student_old']);

// Default graduation year: end year of the student's academic year if
// known, otherwise the current calendar year.
$ucsDefaultYear = (int) date('Y');
if (!empty($ucsStudent['end_date'])) {
    $ucsEndYear = (int) substr((string) $ucsStudent['end_date'], 0, 4);
    if ($ucsEndYear >= 1950 && $ucsEndYear <= (int) date('Y') + 5) {
        $ucsDefaultYear = $ucsEndYear;
    }
}
$ucsFormYear = $ucsOld['graduation_year'] ?? $ucsDefaultYear;

$hidePageHeader = true;
require_once __DIR__ . '/../../includes/admin-layout-top.php';
?>
<!-- Page Header -->
<div class="mb-6 flex flex-col gap-1">
    <h1 class="text-2xl font-bold tracking-tight text-slate-900"><?php echo htmlspecialchars($pageTitle); ?></h1>
    <p class="text-sm text-slate-500"><?php echo htmlspecialchars($pageSubtitle); ?></p>
</div>

<?php if (!empty($ucsErrors)): ?>
    <div class="rounded-lg bg-red-50 border border-red-200 px-4 py-3" role="alert">
        <p class="text-sm font-semibold text-red-700">Please fix the following:</p>
        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-700">
            <?php foreach ($ucsErrors as $ucsError): ?>
                <li><?php echo htmlspecialchars($ucsError); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="mx-auto max-w-2xl">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-6 py-5">
            <h2 class="text-base font-semibold text-slate-900">Confirm Graduation</h2>
            <p class="mt-1 text-sm text-slate-500">This officially changes the student's academic status to <strong>Graduated</strong>. The student account and historical record remain intact.</p>
        </div>

        <!-- Student context -->
        <div class="border-b border-slate-100 px-6 py-5">
            <dl class="grid grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Name</dt>
                    <dd class="mt-1 text-sm font-semibold text-slate-900"><?php echo htmlspecialchars($ucsStudent['name']); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Student ID</dt>
                    <dd class="mt-1 text-sm text-slate-700"><?php echo htmlspecialchars($ucsStudent['student_id']); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Roll Number</dt>
                    <dd class="mt-1 text-sm text-slate-700"><?php echo htmlspecialchars($ucsStudent['roll_number']); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Major</dt>
                    <dd class="mt-1 text-sm text-slate-700"><?php echo htmlspecialchars($ucsStudent['major_name']); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Classroom</dt>
                    <dd class="mt-1 text-sm text-slate-700"><?php echo htmlspecialchars($ucsStudent['classroom_name'] . ($ucsStudent['academic_year'] !== '' && $ucsStudent['academic_year'] !== null ? ' — ' . $ucsStudent['academic_year'] : '')); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Academic Status</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">Active</span>
                    </dd>
                </div>
            </dl>
        </div>

        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/student-graduate.php'); ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
            <input type="hidden" name="student_id" value="<?php echo (int) $ucsStudent['id']; ?>">

            <div class="space-y-6 px-6 py-6">
                <div>
                    <label for="graduation_year" class="block text-sm font-medium text-slate-700">Graduation Year <span class="text-red-500">*</span></label>
                    <input type="number" id="graduation_year" name="graduation_year" value="<?php echo (int) $ucsFormYear; ?>" required min="1950" max="<?php echo (int) date('Y') + 5; ?>" inputmode="numeric"
                           class="mt-2 block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    <p class="mt-1.5 text-xs text-slate-500">The academic year in which this student completed the required programme.</p>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/students/index.php'); ?>" class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition-colors duration-150 hover:bg-slate-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-400">
                    Cancel
                </a>
                <button type="submit" onclick="return confirm('Confirm graduation for <?php echo htmlspecialchars(str_replace(['\\', "'"], ['\\\\', "\\'"], $ucsStudent['name'])); ?>? The student will be marked as Graduated.');"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Confirm Graduation
                </button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>