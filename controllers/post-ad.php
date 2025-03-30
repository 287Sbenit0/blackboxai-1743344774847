<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    redirect('index.php');
}

try {
    // Validate required fields
    $required = ['title', 'price', 'location', 'description'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo $field es obligatorio");
        }
    }

    // Validate price
    $price = (float)$_POST['price'];
    if ($price <= 0) {
        throw new Exception("El precio debe ser mayor que 0");
    }

    // Validate images
    if (empty($_FILES['images']) || $_FILES['images']['error'][0] === UPLOAD_ERR_NO_FILE) {
        throw new Exception("Debes subir al menos una imagen");
    }

    $images = $_FILES['images'];
    $uploaded_images = [];
    $features = isset($_POST['features']) ? $_POST['features'] : [];

    // Process each image
    foreach ($images['tmp_name'] as $key => $tmp_name) {
        if ($images['error'][$key] !== UPLOAD_ERR_OK) {
            continue;
        }

        // Validate file type and size
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp_name);
        if (!in_array($mime, ALLOWED_TYPES)) {
            throw new Exception("Solo se permiten imágenes JPEG o PNG");
        }

        if ($images['size'][$key] > MAX_FILE_SIZE) {
            throw new Exception("Una o más imágenes exceden el tamaño máximo de 5MB");
        }

        // Generate unique filename
        $ext = pathinfo($images['name'][$key], PATHINFO_EXTENSION);
        $filename = uniqid('ad_') . '.' . $ext;
        $destination = UPLOAD_DIR . 'ads/' . $filename;

        // Create ads directory if it doesn't exist
        if (!is_dir(UPLOAD_DIR . 'ads/')) {
            mkdir(UPLOAD_DIR . 'ads/', 0755, true);
        }

        if (move_uploaded_file($tmp_name, $destination)) {
            $uploaded_images[] = $filename;
        }

        // Limit to 5 images
        if (count($uploaded_images) >= 5) {
            break;
        }
    }

    if (empty($uploaded_images)) {
        throw new Exception("Error al subir las imágenes");
    }

    // Prepare data for database
    $data = [
        'user_id' => $_SESSION['user_id'],
        'title' => sanitize($_POST['title']),
        'price' => $price,
        'location' => sanitize($_POST['location']),
        'description' => sanitize($_POST['description']),
        'images' => implode(',', $uploaded_images),
        'features' => json_encode($features)
    ];

    // Insert into database
    $stmt = $pdo->prepare("
        INSERT INTO ads 
        (user_id, title, price, location, description, images, features) 
        VALUES 
        (:user_id, :title, :price, :location, :description, :images, :features)
    ");

    if (!$stmt->execute($data)) {
        throw new Exception("Error al guardar el anuncio en la base de datos");
    }

    redirect('post-ad.php?success=Anuncio publicado correctamente');
} catch (Exception $e) {
    redirect('post-ad.php?error=' . urlencode($e->getMessage()));
}