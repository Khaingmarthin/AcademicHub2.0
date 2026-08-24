<?php
/**
 * Notification dispatch helper for the News module.
 *
 * Resolves which students should receive an email for a given news article
 * based on the news_targets rules, then sends HTML emails using the
 * email-service helper.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

require_once __DIR__ . '/email-service.php';

/**
 * Find all active students who should receive a notification for the given
 * news article, based on its news_targets.
 *
 * If no targets exist, every active student with email_notifications = 1
 * is returned (broadcast).
 *
 * @param PDO  $pdo    Database connection.
 * @param int  $newsId The news article id.
 * @return array List of ['id' => ..., 'name' => ..., 'email' => ...].
 */
function ucs_resolve_target_students($pdo, $newsId)
{
    $ucsTargets = [];
    try {
        $ucsStmt = $pdo->prepare(
            "SELECT classroom_id, major_id, year_level, section
             FROM news_targets
             WHERE news_id = :news_id"
        );
        $ucsStmt->execute([':news_id' => $newsId]);
        $ucsTargets = $ucsStmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }

    if (empty($ucsTargets)) {
        // Broadcast: notify all opted-in active students.
        try {
            $ucsStmt = $pdo->query(
                "SELECT id, name, email
                 FROM students
                 WHERE status = 1
                   AND student_status = 'active'
                   AND email_notifications = 1
                   AND email IS NOT NULL
                   AND email != ''"
            );
            return $ucsStmt->fetchAll() ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    $students = [];
    $seen     = [];

    foreach ($ucsTargets as $target) {
        $conditions = ['s.status = 1', "s.student_status = 'active'", 's.email_notifications = 1', "s.email IS NOT NULL", "s.email != ''"];
        $params     = [];

        if (!empty($target['classroom_id'])) {
            $conditions[] = 's.classroom_id = :classroom_id';
            $params[':classroom_id'] = (int) $target['classroom_id'];
        }

        if (!empty($target['major_id'])) {
            $conditions[] = 'cl.major_id = :major_id';
            $params[':major_id'] = (int) $target['major_id'];
        }

        if (!empty($target['year_level'])) {
            $conditions[] = 'cl.year_level = :year_level';
            $params[':year_level'] = $target['year_level'];
        }

        if (!empty($target['section'])) {
            $conditions[] = 'cl.section = :section';
            $params[':section'] = $target['section'];
        }

        $where = implode(' AND ', $conditions);

        try {
            $ucsStmt = $pdo->prepare(
                "SELECT DISTINCT s.id, s.name, s.email
                 FROM students s
                 JOIN classrooms cl ON cl.id = s.classroom_id
                 WHERE {$where}"
            );
            $ucsStmt->execute($params);
            $rows = $ucsStmt->fetchAll() ?: [];

            foreach ($rows as $row) {
                $id = (int) $row['id'];
                if (!isset($seen[$id])) {
                    $seen[$id]    = true;
                    $students[]   = $row;
                }
            }
        } catch (PDOException $e) {
            continue;
        }
    }

    return $students;
}

/**
 * Send email notifications for a published news article.
 *
 * Resolves the target students, renders the email template, and sends
 * one email per student. Returns the count of emails sent.
 *
 * @param PDO    $pdo     Database connection.
 * @param int    $newsId  The news article id.
 * @param string $title   Article title.
 * @param string $content Article content (used as summary).
 * @param string $slug    Article URL slug.
 * @return int Number of emails successfully sent.
 */
function ucs_send_news_notification($pdo, $newsId, $title, $content, $slug)
{
    $students = ucs_resolve_target_students($pdo, $newsId);
    if (empty($students)) {
        return 0;
    }

    $readUrl = BASE_URL . '/news-details.php?slug=' . urlencode($slug);
    $uniName = APP_NAME ?? 'Academic Hub';
    $summary = mb_substr(strip_tags($content), 0, 300);
    if (mb_strlen(strip_tags($content)) > 300) {
        $summary .= '...';
    }

    // Render the email template.
    ob_start();
    $title   = $title;
    $summary = $summary;
    $readUrl = $readUrl;
    $uniName = $uniName;
    require __DIR__ . '/../templates/news-notification.php';
    $htmlBody = ob_get_clean();

    $sent = 0;
    foreach ($students as $student) {
        $emailResult = ucs_send_email(
            $student['email'],
            $uniName . ': ' . $title,
            $htmlBody
        );
        if ($emailResult) {
            $sent++;
        }
    }

    return $sent;
}
