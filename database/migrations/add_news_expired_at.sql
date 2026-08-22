-- Migration: Add expired_at column to the news table.
-- Expired_at is nullable; when set, the announcement is considered expired
-- once the current datetime passes this value.

ALTER TABLE news
    ADD COLUMN expired_at DATETIME NULL AFTER published_at;
