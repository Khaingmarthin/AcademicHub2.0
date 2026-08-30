-- In-app notifications for students (news + discussion replies)
CREATE TABLE notifications (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id      BIGINT UNSIGNED NOT NULL,
    type            ENUM('news','discussion_reply') NOT NULL,
    title           VARCHAR(255) NOT NULL,
    message         TEXT NOT NULL,
    link            VARCHAR(500) NOT NULL,
    is_read         TINYINT(1) NOT NULL DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notifications_student
        FOREIGN KEY (student_id) REFERENCES students(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_notifications_student_read
    ON notifications (student_id, is_read, created_at DESC);
