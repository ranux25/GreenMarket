<?php
session_start();
include('connexion.php');

// Verificar que el usuario esté logueado y sea producteur
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'producteur') {
    header("Location: signin.php");
    exit();
}

$theme = $_COOKIE['theme'] ?? 'light';
$id_producteur = $_SESSION['user_id'];
$id_boutique = intval($_GET['id'] ?? 0);

// Verificar que la boutique pertenece al producteur
try {
    $stmt = $pdo->prepare("
        SELECT b.*, c.nom_categorie 
        FROM boutique b
        LEFT JOIN categorie c ON b.id_categorie = c.id_categorie
        WHERE b.id_boutique = ? AND b.id_producteur = ?
    ");
    $stmt->execute([$id_boutique, $id_producteur]);
    $boutique = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$boutique) {
        header("Location: dashboard_producteur.php?error=boutique_non_trouvee");
        exit();
    }
} catch(PDOException $e) {
    header("Location: dashboard_producteur.php?error=erreur");
    exit();
}

// Récupérer toutes les catégories disponibles
try {
    $stmt = $pdo->query("SELECT id_categorie, nom_categorie FROM categorie ORDER BY nom_categorie");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $categories = [];
}

$error = '';
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_boutique = trim($_POST['nom_boutique'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $id_categorie = intval($_POST['id_categorie'] ?? 0);
    $supprimer_image = isset($_POST['supprimer_image']);
    $image = $_FILES['image'] ?? null;
    
    // Validation
    if (empty($nom_boutique)) {
        $error = "Veuillez saisir un nom pour votre boutique";
    } elseif (strlen($nom_boutique) < 3) {
        $error = "Le nom doit contenir au moins 3 caractères";
    } elseif ($id_categorie <= 0) {
        $error = "Veuillez sélectionner une catégorie";
    } else {
        try {
            $image_path = $boutique['image'] ?? null;
            
            // Supprimer l'image si demandé
            if ($supprimer_image && !empty($image_path)) {
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
                $image_path = null;
            }
            
            // Gérer l'upload de la nouvelle image
            if ($image && $image['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'IMAGES/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $extension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (!in_array($extension, $allowed)) {
                    $error = "Format d'image non autorisé. Utilisez JPG, PNG, GIF ou WEBP.";
                } else {
                    $filename = 'boutique_' . time() . '_' . uniqid() . '.' . $extension;
                    $new_image_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($image['tmp_name'], $new_image_path)) {
                        // Supprimer l'ancienne image si elle existe
                        if (!empty($image_path) && file_exists($image_path)) {
                            unlink($image_path);
                        }
                        $image_path = $new_image_path;
                    } else {
                        $error = "Erreur lors de l'upload de l'image";
                    }
                }
            }
            
            if (empty($error)) {
                // Mettre à jour la boutique
                $stmt = $pdo->prepare("
                    UPDATE boutique 
                    SET nom_boutique = ?, description = ?, id_categorie = ?, image = ?
                    WHERE id_boutique = ? AND id_producteur = ?
                ");
                $stmt->execute([$nom_boutique, $description, $id_categorie, $image_path, $id_boutique, $id_producteur]);
                
                header("Location: gerer-boutique.php?id=" . $id_boutique . "&updated=1");
                exit();
            }
        } catch(PDOException $e) {
            $error = "Erreur lors de la modification : " . $e->getMessage();
        }
    }
}

// Fonction pour vérifier si une image existe
function hasImage($boutique) {
    return isset($boutique['image']) && !empty($boutique['image']) && file_exists($boutique['image']);
}

// Vérifier si la boutique a une image
$has_image = hasImage($boutique);
$image_url = $has_image ? htmlspecialchars($boutique['image']) : null;

// Obtenir le nom de la catégorie actuelle
$categorie_actuelle = $boutique['nom_categorie'] ?? 'Non catégorisé';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la boutique - GreenMarket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #5D0D18;
            --primary-light: #7a1020;
            --secondary: #9FB2AC;
            --gold: #c07a1a;
            --bg: #FFF9EB;
            --bg-card: #ffffff;
            --text-dark: #2C2C2C;
            --text-light: #6B6B6B;
            --border-color: #e8ddd0;
            --shadow-color: rgba(93,13,24,0.08);
        }
        [data-theme="dark"] {
            --primary: #8a6048;
            --secondary: #6d4c3a;
            --bg: #2c241e;
            --bg-card: #3d3229;
            --text-dark: #f0e6d8;
            --text-light: #b8a896;
            --border-color: #5a4a3a;
        }
        body {
            background: var(--bg);
            color: var(--text-dark);
            font-family: 'Lato', sans-serif;
            min-height: 100vh;
        }
        .container { max-width: 800px; margin: 0 auto; padding: 2rem 1.5rem; }
        .page-header {
            background: var(--primary);
            padding: 2rem 2.5rem;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
        }
        .page-header p {
            color: rgba(255,255,255,0.7);
            margin-top: 0.3rem;
        }
        .form-card {
            background: var(--bg-card);
            border-radius: 20px;
            border: 1.5px solid var(--border-color);
            padding: 2rem;
            box-shadow: 0 4px 16px var(--shadow-color);
            margin-top: 2rem;
            transition: all 0.3s;
        }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }
        .form-group label .required { color: #dc3545; }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid var(--border-color);
            border-radius: 10px;
            background: var(--bg);
            color: var(--text-dark);
            font-family: 'Lato', sans-serif;
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
        }
        .form-group textarea { resize: vertical; min-height: 100px; }
        .form-group .help-text {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-top: 0.3rem;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-light);
            text-decoration: none;
            margin-bottom: 1rem;
            transition: color 0.3s;
        }
        .btn-back:hover { color: var(--primary); }
        .btn-submit {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 0.9rem 2rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
        }
        .btn-submit:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(93,13,24,0.3);
        }
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .image-preview {
            margin-top: 0.5rem;
            max-width: 200px;
            border-radius: 10px;
            border: 2px dashed var(--border-color);
            padding: 0.5rem;
            display: none;
        }
        .image-preview img { width: 100%; height: auto; border-radius: 8px; }
        .image-preview.show { display: block; }
        .current-image {
            margin: 0.5rem 0;
            padding: 0.5rem;
            border-radius: 10px;
            background: var(--bg);
        }
        .current-image img { max-width: 150px; border-radius: 8px; }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .checkbox-group label {
            cursor: pointer;
            margin: 0;
            font-weight: 400;
        }
        .categorie-actuelle {
            display: inline-block;
            background: var(--bg);
            padding: 0.3rem 0.8rem;
            border-radius: 999px;
            font-size: 0.85rem;
            color: var(--text-light);
            border: 1px solid var(--border-color);
        }
        @media (max-width: 640px) {
            .page-header { padding: 1.5rem; }
            .page-header h1 { font-size: 1.5rem; }
            .form-card { padding: 1.5rem; }
            .container { padding: 1rem; }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="page-header">
    <div style="max-width:800px;margin:0 auto;">
        <h1><i class="bi bi-pencil"></i> Modifier la boutique</h1>
        <p>Mettez à jour les informations de votre boutique</p>
    </div>
</div>

<div class="container">

    <a href="gerer-boutique.php?id=<?php echo $id_boutique; ?>" class="btn-back">
        <i class="bi bi-arrow-left"></i> Retour à la boutique
    </a>

    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="form-card">
        <h2 style="font-family:'Playfair Display',serif;font-size:1.3rem;color:var(--primary);margin-bottom:1.5rem;">
            <i class="bi bi-shop"></i> <?php echo htmlspecialchars($boutique['nom_boutique'] ?? 'Ma boutique'); ?>
        </h2>

        <form method="POST" enctype="multipart/form-data">
            <!-- Nom de la boutique -->
            <div class="form-group">
                <label>Nom de la boutique <span class="required">*</span></label>
                <input type="text" name="nom_boutique" 
                       value="<?php echo htmlspecialchars($boutique['nom_boutique'] ?? ''); ?>"
                       required minlength="3" maxlength="100">
            </div>

            <!-- Catégorie -->
            <div class="form-group">
                <label>Catégorie principale <span class="required">*</span></label>
                <select name="id_categorie" required>
                    <option value="">Sélectionnez une catégorie</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id_categorie']; ?>" 
                            <?php echo ($boutique['id_categorie'] ?? 0) == $cat['id_categorie'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nom_categorie']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($categorie_actuelle) && $categorie_actuelle != 'Non catégorisé'): ?>
                <div class="help-text">
                    <span class="categorie-actuelle">
                        <i class="bi bi-tag"></i> Catégorie actuelle : <?php echo htmlspecialchars($categorie_actuelle); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label>Description de la boutique</label>
                <textarea name="description" rows="4" maxlength="500"><?php echo htmlspecialchars($boutique['description'] ?? ''); ?></textarea>
                <div class="help-text">Une description attrayante donne envie aux clients de découvrir vos produits.</div>
            </div>

            <!-- Image actuelle -->
            <?php if ($has_image && $image_url): ?>
            <div class="form-group">
                <label>Image actuelle</label>
                <div class="current-image">
                    <img src="<?php echo $image_url; ?>" alt="Image de la boutique">
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" name="supprimer_image" id="supprimer_image">
                    <label for="supprimer_image">Supprimer cette image</label>
                </div>
            </div>
            <?php endif; ?>

            <!-- Nouvelle image -->
            <div class="form-group">
                <label><?php echo $has_image ? 'Changer l\'image' : 'Image de la boutique'; ?></label>
                <input type="file" name="image" id="image" accept="image/*" onchange="previewImage(event)">
                <div class="help-text">Format recommandé : JPG ou PNG, 1200x800px minimum.</div>
                <div class="image-preview" id="imagePreview">
                    <img id="previewImg" src="#" alt="Aperçu">
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="bi bi-check2-circle"></i> Enregistrer les modifications
            </button>
        </form>
    </div>

</div>

<?php include 'footer.php'; ?>

<script>
function previewImage(event) {
    const preview = document.getElementById('imagePreview');
    const img = document.getElementById('previewImg');
    
    if (event.target.files && event.target.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            preview.classList.add('show');
        };
        reader.readAsDataURL(event.target.files[0]);
    } else {
        preview.classList.remove('show');
    }
}

// Si on coche "supprimer l'image", on désactive l'upload
const supprimerImageCheckbox = document.getElementById('supprimer_image');
if (supprimerImageCheckbox) {
    supprimerImageCheckbox.addEventListener('change', function() {
        const fileInput = document.getElementById('image');
        if (this.checked) {
            fileInput.disabled = true;
            fileInput.value = '';
            document.getElementById('imagePreview').classList.remove('show');
        } else {
            fileInput.disabled = false;
        }
    });
}
</script>
</body>
</html>