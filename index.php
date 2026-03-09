<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/app.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'home';
$basePath = __DIR__ . '/views/';

// Public routes
if ($page === 'login') {
    require __DIR__ . '/controllers/AuthController.php';
    $controller = new AuthController();
    $controller->login();
    exit;
} elseif ($page === 'logout') {
    require __DIR__ . '/controllers/AuthController.php';
    $controller = new AuthController();
    $controller->logout();
    exit;
} elseif ($page === 'validate') {
    require __DIR__ . '/controllers/CertificateController.php';
    $controller = new CertificateController();
    $controller->validate();
    exit;
} elseif ($page === 'download_certificate') {
    require __DIR__ . '/controllers/CertificateController.php';
    $controller = new CertificateController();
    $controller->download();
    exit;
} elseif ($page === 'constancias' && (
    ((isset($_GET['action']) || isset($_POST['action'])) && in_array($_GET['action'] ?? $_POST['action'], ['validate', 'download', 'register_lead'])) ||
    (isset($_GET['view']) && in_array($_GET['view'], ['public', 'success']))
)) {
    require __DIR__ . '/controllers/ConstanciaController.php';
    $controller = new ConstanciaController();
    $controller->handleRequest();
    exit;
}

// Protected routes - Check authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

// Refresh user session data to ensure name is up to date
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/models/User.php';
    $userModel = new User();
    $currentUser = $userModel->findById($_SESSION['user_id']);
    if ($currentUser) {
        $_SESSION['user_name'] = $currentUser['name'] ?? $currentUser['username'] ?? 'Usuario';
        $_SESSION['user_profile_image'] = $currentUser['profile_image'] ?? null;
    }
}

if ($page === 'participants') {
    require __DIR__ . '/controllers/ParticipantController.php';
    $controller = new ParticipantController();
    $controller->handleRequest();
} elseif ($page === 'constancias') {
    require __DIR__ . '/controllers/ConstanciaController.php';
    $controller = new ConstanciaController();
    $controller->handleRequest();
} elseif ($page === 'courses') {
    require __DIR__ . '/controllers/CourseController.php';
    $controller = new CourseController();
    $controller->handleRequest();
} elseif ($page === 'auspices') {
    require __DIR__ . '/controllers/AuspiceController.php';
    $controller = new AuspiceController();
    $controller->handleRequest();
} elseif ($page === 'certificates') {
    require __DIR__ . '/controllers/CertificateController.php';
    $controller = new CertificateController();
    $controller->handleRequest();
} elseif ($page === 'users') {
    require __DIR__ . '/controllers/UserController.php';
    $controller = new UserController();
    $controller->handleRequest();
} elseif ($page === 'updates') {
    require __DIR__ . '/controllers/UpdateController.php';
    $controller = new UpdateController();
    $controller->handleRequest();
} elseif ($page === 'settings') {
    require __DIR__ . '/controllers/SettingsController.php';
    $controller = new SettingsController();
    $controller->handleRequest();
} else {
    // Default home page (protected)
    require $basePath . 'home/index.php';
}
