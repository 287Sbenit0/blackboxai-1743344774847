<?php
require_once 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

try {
    // Get available subscription plans from database
    $stmt = $pdo->query("SELECT * FROM subscriptions ORDER BY price ASC");
    $plans = $stmt->fetchAll();

    // Get current user's subscription
    $stmt = $pdo->prepare("
        SELECT s.* FROM user_subscriptions us
        JOIN subscriptions s ON s.plan_id = us.plan_id
        WHERE us.user_id = ? AND us.expires_at > NOW()
        ORDER BY us.expires_at DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $current_plan = $stmt->fetch();

} catch (Exception $e) {
    $_SESSION['error'] = "Error al cargar los planes: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planes de Suscripción - Red Social de Pisos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.0.0/css/all.min.css">
    <style>
        .pricing-card {
            border-radius: 10px;
            transition: all 0.3s;
            border: 1px solid #dee2e6;
        }
        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .pricing-header {
            border-radius: 10px 10px 0 0;
            padding: 20px 0;
        }
        .price {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .feature-list li:last-child {
            border-bottom: none;
        }
        .feature-list li i {
            margin-right: 10px;
            color: #0d6efd;
        }
        .recommended {
            border: 2px solid #0d6efd;
            position: relative;
        }
        .recommended-badge {
            position: absolute;
            top: -10px;
            right: 20px;
            background: #0d6efd;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="text-center mb-5">
            <h1>Planes de Suscripción</h1>
            <p class="lead">Elige el plan que mejor se adapte a tus necesidades</p>
            <?php if ($current_plan): ?>
                <div class="alert alert-info">
                    Actualmente tienes el plan <strong><?= htmlspecialchars($current_plan['name']) ?></strong>
                </div>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <?php foreach ($plans as $plan): ?>
                <div class="col-md-4">
                    <div class="pricing-card h-100 <?= $plan['name'] === 'Pro' ? 'recommended' : '' ?>">
                        <?php if ($plan['name'] === 'Pro'): ?>
                            <div class="recommended-badge">RECOMENDADO</div>
                        <?php endif; ?>
                        <div class="pricing-header text-center bg-light">
                            <h3><?= htmlspecialchars($plan['name']) ?></h3>
                            <div class="price text-primary"><?= number_format($plan['price'], 2) ?>€<small class="text-muted">/mes</small></div>
                        </div>
                        <div class="card-body">
                            <ul class="feature-list">
                                <?php
                                $features = json_decode($plan['features'], true);
                                foreach ($features as $feature): ?>
                                    <li><i class="fas fa-check"></i> <?= htmlspecialchars($feature) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="text-center mt-4">
                                <?php if ($current_plan && $current_plan['plan_id'] === $plan['plan_id']): ?>
                                    <button class="btn btn-outline-primary w-100" disabled>Plan Actual</button>
                                <?php else: ?>
                                    <a href="controllers/subscribe.php?plan_id=<?= $plan['plan_id'] ?>" 
                                       class="btn btn-primary w-100">
                                        <?= $current_plan ? 'Cambiar a este plan' : 'Suscribirse' ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">Preguntas Frecuentes</h3>
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        ¿Puedo cancelar mi suscripción en cualquier momento?
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Sí, puedes cancelar tu suscripción en cualquier momento desde la sección de configuración de tu cuenta. 
                                        Tu suscripción permanecerá activa hasta el final del período de facturación actual.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        ¿Hay algún compromiso de permanencia?
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        No, no hay ningún compromiso de permanencia. Puedes cancelar tu suscripción cuando quieras sin penalización.
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                        ¿Qué métodos de pago aceptan?
                                    </button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        Aceptamos tarjetas de crédito/débito (Visa, MasterCard, American Express) y PayPal.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>