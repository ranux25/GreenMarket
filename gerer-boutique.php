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

// Si no hay ID de boutique, mostrar error
if ($id_boutique <= 0) {
    header("Location: dashboard_producteur.php?error=id_invalide");
    exit();
}

// Vérifier que la boutique appartient au producteur
try {
    $stmt = $pdo->prepare("
        SELECT b.*, p.nom_entreprise, c.nom_categorie, c.id_categorie as cat_id
        FROM boutique b
        JOIN producteur p ON b.id_producteur = p.id_producteur
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
    header("Location: dashboard_producteur.php?error=erreur_bd");
    exit();
}

// Récupérer les produits de la boutique
try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.nom_categorie 
        FROM produit p
        LEFT JOIN categorie c ON p.id_categorie = c.id_categorie
        WHERE p.id_boutique = ?
        ORDER BY p.date_creation DESC
    ");
    $stmt->execute([$id_boutique]);
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $produits = [];
}

// Récupérer les catégories pour le formulaire
try {
    $stmt = $pdo->query("SELECT id_categorie, nom_categorie FROM categorie ORDER BY nom_categorie");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $categories = [];
}

// Compter les produits
$total_produits = count($produits);

// Vérifier les messages de succès
$success_message = '';
if (isset($_GET['product_added'])) {
    $success_message = '✅ Produit ajouté avec succès !';
} elseif (isset($_GET['product_updated'])) {
    $success_message = '✅ Produit modifié avec succès !';
} elseif (isset($_GET['product_deleted'])) {
    $success_message = '✅ Produit supprimé avec succès !';
} elseif (isset($_GET['updated'])) {
    $success_message = '✅ Boutique modifiée avec succès !';
}

// Fonction pour vérifier si une image existe
function hasImage($boutique) {
    return isset($boutique['image']) && !empty($boutique['image']) && file_exists($boutique['image']);
}

// Fonction pour obtenir l'URL de l'image
function getImageUrl($boutique) {
    if (hasImage($boutique)) {
        return htmlspecialchars($boutique['image']);
    }
    return null;
}

// Obtenir le nom de la catégorie
$categorie_nom = $boutique['nom_categorie'] ?? 'Non catégorisé';
if (empty($categorie_nom) || $categorie_nom == 'Non catégorisé') {
    // Si la catégorie n'est pas trouvée, essayer de la récupérer directement
    if (!empty($boutique['id_categorie'])) {
        try {
            $stmt = $pdo->prepare("SELECT nom_categorie FROM categorie WHERE id_categorie = ?");
            $stmt->execute([$boutique['id_categorie']]);
            $cat = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($cat) {
                $categorie_nom = $cat['nom_categorie'];
            }
        } catch(PDOException $e) {
            $categorie_nom = 'Non catégorisé';
        }
    }
}

// Vérifier si l'image existe
$has_image = hasImage($boutique);
$image_url = getImageUrl($boutique);
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title><?php echo htmlspecialchars($boutique['nom_boutique'] ?? 'Boutique'); ?> - GreenMarket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #5D0D18;
            --primary-light: #7a1020;
            --secondary: #9FB2AC;
            --secondary-dark: #8aa09a;
            --gold: #c07a1a;
            --bg: #FFF9EB;
            --bg-light: #f5f0e8;
            --bg-card: #ffffff;
            --text-dark: #2C2C2C;
            --text-light: #6B6B6B;
            --border-color: #e8ddd0;
            --shadow-color: rgba(93,13,24,0.08);
            --shadow-hover: rgba(93,13,24,0.14);
            --danger: #c62828;
            --warning: #f57c00;
        }
        [data-theme="dark"] {
            --primary: #8a6048;
            --secondary: #6d4c3a;
            --bg: #2c241e;
            --bg-light: #3d3229;
            --bg-card: #3d3229;
            --text-dark: #f0e6d8;
            --text-light: #b8a896;
            --border-color: #5a4a3a;
            --shadow-color: rgba(0,0,0,0.3);
            --shadow-hover: rgba(0,0,0,0.4);
            --danger: #ef5350;
            --warning: #ffa726;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: var(--bg);
            color: var(--text-dark);
            font-family: 'Lato', sans-serif;
            min-height: 100vh;
            transition: background 0.3s, color 0.3s;
        }
        .container {
            max-width: 1200px;
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
            padding: 1rem 1.2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        [data-theme="dark"] .alert-success { background: #1a3d2a; color: #81c784; border-color: #2d5a3d; }
        [data-theme="dark"] .alert-error { background: #3d1a1a; color: #ef9a9a; border-color: #5a2d2d; }

        .store-card {
            background: var(--bg-card);
            border-radius: 20px;
            border: 1.5px solid var(--border-color);
            padding: 2rem;
            box-shadow: 0 4px 16px var(--shadow-color);
            margin-bottom: 2rem;
            transition: all 0.3s;
        }
        .store-card:hover {
            box-shadow: 0 8px 28px var(--shadow-hover);
        }
        .store-header {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
            flex-wrap: wrap;
        }
        .store-image {
            width: 150px;
            height: 150px;
            border-radius: 15px;
            object-fit: cover;
            background: var(--bg);
            border: 2px solid var(--border-color);
            flex-shrink: 0;
        }
        .store-image-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 15px;
            background: var(--bg-light);
            border: 2px dashed var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--text-light);
            flex-shrink: 0;
        }
        .store-info { flex: 1; }
        .store-info h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 0.3rem;
        }
        .store-info .store-meta {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        .store-info .store-meta span {
            font-size: 0.85rem;
            color: var(--text-light);
        }
        .store-info .store-meta i { margin-right: 0.3rem; }
        .store-info .store-desc {
            margin-top: 0.5rem;
            color: var(--text-light);
            line-height: 1.6;
        }
        .store-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        .btn-outline {
            padding: 0.5rem 1.2rem;
            border-radius: 999px;
            border: 2px solid var(--primary);
            background: transparent;
            color: var(--primary);
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-outline:hover {
            background: var(--primary);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-hover);
        }
        .btn-primary {
            padding: 0.5rem 1.2rem;
            border-radius: 999px;
            border: none;
            background: var(--primary);
            color: #fff;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-hover);
        }
        .btn-danger {
            padding: 0.5rem 1.2rem;
            border-radius: 999px;
            border: none;
            background: var(--danger);
            color: #fff;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-danger:hover {
            opacity: 0.85;
            transform: translateY(-2px);
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .section-title .count {
            font-size: 0.8rem;
            color: var(--text-light);
            font-weight: 400;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.5rem;
        }
        .product-card {
            background: var(--bg-card);
            border-radius: 15px;
            border: 1.5px solid var(--border-color);
            overflow: hidden;
            transition: all 0.3s;
            box-shadow: 0 2px 8px var(--shadow-color);
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px var(--shadow-hover);
        }
        .product-img {
            height: 180px;
            width: 100%;
            object-fit: cover;
            background: var(--bg);
        }
        .product-body { padding: 1rem; }
        .product-name {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--text-dark);
            margin-bottom: 0.3rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .product-price {
            color: var(--primary);
            font-weight: 700;
            font-size: 1rem;
        }
        [data-theme="dark"] .product-price { color: var(--gold); }
        .product-stock {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-top: 0.2rem;
        }
        .product-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }
        .btn-sm {
            padding: 0.25rem 0.6rem;
            font-size: 0.7rem;
            border-radius: 999px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }
        .btn-sm-edit {
            background: var(--secondary);
            color: #fff;
        }
        .btn-sm-edit:hover { background: var(--secondary-dark); }
        .btn-sm-delete {
            background: var(--danger);
            color: #fff;
        }
        .btn-sm-delete:hover { opacity: 0.85; }

        .badge {
            display: inline-block;
            padding: 0.15rem 0.6rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        [data-theme="dark"] .badge-success { background: #1a3d2a; color: #81c784; }
        [data-theme="dark"] .badge-warning { background: #3d2a1a; color: #ffb74d; }
        [data-theme="dark"] .badge-danger { background: #3d1a1a; color: #ef9a9a; }
        [data-theme="dark"] .badge-info { background: #1a2a3d; color: #64b5f6; }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-light);
        }
        .empty-state i {
            font-size: 3rem;
            display: block;
            margin-bottom: 1rem;
            opacity: 0.4;
        }
        .empty-state h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-card {
            background: var(--bg);
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            border: 1px solid var(--border-color);
        }
        .stat-card .number {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary);
        }
        [data-theme="dark"] .stat-card .number { color: var(--gold); }
        .stat-card .label {
            font-size: 0.75rem;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .loader {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .page-header { padding: 1.5rem; }
            .page-header h1 { font-size: 1.5rem; }
            .store-header { flex-direction: column; align-items: center; text-align: center; }
            .store-image, .store-image-placeholder { width: 120px; height: 120px; }
            .store-info .store-meta { justify-content: center; }
            .store-actions { justify-content: center; }
            .products-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); }
            .container { padding: 1rem; }
        }
        @media (max-width: 480px) {
            .products-grid { grid-template-columns: 1fr 1fr; }
            .product-img { height: 140px; }
            .store-image, .store-image-placeholder { width: 100px; height: 100px; }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="page-header">
    <div style="max-width:1200px;margin:0 auto;">
        <h1><i class="bi bi-shop"></i> <?php echo htmlspecialchars($boutique['nom_boutique'] ?? 'Ma boutique'); ?></h1>
        <p>Gérez vos produits et les informations de votre boutique</p>
    </div>
</div>

<div class="container">

    <a href="dashboard_producteur.php" class="btn-back">
        <i class="bi bi-arrow-left"></i> Retour au tableau de bord
    </a>

    <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="number"><?php echo $total_produits; ?></div>
            <div class="label">Produits</div>
        </div>
        <div class="stat-card">
            <div class="number">
                <?php 
                if (!empty($boutique['date_creation']) && $boutique['date_creation'] != '0000-00-00 00:00:00') {
                    echo date('d/m/Y', strtotime($boutique['date_creation']));
                } else {
                    echo 'N/A';
                }
                ?>
            </div>
            <div class="label">Créée le</div>
        </div>
        <div class="stat-card">
            <div class="number">
                <?php 
                $publies = 0;
                foreach ($produits as $p) {
                    if (($p['statut_publie'] ?? '') == 'Publié' && ($p['est_valide_par_admin'] ?? 0) == 1) $publies++;
                }
                echo $publies;
                ?>
            </div>
            <div class="label">Publiés</div>
        </div>
    </div>

    <!-- Informations de la boutique -->
    <div class="store-card">
        <div class="store-header">
            <!-- Affichage de l'image -->
            <?php if ($has_image && $image_url): ?>
                <img src="<?php echo $image_url; ?>" 
                     alt="<?php echo htmlspecialchars($boutique['nom_boutique'] ?? 'Boutique'); ?>" 
                     class="store-image"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                <div class="store-image-placeholder" style="display:none;">
                    <i class="bi bi-shop"></i>
                </div>
            <?php else: ?>
                <div class="store-image-placeholder">
                    <i class="bi bi-shop"></i>
                </div>
            <?php endif; ?>
            
            <div class="store-info">
                <h2><?php echo htmlspecialchars($boutique['nom_boutique'] ?? 'Ma boutique'); ?></h2>
                <div class="store-meta">
                    <span><i class="bi bi-tag"></i> 
                        <?php echo htmlspecialchars($categorie_nom); ?>
                    </span>
                    <?php if (!empty($boutique['date_creation']) && $boutique['date_creation'] != '0000-00-00 00:00:00'): ?>
                    <span><i class="bi bi-calendar3"></i> Créée le <?php echo date('d/m/Y', strtotime($boutique['date_creation'])); ?></span>
                    <?php endif; ?>
                    <span><i class="bi bi-box-seam"></i> <?php echo $total_produits; ?> produit<?php echo $total_produits > 1 ? 's' : ''; ?></span>
                    <span><i class="bi bi-building"></i> <?php echo htmlspecialchars($boutique['nom_entreprise'] ?? ''); ?></span>
                </div>
                <?php if (!empty($boutique['description'])): ?>
                    <p class="store-desc"><?php echo htmlspecialchars($boutique['description']); ?></p>
                <?php endif; ?>
                <div class="store-actions">
                <!-- Badge de estado -->
                <div style="display:flex;align-items:center;gap:0.5rem;width:100%;margin-bottom:0.5rem;">
                    <?php if (isset($boutique['est_valide_par_admin']) && $boutique['est_valide_par_admin'] == 1): ?>
                        <span class="badge badge-success" style="background:#d4edda;color:#155724;padding:0.3rem 0.8rem;border-radius:999px;font-weight:600;">
                            <i class="bi bi-check-circle"></i> ✅ Boutique validée
                        </span>
                    <?php else: ?>
                        <span class="badge badge-warning" style="background:#fff3cd;color:#856404;padding:0.3rem 0.8rem;border-radius:999px;font-weight:600;">
                            <i class="bi bi-clock"></i> ⏳ En attente de validation
                        </span>
                    <?php endif; ?>
                </div>
                
                <!-- Botones de acción -->
                <a href="modifier-boutique.php?id=<?php echo $id_boutique; ?>" class="btn-outline">
                    <i class="bi bi-pencil"></i> Modifier
                </a>
                <a href="ajouter-produit.php?boutique=<?php echo $id_boutique; ?>" class="btn-primary">
                    <i class="bi bi-plus-circle"></i> Ajouter un produit
                </a>
                <button onclick="supprimerBoutique(<?php echo $id_boutique; ?>, '<?php echo htmlspecialchars($boutique['nom_boutique'] ?? 'boutique'); ?>')" class="btn-danger">
                    <i class="bi bi-trash3"></i> Supprimer
                </button>
            </div>
            </div>
        </div>
    </div>

    <!-- Liste des produits -->
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;margin-bottom:1rem;">
        <h3 class="section-title" style="margin-bottom:0;">
            <i class="bi bi-box-seam"></i> Mes produits
            <span class="count">(<?php echo $total_produits; ?> produits)</span>
        </h3>
    </div>

    <?php if (empty($produits)): ?>
        <div class="store-card">
            <div class="empty-state">
                <i class="bi bi-box-seam"></i>
                <h3>Aucun produit dans cette boutique</h3>
                <p>Commencez à ajouter vos premiers produits artisanaux.</p>
                <a href="ajouter-produit.php?boutique=<?php echo $id_boutique; ?>" class="btn-primary" style="margin-top:0.5rem;">
                    <i class="bi bi-plus-circle"></i> Ajouter mon premier produit
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($produits as $p): ?>
            <div class="product-card">
                <img src="<?php echo htmlspecialchars($p['photo_url'] ?? 'IMAGES/default-product.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($p['nom_produit'] ?? 'Produit'); ?>" 
                     class="product-img"
                     onerror="this.src='IMAGES/default-product.jpg'">
                <div class="product-body">
                    <div class="product-name" title="<?php echo htmlspecialchars($p['nom_produit'] ?? 'Produit'); ?>">
                        <?php echo htmlspecialchars($p['nom_produit'] ?? 'Produit sans nom'); ?>
                    </div>
                    <div class="product-price"><?php echo number_format($p['prix_unitaire'] ?? 0, 2, ',', ' '); ?> DH</div>
                    <div class="product-stock">
                        Stock : <?php echo $p['stock_quantite'] ?? 0; ?>
                        <span class="badge <?php echo ($p['stock_quantite'] ?? 0) <= 0 ? 'badge-danger' : (($p['stock_quantite'] ?? 0) < 5 ? 'badge-warning' : 'badge-success'); ?>">
                            <?php echo ($p['stock_quantite'] ?? 0) <= 0 ? 'Rupture' : (($p['stock_quantite'] ?? 0) < 5 ? 'Stock faible' : 'Disponible'); ?>
                        </span>
                    </div>
                    <div style="font-size:0.7rem;color:var(--text-light);margin-top:0.2rem;">
                        <span class="badge <?php echo ($p['statut_publie'] ?? 'Brouillon') == 'Publié' ? 'badge-success' : (($p['statut_publie'] ?? 'Brouillon') == 'Suspendu' ? 'badge-danger' : 'badge-warning'); ?>">
                            <?php echo htmlspecialchars($p['statut_publie'] ?? 'Brouillon'); ?>
                        </span>
                        <?php if (!($p['est_valide_par_admin'] ?? 0) && ($p['statut_publie'] ?? '') == 'Publié'): ?>
                            <span class="badge badge-warning">En attente de validation</span>
                        <?php endif; ?>
                        <?php if (($p['est_valide_par_admin'] ?? 0) && ($p['statut_publie'] ?? '') == 'Publié'): ?>
                            <span class="badge badge-success">Validé</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-actions">
                        <a href="modifier-produit.php?id=<?php echo $p['id_produit']; ?>" class="btn-sm btn-sm-edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button onclick="supprimerProduit(<?php echo $p['id_produit']; ?>, '<?php echo htmlspecialchars($p['nom_produit'] ?? 'produit'); ?>', <?php echo $id_boutique; ?>)" class="btn-sm btn-sm-delete">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>

<script>
// Supprimer un produit avec confirmation
function supprimerProduit(id, nom, boutiqueId) {
    if (!id || id <= 0) {
        alert('❌ ID produit invalide');
        return;
    }
    
    if (confirm('Êtes-vous sûr de vouloir supprimer le produit "' + nom + '" ? Cette action est irréversible.')) {
        // Trouver le bouton et le désactiver
        const btns = document.querySelectorAll('.btn-sm-delete');
        let btn = null;
        btns.forEach(b => {
            if (b.onclick && b.onclick.toString().includes('supprimerProduit(' + id + ',')) {
                btn = b;
            }
        });
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="loader"></span>';
        }
        
        fetch('supprimer_produit.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id_produit=' + id
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                window.location.href = 'gerer-boutique.php?id=' + boutiqueId + '&product_deleted=1';
            } else {
                alert('❌ ' + data.message);
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-trash3"></i>';
                }
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('❌ Erreur de connexion au serveur');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-trash3"></i>';
            }
        });
    }
}

// Supprimer la boutique avec confirmation
function supprimerBoutique(id, nom) {
    if (confirm('⚠️ Êtes-vous sûr de vouloir supprimer la boutique "' + nom + '" ?\n\nCette action supprimera également tous ses produits et est irréversible.')) {
        if (confirm('Confirmation finale : Voulez-vous vraiment supprimer cette boutique ?')) {
            // Désactiver le bouton
            const btns = document.querySelectorAll('.btn-danger');
            let btn = null;
            btns.forEach(b => {
                if (b.onclick && b.onclick.toString().includes('supprimerBoutique(' + id + ',')) {
                    btn = b;
                }
            });
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="loader"></span>';
            }
            
            fetch('supprimer_boutique.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id_boutique=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Boutique supprimée avec succès !');
                    window.location.href = 'dashboard_producteur.php';
                } else {
                    alert('❌ Erreur : ' + data.message);
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="bi bi-trash3"></i> Supprimer';
                    }
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('❌ Erreur de connexion au serveur');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-trash3"></i> Supprimer';
                }
            });
        }
    }
}
</script>
</body>
</html>