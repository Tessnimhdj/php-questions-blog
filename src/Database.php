<?php

declare(strict_types=1);

final class Database
{
    private static ?self $instance = null;

    private PDO $connection;

    private function __construct()
    {
        try {
            $this->connection = new PDO(
                'mysql:host=localhost;dbname=question_blog_db;charset=utf8mb4',
                'root',
                '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            http_response_code(500);
            if (!headers_sent()) {
                header('Content-Type: text/html; charset=UTF-8');
            }
            exit(e(t('db_connect_error')));
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->connection;
    }

    private function __clone(): void
    {
    }
}
