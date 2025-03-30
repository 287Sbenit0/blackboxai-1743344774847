<?php
require_once __DIR__ . '/../config.php';

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

try {
    // Get ad details to delete associated images
    $stmt = $pdo->prepare("SELECT images FROM ads WHERE ad_id = ? AND user_id = ?");
    $stmt->execute([$ad_id, $user_id]);
    $ad = $stmt->fetch();

    if (!$ad) {
        throw new Exception("Anuncio no encontrado o no tienes permiso para eliminarlo");
    }

    // Delete associated images
    $images = explode(',', $ad['images']);
    foreach ($images as $image) {
        @unlink(UPLOAD_DIR . 'ads/' . $image);
    }

    // Delete ad from database
    $stmt = $pdo->prepare("DELETE FROM ads WHERE ad_id = ? AND user_id = ?");
    $stmt->execute([$ad_id, $user_id]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("Error al eliminar el anuncio");
    }

    $_SESSION['success'] = "Anuncio eliminado correctamente";
    redirect('dashboard.php');

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    redirect('dashboard.php');
}