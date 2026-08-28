-- Migration: Merge 5 admission columns into single admission_description.
-- Combines admission_title, admission_description, admission_requirements,
-- admission_important_dates, and admission_application_info into one TEXT column.

-- Step 1: Merge existing data into admission_description
UPDATE university_profile
SET admission_description = CONCAT_WS('\n\n',
    NULLIF(admission_title, ''),
    NULLIF(admission_description, ''),
    NULLIF(admission_requirements, ''),
    NULLIF(admission_important_dates, ''),
    NULLIF(admission_application_info, '')
);

-- Step 2: Drop the 4 redundant columns
ALTER TABLE university_profile
    DROP COLUMN admission_title,
    DROP COLUMN admission_requirements,
    DROP COLUMN admission_important_dates,
    DROP COLUMN admission_application_info;
