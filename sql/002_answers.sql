CREATE TABLE IF NOT EXISTS answers (
    id INT(11) NOT NULL AUTO_INCREMENT,
    question_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    body TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY answers_question_id_index (question_id),
    KEY answers_user_id_index (user_id),
    CONSTRAINT answers_question_id_fk
        FOREIGN KEY (question_id) REFERENCES questions (id)
        ON DELETE CASCADE,
    CONSTRAINT answers_user_id_fk
        FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
