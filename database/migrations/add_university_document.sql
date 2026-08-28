-- Migration: Add document column to university_profile table.
-- Stores file path for academic entrance documents (PDFs, forms, etc.).

ALTER TABLE university_profile
    ADD COLUMN document VARCHAR(255) NULL AFTER admission_description;
