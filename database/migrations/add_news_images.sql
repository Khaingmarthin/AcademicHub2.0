-- Additional images for news articles (gallery)
CREATE TABLE news_images (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    news_id     BIGINT UNSIGNED NOT NULL,
    image_path  VARCHAR(255) NOT NULL,
    sort_order  INT NOT NULL DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_news_images_news
        FOREIGN KEY (news_id) REFERENCES news(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE INDEX idx_news_images_news_order
    ON news_images (news_id, sort_order ASC);
