<?php
session_start();
include('connexion.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'producteur') {
    header("Location: signin.php");
    exit();
}

$theme = $_COOKIE['theme'] ?? 'light';
$id_producteur = $_SESSION['user_id'];
$id_boutique = intval($_GET['boutique'] ?? 0);

try {
    $stmt = $pdo->prepare("SELECT * FROM boutique WHERE id_boutique = ? AND id_producteur = ?");
    $stmt->execute([$id_boutique, $id_producteur]);
    $boutique = $stmt->fetch();
    
    if (!$boutique) {
        header("Location: dashboard_producteur.php?error=boutique_non_trouvee");
        exit();
    }
} catch(PDOException $e) {
    header("Location: dashboard_producteur.php?error=erreur");
    exit();
}

try {
    $stmt = $pdo->query("SELECT id_categorie, nom_categorie FROM categorie ORDER BY nom_categorie");
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    $categories = [];
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_produit = trim($_POST['nom_produit'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $prix_unitaire = floatval(str_replace(',', '.', $_POST['prix_unitaire'] ?? 0));
    $stock_quantite = intval($_POST['stock_quantite'] ?? 0);
    $id_categorie = intval($_POST['id_categorie'] ?? 0);
    $statut_publie = $_POST['statut_publie'] ?? 'Brouillon';
    $image = $_FILES['photo'] ?? null;
    
    if (empty($nom_produit)) {
        $error = "Veuillez saisir un nom pour le produit";
    } elseif (strlen($nom_produit) < 3) {
        $error = "Le nom doit contenir au moins 3 caractères";
    } elseif ($prix_unitaire <= 0) {
        $error = "Veuillez saisir un prix valide";
    } elseif ($stock_quantite < 0) {
        $error = "Le stock ne peut pas être négatif";
    } elseif ($id_categorie <= 0) {
        $error = "Veuillez sélectionner une catégorie";
    } else {
        try {
            $image_path = null;
            if ($image && $image['error'] === UPLOAD_ERR_OK) {
                $upload_dir = 'IMAGES/produits/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $extension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (!in_array($extension, $allowed)) {
                    $error = "Format d'image non autorisé. Utilisez JPG, PNG, GIF ou WEBP.";
                } else {
                    $filename = 'produit_' . time() . '_' . uniqid() . '.' . $extension;
                    $image_path = $upload_dir . $filename;
                    
                    if (!move_uploaded_file($image['tmp_name'], $image_path)) {
                        $error = "Erreur lors de l'upload de l'image";
                    }
                }
            }
            
            if (empty($error)) {
                $stmt = $pdo->prepare("
                    INSERT INTO produit (
                        id_categorie, id_boutique, nom_produit, description, 
                        prix_unitaire, stock_quantite, statut_publie, 
                        est_valide_par_admin, date_creation, photo_url
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW(), ?)
                ");
                $stmt->execute([
                    $id_categorie,
                    $id_boutique,
                    $nom_produit,
                    $description,
                    $prix_unitaire,
                    $stock_quantite,
                    $statut_publie,
                    $image_path
                ]);
                
                $id_produit = $pdo->lastInsertId();
                
                header("Location: gerer-boutique.php?id=" . $id_boutique . "&product_added=1");
                exit();
            }
        } catch(PDOException $e) {
            $error = "Erreur lors de l'ajout du produit : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un produit - GreenMarket</title>
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
        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-group .help-text {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-top: 0.3rem;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
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

        #toast {
            position: fixed;
            bottom: 28px;
            right: 28px;
            background: var(--primary);
            color: #fff;
            padding: 14px 22px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.95rem;
            z-index: 9999;
            transform: translateY(80px);
            opacity: 0;
            transition: 0.4s cubic-bezier(.22,1,.36,1);
            max-width: 320px;
        }
        #toast.show { transform: translateY(0); opacity: 1; }
        .boutique-info {
            background: var(--bg);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .boutique-info .name { font-weight: 700; }
        .boutique-info .badge {
            background: var(--secondary);
            color: #fff;
            padding: 0.2rem 0.8rem;
            border-radius: 999px;
            font-size: 0.75rem;
        }
        @media (max-width: 640px) {
            .page-header { padding: 1.5rem; }
            .page-header h1 { font-size: 1.5rem; }
            .form-card { padding: 1.5rem; }
            .container { padding: 1rem; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div id="toast"></div>

<div class="page-header">
    <div style="max-width:800px;margin:0 auto;">
        <h1><i class="bi bi-plus-circle"></i> Ajouter un produit</h1>
        <p>Ajoutez un nouveau produit à votre boutique</p>
    </div>
</div>

<div class="container">

    <a href="gerer-boutique.php?id=<?php echo $id_boutique; ?>" class="btn-back">
        <i class="bi bi-arrow-left"></i> Retour à la boutique
    </a>

    <?php if ($error): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        showToast('❌ <?php echo addslashes(htmlspecialchars($error)); ?>', '#c0392b');
    });
    </script>
    <?php endif; ?>

    <div class="form-card">
        <div class="boutique-info">
            <span class="name"><i class="bi bi-shop"></i> <?php echo htmlspecialchars($boutique['nom_boutique']); ?></span>
            <span class="badge">Ajout d'un produit</span>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Nom du produit <span class="required">*</span></label>
                <input type="text" name="nom_produit" 
                       placeholder="Ex: Tajine décoratif en céramique"
                       value="<?php echo htmlspecialchars($_POST['nom_produit'] ?? ''); ?>"
                       required minlength="3" maxlength="100">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Prix (DH) <span class="required">*</span></label>
                    <input type="number" name="prix_unitaire" 
                           placeholder="0.00" step="0.01" min="0.01"
                           value="<?php echo htmlspecialchars($_POST['prix_unitaire'] ?? ''); ?>"
                           required>
                </div>

                <div class="form-group">
                    <label>Quantité en stock <span class="required">*</span></label>
                    <input type="number" name="stock_quantite" 
                           placeholder="0" min="0"
                           value="<?php echo htmlspecialchars($_POST['stock_quantite'] ?? 0); ?>"
                           required>
                </div>
            </div>

            <div class="form-group">
                <label>Catégorie <span class="required">*</span></label>
                <select name="id_categorie" required>
                    <option value="">Sélectionnez une catégorie</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id_categorie']; ?>" 
                            <?php echo (isset($_POST['id_categorie']) && $_POST['id_categorie'] == $cat['id_categorie']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nom_categorie']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Description du produit</label>
                <textarea name="description" rows="4" 
                          placeholder="Décrivez votre produit, ses matériaux, ses dimensions..."
                          maxlength="500"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>Photo du produit</label>
                <input type="file" name="photo" id="photo" accept="image/*" onchange="previewImage(event)">
                <div class="help-text">Format recommandé : JPG ou PNG, 800x800px minimum.</div>
                <div class="image-preview" id="imagePreview">
                    <img id="previewImg" src="#" alt="Aperçu">
                </div>
            </div>

            <div class="form-group">
                <label>Statut de publication</label>
                <select name="statut_publie">
                    <option value="Brouillon">Brouillon (non visible)</option>
                    <option value="Publié" selected>Publié (visible)</option>
                    <option value="Suspendu">Suspendu</option>
                </select>
                <div class="help-text">
                    <i class="bi bi-info-circle"></i>
                    Les produits en "Brouillon" ne sont pas visibles par les clients.
                    Les produits "Publiés" doivent être validés par l'administrateur.
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="bi bi-check2-circle"></i> Ajouter le produit
            </button>
        </form>
    </div>

</div>

<?php include 'footer.php'; ?>

<script>
function showToast(msg, bg) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.style.background = bg || 'var(--primary)';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

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
</script>
</body>
</html>