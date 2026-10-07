<?php

declare(strict_types=1);

final class User
{
    private static function pdo(): PDO
    {
        return Database::getInstance()->getConnection();
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = self::pdo()->prepare(
            'SELECT id, username, email, password_hash, role FROM users WHERE email = :email'
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public static function emailTaken(string $email): bool
    {
        $stmt = self::pdo()->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);

        return $stmt->fetch() !== false;
    }

    public static function create(string $username, string $email, string $password): int
    {
        $stmt = self::pdo()->prepare(
            'INSERT INTO users (username, email, password_hash, role, created_at)
             VALUES (:username, :email, :password_hash, :role, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'user',
        ]);

        return (int) self::pdo()->lastInsertId();
    }
}
