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
$error = '';
$success = '';

// Récupérer las categorías disponibles
try {
    $stmt = $pdo->query("SELECT id_categorie, nom_categorie FROM categorie ORDER BY nom_categorie");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $categories = [];
}

// Récupérer el número de boutiques del producteur
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM boutique WHERE id_producteur = ?");
    $stmt->execute([$id_producteur]);
    $nb_boutiques = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch(PDOException $e) {
    $nb_boutiques = 0;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_boutique = trim($_POST['nom_boutique'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $id_categorie = intval($_POST['id_categorie'] ?? 0);
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
            // Gérer l'upload de l'image
            $image_path = null;
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
                    $image_path = $upload_dir . $filename;
                    
                    if (!move_uploaded_file($image['tmp_name'], $image_path)) {
                        $error = "Erreur lors de l'upload de l'image";
                    }
                }
            }
            
            if (empty($error)) {
                // 🔥 Insérer la boutique avec est_valide_par_admin = 0 (en attente de validation)
                $stmt = $pdo->prepare("
                    INSERT INTO boutique (id_producteur, nom_boutique, description, id_categorie, image, est_valide_par_admin, date_creation) 
                    VALUES (?, ?, ?, ?, ?, 0, NOW())
                ");
                $stmt->execute([$id_producteur, $nom_boutique, $description, $id_categorie, $image_path]);
                
                $id_boutique = $pdo->lastInsertId();
                
                // Rediriger avec un message indiquant que la boutique est en attente de validation
                header("Location: dashboard_producteur.php?boutique_creation=success&id=" . $id_boutique);
                exit();
            }
        } catch(PDOException $e) {
            $error = "Erreur lors de la création : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une boutique - GreenMarket</title>
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
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }
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
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }
        .form-group label .required {
            color: #dc3545;
        }
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
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        .form-group .help-text {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-top: 0.3rem;
        }
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
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-light);
            text-decoration: none;
            margin-bottom: 1rem;
            transition: color 0.3s;
            font-weight: 600;
        }
        .btn-back:hover { color: var(--primary); }
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .image-preview {
            margin-top: 0.5rem;
            max-width: 200px;
            border-radius: 10px;
            border: 2px dashed var(--border-color);
            padding: 0.5rem;
            display: none;
        }
        .image-preview img {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }
        .image-preview.show {
            display: block;
        }
        .badge-count {
            display: inline-block;
            background: var(--secondary);
            color: #fff;
            padding: 0.15rem 0.7rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        .info-box {
            background: var(--bg);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--gold);
        }
        .info-box p {
            font-size: 0.85rem;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .info-box i {
            color: var(--gold);
            font-size: 1.1rem;
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
        <h1><i class="bi bi-shop"></i> Créer une boutique</h1>
        <p>Donnez vie à votre projet artisanal en ouvrant votre boutique sur GreenMarket</p>
    </div>
</div>

<div class="container">

    <a href="dashboard_producteur.php" class="btn-back">
        <i class="bi bi-arrow-left"></i> Retour au tableau de bord
    </a>

    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="form-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:0.5rem;">
            <h2 style="font-family:'Playfair Display',serif;font-size:1.3rem;color:var(--primary);">
                <i class="bi bi-plus-circle"></i> Nouvelle boutique
            </h2>
            <span style="font-size:0.85rem;color:var(--text-light);">
                <i class="bi bi-shop"></i> Vous avez <strong><?php echo $nb_boutiques; ?></strong> boutique<?php echo $nb_boutiques > 1 ? 's' : ''; ?>
            </span>
        </div>

        <div class="info-box">
            <p>
                <i class="bi bi-info-circle"></i>
                <span>Votre boutique sera créée en <strong>attente de validation</strong> par un administrateur. 
                Vous serez notifié une fois qu'elle sera approuvée.</span>
            </p>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <!-- Nom de la boutique -->
            <div class="form-group">
                <label>
                    Nom de la boutique <span class="required">*</span>
                </label>
                <input type="text" name="nom_boutique" id="nom_boutique" 
                       placeholder="Ex: Atelier de Tissage d'Azilal"
                       value="<?php echo htmlspecialchars($_POST['nom_boutique'] ?? ''); ?>"
                       required minlength="3" maxlength="100">
                <div class="help-text">Choisissez un nom qui reflète l'identité de votre artisanat.</div>
            </div>

            <!-- Catégorie -->
            <div class="form-group">
                <label>
                    Catégorie principale <span class="required">*</span>
                </label>
                <select name="id_categorie" id="id_categorie" required>
                    <option value="">Sélectionnez une catégorie</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id_categorie']; ?>" 
                            <?php echo (isset($_POST['id_categorie']) && $_POST['id_categorie'] == $cat['id_categorie']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nom_categorie']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="help-text">La catégorie principale de votre boutique.</div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label>Description de la boutique</label>
                <textarea name="description" id="description" 
                          placeholder="Décrivez votre boutique, votre savoir-faire, vos valeurs..."
                          maxlength="500"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                <div class="help-text">
                    Une description attrayante donne envie aux clients de découvrir vos produits.
                </div>
            </div>

            <!-- Image -->
            <div class="form-group">
                <label>Image de la boutique</label>
                <input type="file" name="image" id="image" accept="image/*" onchange="previewImage(event)">
                <div class="help-text">Format recommandé : JPG ou PNG, 1200x800px minimum.</div>
                <div class="image-preview" id="imagePreview">
                    <img id="previewImg" src="#" alt="Aperçu">
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <i class="bi bi-check2-circle"></i> Créer ma boutique
            </button>
        </form>
    </div>

</div>

<?php include 'footer.php'; ?>

<script>
// Prévisualisation de l'image
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

// Validation du formulaire
document.querySelector('form').addEventListener('submit', function(e) {
    const nom = document.getElementById('nom_boutique').value.trim();
    const categorie = document.getElementById('id_categorie').value;
    
    if (nom.length < 3) {
        e.preventDefault();
        alert('Le nom de la boutique doit contenir au moins 3 caractères.');
        document.getElementById('nom_boutique').focus();
        return false;
    }
    
    if (!categorie) {
        e.preventDefault();
        alert('Veuillez sélectionner une catégorie.');
        document.getElementById('id_categorie').focus();
        return false;
    }
    
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i> Création en cours...';
});
</script>
</body>
</html>