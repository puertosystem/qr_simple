<?php
require_once __DIR__ . '/../config/database.php';

class UserController {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    public function handleRequest() {
        $view = isset($_GET['view']) ? $_GET['view'] : 'index';

        switch ($view) {
            case 'create':
                $this->create();
                break;
            case 'edit':
                $this->edit();
                break;
            case 'delete':
                $this->delete();
                break;
            default:
                $this->index();
                break;
        }
    }

    public function index() {
        // Fetch users from database
        $stmt = $this->pdo->query("SELECT * FROM user ORDER BY id DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/users/index.php';
    }

    private function handleFileUpload($file) {
        if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes)) {
                return null; // Or throw error
            }

            $uploadDir = __DIR__ . '/../assets/img/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('avatar_') . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                return 'assets/img/avatars/' . $fileName;
            }
        }
        return null;
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'];
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $role = $_POST['role'] ?? 'user';
            
            $profile_image = null;
            if (isset($_FILES['profile_image'])) {
                $profile_image = $this->handleFileUpload($_FILES['profile_image']);
            }

            // Check if email or username exists
            $stmt = $this->pdo->prepare("SELECT id FROM user WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            if ($stmt->fetch()) {
                header('Location: index.php?page=users&error=' . urlencode('El email o nombre de usuario ya existe.'));
                exit;
            }

            $stmt = $this->pdo->prepare("INSERT INTO user (name, username, email, password_hash, role, profile_image) VALUES (?, ?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$name, $username, $email, $password, $role, $profile_image]);
                header('Location: index.php?page=users&success=Usuario creado correctamente');
                exit;
            } catch (PDOException $e) {
                header('Location: index.php?page=users&error=' . urlencode('Error al crear usuario: ' . $e->getMessage()));
                exit;
            }
        }
        // If not POST, redirect back to index
        header('Location: index.php?page=users');
        exit;
    }

    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: index.php?page=users&error=ID no proporcionado');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'];
            $username = $_POST['username'];
            $email = $_POST['email'];
            $role = $_POST['role'];

            // Check duplicates excluding current user
            $stmt = $this->pdo->prepare("SELECT id FROM user WHERE (email = ? OR username = ?) AND id != ?");
            $stmt->execute([$email, $username, $id]);
            if ($stmt->fetch()) {
                header('Location: index.php?page=users&error=' . urlencode('El email o nombre de usuario ya existe.'));
                exit;
            }

            $updates = [
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'role' => $role
            ];
            
            if (!empty($_POST['password'])) {
                $updates['password_hash'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
            
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $newImage = $this->handleFileUpload($_FILES['profile_image']);
                if ($newImage) {
                    $updates['profile_image'] = $newImage;
                    // TODO: Optionally delete old image
                }
            }

            $sql = "UPDATE user SET ";
            $setClauses = [];
            $params = [];
            
            foreach ($updates as $key => $value) {
                $setClauses[] = "$key = ?";
                $params[] = $value;
            }
            
            $sql .= implode(', ', $setClauses);
            $sql .= " WHERE id = ?";
            $params[] = $id;

            $stmt = $this->pdo->prepare($sql);
            try {
                $stmt->execute($params);
                
                // Update session if user is updating themselves
                if ($id == $_SESSION['user_id']) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    if (isset($updates['profile_image'])) {
                        $_SESSION['user_profile_image'] = $updates['profile_image'];
                    }
                }
                
                header('Location: index.php?page=users&success=Usuario actualizado correctamente');
                exit;
            } catch (PDOException $e) {
                header('Location: index.php?page=users&error=' . urlencode('Error al actualizar usuario: ' . $e->getMessage()));
                exit;
            }
        }

        // If not POST (should not happen with modal), redirect back to index
        header('Location: index.php?page=users');
        exit;
    }

    public function delete() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            // Prevent deleting self
            if ($id == $_SESSION['user_id']) {
                 header('Location: index.php?page=users&error=No puedes eliminar tu propia cuenta');
                 exit;
            }

            $stmt = $this->pdo->prepare("DELETE FROM user WHERE id = ?");
            try {
                $stmt->execute([$id]);
                header('Location: index.php?page=users&success=Usuario eliminado correctamente');
            } catch (PDOException $e) {
                header('Location: index.php?page=users&error=Error al eliminar usuario');
            }
        }
        exit;
    }
}
