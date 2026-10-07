<?php

declare(strict_types=1);

final class Answer
{
    private static function pdo(): PDO
    {
        return Database::getInstance()->getConnection();
    }

    public static function forQuestion(int $questionId): array
    {
        $stmt = self::pdo()->prepare(
            'SELECT answers.id, answers.body, answers.created_at, answers.user_id, users.username
             FROM answers
             INNER JOIN users ON users.id = answers.user_id
             WHERE answers.question_id = :question_id
             ORDER BY answers.created_at ASC, answers.id ASC'
        );
        $stmt->execute(['question_id' => $questionId]);

        return $stmt->fetchAll();
    }

    public static function isOwnedBy(int $id, int $userId): bool
    {
        $stmt = self::pdo()->prepare('SELECT id FROM answers WHERE id = :id AND user_id = :user_id');
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);

        return $stmt->fetch() !== false;
    }

    public static function create(int $questionId, int $userId, string $body): void
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO answers (question_id, user_id, body, created_at)
             VALUES (:question_id, :user_id, :body, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'question_id' => $questionId,
            'user_id' => $userId,
            'body' => $body,
        ]);
    }

    public static function update(int $id, int $userId, string $body): void
    {
        $stmt = self::pdo()->prepare(
            'UPDATE answers SET body = :body WHERE id = :id AND user_id = :user_id'
        );
        $stmt->execute([
            'body' => $body,
            'id' => $id,
            'user_id' => $userId,
        ]);
    }

    public static function delete(int $id, int $userId): int
    {
        $stmt = self::pdo()->prepare('DELETE FROM answers WHERE id = :id AND user_id = :user_id');
        $stmt->execute([
            'id' => $id,
            'user_id' => $userId,
        ]);

        return $stmt->rowCount();
    }
}
