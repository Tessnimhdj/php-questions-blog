CREATE TABLE IF NOT EXISTS categories (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY categories_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO categories (name)
SELECT seed.name
FROM (
    SELECT 'برمجة' AS name
    UNION ALL SELECT 'تطوير الويب'
    UNION ALL SELECT 'قواعد البيانات'
    UNION ALL SELECT 'عام'
) AS seed
WHERE NOT EXISTS (
    SELECT 1 FROM categories WHERE categories.name = seed.name
);

ALTER TABLE questions
    ADD COLUMN category_id INT(11) NULL DEFAULT NULL AFTER user_id,
    ADD KEY questions_category_id_index (category_id),
    ADD CONSTRAINT questions_category_id_fk
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS tags (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(30) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY tags_name_unique (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS question_tag (
    question_id INT(11) NOT NULL,
    tag_id INT(11) NOT NULL,
    PRIMARY KEY (question_id, tag_id),
    KEY question_tag_tag_id_index (tag_id),
    CONSTRAINT question_tag_question_fk
        FOREIGN KEY (question_id) REFERENCES questions (id)
        ON DELETE CASCADE,
    CONSTRAINT question_tag_tag_fk
        FOREIGN KEY (tag_id) REFERENCES tags (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
