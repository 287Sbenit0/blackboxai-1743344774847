<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: text/html');

try {
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;

    if ($user_id) {
        // Get user's properties
        $stmt = $pdo->prepare("
            SELECT * FROM ads 
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
    } else {
        // Get featured properties
        $stmt = $pdo->prepare("
            SELECT ads.*, users.username, users.profile_pic 
            FROM ads
            JOIN users ON ads.user_id = users.user_id
            ORDER BY ads.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
    }

    $properties = $stmt->fetchAll();

    if (empty($properties)) {
        if ($user_id) {
            echo '<div class="col-12 text-center py-5">
                <i class="fas fa-home fa-3x text-muted mb-3"></i>
                <p class="text-muted">No has publicado ningún anuncio todavía</p>
                <a href="post-ad.php" class="btn btn-primary">Publicar mi primer anuncio</a>
            </div>';
        } else {
            echo '<div class="col-12 text-center py-5">
                <i class="fas fa-home fa-3x text-muted mb-3"></i>
                <p class="text-muted">No hay anuncios disponibles</p>
            </div>';
        }
        exit();
    }

    foreach ($properties as $property) {
        $images = explode(',', $property['images']);
        $first_image = !empty($images[0]) ? $images[0] : 'default-property.jpg';
        $features = json_decode($property['features'] ?? '[]', true);
        ?>
        <div class="col-md-4 mb-4">
            <div class="card property-card h-100">
                <img src="uploads/ads/<?= htmlspecialchars($first_image) ?>" class="card-img-top property-img" alt="Imagen del piso">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($property['title']) ?></h5>
                    <p class="card-text text-muted">
                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($property['location']) ?>
                    </p>
                    <p class="card-text"><?= htmlspecialchars(substr($property['description'], 0, 100)) ?>...</p>
                    <div class="mb-3">
                        <?php foreach ($features as $feature): ?>
                            <span class="badge bg-secondary me-1"><?= htmlspecialchars($feature) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="h5 text-primary mb-0"><?= number_format($property['price'], 2) ?> €</span>
                        <?php if (!$user_id): ?>
                            <a href="message.php?user_id=<?= $property['user_id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-envelope"></i> Contactar
                            </a>
                        <?php else: ?>
                            <div class="btn-group">
                                <a href="edit-ad.php?id=<?= $property['ad_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="controllers/delete-ad.php?id=<?= $property['ad_id'] ?>" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
} catch (Exception $e) {
    echo '<div class="col-12 text-center py-5">
        <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
        <p class="text-danger">Error al cargar los anuncios</p>
    </div>';
}