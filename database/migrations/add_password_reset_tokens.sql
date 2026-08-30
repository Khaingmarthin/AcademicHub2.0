-- =========================================================
-- Migration: Add password_reset_tokens table
-- Used by the "Forgot Password" feature for both students and admins.
-- =========================================================

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_type   ENUM('student','admin') NOT NULL,
    user_id     BIGINT UNSIGNED NOT NULL,
    token       VARCHAR(64) NOT NULL UNIQUE,
    expires_at  DATETIME NOT NULL,
    used        BOOLEAN NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_token (token),
    INDEX idx_user (user_type, user_id)
) ENGINE=InnoDB;
