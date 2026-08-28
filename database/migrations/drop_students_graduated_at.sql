-- Migration: Drop graduated_at column from students table.
-- The graduation_year column is sufficient for tracking graduation;
-- graduated_at was written but never displayed or used in logic.

ALTER TABLE students
    DROP COLUMN graduated_at;
