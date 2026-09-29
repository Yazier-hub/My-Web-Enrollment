<?php
// classes/Database.php

class Database
{
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function getConnection(): ?PDO
    {
        if (self::$instance === null) {
            $host     = 'localhost';
            $dbname   = 'lms';
            $username = 'root';
            $password = '';

            try {
                self::$instance = new PDO(
                    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $e) {
                error_log('Database connect failed: ' . $e->getMessage());
                return null;
            }
        }
        return self::$instance;
    }
}