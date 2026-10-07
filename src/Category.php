<?php

declare(strict_types=1);

final class Category
{
    public const MAX_LENGTH = 50;

    public static function normalize(string $input): array
    {
        $name = trim($input);
        if ($name === '') {
            return [null, null];
        }

        if (mb_strlen($name, 'UTF-8') > self::MAX_LENGTH || !preg_match('/^[\p{L}\p{N} _-]+$/u', $name)) {
            return [null, t('category_name_invalid', ['max' => self::MAX_LENGTH])];
        }

        return [$name, null];
    }

    public static function findOrCreate(string $name): int
    {
        $existing = self::findIdByName($name);
        if ($existing !== null) {
            return $existing;
        }

        $pdo = Database::getInstance()->getConnection();
        try {
            $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (:name)');
            $stmt->execute(['name' => $name]);

            return (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            $existing = self::findIdByName($name);
            if ($existing !== null) {
                return $existing;
            }

            throw $e;
        }
    }

    public static function all(): array
    {
        $stmt = Database::getInstance()->getConnection()->prepare(
            'SELECT id, name FROM categories ORDER BY name ASC'
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public static function exists(int $id): bool
    {
        $stmt = Database::getInstance()->getConnection()->prepare(
            'SELECT id FROM categories WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() !== false;
    }

    private static function findIdByName(string $name): ?int
    {
        $stmt = Database::getInstance()->getConnection()->prepare(
            'SELECT id FROM categories WHERE name = :name'
        );
        $stmt->execute(['name' => $name]);
        $row = $stmt->fetch();

        return $row === false ? null : (int) $row['id'];
    }
}
