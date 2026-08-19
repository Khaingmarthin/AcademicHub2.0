<?php
/**
 * Shared admin dropdown option loaders.
 *
 * Small query helpers used by the admin create/edit forms that need to
 * select a related record (academic year, major, classroom). Returns plain
 * assoc arrays ready to be rendered as <option> elements.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}

/**
 * All academic years, newest first.
 *
 * @param PDO $pdo Database connection.
 * @return array List of ['id', 'year_name', 'status'] rows.
 */
function ucs_admin_academic_years($pdo)
{
    try {
        $ucsStmt = $pdo->query(
            "SELECT id, year_name, status
             FROM academic_years
             ORDER BY start_date DESC, id DESC"
        );
        return $ucsStmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * The currently Active academic year, or null when none is active.
 *
 * @param PDO $pdo Database connection.
 * @return array|null Row with 'id', 'year_name', 'status'.
 */
function ucs_admin_active_academic_year($pdo)
{
    try {
        $ucsStmt = $pdo->query(
            "SELECT id, year_name, status
             FROM academic_years
             WHERE status = 'Active'
             ORDER BY start_date DESC, id DESC
             LIMIT 1"
        );
        return $ucsStmt->fetch() ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Active majors, alphabetically.
 *
 * @param PDO $pdo Database connection.
 * @return array List of ['id', 'name'] rows.
 */
function ucs_admin_majors($pdo)
{
    try {
        $ucsStmt = $pdo->query(
            "SELECT id, name
             FROM majors
             WHERE status = 1
             ORDER BY name ASC"
        );
        return $ucsStmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Active classrooms with a human-readable label, grouped by academic year
 * with the currently Active year first.
 *
 * @param PDO $pdo Database connection.
 * @return array List of ['id', 'classroom_name', 'year_level', 'section',
 *                         'major_name', 'academic_year', 'academic_year_status'] rows.
 */
function ucs_admin_classrooms($pdo)
{
    try {
        $ucsStmt = $pdo->query(
            "SELECT cl.id, cl.classroom_name, cl.year_level, cl.section,
                    m.name AS major_name, ay.year_name AS academic_year,
                    ay.status AS academic_year_status
             FROM classrooms cl
             JOIN majors m ON m.id = cl.major_id
             JOIN academic_years ay ON ay.id = cl.academic_year_id
             WHERE cl.status = 1
             ORDER BY (ay.status = 'Active') DESC, ay.start_date DESC, cl.year_level ASC, cl.section ASC"
        );
        return $ucsStmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Classrooms belonging to the currently Active academic year.
 *
 * Used by the Timetables module where classrooms are always scoped to the
 * active academic year.
 *
 * @param PDO $pdo Database connection.
 * @return array List of ['id', 'classroom_name', 'year_level', 'section',
 *                         'major_name', 'academic_year'] rows.
 */
function ucs_admin_active_year_classrooms($pdo)
{
    try {
        $ucsStmt = $pdo->query(
            "SELECT cl.id, cl.classroom_name, cl.year_level, cl.section,
                    m.name AS major_name, ay.year_name AS academic_year
             FROM classrooms cl
             JOIN majors m ON m.id = cl.major_id
             JOIN academic_years ay ON ay.id = cl.academic_year_id
             WHERE ay.status = 'Active'
             ORDER BY cl.year_level ASC, (cl.section IS NULL) ASC, cl.section ASC, cl.classroom_name ASC"
        );
        return $ucsStmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Active news categories, alphabetically.
 *
 * @param PDO $pdo Database connection.
 * @return array List of ['id', 'name'] rows.
 */
function ucs_admin_categories($pdo)
{
    try {
        $ucsStmt = $pdo->query(
            "SELECT id, name
             FROM categories
             WHERE status = 1
             ORDER BY name ASC"
        );
        return $ucsStmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Active faculties, alphabetically.
 *
 * @param PDO $pdo Database connection.
 * @return array List of ['id', 'name'] rows.
 */
function ucs_admin_faculties($pdo)
{
    try {
        $ucsStmt = $pdo->query(
            "SELECT id, name
             FROM faculties
             WHERE status = 1
             ORDER BY name ASC"
        );
        return $ucsStmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * All alumni profiles with a human-readable label for the story editor.
 *
 * Any graduate with an alumni profile can be featured in a story, so this
 * returns every profile (regardless of verification state). The label
 * includes the student name, major and graduation year.
 *
 * @param PDO $pdo Database connection.
 * @return array List of ['id', 'student_name', 'major_name', 'graduation_year',
 *                         'verification_status', 'visibility'] rows.
 */
function ucs_admin_alumni_profiles($pdo)
{
    try {
        $ucsStmt = $pdo->query(
            "SELECT ap.id, s.name AS student_name, m.name AS major_name,
                    s.graduation_year, ap.verification_status, ap.visibility
             FROM alumni_profiles ap
             JOIN students s ON s.id = ap.student_id
             JOIN classrooms cl ON cl.id = s.classroom_id
             JOIN majors m ON m.id = cl.major_id
             ORDER BY s.name ASC"
        );
        return $ucsStmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}