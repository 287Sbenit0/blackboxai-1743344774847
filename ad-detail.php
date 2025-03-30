<?php
require_once 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

// Check if ad ID is provided
if (!isset($_GET['id'])) {
    redirect('dashboard.php');
}

$ad_id = (int)$_GET['id'];

try {
    // Get property details
    $stmt = $pdo->prepare("
        SELECT ads.*, users.username, users.profile_pic, users.email 
        FROM ads
        JOIN users ON ads.user_id = users.user_id
        WHERE ad_id = ?
    ");
    $stmt->execute([$ad_id]);
    $property = $stmt->fetch();

    if (!$property) {
        throw new Exception("Anuncio no encontrado");
    }

    $images = explode(',', $property['images']);
    $features = json_decode($property['features'] ?? '[]', true);

} catch (Exception $e) {
    redirect('dashboard.php?error=' . urlencode($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($property['title']) ?> - Red Social de Pisos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.0.0/css/all.min.css">
    <style>
        .property-img {
            height: 400px;
            object-fit: cover;
            width: 100%;
        }
        .thumbnail {
            height: 100px;
            object-fit: cover;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.3s;
        }
        .thumbnail:hover, .thumbnail.active {
            opacity: 1;
        }
        .feature-badge {
            font-size: 0.9rem;
            margin-right: 5px;
            margin-bottom: 5px;
        }
        #map {
            height: 300px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-8">
                <!-- Main Image -->
                <div class="mb-4">
                    <img id="mainImage" src="uploads/ads/<?= htmlspecialchars($images[0] ?? 'default-property.jpg') ?>" 
                         class="property-img rounded" alt="Imagen principal">
                </div>

                <!-- Thumbnails -->
                <div class="row g-2 mb-4">
                    <?php foreach ($images as $index => $image): ?>
                        <div class="col-3">
                            <img src="uploads/ads/<?= htmlspecialchars($image) ?>" 
                                 class="thumbnail <?= $index === 0 ? 'active' : '' ?> rounded" 
                                 onclick="changeImage(this, '<?= htmlspecialchars($image) ?>')"
                                 alt="Miniatura <?= $index + 1 ?>">
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Property Details -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h1 class="card-title"><?= htmlspecialchars($property['title']) ?></h1>
                        <h3 class="text-primary mb-4"><?= number_format($property['price'], 2) ?> €</h3>
                        
                        <div class="mb-4">
                            <h5>Descripción</h5>
                            <p class="card-text"><?= nl2br(htmlspecialchars($property['description'])) ?></p>
                        </div>

                        <div class="mb-4">
                            <h5>Ubicación</h5>
                            <p><i class="fas fa-map-marker-alt text-danger"></i> <?= htmlspecialchars($property['location']) ?></p>
                            <div id="map" class="mt-2">
                                <!-- Map placeholder - would integrate with Google Maps API in production -->
                                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                    <i class="fas fa-map-marked-alt fa-3x me-3"></i>
                                    <span>Mapa de <?= htmlspecialchars($property['location']) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h5>Características</h5>
                            <div>
                                <?php foreach ($features as $feature): ?>
                                    <span class="badge bg-primary feature-badge">
                                        <i class="fas fa-check-circle me-1"></i> <?= htmlspecialchars($feature) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Owner Card -->
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <img src="uploads/<?= htmlspecialchars($property['profile_pic']) ?>" 
                             class="rounded-circle mb-3" width="100" height="100" alt="Foto de perfil">
                        <h4><?= htmlspecialchars($property['username']) ?></h4>
                        <p class="text-muted">Publicado el <?= date('d/m/Y', strtotime($property['created_at'])) ?></p>
                        
                        <?php if ($property['user_id'] !== $_SESSION['user_id']): ?>
                            <button class="btn btn-primary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#messageModal">
                                <i class="fas fa-envelope me-2"></i> Enviar Mensaje
                            </button>
                            <button class="btn btn-outline-primary w-100">
                                <i class="fas fa-phone me-2"></i> Mostrar Teléfono
                            </button>
                        <?php else: ?>
                            <a href="edit-ad.php?id=<?= $property['ad_id'] ?>" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-edit me-2"></i> Editar Anuncio
                            </a>
                            <a href="controllers/delete-ad.php?id=<?= $property['ad_id'] ?>" class="btn btn-outline-danger w-100">
                                <i class="fas fa-trash me-2"></i> Eliminar Anuncio
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Similar Properties -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Anuncios Similares</h5>
                    </div>
                    <div class="card-body" id="similarProperties">
                        <!-- Similar properties will be loaded via AJAX -->
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Enviar Mensaje</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="controllers/send-message.php" method="POST">
                    <input type="hidden" name="receiver_id" value="<?= $property['user_id'] ?>">
                    <input type="hidden" name="ad_id" value="<?= $property['ad_id'] ?>">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Asunto</label>
                            <input type="text" class="form-control" name="subject" 
                                   value="Interés en <?= htmlspecialchars($property['title']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mensaje</label>
                            <textarea class="form-control" name="content" rows="5" required>Hola <?= htmlspecialchars($property['username']) ?>, estoy interesado en tu anuncio "<?= htmlspecialchars($property['title']) ?>".</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Enviar Mensaje</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Change main image when clicking thumbnails
        function changeImage(element, imageSrc) {
            document.getElementById('mainImage').src = 'uploads/ads/' + imageSrc;
            
            // Update active thumbnail
            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            element.classList.add('active');
        }

        // Load similar properties
        document.addEventListener('DOMContentLoaded', function() {
            fetch('controllers/get-properties.php?location=<?= urlencode($property['location']) ?>&limit=3&exclude=<?= $property['ad_id'] ?>')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('similarProperties').innerHTML = data;
                });
        });
    </script>
</body>
</html>