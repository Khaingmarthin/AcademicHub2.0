<?php
/**
 * Shared validation helpers for the Admin Alumni & Career Community module.
 *
 * Covers the alumni lifecycle controlled by admins: creating an alumni
 * profile for a graduated student, and verifying / rejecting pending
 * profiles. All state transitions are enforced server-side here; hiding
 * buttons on the page is never the only line of defence.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

const ALUMNI_VERIFICATION_STATUSES = ['pending', 'verified', 'rejected'];

/**
 * Store a flash message for the Alumni module pages.
 *
 * @param string $type    One of 'success' or 'error'.
 * @param string $message The message body.
 * @return void
 */
function alumni_flash($type, $message)
{
    $_SESSION['alumni_flash'] = [
        'type'    => $type === 'success' ? 'success' : 'error',
        'message' => (string) $message,
    ];
}

/**
 * Validate creation of an alumni profile for a student.
 *
 * Rules:
 *  - the student must exist,
 *  - the student must already be officially graduated,
 *  - the student must not already have an alumni profile.
 *
 * @param array $input Raw form values (e.g. $_POST).
 * @param PDO   $pdo   Database connection.
 * @return array{clean:array, errors:array}
 */
function alumni_create_validate_input($input, $pdo)
{
    $errors = [];

    $studentId = filter_var($input['student_id'] ?? null, FILTER_VALIDATE_INT);

    if ($studentId === false || $studentId < 1) {
        $errors[] = 'Invalid student selected.';
    } else {
        try {
            $ucsStmt = $pdo->prepare(
                "SELECT s.id, s.name, s.student_status,
                        ap.id AS alumni_profile_id
                 FROM students s
                 LEFT JOIN alumni_profiles ap ON ap.student_id = s.id
                 WHERE s.id = :id
                 LIMIT 1"
            );
            $ucsStmt->execute([':id' => $studentId]);
            $ucsStudent = $ucsStmt->fetch() ?: null;

            if ($ucsStudent === null) {
                $errors[] = 'Student not found.';
            } elseif ($ucsStudent['student_status'] !== 'graduated') {
                $errors[] = 'Only officially graduated students can receive an Alumni Profile.';
            } elseif ($ucsStudent['alumni_profile_id'] !== null) {
                $errors[] = 'This student already has an Alumni Profile.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to validate the student. Please try again.';
        }
    }

    return [
        'clean' => [
            'student_id' => $studentId,
        ],
        'errors' => $errors,
    ];
}

/**
 * Validate a graduated student's self-service alumni profile application.
 *
 * This powers the "Join the Alumni Community" flow: a graduated student who
 * has no alumni profile yet (or whose profile was rejected) may submit their
 * career information. The profile is stored as 'pending' and must then be
 * verified by an authorised admin through the existing verification workflow.
 *
 * @param array $input Raw form values (e.g. $_POST).
 * @param PDO   $pdo   Database connection.
 * @return array{clean:array, errors:array}
 */
function alumni_join_validate_input($input, $pdo)
{
    $errors = [];

    $studentId = (int) (student_current_user()['id'] ?? 0);

    if ($studentId < 1) {
        $errors[] = 'You must be logged in to join the alumni community.';
        return ['clean' => [], 'errors' => $errors];
    }

    try {
        $ucsStmt = $pdo->prepare(
            "SELECT s.student_status, ap.id AS alumni_profile_id,
                    ap.verification_status AS profile_status
             FROM students s
             LEFT JOIN alumni_profiles ap ON ap.student_id = s.id
             WHERE s.id = :id
             LIMIT 1"
        );
        $ucsStmt->execute([':id' => $studentId]);
        $ucsStudent = $ucsStmt->fetch() ?: null;

        if ($ucsStudent === null) {
            $errors[] = 'Student account not found.';
            return ['clean' => [], 'errors' => $errors];
        }
        if ($ucsStudent['student_status'] !== 'graduated') {
            $errors[] = 'Only officially graduated students can join the alumni community.';
            return ['clean' => [], 'errors' => $errors];
        }
        if ($ucsStudent['alumni_profile_id'] !== null
            && in_array($ucsStudent['profile_status'], ['pending', 'verified'], true)) {
            $errors[] = 'You already have an alumni profile.';
            return ['clean' => [], 'errors' => $errors];
        }
    } catch (PDOException $e) {
        $errors[] = 'Unable to validate your account. Please try again.';
        return ['clean' => [], 'errors' => $errors];
    }

    $currentJob        = trim((string) ($input['current_job'] ?? ''));
    $company           = trim((string) ($input['company'] ?? ''));
    $professionalField = trim((string) ($input['professional_field'] ?? ''));
    $skills            = trim((string) ($input['skills'] ?? ''));
    $bio               = trim((string) ($input['bio'] ?? ''));
    $careerJourney     = trim((string) ($input['career_journey'] ?? ''));
    $linkedinUrl       = trim((string) ($input['linkedin_url'] ?? ''));
    $githubUrl         = trim((string) ($input['github_url'] ?? ''));
    $websiteUrl        = trim((string) ($input['website_url'] ?? ''));
    $mentorshipActive  = $input['mentorship_available'] ?? 0;
    $visibility        = (string) ($input['visibility'] ?? '');

    if (mb_strlen($currentJob) > 255) {
        $errors[] = 'Current job must be 255 characters or fewer.';
    }
    if (mb_strlen($company) > 255) {
        $errors[] = 'Company must be 255 characters or fewer.';
    }
    if (mb_strlen($professionalField) > 255) {
        $errors[] = 'Professional field must be 255 characters or fewer.';
    }
    if (mb_strlen($skills) > 2000) {
        $errors[] = 'Skills must be 2000 characters or fewer.';
    }
    if (mb_strlen($bio) > 5000) {
        $errors[] = 'Biography must be 5000 characters or fewer.';
    }
    if (mb_strlen($careerJourney) > 5000) {
        $errors[] = 'Career journey must be 5000 characters or fewer.';
    }

    foreach (['linkedin_url' => $linkedinUrl, 'github_url' => $githubUrl, 'website_url' => $websiteUrl] as $ucsField => $ucsValue) {
        if ($ucsValue !== '') {
            if (mb_strlen($ucsValue) > 191) {
                $errors[] = 'Professional links must be 191 characters or fewer.';
            } elseif (!filter_var($ucsValue, FILTER_VALIDATE_URL)) {
                $errors[] = 'Please enter a valid URL for the ' . str_replace('_', ' ', $ucsField) . '.';
            }
        }
    }

    if (!in_array($mentorshipActive, [0, 1, '0', '1'], true)) {
        $errors[] = 'Invalid mentorship availability selected.';
    }

    if (!in_array($visibility, ['public', 'private'], true)) {
        $errors[] = 'Invalid visibility selected.';
    }

    return [
        'clean' => [
            'student_id'           => $studentId,
            'current_job'          => $currentJob,
            'company'              => $company,
            'professional_field'   => $professionalField,
            'skills'               => $skills,
            'bio'                  => $bio,
            'career_journey'       => $careerJourney,
            'linkedin_url'         => $linkedinUrl,
            'github_url'           => $githubUrl,
            'website_url'          => $websiteUrl,
            'mentorship_available' => in_array($mentorshipActive, [1, '1'], true) ? 1 : 0,
            'visibility'           => $visibility,
        ],
        'errors' => $errors,
    ];
}

/**
 * Validate a verification status change (verify or reject) on an alumni
 * profile.
 *
 * Allowed transitions (admin-controlled "activate / deactivate" lifecycle):
 *   - pending  -> verified  (verify)
 *   - pending  -> rejected  (reject)
 *   - rejected -> verified  (activate / re-verify)
 *   - verified -> rejected  (deactivate / revoke)
 *
 * @param array  $input        Raw form values (e.g. $_POST).
 * @param PDO    $pdo          Database connection.
 * @param string $targetStatus The status being applied: 'verified' or 'rejected'.
 * @return array{clean:array, errors:array}
 */
function alumni_verification_validate_input($input, $pdo, $targetStatus)
{
    $errors = [];

    if (!in_array($targetStatus, ['verified', 'rejected'], true)) {
        $errors[] = 'Invalid verification action.';
        return ['clean' => [], 'errors' => $errors];
    }

    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);

    if ($id === false || $id < 1) {
        $errors[] = 'Invalid Alumni Profile selected.';
    } else {
        try {
            $ucsStmt = $pdo->prepare(
                "SELECT id, verification_status
                 FROM alumni_profiles
                 WHERE id = :id
                 LIMIT 1"
            );
            $ucsStmt->execute([':id' => $id]);
            $ucsProfile = $ucsStmt->fetch() ?: null;

            if ($ucsProfile === null) {
                $errors[] = 'Alumni Profile not found.';
            } else {
                $ucsCurrent = $ucsProfile['verification_status'];
                $ucsAllowed = ($targetStatus === 'verified')
                    ? in_array($ucsCurrent, ['pending', 'rejected'], true)
                    : in_array($ucsCurrent, ['pending', 'verified'], true);

                if (!$ucsAllowed) {
                    $errors[] = 'This Alumni Profile cannot be ' . ($targetStatus === 'verified' ? 'verified' : 'rejected') . ' from its current status.';
                }
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to validate the Alumni Profile. Please try again.';
        }
    }

    return [
        'clean' => [
            'id' => $id,
        ],
        'errors' => $errors,
    ];
}

/**
 * Validate the alumni-specific profile fields for the admin edit form.
 *
 * Only alumni-specific information is editable here. Student identity
 * and academic status are managed by their own dedicated workflows
 * and never appear in this validator.
 *
 * @param array $input Raw form values (e.g. $_POST).
 * @param PDO   $pdo   Database connection.
 * @param int   $id    The alumni profile id being edited.
 * @return array{clean:array, errors:array}
 */
function alumni_update_validate_input($input, $pdo, $id)
{
    $errors = [];

    $currentJob        = trim((string) ($input['current_job'] ?? ''));
    $company           = trim((string) ($input['company'] ?? ''));
    $professionalField = trim((string) ($input['professional_field'] ?? ''));
    $skills            = trim((string) ($input['skills'] ?? ''));
    $bio               = trim((string) ($input['bio'] ?? ''));
    $careerJourney     = trim((string) ($input['career_journey'] ?? ''));
    $linkedinUrl       = trim((string) ($input['linkedin_url'] ?? ''));
    $githubUrl         = trim((string) ($input['github_url'] ?? ''));
    $websiteUrl        = trim((string) ($input['website_url'] ?? ''));
    $verificationStatus = (string) ($input['verification_status'] ?? 'pending');

    $id = filter_var($id, FILTER_VALIDATE_INT);
    if ($id === false || $id < 1) {
        $errors[] = 'Invalid Alumni Profile selected.';
    } else {
        try {
            $ucsStmt = $pdo->prepare("SELECT id FROM alumni_profiles WHERE id = :id LIMIT 1");
            $ucsStmt->execute([':id' => $id]);
            if ($ucsStmt->fetchColumn() === false) {
                $errors[] = 'Alumni Profile not found.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Unable to validate the Alumni Profile. Please try again.';
        }
    }

    if (mb_strlen($currentJob) > 255) {
        $errors[] = 'Current job must be 255 characters or fewer.';
    }
    if (mb_strlen($company) > 255) {
        $errors[] = 'Company must be 255 characters or fewer.';
    }
    if (mb_strlen($professionalField) > 255) {
        $errors[] = 'Professional field must be 255 characters or fewer.';
    }
    if (mb_strlen($skills) > 2000) {
        $errors[] = 'Skills must be 2000 characters or fewer.';
    }
    if (mb_strlen($bio) > 5000) {
        $errors[] = 'Biography must be 5000 characters or fewer.';
    }
    if (mb_strlen($careerJourney) > 5000) {
        $errors[] = 'Career journey must be 5000 characters or fewer.';
    }

    foreach (['linkedin_url' => $linkedinUrl, 'github_url' => $githubUrl, 'website_url' => $websiteUrl] as $ucsField => $ucsValue) {
        if ($ucsValue !== '') {
            if (mb_strlen($ucsValue) > 191) {
                $errors[] = 'Professional links must be 191 characters or fewer.';
            } elseif (!filter_var($ucsValue, FILTER_VALIDATE_URL)) {
                $errors[] = 'Please enter a valid URL for the ' . str_replace('_', ' ', $ucsField) . '.';
            }
        }
    }

    if (!in_array($verificationStatus, ['pending', 'verified', 'rejected'], true)) {
        $errors[] = 'Invalid verification status selected.';
    }

    return [
        'clean' => [
            'id'                   => $id,
            'current_job'          => $currentJob,
            'company'              => $company,
            'professional_field'   => $professionalField,
            'skills'               => $skills,
            'bio'                  => $bio,
            'career_journey'       => $careerJourney,
            'linkedin_url'         => $linkedinUrl,
            'github_url'           => $githubUrl,
            'website_url'          => $websiteUrl,
            'verification_status'  => $verificationStatus,
        ],
        'errors' => $errors,
    ];
}

/**
 * Return the authenticated student's verified alumni profile, or null.
 *
 * An alumnus is a logged-in student whose record has exactly one alumni
 * profile in the 'verified' state. This is the gate used for alumni
 * self-management: only the owner of a verified profile can edit it.
 *
 * @param PDO $pdo Database connection.
 * @return array|null The verified alumni profile row, or null.
 */
function alumni_current_profile($pdo)
{
    $student = student_current_user();
    if ($student === null) {
        return null;
    }

    try {
        $ucsStmt = $pdo->prepare(
            "SELECT id, student_id, current_job, company, professional_field,
                    skills, bio, career_journey, profile_photo,
                    linkedin_url, github_url, website_url,
                    mentorship_available, mentorship_contact_email,
                    mentorship_suspended, visibility, verification_status,
                    created_at, updated_at
             FROM alumni_profiles
             WHERE student_id = :student_id
             LIMIT 1"
        );
        $ucsStmt->execute([':student_id' => $student['id']]);
        $ucsProfile = $ucsStmt->fetch() ?: null;

        if ($ucsProfile === null || $ucsProfile['verification_status'] !== 'verified') {
            return null;
        }

        return $ucsProfile;
    } catch (PDOException $e) {
        return null;
    }
}