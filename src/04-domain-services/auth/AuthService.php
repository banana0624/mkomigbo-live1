<?php

require_once __DIR__ . '/../../05-data-layer/repositories/UserRepository.php';

class AuthService
{
    private $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository();
    }

    public function login($email, $password)
    {
        $user = $this->userRepo->findByEmail($email);

        if (!$user) {
            return ["success" => false, "message" => "User not found"];
        }

        if (!password_verify($password, $user['password'])) {
            return ["success" => false, "message" => "Incorrect password"];
        }

        return [
            "success" => true,
            "message" => "Login successful",
            "user" => [
                "id" => $user['id'],
                "email" => $user['email'],
                "role" => $user['role']
            ]
        ];
    }
}