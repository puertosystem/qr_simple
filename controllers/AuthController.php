<?php
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if (empty($email) || empty($password)) {
                $error = 'Por favor ingrese email y contraseña.';
                require __DIR__ . '/../views/auth/login.php';
                return;
            }

            $user = $this->userModel->findByEmail($email);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'] ?? $user['username'] ?? 'Admin';
                $_SESSION['user_email'] = $user['email'] ?? '';
                $_SESSION['user_profile_image'] = $user['profile_image'] ?? null;
                
                header('Location: index.php');
                exit;
            } else {
                $error = 'Credenciales inválidas.';
                require __DIR__ . '/../views/auth/login.php';
            }
        } else {
            // Show login form
            if (isset($_SESSION['user_id'])) {
                header('Location: index.php');
                exit;
            }
            require __DIR__ . '/../views/auth/login.php';
        }
    }

    public function logout() {
        session_destroy();
        header('Location: index.php?page=login');
        exit;
    }
}
