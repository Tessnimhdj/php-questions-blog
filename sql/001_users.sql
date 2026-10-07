CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE questions
    ADD COLUMN user_id INT(11) NULL DEFAULT NULL AFTER id,
    ADD KEY questions_user_id_index (user_id),
    ADD CONSTRAINT questions_user_id_fk
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE SET NULL;
