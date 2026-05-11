<?php

require_once __DIR__ . '/../config/Database.php';

abstract class Model {
    protected mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
}