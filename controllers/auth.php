<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'register') {
            // Registration handling
            $username = sanitize($_POST['username']);
            $email = sanitize($_POST['email']);
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            // Validate inputs
            if (empty($username) || empty($email) || empty($password)) {
                throw new Exception('Todos los campos son obligatorios');
            }

            if ($password !== $confirm_password) {
                throw new Exception('Las contraseñas no coinciden');
            }

            if (strlen($password) < 8) {
                throw new Exception('La contraseña debe tener al menos 8 caracteres');
            }

            // Check if email already exists
            $stmt = $pdo->prepare("SELECT email FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception('Este email ya está registrado');
            }

            // Handle profile picture upload
            $profile_pic = 'default.jpg';
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_pic'];
                
                // Validate file
                if ($file['size'] > MAX_FILE_SIZE) {
                    throw new Exception('El archivo es demasiado grande (máximo 5MB)');
                }

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                if (!in_array($mime, ALLOWED_TYPES)) {
                    throw new Exception('Solo se permiten imágenes JPEG o PNG');
                }

                // Generate unique filename
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $profile_pic = uniqid('user_') . '.' . $ext;
                $destination = UPLOAD_DIR . $profile_pic;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    throw new Exception('Error al subir la imagen');
                }
            }

            // Hash password and create user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, profile_pic) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $password_hash, $profile_pic]);

            // Automatically log in after registration
            $user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['profile_pic'] = $profile_pic;

            redirect('dashboard.php');

        } elseif ($action === 'login') {
            // Login handling
            $email = sanitize($_POST['email']);
            $password = $_POST['password'];

            $stmt = $pdo->prepare("SELECT user_id, username, password, profile_pic FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password'])) {
                throw new Exception('Email o contraseña incorrectos');
            }

            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['profile_pic'] = $user['profile_pic'];

            redirect('dashboard.php');
        }
    } catch (Exception $e) {
        $error = urlencode($e->getMessage());
        if ($action === 'register') {
            redirect("register.php?error=$error");
        } else {
            redirect("login.php?error=$error");
        }
    }
} else {
    redirect('index.php');
}