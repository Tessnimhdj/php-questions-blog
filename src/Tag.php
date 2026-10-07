<?php

declare(strict_types=1);

final class Tag
{
    public const MAX_LENGTH = 30;
    public const MAX_COUNT = 8;

    private static function pdo(): PDO
    {
        return Database::getInstance()->getConnection();
    }

    public static function all(): array
    {
        $stmt = self::pdo()->prepare('SELECT id, name FROM tags ORDER BY name ASC');
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function exists(int $id): bool
    {
        $stmt = self::pdo()->prepare('SELECT id FROM tags WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() !== false;
    }

    public static function parse(string $input): array
    {
        $input = trim($input);
        if ($input === '') {
            return [[], null];
        }

        $parts = preg_split('/\s*,\s*/u', $input) ?: [];
        $names = [];
        foreach ($parts as $part) {
            $name = mb_strtolower(trim($part), 'UTF-8');
            if ($name === '') {
                continue;
            }
            if (mb_strlen($name, 'UTF-8') > self::MAX_LENGTH || !preg_match('/^[\p{L}\p{N} _-]+$/u', $name)) {
                return [[], t('tag_invalid')];
            }
            $names[$name] = $name;
        }

        if (count($names) > self::MAX_COUNT) {
            return [[], t('tag_too_many', ['max' => self::MAX_COUNT])];
        }

        return [array_values($names), null];
    }

    public static function namesForQuestion(int $questionId): array
    {
        $grouped = self::groupedByQuestion([$questionId]);

        return array_column($grouped[$questionId] ?? [], 'name');
    }

    public static function groupedByQuestion(array $questionIds): array
    {
        $questionIds = array_values(array_unique(array_filter(
            array_map(static fn ($id): int => (int) $id, $questionIds),
            static fn (int $id): bool => $id > 0
        )));
        if ($questionIds === []) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($questionIds as $index => $questionId) {
            $key = 'id' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $questionId;
        }

        $stmt = self::pdo()->prepare(
            'SELECT question_tag.question_id, tags.id, tags.name
             FROM question_tag
             INNER JOIN tags ON tags.id = question_tag.tag_id
             WHERE question_tag.question_id IN (' . implode(', ', $placeholders) . ')
             ORDER BY tags.name ASC'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();

        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $grouped[(int) $row['question_id']][] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
            ];
        }

        return $grouped;
    }

    public static function sync(int $questionId, array $names): void
    {
        $delete = self::pdo()->prepare('DELETE FROM question_tag WHERE question_id = :question_id');
        $delete->execute(['question_id' => $questionId]);

        $link = self::pdo()->prepare(
            'INSERT INTO question_tag (question_id, tag_id) VALUES (:question_id, :tag_id)'
        );
        foreach ($names as $name) {
            $link->execute([
                'question_id' => $questionId,
                'tag_id' => self::findOrCreate((string) $name),
            ]);
        }
    }

    private static function findOrCreate(string $name): int
    {
        $existing = self::findIdByName($name);
        if ($existing !== null) {
            return $existing;
        }

        try {
            $stmt = self::pdo()->prepare('INSERT INTO tags (name) VALUES (:name)');
            $stmt->execute(['name' => $name]);

            return (int) self::pdo()->lastInsertId();
        } catch (PDOException $e) {
            $existing = self::findIdByName($name);
            if ($existing !== null) {
                return $existing;
            }

            throw $e;
        }
    }

    private static function findIdByName(string $name): ?int
    {
        $stmt = self::pdo()->prepare('SELECT id FROM tags WHERE name = :name');
        $stmt->execute(['name' => $name]);
        $row = $stmt->fetch();

        return $row === false ? null : (int) $row['id'];
    }
}
