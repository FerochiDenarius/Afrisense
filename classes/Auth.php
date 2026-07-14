<?php

require_once 'user.php';

class Auth
{
    private $pdo;
    private $userModel;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;

        $this->userModel = new User($pdo);
    }

public function login($email, $password)
{
    if (empty($email) || empty($password)) {

        return [
            'success' => false,
            'message' => 'Email and Password are required.'
        ];
    }

    $user = $this->userModel->getUserByEmail($email);

    if (!$user) {

        return [
            'success' => false,
            'message' => 'Invalid email or password.'
        ];
    }

    if (!password_verify($password, $user['password'])) {

        return [
            'success' => false,
            'message' => 'Invalid email or password.'
        ];
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['fullname'] = $user['fullname'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['email'] = $user['email'];

    return [
        'success' => true,
        'message' => 'Login Successful.'
    ];
}





}