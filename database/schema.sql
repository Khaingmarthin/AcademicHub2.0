-- =========================================================
-- UCSMTLA ACADEMIC HUB
-- FINAL DATABASE SCHEMA
-- MySQL / MariaDB
-- =========================================================

CREATE DATABASE IF NOT EXISTS ucsmtla_academic_hub
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE ucsmtla_academic_hub;


-- =========================================================
-- 1. UNIVERSITY PROFILE
-- =========================================================

CREATE TABLE university_profile (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(255) NOT NULL,
    short_name VARCHAR(100) NOT NULL,

    history TEXT NULL,
    vision TEXT NULL,
    mission TEXT NULL,
    rector_message TEXT NULL,

    established_year YEAR NULL,

    logo VARCHAR(255) NULL,
    hero_media VARCHAR(255) NULL,

    address TEXT NULL,
    phone VARCHAR(50) NULL,
    email VARCHAR(255) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);


-- =========================================================
-- 2. FACULTIES
-- =========================================================

CREATE TABLE faculties (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(255) NOT NULL,
    description TEXT NULL,

    status BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);


-- =========================================================
-- 3. DEPARTMENTS
-- =========================================================

CREATE TABLE departments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    faculty_id BIGINT UNSIGNED NOT NULL,

    name VARCHAR(255) NOT NULL,
    description TEXT NULL,

    status BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_departments_faculty
        FOREIGN KEY (faculty_id)
        REFERENCES faculties(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- =========================================================
-- 4. FACILITIES
-- =========================================================

CREATE TABLE facilities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(255) NOT NULL,
    image VARCHAR(255) NULL,
    description TEXT NULL,
    location VARCHAR(255) NULL,

    status BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);


-- =========================================================
-- 5. ACADEMIC YEARS
-- =========================================================

CREATE TABLE academic_years (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    year_name VARCHAR(20) NOT NULL UNIQUE,

    start_date DATE NULL,
    end_date DATE NULL,

    status ENUM(
        'Preparation',
        'Active',
        'Archived'
    ) NOT NULL DEFAULT 'Preparation',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);


-- =========================================================
-- 6. MAJORS
-- =========================================================

CREATE TABLE majors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(255) NOT NULL,
    short_name VARCHAR(50) NULL,

    degree_name VARCHAR(100) NULL,
    description TEXT NULL,

    status BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);


-- =========================================================
-- 7. COURSES
-- =========================================================

CREATE TABLE courses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    academic_year_id BIGINT UNSIGNED NOT NULL,
    major_id BIGINT UNSIGNED NOT NULL,

    course_code VARCHAR(50) NOT NULL,
    course_name VARCHAR(255) NOT NULL,

    year_level ENUM(
        'First Year',
        'Second Year',
        'Third Year',
        'Fourth Year',
        'Fifth Year'
    ) NOT NULL,

    semester ENUM(
        'First Semester',
        'Second Semester'
    ) NOT NULL,

    credit_hours INT UNSIGNED NULL,
    description TEXT NULL,

    status BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_courses_academic_year
        FOREIGN KEY (academic_year_id)
        REFERENCES academic_years(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_courses_major
        FOREIGN KEY (major_id)
        REFERENCES majors(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    UNIQUE KEY uq_course_year_major_code
        (academic_year_id, major_id, course_code)
);


-- =========================================================
-- 8. CLASSROOMS
-- =========================================================

CREATE TABLE classrooms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    academic_year_id BIGINT UNSIGNED NOT NULL,
    major_id BIGINT UNSIGNED NOT NULL,

    year_level ENUM(
        'First Year',
        'Second Year',
        'Third Year',
        'Fourth Year',
        'Fifth Year'
    ) NOT NULL,

    section VARCHAR(10) NOT NULL,

    classroom_name VARCHAR(100) NOT NULL,

    status BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_classrooms_academic_year
        FOREIGN KEY (academic_year_id)
        REFERENCES academic_years(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_classrooms_major
        FOREIGN KEY (major_id)
        REFERENCES majors(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    UNIQUE KEY uq_classroom
        (academic_year_id, major_id, year_level, section)
);


-- =========================================================
-- 9. TIMETABLES
-- =========================================================

CREATE TABLE timetables (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    classroom_id BIGINT UNSIGNED NOT NULL,

    semester ENUM(
        'First Semester',
        'Second Semester'
    ) NOT NULL,

    title VARCHAR(255) NOT NULL,

    image VARCHAR(255) NOT NULL,

    status BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_timetables_classroom
        FOREIGN KEY (classroom_id)
        REFERENCES classrooms(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    UNIQUE KEY uq_classroom_semester
        (classroom_id, semester)
);


-- =========================================================
-- 10. STUDENTS
-- =========================================================

CREATE TABLE students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    student_id VARCHAR(50) NOT NULL UNIQUE,

    roll_number VARCHAR(50) NOT NULL UNIQUE,

    name VARCHAR(255) NOT NULL,

    email VARCHAR(191) NOT NULL UNIQUE,

    password VARCHAR(255) NOT NULL,

    classroom_id BIGINT UNSIGNED NOT NULL,

    -- Account status (active/inactive): controls whether the student may
    -- log in. Distinct from the academic status below.
    status BOOLEAN NOT NULL DEFAULT TRUE,

    -- Academic status: the university's official graduation confirmation.
    -- 'graduated' is set ONLY by an authorised admin via the graduation
    -- workflow; it never happens automatically from year level or exams.
    student_status ENUM(
        'active',
        'graduated'
    ) NOT NULL DEFAULT 'active',

    -- Graduation information recorded when the admin confirms graduation.
    graduation_year YEAR NULL,
    graduated_at TIMESTAMP NULL DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_students_classroom
        FOREIGN KEY (classroom_id)
        REFERENCES classrooms(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- =========================================================
-- 11. ADMISSIONS
-- =========================================================

CREATE TABLE admissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    academic_year_id BIGINT UNSIGNED NOT NULL,

    title VARCHAR(255) NOT NULL,

    description TEXT NULL,
    requirements TEXT NULL,
    important_dates TEXT NULL,
    application_info TEXT NULL,

    document_title VARCHAR(255) NULL,
    document_path VARCHAR(255) NULL,
    document_type VARCHAR(50) NULL,

    status BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_admissions_academic_year
        FOREIGN KEY (academic_year_id)
        REFERENCES academic_years(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- =========================================================
-- 12. CATEGORIES
-- =========================================================

CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,

    status BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);


-- =========================================================
-- 13. ADMINS
-- =========================================================

CREATE TABLE admins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(255) NOT NULL,

    email VARCHAR(191) NOT NULL UNIQUE,

    password VARCHAR(255) NOT NULL,

    status BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);


-- =========================================================
-- 14. NEWS
-- =========================================================

CREATE TABLE news (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    category_id BIGINT UNSIGNED NOT NULL,
    admin_id BIGINT UNSIGNED NOT NULL,

    title VARCHAR(255) NOT NULL,

    slug VARCHAR(191) NOT NULL UNIQUE,

    content LONGTEXT NOT NULL,

    cover_image VARCHAR(255) NULL,

    published_at DATETIME NULL,

    status ENUM(
        'Draft',
        'Published',
        'Expired'
    ) NOT NULL DEFAULT 'Draft',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_news_category
        FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_news_admin
        FOREIGN KEY (admin_id)
        REFERENCES admins(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- =========================================================
-- 15. NEWS TARGETS
-- =========================================================

CREATE TABLE news_targets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    news_id BIGINT UNSIGNED NOT NULL,

    academic_year_id BIGINT UNSIGNED NULL,
    major_id BIGINT UNSIGNED NULL,

    year_level ENUM(
        'First Year',
        'Second Year',
        'Third Year',
        'Fourth Year',
        'Fifth Year'
    ) NULL,

    section VARCHAR(10) NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_news_targets_news
        FOREIGN KEY (news_id)
        REFERENCES news(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_news_targets_academic_year
        FOREIGN KEY (academic_year_id)
        REFERENCES academic_years(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_news_targets_major
        FOREIGN KEY (major_id)
        REFERENCES majors(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
);


-- =========================================================
-- 16. ALUMNI PROFILES
-- =========================================================
-- Alumni & Career Community module foundation.
--
-- A student becomes an alumnus by gaining exactly one alumni_profiles row
-- (UNIQUE student_id). Identity, credentials, roll number and major are
-- reused from the students table; nothing here duplicates that data.
-- Graduation year/date live on the students table, not here.
--
-- Transition states (verification_status):
--   - No row                 -> current student (unchanged behaviour)
--   - 'pending'              -> graduation confirmed by admin
--   - 'verified'             -> alumni access available
--   - 'rejected'             -> denied / revoked
--
-- Privacy: visibility defaults to 'private'. Public alumni profiles must
-- never expose password, phone, private email, address, NRC, date of birth
-- or private student data; they are composed only of the public fields in
-- this table joined with students.name and the classroom-derived major.

CREATE TABLE alumni_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    student_id BIGINT UNSIGNED NOT NULL,

    current_job VARCHAR(255) NULL,
    company VARCHAR(255) NULL,
    professional_field VARCHAR(255) NULL,
    career_journey TEXT NULL,
    skills TEXT NULL,
    bio TEXT NULL,
    profile_photo VARCHAR(255) NULL,
    linkedin_url VARCHAR(191) NULL,
    github_url VARCHAR(191) NULL,
    website_url VARCHAR(191) NULL,
    mentorship_available BOOLEAN NOT NULL DEFAULT FALSE,
    mentorship_contact_email VARCHAR(191) NULL,
    mentorship_suspended BOOLEAN NOT NULL DEFAULT FALSE,

    visibility ENUM(
        'public',
        'private'
    ) NOT NULL DEFAULT 'private',

    verification_status ENUM(
        'pending',
        'verified',
        'rejected'
    ) NOT NULL DEFAULT 'pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_alumni_profiles_student
        FOREIGN KEY (student_id)
        REFERENCES students(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    UNIQUE KEY uq_alumni_profiles_student (student_id)
);


-- =========================================================
-- 16b. ALUMNI STORIES
-- =========================================================
--
-- Admin-controlled editorial stories that highlight a graduate's
-- experiences, achievements and career journey. Content is written by
-- admins; it is never auto-generated from alumni profile fields. Each story
-- is tied to the featured alumnus (alumni_profile_id) and the admin who
-- wrote it (admin_id).
--
-- Status workflow:
--   - 'draft'       -> work in progress, never shown publicly
--   - 'published'   -> visible on the public Alumni Stories page
--   - 'unpublished' -> taken down from the public page but kept in admin
--
-- publication_date is the editorial publish date (defaults to today when a
-- story is first published) and is used to order the public listing.

CREATE TABLE alumni_stories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    alumni_profile_id BIGINT UNSIGNED NOT NULL,
    admin_id BIGINT UNSIGNED NULL,
    alumni_student_id BIGINT UNSIGNED NULL,

    title VARCHAR(255) NOT NULL,
    summary TEXT NULL,
    content TEXT NOT NULL,
    career_field VARCHAR(255) NULL,
    cover_image VARCHAR(255) NULL,
    publication_date DATE NULL,

    status ENUM(
        'draft',
        'pending',
        'published',
        'rejected',
        'unpublished'
    ) NOT NULL DEFAULT 'draft',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_alumni_stories_alumni_profile
        FOREIGN KEY (alumni_profile_id)
        REFERENCES alumni_profiles(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_alumni_stories_admin
        FOREIGN KEY (admin_id)
        REFERENCES admins(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_alumni_stories_student
        FOREIGN KEY (alumni_student_id)
        REFERENCES students(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


-- =========================================================
-- 17. CAREER DISCUSSIONS
-- =========================================================
--
-- Moderated, forum-style career community connecting current students
-- (questions, comments) and verified alumni (answers, comments, own
-- discussions). This is NOT a chat system: no private messaging, no
-- real-time messaging and no friend/follow functionality.
--
-- discussion_categories holds the fixed career categories shown to users.
-- discussions are questions / topics started by a logged-in student (which
-- includes verified alumni, since an alumnus is still a student row). Each
-- discussion's status is:
--   - 'open'    -> normal, visible on the public listing
--   - 'closed'  -> locked by an admin; visible but no new replies
--   - 'hidden'  -> removed from the public site by an admin (kept for audit)
-- Replies belong to a discussion and carry status 'visible'/'hidden' so an
-- admin can hide a single inappropriate reply without taking down the whole
-- discussion. discussion_reports is a polymorphic report log: a student or
-- alumnus reports a discussion or a reply; admins resolve or dismiss it.
--
-- FKs (documented only; tables run on MyISAM which ignores them):
--   discussions.category_id         -> discussion_categories(id)
--   discussions.author_student_id   -> students(id)
--   discussion_replies.discussion_id -> discussions(id)   ON DELETE CASCADE
--   discussion_replies.author_student_id -> students(id)
--   discussion_reports.content_id    -> discussions(id) or discussion_replies(id)
--   discussion_reports.reporter_student_id -> students(id)
--   discussion_reports.resolved_by_admin_id -> admins(id)
-- The unique key on (content_type, content_id, reporter_student_id) lets a
-- reporter file at most one report per piece of content.

CREATE TABLE discussion_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(191) NOT NULL,
    slug VARCHAR(191) NOT NULL,
    description TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    status ENUM(
        'active',
        'inactive'
    ) NOT NULL DEFAULT 'active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_discussion_categories_name UNIQUE (name),
    CONSTRAINT uq_discussion_categories_slug UNIQUE (slug)
);

CREATE TABLE discussions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    category_id BIGINT UNSIGNED NOT NULL,
    author_student_id BIGINT UNSIGNED NOT NULL,

    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,

    status ENUM(
        'open',
        'closed',
        'hidden'
    ) NOT NULL DEFAULT 'open',
    is_pinned TINYINT(1) NOT NULL DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_discussions_category
        FOREIGN KEY (category_id)
        REFERENCES discussion_categories(id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_discussions_author
        FOREIGN KEY (author_student_id)
        REFERENCES students(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE discussion_replies (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    discussion_id BIGINT UNSIGNED NOT NULL,
    author_student_id BIGINT UNSIGNED NOT NULL,

    content TEXT NOT NULL,
    status ENUM(
        'visible',
        'hidden'
    ) NOT NULL DEFAULT 'visible',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_discussion_replies_discussion
        FOREIGN KEY (discussion_id)
        REFERENCES discussions(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_discussion_replies_author
        FOREIGN KEY (author_student_id)
        REFERENCES students(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE discussion_reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    content_type ENUM(
        'discussion',
        'reply'
    ) NOT NULL,
    content_id BIGINT UNSIGNED NOT NULL,
    reporter_student_id BIGINT UNSIGNED NOT NULL,

    reason VARCHAR(50) NOT NULL,
    details TEXT NULL,
    status ENUM(
        'open',
        'resolved',
        'dismissed'
    ) NOT NULL DEFAULT 'open',
    resolved_by_admin_id BIGINT UNSIGNED NULL,
    resolved_at TIMESTAMP NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT uq_discussion_reports_content_reporter
        UNIQUE (content_type, content_id, reporter_student_id),

    CONSTRAINT fk_discussion_reports_reporter
        FOREIGN KEY (reporter_student_id)
        REFERENCES students(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_discussion_reports_resolver
        FOREIGN KEY (resolved_by_admin_id)
        REFERENCES admins(id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
);

-- Seeded career categories (managed by admins via the Admin Discussions ->
-- Categories pages).
INSERT INTO discussion_categories (name, slug, description, sort_order) VALUES
    ('Career Advice',           'career-advice',           'General guidance about building and managing your career.', 1),
    ('Internship',              'internship',              'Questions about finding, applying for and succeeding in internships.', 2),
    ('CV & Portfolio',          'cv-portfolio',            'Help with CVs, portfolios and professional profiles.', 3),
    ('Interview Preparation',   'interview-preparation',   'Tips and practice for job and internship interviews.', 4),
    ('Technical Career',        'technical-career',        'Career paths and skills in technical and IT fields.', 5),
    ('Industry Experience',     'industry-experience',     'Real-world work experience and workplace insights.', 6),
    ('Further Study',           'further-study',           'Higher education, certifications and postgraduate study.', 7),
    ('General Career',          'general-career',          'Any other career-related questions and discussions.', 8);


-- =========================================================
-- INDEXES
-- =========================================================

CREATE INDEX idx_courses_academic_year
    ON courses(academic_year_id);

CREATE INDEX idx_courses_major
    ON courses(major_id);

CREATE INDEX idx_classrooms_academic_year
    ON classrooms(academic_year_id);

CREATE INDEX idx_classrooms_major
    ON classrooms(major_id);

CREATE INDEX idx_students_classroom
    ON students(classroom_id);

CREATE INDEX idx_timetables_classroom
    ON timetables(classroom_id);

CREATE INDEX idx_admissions_academic_year
    ON admissions(academic_year_id);

CREATE INDEX idx_news_category
    ON news(category_id);

CREATE INDEX idx_news_admin
    ON news(admin_id);

CREATE INDEX idx_news_status
    ON news(status);

CREATE INDEX idx_news_published_at
    ON news(published_at);

CREATE INDEX idx_news_targets_news
    ON news_targets(news_id);

CREATE INDEX idx_news_targets_academic_year
    ON news_targets(academic_year_id);

CREATE INDEX idx_news_targets_major
    ON news_targets(major_id);

CREATE INDEX idx_alumni_profiles_verification_status
    ON alumni_profiles(verification_status);

CREATE INDEX idx_alumni_profiles_visibility
    ON alumni_profiles(visibility);

CREATE INDEX idx_students_graduation_year
    ON students(graduation_year);

CREATE INDEX idx_alumni_stories_alumni_profile
    ON alumni_stories(alumni_profile_id);

CREATE INDEX idx_alumni_stories_admin
    ON alumni_stories(admin_id);

CREATE INDEX idx_alumni_stories_student
    ON alumni_stories(alumni_student_id);

CREATE INDEX idx_alumni_stories_status
    ON alumni_stories(status);

CREATE INDEX idx_alumni_stories_publication_date
    ON alumni_stories(publication_date);

CREATE INDEX idx_discussion_categories_slug
    ON discussion_categories(slug);

CREATE INDEX idx_discussions_category
    ON discussions(category_id);

CREATE INDEX idx_discussions_author
    ON discussions(author_student_id);

CREATE INDEX idx_discussions_status
    ON discussions(status);

CREATE INDEX idx_discussions_pinned
    ON discussions(is_pinned);

CREATE INDEX idx_discussion_replies_discussion
    ON discussion_replies(discussion_id);

CREATE INDEX idx_discussion_replies_author
    ON discussion_replies(author_student_id);

CREATE INDEX idx_discussion_replies_status
    ON discussion_replies(status);

CREATE INDEX idx_discussion_reports_content
    ON discussion_reports(content_type, content_id);

CREATE INDEX idx_discussion_reports_status
    ON discussion_reports(status);

CREATE INDEX idx_discussion_reports_reporter
    ON discussion_reports(reporter_student_id);