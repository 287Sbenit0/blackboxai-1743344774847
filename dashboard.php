<?php
require_once 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT username, email, profile_pic FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Red Social de Pisos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.0.0/css/all.min.css">
    <style>
        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
        }
        .property-card {
            transition: transform 0.3s;
        }
        .property-card:hover {
            transform: translateY(-5px);
        }
        .property-img {
            height: 200px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Red Social de Pisos</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php"><i class="fas fa-home"></i> Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="post-ad.php"><i class="fas fa-plus-circle"></i> Publicar Anuncio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="message.php"><i class="fas fa-envelope"></i> Mensajes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="subscription.php"><i class="fas fa-crown"></i> Planes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="controllers/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <!-- User Profile Section -->
        <div class="row mb-5">
            <div class="col-md-4 text-center">
                <img src="uploads/<?= htmlspecialchars($user['profile_pic']) ?>" class="profile-img mb-3" alt="Foto de perfil">
                <h3><?= htmlspecialchars($user['username']) ?></h3>
                <p class="text-muted"><?= htmlspecialchars($user['email']) ?></p>
                <a href="edit-profile.php" class="btn btn-outline-primary">Editar Perfil</a>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Mis Anuncios</h4>
                    </div>
                    <div class="card-body">
                        <div class="row" id="user-properties">
                            <!-- User properties will be loaded here via AJAX -->
                            <div class="col-12 text-center py-5">
                                <i class="fas fa-home fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No has publicado ningún anuncio todavía</p>
                                <a href="post-ad.php" class="btn btn-primary">Publicar mi primer anuncio</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Featured Properties -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="mb-4">Pisos Disponibles</h2>
                <div class="row" id="featured-properties">
                    <!-- Featured properties will be loaded here via AJAX -->
                </div>
            </div>
        </div>

        <!-- Subscription Promotion -->
        <div class="row">
            <div class="col-12">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h3 class="mb-3">¡Mejora tu experiencia!</h3>
                        <p class="lead mb-4">Obtén más visibilidad para tus anuncios con nuestros planes premium</p>
                        <a href="subscription.php" class="btn btn-primary btn-lg">Ver Planes</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Load properties via AJAX
        document.addEventListener('DOMContentLoaded', function() {
            // Load user properties
            fetch('controllers/get-properties.php?user_id=<?= $user_id ?>')
                .then(response => response.text())
                .then(data => {
                    if (data) {
                        document.getElementById('user-properties').innerHTML = data;
                    }
                });

            // Load featured properties
            fetch('controllers/get-properties.php')
                .then(response => response.text())
                .then(data => {
                    if (data) {
                        document.getElementById('featured-properties').innerHTML = data;
                    }
                });
        });
    </script>
</body>
</html>