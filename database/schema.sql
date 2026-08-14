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

    name VARCHAR(255) NOT NULL,
    description TEXT NULL,

    status BOOLEAN NOT NULL DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
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

    name VARCHAR(255) NOT NULL,

    email VARCHAR(191) NOT NULL UNIQUE,

    password VARCHAR(255) NOT NULL,

    classroom_id BIGINT UNSIGNED NOT NULL,

    status BOOLEAN NOT NULL DEFAULT TRUE,

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