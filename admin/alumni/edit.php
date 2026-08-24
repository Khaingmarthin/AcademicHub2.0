<?php
/**
 * Admin Alumni module - edit profile.
 *
 * Edits alumni-specific profile fields only. Student identity, academic
 * status and verification status are managed by their own dedicated
 * workflows and are never edited here.
 */
require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers/alumni-validation.php';

admin_require_login();

$pageTitle    = 'Edit Alumni Profile';
$pageSubtitle = 'Update an alumni profile.';
$activeNav    = 'alumni';

$ucsId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

$ucsProfile = null;
try {
    $ucsStmt = $pdo->prepare(
        "SELECT ap.id, ap.current_job, ap.company, ap.professional_field,
                ap.skills, ap.bio, ap.career_journey,
                ap.linkedin_url, ap.github_url, ap.website_url,
                ap.verification_status,
                s.name AS student_name, s.student_id AS student_code,
                s.roll_number, s.graduation_year,
                m.name AS major_name
         FROM alumni_profiles ap
         JOIN students s ON s.id = ap.student_id
         JOIN classrooms cl ON cl.id = s.classroom_id
         JOIN majors m ON m.id = cl.major_id
         WHERE ap.id = :id
         LIMIT 1"
    );
    $ucsStmt->execute([':id' => $ucsId]);
    $ucsProfile = $ucsStmt->fetch() ?: null;
} catch (PDOException $e) {
    $ucsProfile = null;
}

if ($ucsProfile === null || $ucsId === false || $ucsId < 1) {
    alumni_flash('error', 'Alumni Profile not found.');
    header('Location: ' . ROOT_URL . '/admin/alumni/index.php');
    exit;
}

$ucsErrors = $_SESSION['alumni_errors'] ?? [];
unset($_SESSION['alumni_errors']);

$ucsOld = $_SESSION['alumni_old'] ?? null;
unset($_SESSION['alumni_old']);

$ucsForm = [
    'current_job'          => $ucsOld['current_job'] ?? (string) $ucsProfile['current_job'],
    'company'              => $ucsOld['company'] ?? (string) $ucsProfile['company'],
    'professional_field'   => $ucsOld['professional_field'] ?? (string) $ucsProfile['professional_field'],
    'skills'               => $ucsOld['skills'] ?? (string) $ucsProfile['skills'],
    'bio'                  => $ucsOld['bio'] ?? (string) $ucsProfile['bio'],
    'career_journey'       => $ucsOld['career_journey'] ?? (string) $ucsProfile['career_journey'],
    'linkedin_url'         => $ucsOld['linkedin_url'] ?? (string) $ucsProfile['linkedin_url'],
    'github_url'           => $ucsOld['github_url'] ?? (string) $ucsProfile['github_url'],
    'website_url'          => $ucsOld['website_url'] ?? (string) $ucsProfile['website_url'],
    'verification_status'  => $ucsOld['verification_status'] ?? (string) $ucsProfile['verification_status'],
];

require_once __DIR__ . '/../../includes/admin-layout-top.php';
?>
<?php if (!empty($ucsErrors)): ?>
    <div class="rounded-xl bg-red-50 px-4 py-3 ring-1 ring-red-100" role="alert">
        <p class="text-sm font-semibold text-red-700">Please fix the following:</p>
        <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-700">
            <?php foreach ($ucsErrors as $ucsError): ?>
                <li><?php echo htmlspecialchars($ucsError); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="mx-auto max-w-2xl">
    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="border-b border-gray-100 px-6 py-5">
            <h2 class="text-base font-semibold text-gray-900">Edit Alumni Profile</h2>
            <p class="mt-1 text-sm text-gray-500">Editing <span class="font-semibold text-gray-700"><?php echo htmlspecialchars((string) $ucsProfile['student_name']); ?></span> (<?php echo htmlspecialchars((string) $ucsProfile['roll_number']); ?>) &middot; <?php echo htmlspecialchars((string) $ucsProfile['major_name']); ?> &middot; Class of <?php echo htmlspecialchars((string) $ucsProfile['graduation_year']); ?>.</p>
        </div>

        <form method="post" action="<?php echo htmlspecialchars(ROOT_URL . '/actions/admin/alumni-update.php'); ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo (int) $ucsProfile['id']; ?>">

            <div class="space-y-6 px-6 py-6">
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="current_job" class="block text-sm font-medium text-gray-700">Current Job <span class="text-gray-400">(optional)</span></label>
                        <select id="current_job" name="current_job"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="">— Select job title —</option>
                            <?php
                            $ucsJobs = [
                                'Software Engineer',
                                'Frontend Developer',
                                'Backend Developer',
                                'Full Stack Developer',
                                'Mobile Developer',
                                'Data Analyst',
                                'Data Scientist',
                                'DevOps Engineer',
                                'Cloud Engineer',
                                'System Administrator',
                                'Network Engineer',
                                'Database Administrator',
                                'Cybersecurity Analyst',
                                'AI/ML Engineer',
                                'QA Engineer',
                                'UI/UX Designer',
                                'Product Manager',
                                'Project Manager',
                                'Scrum Master',
                                'Business Analyst',
                                'IT Consultant',
                                'Technical Writer',
                                'CTO',
                                'CEO',
                                'Founder',
                                'Teacher/Lecturer',
                                'Researcher',
                                'Freelancer',
                                'Other',
                            ];
                            foreach ($ucsJobs as $ucsJob): ?>
                                <option value="<?php echo htmlspecialchars($ucsJob); ?>" <?php echo $ucsForm['current_job'] === $ucsJob ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ucsJob); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($ucsForm['current_job']) && !in_array($ucsForm['current_job'], $ucsJobs, true)): ?>
                            <p class="mt-1.5 text-xs text-gray-500">Current: <span class="font-medium text-gray-700"><?php echo htmlspecialchars($ucsForm['current_job']); ?></span> (not in the list above)</p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <label for="company" class="block text-sm font-medium text-gray-700">Company / Organization <span class="text-gray-400">(optional)</span></label>
                        <input type="text" id="company" name="company" value="<?php echo htmlspecialchars($ucsForm['company']); ?>" maxlength="255" placeholder="e.g. Acme Ltd"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                </div>

                <div>
                    <label for="professional_field" class="block text-sm font-medium text-gray-700">Professional Field <span class="text-gray-400">(optional)</span></label>
                    <select id="professional_field" name="professional_field"
                            class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                        <option value="">— Select field —</option>
                        <?php
                        $ucsFields = [
                            'Information Technology',
                            'Software Engineering',
                            'Web Development',
                            'Data Science',
                            'Cybersecurity',
                            'Artificial Intelligence',
                            'Network Engineering',
                            'Systems Administration',
                            'Database Administration',
                            'Cloud Computing',
                            'Mobile Development',
                            'DevOps',
                            'UI/UX Design',
                            'Project Management',
                            'Business Analysis',
                            'Marketing',
                            'Finance',
                            'Accounting',
                            'Human Resources',
                            'Education',
                            'Healthcare',
                            'Engineering',
                            'Legal',
                            'Consulting',
                            'Entrepreneurship',
                            'Other',
                        ];
                        foreach ($ucsFields as $ucsField): ?>
                            <option value="<?php echo htmlspecialchars($ucsField); ?>" <?php echo $ucsForm['professional_field'] === $ucsField ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ucsField); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($ucsForm['professional_field']) && !in_array($ucsForm['professional_field'], $ucsFields, true)): ?>
                        <p class="mt-1.5 text-xs text-gray-500">Current: <span class="font-medium text-gray-700"><?php echo htmlspecialchars($ucsForm['professional_field']); ?></span> (not in the list above)</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label for="skills" class="block text-sm font-medium text-gray-700">Skills <span class="text-gray-400">(optional)</span></label>
                    <textarea id="skills" name="skills" rows="3" maxlength="2000" placeholder="e.g. PHP, Laravel, Tailwind CSS, Leadership"
                              class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['skills']); ?></textarea>
                    <p class="mt-1.5 text-xs text-gray-500">Comma-separated skills, one per line, or any free-form text.</p>
                </div>

                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700">Biography <span class="text-gray-400">(optional)</span></label>
                    <textarea id="bio" name="bio" rows="4" maxlength="5000" placeholder="A short professional biography shared on the profile…"
                              class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['bio']); ?></textarea>
                </div>

                <div>
                    <label for="career_journey" class="block text-sm font-medium text-gray-700">Career Journey <span class="text-gray-400">(optional)</span></label>
                    <textarea id="career_journey" name="career_journey" rows="4" maxlength="5000" placeholder="Key roles, achievements or milestones in the alumnus career…"
                              class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100"><?php echo htmlspecialchars($ucsForm['career_journey']); ?></textarea>
                </div>

                <div class="grid gap-6 sm:grid-cols-3">
                    <div>
                        <label for="linkedin_url" class="block text-sm font-medium text-gray-700">LinkedIn URL <span class="text-gray-400">(optional)</span></label>
                        <input type="url" id="linkedin_url" name="linkedin_url" value="<?php echo htmlspecialchars($ucsForm['linkedin_url']); ?>" maxlength="191" placeholder="https://linkedin.com/in/…"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="github_url" class="block text-sm font-medium text-gray-700">GitHub URL <span class="text-gray-400">(optional)</span></label>
                        <input type="url" id="github_url" name="github_url" value="<?php echo htmlspecialchars($ucsForm['github_url']); ?>" maxlength="191" placeholder="https://github.com/…"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                    <div>
                        <label for="website_url" class="block text-sm font-medium text-gray-700">Website URL <span class="text-gray-400">(optional)</span></label>
                        <input type="url" id="website_url" name="website_url" value="<?php echo htmlspecialchars($ucsForm['website_url']); ?>" maxlength="191" placeholder="https://example.com"
                               class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                    </div>
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <label for="verification_status" class="block text-sm font-medium text-gray-700">Verification Status</label>
                        <select id="verification_status" name="verification_status" required
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 transition-colors focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
                            <option value="pending" <?php echo $ucsForm['verification_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="verified" <?php echo $ucsForm['verification_status'] === 'verified' ? 'selected' : ''; ?>>Verified</option>
                            <option value="rejected" <?php echo $ucsForm['verification_status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                        <p class="mt-1.5 text-xs text-gray-500">Only verified profiles are shown on the public directory.</p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <a href="<?php echo htmlspecialchars(ROOT_URL . '/admin/alumni/view.php?id=' . (int) $ucsProfile['id']); ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-400">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors duration-200 hover:bg-blue-700 focus:outline-none focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../../includes/admin-layout-bottom.php'; ?>