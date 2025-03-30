<?php
require_once 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$conversation_id = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : null;
$recipient_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

try {
    // Get all conversations for the user
    $stmt = $pdo->prepare("
        SELECT 
            m.conversation_id,
            u.user_id,
            u.username,
            u.profile_pic,
            m.content,
            m.sent_at,
            m.read
        FROM (
            SELECT 
                conversation_id,
                MAX(message_id) as last_message_id
            FROM messages
            WHERE sender_id = ? OR receiver_id = ?
            GROUP BY conversation_id
        ) as last_messages
        JOIN messages m ON m.message_id = last_messages.last_message_id
        JOIN users u ON u.user_id = CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END
        ORDER BY m.sent_at DESC
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    $conversations = $stmt->fetchAll();

    // Get messages for selected conversation
    $messages = [];
    if ($conversation_id) {
        // Mark messages as read
        $pdo->prepare("UPDATE messages SET read = 1 WHERE conversation_id = ? AND receiver_id = ?")
            ->execute([$conversation_id, $user_id]);

        $stmt = $pdo->prepare("
            SELECT m.*, u.username, u.profile_pic
            FROM messages m
            JOIN users u ON u.user_id = m.sender_id
            WHERE m.conversation_id = ?
            ORDER BY m.sent_at ASC
        ");
        $stmt->execute([$conversation_id]);
        $messages = $stmt->fetchAll();
    }

    // Get recipient info if starting new conversation
    $recipient = null;
    if ($recipient_id && !$conversation_id) {
        $stmt = $pdo->prepare("SELECT user_id, username, profile_pic FROM users WHERE user_id = ?");
        $stmt->execute([$recipient_id]);
        $recipient = $stmt->fetch();
    }

} catch (Exception $e) {
    $_SESSION['error'] = "Error al cargar los mensajes: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajes - Red Social de Pisos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.0.0/css/all.min.css">
    <style>
        .conversation-list {
            max-height: 600px;
            overflow-y: auto;
        }
        .conversation-item {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .conversation-item:hover, .conversation-item.active {
            background-color: #f8f9fa;
        }
        .unread {
            font-weight: bold;
        }
        .message-container {
            max-height: 500px;
            overflow-y: auto;
        }
        .message-sent {
            background-color: #e3f2fd;
            border-radius: 15px 15px 0 15px;
        }
        .message-received {
            background-color: #f1f1f1;
            border-radius: 15px 15px 15px 0;
        }
        .profile-pic-sm {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Conversaciones</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="conversation-list">
                            <?php if (empty($conversations)): ?>
                                <div class="p-3 text-center text-muted">
                                    No tienes conversaciones
                                </div>
                            <?php else: ?>
                                <?php foreach ($conversations as $conv): ?>
                                    <a href="message.php?conversation_id=<?= $conv['conversation_id'] ?>" 
                                       class="d-block p-3 conversation-item <?= $conv['conversation_id'] == $conversation_id ? 'active' : '' ?>">
                                        <div class="d-flex align-items-center">
                                            <img src="uploads/<?= htmlspecialchars($conv['profile_pic']) ?>" 
                                                 class="profile-pic-sm me-3" alt="Foto de perfil">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0 <?= !$conv['read'] && $conv['user_id'] != $user_id ? 'unread' : '' ?>">
                                                    <?= htmlspecialchars($conv['username']) ?>
                                                </h6>
                                                <small class="text-muted text-truncate d-block">
                                                    <?= htmlspecialchars(substr($conv['content'], 0, 30)) ?>
                                                </small>
                                            </div>
                                            <small class="text-muted">
                                                <?= date('H:i', strtotime($conv['sent_at'])) ?>
                                            </small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <?php if ($conversation_id && !empty($messages)): ?>
                                    Conversación con <?= htmlspecialchars($messages[0]['username']) ?>
                                <?php elseif ($recipient): ?>
                                    Nuevo mensaje para <?= htmlspecialchars($recipient['username']) ?>
                                <?php else: ?>
                                    Selecciona una conversación
                                <?php endif; ?>
                            </h5>
                            <?php if ($conversation_id || $recipient_id): ?>
                                <a href="message.php" class="btn btn-sm btn-light">
                                    <i class="fas fa-times"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-body">
                        <?php if ($conversation_id || $recipient_id): ?>
                            <!-- Messages -->
                            <div class="message-container mb-3" id="messageContainer">
                                <?php if (!empty($messages)): ?>
                                    <?php foreach ($messages as $msg): ?>
                                        <div class="mb-3 d-flex <?= $msg['sender_id'] == $user_id ? 'justify-content-end' : 'justify-content-start' ?>">
                                            <div class="p-3 <?= $msg['sender_id'] == $user_id ? 'message-sent' : 'message-received' ?>">
                                                <div class="d-flex align-items-center mb-2">
                                                    <?php if ($msg['sender_id'] != $user_id): ?>
                                                        <img src="uploads/<?= htmlspecialchars($msg['profile_pic']) ?>" 
                                                             class="profile-pic-sm me-2" alt="Foto de perfil">
                                                    <?php endif; ?>
                                                    <small class="text-muted">
                                                        <?= date('d/m/Y H:i', strtotime($msg['sent_at'])) ?>
                                                    </small>
                                                </div>
                                                <p class="mb-0"><?= nl2br(htmlspecialchars($msg['content'])) ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-5 text-muted">
                                        <i class="fas fa-comments fa-3x mb-3"></i>
                                        <p>No hay mensajes en esta conversación</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Message Form -->
                            <form action="controllers/send-message.php" method="POST" class="mt-auto">
                                <input type="hidden" name="conversation_id" value="<?= $conversation_id ?>">
                                <input type="hidden" name="receiver_id" 
                                       value="<?= $conversation_id ? $messages[0]['sender_id'] : $recipient_id ?>">
                                <div class="input-group">
                                    <input type="text" name="content" class="form-control" placeholder="Escribe un mensaje..." required>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <p>Selecciona una conversación o inicia una nueva desde un anuncio</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Scroll to bottom of messages
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('messageContainer');
            if (container) {
                container.scrollTop = container.scrollHeight;
            }
        });
    </script>
</body>
</html>