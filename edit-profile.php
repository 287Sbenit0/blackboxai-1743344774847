<?php
require_once 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

try {
    // Get current user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("Usuario no encontrado");
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $username = sanitize($_POST['username']);
            $email = sanitize($_POST['email']);

            // Validate inputs
            if (empty($username) || empty($email)) {
                throw new Exception("Todos los campos son obligatorios");
            }

            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                throw new Exception("Este email ya está en uso por otro usuario");
            }

            // Handle profile picture upload
            $profile_pic = $user['profile_pic'];
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_pic'];
                
                // Validate file
                if ($file['size'] > MAX_FILE_SIZE) {
                    throw new Exception("El archivo es demasiado grande (máximo 5MB)");
                }

                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                if (!in_array($mime, ALLOWED_TYPES)) {
                    throw new Exception("Solo se permiten imágenes JPEG o PNG");
                }

                // Generate unique filename
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $profile_pic = uniqid('user_') . '.' . $ext;
                $destination = UPLOAD_DIR . $profile_pic;

                if (!move_uploaded_file($file['tmp_name'], $destination)) {
                    throw new Exception("Error al subir la imagen");
                }

                // Delete old profile picture if not default
                if ($user['profile_pic'] !== 'default.jpg') {
                    @unlink(UPLOAD_DIR . $user['profile_pic']);
                }
            }

            // Update user in database
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, profile_pic = ? WHERE user_id = ?");
            $stmt->execute([$username, $email, $profile_pic, $user_id]);

            // Update session
            $_SESSION['username'] = $username;
            $_SESSION['profile_pic'] = $profile_pic;

            $success = "Perfil actualizado correctamente";

        } elseif ($action === 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            // Validate inputs
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                throw new Exception("Todos los campos son obligatorios");
            }

            if ($new_password !== $confirm_password) {
                throw new Exception("Las contraseñas no coinciden");
            }

            if (strlen($new_password) < 8) {
                throw new Exception("La contraseña debe tener al menos 8 caracteres");
            }

            // Verify current password
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception("La contraseña actual es incorrecta");
            }

            // Update password
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$new_password_hash, $user_id]);

            $success = "Contraseña cambiada correctamente";
        }
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil - Red Social de Pisos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.0.0/css/all.min.css">
    <style>
        .profile-pic {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
        }
        .form-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .tab-content {
            padding: 20px 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <div class="form-container">
            <h2 class="text-center mb-4">Editar Perfil</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" 
                            data-bs-target="#profile" type="button" role="tab">
                        Información del Perfil
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="password-tab" data-bs-toggle="tab" 
                            data-bs-target="#password" type="button" role="tab">
                        Cambiar Contraseña
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="profileTabsContent">
                <!-- Profile Info Tab -->
                <div class="tab-pane fade show active" id="profile" role="tabpanel">
                    <form action="edit-profile.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="text-center mb-4">
                            <img src="uploads/<?= htmlspecialchars($user['profile_pic']) ?>" 
                                 class="profile-pic mb-3" alt="Foto de perfil">
                            <div>
                                <input type="file" class="form-control" id="profile_pic" name="profile_pic" 
                                       accept="image/*" style="display: none;">
                                <label for="profile_pic" class="btn btn-outline-primary">
                                    <i class="fas fa-camera me-2"></i>Cambiar Foto
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Nombre de Usuario</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>

                <!-- Password Tab -->
                <div class="tab-pane fade" id="password" role="tabpanel">
                    <form action="edit-profile.php" method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Contraseña Actual</label>
                            <input type="password" class="form-control" id="current_password" 
                                   name="current_password" required>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="new_password" 
                                   name="new_password" required>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Nueva Contraseña</label>
                            <input type="password" class="form-control" id="confirm_password" 
                                   name="confirm_password" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Cambiar Contraseña</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Preview profile picture before upload
        document.getElementById('profile_pic').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-pic').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>