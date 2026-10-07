<?php

declare(strict_types=1);

final class Question
{
    private static function pdo(): PDO
    {
        return Database::getInstance()->getConnection();
    }

    public static function search(
        string $term,
        string $sort,
        int $page,
        int $perPage = 10,
        ?int $categoryId = null,
        ?int $tagId = null
    ): array {
        $perPage = max(1, $perPage);
        $page = max(1, $page);
        $orderBy = self::orderBy($sort);
        $conditions = [];
        $params = [];
        $term = trim($term);

        if ($term !== '') {
            $like = '%' . self::escapeLike($term) . '%';
            $conditions[] = "(`nom` LIKE :nom ESCAPE '\\\\' OR `contenu` LIKE :contenu ESCAPE '\\\\')";
            $params['nom'] = $like;
            $params['contenu'] = $like;
        }
        if ($categoryId !== null) {
            $conditions[] = 'category_id = :category_id';
            $params['category_id'] = $categoryId;
        }
        if ($tagId !== null) {
            $conditions[] = 'EXISTS (SELECT 1 FROM question_tag WHERE question_tag.question_id = questions.id AND question_tag.tag_id = :tag_id)';
            $params['tag_id'] = $tagId;
        }
        $where = $conditions === [] ? '' : 'WHERE ' . implode(' AND ', $conditions);

        $count = self::pdo()->prepare('SELECT COUNT(*) FROM questions ' . $where);
        self::bind($count, $params);
        $count->execute();
        $total = (int) $count->fetchColumn();
        $pages = $total === 0 ? 0 : (int) ceil($total / $perPage);

        if ($pages > 0 && $page > $pages) {
            $page = $pages;
        }

        $offset = ($page - 1) * $perPage;
        $stmt = self::pdo()->prepare(
            'SELECT id, nom, user_id, category_id, (SELECT categories.name FROM categories WHERE categories.id = questions.category_id) AS category_name, (SELECT COUNT(*) FROM answers WHERE answers.question_id = questions.id) AS answer_count FROM questions ' . $where . ' ORDER BY ' . $orderBy . ' LIMIT :limit OFFSET :offset'
        );
        self::bind($stmt, $params);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'rows' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $pages === 0 ? 1 : $page,
            'pages' => $pages,
        ];
    }

    private static function orderBy(string $sort): string
    {
        return match ($sort) {
            'oldest' => '`date` ASC, id ASC',
            'az' => 'nom ASC, id ASC',
            default => '`date` DESC, id DESC',
        };
    }

    private static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    private static function bind(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $name => $value) {
            $stmt->bindValue(':' . $name, $value, PDO::PARAM_STR);
        }
    }

    public static function find(int $id): ?array
    {
        $stmt = self::pdo()->prepare('SELECT id, nom, contenu, user_id, category_id FROM questions WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function isOwnedBy(int $id, int $userId): bool
    {
        $stmt = self::pdo()->prepare('SELECT id FROM questions WHERE id = :id AND user_id = :user_id');
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);

        return $stmt->fetch() !== false;
    }

    public static function create(string $name, int $userId, ?int $categoryId): int
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO questions (nom, contenu, date, user_id, category_id)
             VALUES (:nom, NULL, CURRENT_TIMESTAMP, :user_id, :category_id)'
        );
        $stmt->bindValue(':nom', $name, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        if ($categoryId === null) {
            $stmt->bindValue(':category_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return (int) self::pdo()->lastInsertId();
    }

    public static function updateName(int $id, int $userId, string $name, ?int $categoryId): void
    {
        $stmt = self::pdo()->prepare(
            'UPDATE questions SET nom = :nom, category_id = :category_id WHERE id = :id AND user_id = :user_id'
        );
        $stmt->bindValue(':nom', $name, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        if ($categoryId === null) {
            $stmt->bindValue(':category_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        }
        $stmt->execute();
    }

    public static function updateContent(int $id, int $userId, string $content): void
    {
        $stmt = self::pdo()->prepare('UPDATE questions SET contenu = :contenu WHERE id = :id AND user_id = :user_id');
        $stmt->execute([
            'contenu' => $content,
            'id' => $id,
            'user_id' => $userId,
        ]);
    }

    public static function delete(int $id, int $userId): int
    {
        $stmt = self::pdo()->prepare('DELETE FROM questions WHERE id = :id AND user_id = :user_id');
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);

        return $stmt->rowCount();
    }
}
