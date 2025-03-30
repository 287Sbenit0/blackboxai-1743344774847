<?php
require_once 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publicar Anuncio - Red Social de Pisos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome@6.0.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
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
        .remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container">
        <div class="form-container">
            <h2 class="text-center mb-4">Publicar Nuevo Anuncio</h2>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?= htmlspecialchars($_GET['success']) ?></div>
            <?php endif; ?>

            <form action="controllers/post-ad.php" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="title" class="form-label">Título del Anuncio*</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="price" class="form-label">Precio (€)*</label>
                        <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" required>
                    </div>
                    <div class="col-md-6">
                        <label for="location" class="form-label">Ubicación*</label>
                        <input type="text" class="form-control" id="location" name="location" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Descripción*</label>
                    <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                </div>

                <div class="mb-3">
                    <label for="images" class="form-label">Imágenes (Máximo 5)*</label>
                    <input type="file" class="form-control" id="images" name="images[]" multiple accept="image/*" required>
                    <small class="text-muted">Selecciona entre 1 y 5 imágenes del piso</small>
                    
                    <div class="image-preview mt-2" id="imagePreview"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Características</label>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="furnished" name="features[]" value="amueblado">
                                <label class="form-check-label" for="furnished">Amueblado</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="elevator" name="features[]" value="ascensor">
                                <label class="form-check-label" for="elevator">Ascensor</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="parking" name="features[]" value="parking">
                                <label class="form-check-label" for="parking">Parking</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="terrace" name="features[]" value="terraza">
                                <label class="form-check-label" for="terrace">Terraza</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">Publicar Anuncio</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Image preview functionality
        document.getElementById('images').addEventListener('change', function(e) {
            const previewContainer = document.getElementById('imagePreview');
            previewContainer.innerHTML = '';
            
            const files = e.target.files;
            if (files.length > 5) {
                alert('Solo puedes subir un máximo de 5 imágenes');
                this.value = '';
                return;
            }

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewItem = document.createElement('div');
                        previewItem.className = 'preview-item';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'preview-img';
                        
                        const removeBtn = document.createElement('button');
                        removeBtn.className = 'remove-btn';
                        removeBtn.innerHTML = '×';
                        removeBtn.onclick = function() {
                            previewItem.remove();
                            // TODO: Remove from file list
                        };
                        
                        previewItem.appendChild(img);
                        previewItem.appendChild(removeBtn);
                        previewContainer.appendChild(previewItem);
                    }
                    reader.readAsDataURL(file);
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>