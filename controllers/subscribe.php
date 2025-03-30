<?php
require_once __DIR__ . '/../config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

// Check if plan ID is provided
if (!isset($_GET['plan_id'])) {
    redirect('subscription.php');
}

$plan_id = (int)$_GET['plan_id'];
$user_id = $_SESSION['user_id'];

try {
    // Verify plan exists
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE plan_id = ?");
    $stmt->execute([$plan_id]);
    $plan = $stmt->fetch();

    if (!$plan) {
        throw new Exception("Plan de suscripción no válido");
    }

    // Calculate subscription dates
    $start_date = date('Y-m-d H:i:s');
    $end_date = date('Y-m-d H:i:s', strtotime('+1 month'));

    // Create subscription (in a real app, this would be after payment processing)
    $pdo->beginTransaction();

    // Deactivate any current subscriptions
    $stmt = $pdo->prepare("UPDATE user_subscriptions SET active = FALSE WHERE user_id = ?");
    $stmt->execute([$user_id]);

    // Create new subscription
    $stmt = $pdo->prepare("
        INSERT INTO user_subscriptions 
        (user_id, plan_id, starts_at, expires_at, active)
        VALUES 
        (?, ?, ?, ?, TRUE)
    ");
    $stmt->execute([$user_id, $plan_id, $start_date, $end_date]);

    $pdo->commit();

    $_SESSION['success'] = "¡Suscripción activada correctamente! Ahora tienes acceso a todas las características del plan " . htmlspecialchars($plan['name']);
    redirect('subscription.php');

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = $e->getMessage();
    redirect('subscription.php');
}