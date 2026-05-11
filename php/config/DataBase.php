<?php

class Database {
    private static ?Database $instance = null;
    private mysqli $connection;

    private function __construct() {
        $host = "localhost";
        $user = "root";
        $pass = "";
        $db   = "subscription_db";

        $this->connection = new mysqli($host, $user, $pass, $db);
        $this->connection->set_charset("utf8mb4");

        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection(): mysqli {
        return $this->connection;
    }

    private function __clone() {}
}