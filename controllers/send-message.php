<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    redirect('index.php');
}

try {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = (int)$_POST['receiver_id'];
    $content = trim($_POST['content']);
    $conversation_id = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : null;
    $ad_id = isset($_GET['ad_id']) ? (int)$_GET['ad_id'] : null;

    // Validate input
    if (empty($content)) {
        throw new Exception("El mensaje no puede estar vacío");
    }

    if ($receiver_id <= 0) {
        throw new Exception("Destinatario inválido");
    }

    // Check if users exist
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id IN (?, ?)");
    $stmt->execute([$sender_id, $receiver_id]);
    if ($stmt->rowCount() !== 2) {
        throw new Exception("Usuario no encontrado");
    }

    // Create new conversation if needed
    if (!$conversation_id) {
        $pdo->beginTransaction();
        
        // Create conversation
        $stmt = $pdo->prepare("INSERT INTO conversations (created_at) VALUES (NOW())");
        $stmt->execute();
        $conversation_id = $pdo->lastInsertId();

        // Add participants
        $stmt = $pdo->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?), (?, ?)");
        $stmt->execute([$conversation_id, $sender_id, $conversation_id, $receiver_id]);

        $pdo->commit();
    }

    // Insert message
    $stmt = $pdo->prepare("
        INSERT INTO messages 
        (conversation_id, sender_id, receiver_id, content, sent_at, ad_id)
        VALUES 
        (?, ?, ?, ?, NOW(), ?)
    ");
    $stmt->execute([$conversation_id, $sender_id, $receiver_id, $content, $ad_id]);

    // Redirect back to conversation
    redirect("message.php?conversation_id=$conversation_id");

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = $e->getMessage();
    if ($conversation_id) {
        redirect("message.php?conversation_id=$conversation_id");
    } else {
        redirect("message.php?user_id=$receiver_id");
    }
}