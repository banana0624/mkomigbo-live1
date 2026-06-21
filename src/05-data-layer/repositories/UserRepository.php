<?php

require_once __DIR__ . '/../database/Connection.php';

class UserRepository
{
    private $conn;

    public function __construct()
    {
        $this->conn = Connection::get();
    }

    public function findByEmail($email)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}