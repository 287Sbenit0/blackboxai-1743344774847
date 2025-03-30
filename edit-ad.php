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
$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

try {
    // Get property details
    $stmt = $pdo->prepare("SELECT * FROM ads WHERE ad_id = ? AND user_id = ?");
    $stmt->execute([$ad_id, $user_id]);
    $property = $stmt->fetch();

    if (!$property) {
        throw new Exception("Anuncio no encontrado o no tienes permiso para editarlo");
    }

    $images = explode(',', $property['images']);
    $features = json_decode($property['features'] ?? '[]', true);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = sanitize($_POST['title']);
        $price = (float)$_POST['price'];
        $location = sanitize($_POST['location']);
        $description = sanitize($_POST['description']);
        $new_features = isset($_POST['features']) ? $_POST['features'] : [];
        $delete_images = isset($_POST['delete_images']) ? $_POST['delete_images'] : [];
        $new_images = [];

        // Validate inputs
        if (empty($title) || empty($location) || empty($description)) {
            throw new Exception("Todos los campos son obligatorios");
        }

        if ($price <= 0) {
            throw new Exception("El precio debe ser mayor que 0");
        }

        // Handle new image uploads
        if (isset($_FILES['new_images']) && $_FILES['new_images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            foreach ($_FILES['new_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['new_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file = [
                        'tmp_name' => $tmp_name,
                        'name' => $_FILES['new_images']['name'][$key],
                        'size' => $_FILES['new_images']['size'][$key]
                    ];

                    // Validate file
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($file['tmp_name']);
                    if (!in_array($mime, ALLOWED_TYPES)) {
                        throw new Exception("Solo se permiten imágenes JPEG o PNG");
                    }

                    if ($file['size'] > MAX_FILE_SIZE) {
                        throw new Exception("Una o más imágenes exceden el tamaño máximo de 5MB");
                    }

                    // Generate unique filename
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('ad_') . '.' . $ext;
                    $destination = UPLOAD_DIR . 'ads/' . $filename;

                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $new_images[] = $filename;
                    }
                }
            }
        }

        // Process image deletions
        $remaining_images = array_diff($images, $delete_images);
        
        // Delete image files from server
        foreach ($delete_images as $image) {
            @unlink(UPLOAD_DIR . 'ads/' . $image);
        }

        // Combine remaining and new images
        $all_images = array_merge($remaining_images, $new_images);

        if (empty($all_images)) {
            throw new Exception("Debe haber al menos una imagen");
        }

        // Update property in database
        $stmt = $pdo->prepare("
            UPDATE ads SET 
                title = ?, 
                price = ?, 
                location = ?, 
                description = ?, 
                images = ?, 
                features = ?,
                updated_at = NOW()
            WHERE ad_id = ?
        ");
        $stmt->execute([
            $title,
            $price,
            $location,
            $description,
            implode(',', $all_images),
            json_encode($new_features),
            $ad_id
        ]);

        $success = "Anuncio actualizado correctamente";
        $images = $all_images;
        $features = $new_features;
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
    <title>Editar Anuncio - Red Social de Pisos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.0.0/css/all.min.css">
    <style>
        .image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .preview-item {
            position: relative;
            width: 120px;
            height: 120px;
        }
        .preview-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 5px;
        }
        .delete-checkbox {
            position: absolute;
            top: 5px;
            left: 5px;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0">Editar Anuncio</h2>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form action="edit-ad.php?id=<?= $ad_id ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="title" class="form-label">Título del Anuncio*</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?= htmlspecialchars($property['title']) ?>" required>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="price" class="form-label">Precio (€)*</label>
                            <input type="number" class="form-control" id="price" name="price" 
                                   min="0" step="0.01" value="<?= htmlspecialchars($property['price']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="location" class="form-label">Ubicación*</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?= htmlspecialchars($property['location']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descripción*</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="5" required><?= htmlspecialchars($property['description']) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Imágenes Actuales</label>
                        <div class="image-preview">
                            <?php foreach ($images as $image): ?>
                                <div class="preview-item">
                                    <img src="uploads/ads/<?= htmlspecialchars($image) ?>" 
                                         class="preview-img" alt="Imagen del anuncio">
                                    <div class="form-check delete-checkbox">
                                        <input class="form-check-input" type="checkbox" 
                                               name="delete_images[]" value="<?= htmlspecialchars($image) ?>" 
                                               id="delete_<?= htmlspecialchars($image) ?>">
                                        <label class="form-check-label" for="delete_<?= htmlspecialchars($image) ?>">
                                            Eliminar
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="new_images" class="form-label">Añadir Nuevas Imágenes</label>
                        <input type="file" class="form-control" id="new_images" name="new_images[]" 
                               multiple accept="image/*">
                        <small class="text-muted">Puedes seleccionar múltiples imágenes (máximo 5 en total)</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Características</label>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="furnished" 
                                           name="features[]" value="amueblado" <?= in_array('amueblado', $features) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="furnished">Amueblado</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="elevator" 
                                           name="features[]" value="ascensor" <?= in_array('ascensor', $features) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="elevator">Ascensor</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="parking" 
                                           name="features[]" value="parking" <?= in_array('parking', $features) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="parking">Parking</label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terrace" 
                                           name="features[]" value="terraza" <?= in_array('terraza', $features) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="terrace">Terraza</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">Guardar Cambios</button>
                        <a href="dashboard.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>