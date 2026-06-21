<?php
session_start();
include('connexion.php');

// Verificar que el usuario esté logueado y sea cliente
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header("Location: signin.php");
    exit();
}

$theme = $_COOKIE['theme'] ?? 'light';
$id_client = $_SESSION['user_id'];

// Récupérer los favoritos del cliente (productos)
try {
    $stmt = $pdo->prepare("
        SELECT f.id_produit, f.id_client,
               p.nom_produit, p.prix_unitaire, p.description, p.photo_url, p.stock_quantite,
               b.nom_boutique, b.id_boutique,
               c.nom_categorie
        FROM favoris f
        JOIN produit p ON f.id_produit = p.id_produit
        JOIN boutique b ON p.id_boutique = b.id_boutique
        LEFT JOIN categorie c ON p.id_categorie = c.id_categorie
        WHERE f.id_client = ?
        ORDER BY f.id_produit DESC
    ");
    $stmt->execute([$id_client]);
    $favoris_produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_favoris_produits = count($favoris_produits);
} catch(PDOException $e) {
    error_log("Error favoris produits: " . $e->getMessage());
    $favoris_produits = [];
    $total_favoris_produits = 0;
}

// Récupérer los favoritos de tiendas
try {
    $stmt = $pdo->prepare("
        SELECT fb.id_boutique,
               b.nom_boutique, b.image, b.description, b.id_producteur,
               p.nom_entreprise as producteur_nom,
               c.nom_categorie
        FROM favoris_boutique fb
        JOIN boutique b ON fb.id_boutique = b.id_boutique
        JOIN producteur p ON b.id_producteur = p.id_producteur
        LEFT JOIN categorie c ON b.id_categorie = c.id_categorie
        WHERE fb.id_client = ?
        ORDER BY fb.id_boutique DESC
    ");
    $stmt->execute([$id_client]);
    $favoris_boutiques = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_favoris_boutiques = count($favoris_boutiques);
} catch(PDOException $e) {
    error_log("Error favoris boutiques: " . $e->getMessage());
    $favoris_boutiques = [];
    $total_favoris_boutiques = 0;
}

// Récupérer le nombre d'articles dans le panier
$cartCount = 0;
try {
    $reqCart = $pdo->prepare("SELECT COALESCE(SUM(quantite), 0) as total FROM panier WHERE id_client = ?");
    $reqCart->execute([$_SESSION['user_id']]);
    $cartCount = (int)$reqCart->fetch(PDO::FETCH_ASSOC)['total'];
} catch(PDOException $e) { $cartCount = 0; }

// Funciones
function formatPrice($price) {
    return number_format($price, 0, ',', ' ') . ' DH';
}

function getStockText($stock) {
    if ($stock <= 0) return 'Rupture de stock';
    if ($stock < 5) return 'Plus que ' . $stock . ' exemplaires';
    return $stock . ' disponibles';
}

function getStockClass($stock) {
    if ($stock <= 0) return 'stock-out';
    if ($stock < 5) return 'stock-low';
    return '';
}

function getImageUrl($image) {
    if (empty($image)) {
        return 'IMAGES/default-boutique.jpg';
    }
    $image = str_replace('\\', '/', $image);
    $image = str_replace('./', '', $image);
    if (strpos($image, 'http://') === 0 || strpos($image, 'https://') === 0) {
        return $image;
    }
    if (strpos($image, '/') === 0) {
        $image = substr($image, 1);
    }
    if (strpos($image, 'IMAGES/') !== 0) {
        $image = 'IMAGES/' . $image;
    }
    return $image;
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes favoris - GreenMarket</title>
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
            --shadow-hover: rgba(93,13,24,0.14);
            --danger: #c62828;
        }
        [data-theme="dark"] {
            --primary: #8a6048;
            --secondary: #6d4c3a;
            --bg: #2c241e;
            --bg-card: #3d3229;
            --text-dark: #f0e6d8;
            --text-light: #b8a896;
            --border-color: #5a4a3a;
            --shadow-color: rgba(0,0,0,0.3);
            --shadow-hover: rgba(0,0,0,0.4);
            --danger: #ef5350;
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

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: var(--primary);
            margin: 1.5rem 0 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .section-title .count {
            font-size: 0.9rem;
            color: var(--text-light);
            font-weight: 400;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        .product-card {
            background: var(--bg-card);
            border-radius: 20px;
            border: 1.5px solid var(--border-color);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s, background 0.3s, border-color 0.3s;
            box-shadow: 0 4px 16px var(--shadow-color);
            position: relative;
        }
        .product-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 36px var(--shadow-hover);
        }
        .product-banner {
            height: 220px;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }
        .product-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        .product-card:hover .product-banner img { transform: scale(1.07); }
        .product-price-badge {
            position: absolute;
            top: 0.8rem;
            right: 0.8rem;
            background: var(--primary);
            color: #fff;
            font-size: 0.9rem;
            font-weight: 700;
            padding: 0.3rem 0.8rem;
            border-radius: 999px;
            z-index: 2;
        }
        [data-theme="dark"] .product-price-badge {
            background: var(--gold);
            color: var(--bg);
        }
        .product-body { padding: 1.2rem; }
        .product-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-dark);
            line-height: 1.3;
            margin-bottom: 0.3rem;
            transition: color 0.3s;
        }
        .product-shop-tag {
            display: inline-block;
            background: var(--secondary);
            color: #fff;
            font-size: 0.65rem;
            font-weight: 600;
            letter-spacing: 0.05em;
            padding: 0.15rem 0.7rem;
            border-radius: 999px;
            margin-bottom: 0.6rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .product-shop-tag:hover { background: var(--primary); }
        .product-desc {
            font-size: 0.85rem;
            color: var(--text-light);
            line-height: 1.5;
            margin-bottom: 1rem;
            transition: color 0.3s;
        }
        .product-stats {
            display: flex;
            flex-wrap: wrap;
            border-top: 1px solid var(--border-color);
            padding-top: 0.8rem;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            transition: border-color 0.3s;
        }
        .product-stock {
            font-family: 'Playfair Display', serif;
            font-size: 0.9rem;
            font-weight: 700;
            color: var(--primary);
            transition: color 0.3s;
        }
        [data-theme="dark"] .product-stock { color: var(--gold); }
        .stock-low { color: #e67e22; }
        .stock-out { color: #e74c3c; }

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
        .btn-sm-remove {
            background: var(--danger);
            color: #fff;
        }
        .btn-sm-remove:hover { opacity: 0.85; }
        .btn-sm-cart {
            background: var(--primary);
            color: #fff;
        }
        .btn-sm-cart:hover { background: var(--primary-light); }
        .btn-sm-cart:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        [data-theme="dark"] .btn-sm-cart {
            color: var(--bg);
        }

        /* Store Cards */
        .stores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        .store-card {
            background: var(--bg-card);
            border-radius: 20px;
            border: 1.5px solid var(--border-color);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s, background 0.3s, border-color 0.3s;
            box-shadow: 0 4px 16px var(--shadow-color);
            position: relative;
            cursor: pointer;
        }
        .store-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 36px var(--shadow-hover);
        }
        .store-banner {
            height: 180px;
            position: relative;
            overflow: hidden;
        }
        .store-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        .store-card:hover .store-banner img { transform: scale(1.07); }
        .store-body { padding: 1.2rem; }
        .store-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.3rem;
        }
        [data-theme="dark"] .store-name {
            color: var(--gold);
        }
        .store-category-tag {
            display: inline-block;
            background: var(--secondary);
            color: #fff;
            font-size: 0.65rem;
            font-weight: 600;
            padding: 0.15rem 0.7rem;
            border-radius: 999px;
            margin-bottom: 0.6rem;
        }
        .store-desc {
            font-size: 0.85rem;
            color: var(--text-light);
            line-height: 1.5;
            margin-bottom: 0.5rem;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--text-light);
        }
        .empty-state i {
            font-size: 3rem;
            display: block;
            margin-bottom: 0.5rem;
            opacity: 0.4;
        }
        .empty-state h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            color: var(--text-dark);
            margin-bottom: 0.3rem;
        }
        .empty-state .btn-primary {
            display: inline-block;
            margin-top: 0.5rem;
            padding: 0.6rem 1.5rem;
            background: var(--primary);
            color: #fff;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        .empty-state .btn-primary:hover { background: var(--primary-light); }

        .toast {
            position: fixed;
            bottom: 28px;
            right: 28px;
            background: var(--primary);
            color: #fff;
            padding: 14px 22px;
            border-radius: 14px;
            font-weight: 700;
            z-index: 9999;
            transform: translateY(80px);
            opacity: 0;
            transition: 0.4s cubic-bezier(.22,1,.36,1);
        }
        .toast.show { transform: translateY(0); opacity: 1; }

        @media (max-width: 640px) {
            .page-header { padding: 1.5rem; }
            .page-header h1 { font-size: 1.5rem; }
            .products-grid, .stores-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            .container { padding: 1rem; }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="page-header">
    <div style="max-width:1200px;margin:0 auto;">
        <h1><i class="bi bi-heart" style="color:var(--gold);"></i> Mes favoris</h1>
        <p>Retrouvez toutes vos boutiques et produits préférés</p>
    </div>
</div>

<div class="container">

    <a href="accueil.php" class="btn-back">
        <i class="bi bi-arrow-left"></i> Retour à l'accueil
    </a>

    <!-- ============================================================ -->
    <!-- SECTION: BOUTIQUES FAVORITES                                  -->
    <!-- ============================================================ -->
    <div class="section-title">
        <i class="bi bi-shop" style="color:var(--gold);"></i>
        Boutiques favorites
        <span class="count">(<?php echo $total_favoris_boutiques; ?>)</span>
    </div>

    <?php if (empty($favoris_boutiques)): ?>
        <div class="empty-state" style="border:1.5px solid var(--border-color);border-radius:20px;background:var(--bg-card);">
            <i class="bi bi-shop"></i>
            <h3>Aucune boutique favorite</h3>
            <p>Ajoutez des boutiques à vos favoris en cliquant sur le ❤️</p>
            <a href="store.php" class="btn-primary">Découvrir les boutiques</a>
        </div>
    <?php else: ?>
        <div class="stores-grid">
            <?php foreach ($favoris_boutiques as $b): ?>
            <div class="store-card" id="favori-store-<?php echo $b['id_boutique']; ?>" onclick="window.location.href='info-store.php?id=<?php echo $b['id_boutique']; ?>'">
                <div class="store-banner">
                    <img src="<?php echo htmlspecialchars(getImageUrl($b['image'] ?? '')); ?>" 
                         alt="<?php echo htmlspecialchars($b['nom_boutique']); ?>"
                         onerror="this.src='IMAGES/default-boutique.jpg'">
                    <button class="btn-sm btn-sm-remove" 
                            style="position:absolute;top:0.5rem;right:0.5rem;z-index:5;padding:0.3rem 0.6rem;"
                            onclick="event.stopPropagation(); supprimerBoutiqueFavori(<?php echo $b['id_boutique']; ?>)">
                        <i class="bi bi-heart-fill"></i>
                    </button>
                </div>
                <div class="store-body">
                    <div class="store-name"><?php echo htmlspecialchars($b['nom_boutique']); ?></div>
                    <span class="store-category-tag">
                        <?php echo htmlspecialchars($b['nom_categorie'] ?? 'Artisanat'); ?>
                    </span>
                    <p class="store-desc"><?php echo htmlspecialchars(substr($b['description'] ?? 'Boutique artisanale', 0, 80)) . (strlen($b['description'] ?? '') > 80 ? '…' : ''); ?></p>
                    <div style="font-size:0.8rem;color:var(--text-light);">
                        <i class="bi bi-building"></i> <?php echo htmlspecialchars($b['producteur_nom']); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- ============================================================ -->
    <!-- SECTION: PRODUITS FAVORITES                                   -->
    <!-- ============================================================ -->
    <div class="section-title" style="margin-top:2.5rem;">
        <i class="bi bi-box-seam" style="color:var(--gold);"></i>
        Produits favoris
        <span class="count">(<?php echo $total_favoris_produits; ?>)</span>
    </div>

    <?php if (empty($favoris_produits)): ?>
        <div class="empty-state" style="border:1.5px solid var(--border-color);border-radius:20px;background:var(--bg-card);">
            <i class="bi bi-box-seam"></i>
            <h3>Aucun produit favori</h3>
            <p>Ajoutez des produits à vos favoris en cliquant sur le ❤️</p>
            <a href="produits.php" class="btn-primary">Découvrir les produits</a>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($favoris_produits as $p): ?>
            <div class="product-card" id="favori-prod-<?php echo $p['id_produit']; ?>">
                <div class="product-banner" onclick="window.location.href='info-produit.php?id=<?php echo $p['id_produit']; ?>'">
                    <img src="<?php echo htmlspecialchars($p['photo_url'] ?? 'IMAGES/default-product.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($p['nom_produit']); ?>"
                         onerror="this.src='IMAGES/default-product.jpg'">
                    <span class="product-price-badge">
                        <?php echo formatPrice($p['prix_unitaire']); ?>
                    </span>
                </div>
                <div class="product-body">
                    <div class="product-name"><?php echo htmlspecialchars($p['nom_produit']); ?></div>
                    <span class="product-shop-tag" onclick="event.stopPropagation();window.location.href='info-store.php?id=<?php echo $p['id_boutique']; ?>'">
                        🏪 <?php echo htmlspecialchars($p['nom_boutique']); ?>
                    </span>
                    <p class="product-desc"><?php echo htmlspecialchars(substr($p['description'] ?? '', 0, 80)) . (strlen($p['description'] ?? '') > 80 ? '…' : ''); ?></p>
                    <div class="product-stats">
                        <div>
                            <span class="product-stock <?php echo getStockClass($p['stock_quantite']); ?>">
                                <?php echo getStockText($p['stock_quantite']); ?>
                            </span>
                        </div>
                        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                            <button class="btn-sm btn-sm-remove" onclick="event.stopPropagation(); supprimerProduitFavori(<?php echo $p['id_produit']; ?>)">
                                <i class="bi bi-heart-fill"></i>
                            </button>
                            <button class="btn-sm btn-sm-cart" 
                                    onclick="event.stopPropagation(); addToCart(<?php echo $p['id_produit']; ?>, '<?php echo htmlspecialchars($p['nom_produit']); ?>')"
                                    <?php echo $p['stock_quantite'] <= 0 ? 'disabled' : ''; ?>>
                                🛒
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<div class="toast" id="toast"></div>

<?php include 'footer.php'; ?>

<script>
// ========== TOAST ==========
function showToast(msg, isError = false) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.style.background = isError ? '#c0392b' : 'var(--primary)';
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
        toast.style.background = 'var(--primary)';
    }, 2800);
}

// ========== AJOUTER AU PANIER ==========
function addToCart(productId, productName) {
    <?php if (!isset($_SESSION['user_id'])): ?>
        showToast('⚠️ Veuillez vous connecter', true);
        setTimeout(() => { window.location.href = 'signin.php'; }, 1500);
        return;
    <?php endif; ?>
    
    <?php if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'client'): ?>
        showToast('⚠️ Seuls les clients peuvent acheter', true);
        return;
    <?php endif; ?>

    fetch('ajouter_panier.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id_produit=' + productId + '&quantite=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('✓ ' + productName + ' ajouté au panier !');
            const badge = document.getElementById('cart-count');
            if (badge && data.total_panier !== undefined) {
                badge.textContent = data.total_panier;
            }
        } else {
            showToast(data.message || '❌ Erreur', true);
        }
    })
    .catch(() => showToast('❌ Erreur de connexion', true));
}

// ========== SUPPRIMER UN PRODUIT FAVORI ==========
function supprimerProduitFavori(productId) {
    if (!confirm('Voulez-vous retirer ce produit de vos favoris ?')) return;

    fetch('toggle_favori.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id_produit=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const card = document.getElementById('favori-prod-' + productId);
            if (card) {
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    card.remove();
                    const remaining = document.querySelectorAll('.product-card');
                    if (remaining.length === 0) {
                        location.reload();
                    }
                }, 300);
            }
            showToast('✅ Retiré des favoris');
        } else {
            showToast('❌ ' + data.message, true);
        }
    })
    .catch(() => showToast('❌ Erreur de connexion', true));
}

// ========== SUPPRIMER UNE BOUTIQUE FAVORI ==========
function supprimerBoutiqueFavori(boutiqueId) {
    if (!confirm('Voulez-vous retirer cette boutique de vos favoris ?')) return;

    fetch('toggle_favori_boutique.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id_boutique=' + boutiqueId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const card = document.getElementById('favori-store-' + boutiqueId);
            if (card) {
                card.style.opacity = '0';
                card.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    card.remove();
                    const remaining = document.querySelectorAll('.store-card');
                    if (remaining.length === 0) {
                        location.reload();
                    }
                }, 300);
            }
            showToast('✅ Boutique retirée des favoris');
        } else {
            showToast('❌ ' + data.message, true);
        }
    })
    .catch(() => showToast('❌ Erreur de connexion', true));
}

// ========== SUPPRIMER TOUS LES FAVORIS ==========
function supprimerTousFavoris() {
    if (!confirm('Voulez-vous vraiment supprimer tous vos favoris (produits et boutiques) ?')) return;

    fetch('supprimer_tous_favoris.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('✅ Tous les favoris supprimés');
            setTimeout(() => location.reload(), 500);
        } else {
            showToast('❌ ' + data.message, true);
        }
    })
    .catch(() => showToast('❌ Erreur de connexion', true));
}
</script>
</body>
</html>