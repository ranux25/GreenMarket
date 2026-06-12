<?php
session_start();
require_once 'connexion.php';

// Vérifier si un ID produit est fourni
$id_produit = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_produit <= 0) {
    header('Location: produits.php');
    exit;
}

// Récupérer les détails du produit
try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               b.nom_boutique, b.id_boutique, b.description as boutique_description, b.image as boutique_image,
               c.nom_categorie, c.id_categorie, c.description as categorie_description
        FROM produit p
        JOIN boutique b ON p.id_boutique = b.id_boutique
        JOIN categorie c ON p.id_categorie = c.id_categorie
        WHERE p.id_produit = ? 
        AND p.est_valide_par_admin = 1 
        AND p.statut_publie = 'Publié'
    ");
    $stmt->execute([$id_produit]);
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$produit) {
        header('Location: produits.php?error=not_found');
        exit;
    }
    
    // Récupérer d'autres produits de la même boutique
    $stmt = $pdo->prepare("
        SELECT p.id_produit, p.nom_produit, p.prix_unitaire, p.photo_url
        FROM produit p
        WHERE p.id_boutique = ? 
        AND p.id_produit != ? 
        AND p.est_valide_par_admin = 1 
        AND p.statut_publie = 'Publié'
        LIMIT 4
    ");
    $stmt->execute([$produit['id_boutique'], $id_produit]);
    $autres_produits = $stmt->fetchAll();
    
    // Récupérer des produits similaires (même catégorie)
    $stmt = $pdo->prepare("
        SELECT p.id_produit, p.nom_produit, p.prix_unitaire, p.photo_url,
               b.nom_boutique
        FROM produit p
        JOIN boutique b ON p.id_boutique = b.id_boutique
        WHERE p.id_categorie = ? 
        AND p.id_produit != ? 
        AND p.est_valide_par_admin = 1 
        AND p.statut_publie = 'Publié'
        LIMIT 4
    ");
    $stmt->execute([$produit['id_categorie'], $id_produit]);
    $produits_similaires = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Error info-produit: " . $e->getMessage());
    header('Location: produits.php?error=database');
    exit;
}

// Fonction pour générer les étoiles de notation
function renderStars($rating = 4.5) {
    $full = floor($rating);
    $half = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    return str_repeat('★', $full) . str_repeat('½', $half) . str_repeat('☆', $empty);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GreenMarket – <?php echo htmlspecialchars($produit['nom_produit']); ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  :root {
    --primary: #5D0D18;
    --primary-light: #7a1020;
    --secondary: #9FB2AC;
    --bg: #FFF9EB;
    --text-dark: #2C2C2C;
    --text-light: #6B6B6B;
    --footer-bg: #3A0A10;
    --gold: #c07a1a;
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'Lato', sans-serif;
    background: var(--bg);
    color: var(--text-dark);
    min-height: 100vh;
  }

  h1, h2, h3, .playfair { font-family: 'Playfair Display', serif; }

  /* Animations */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(40px); }
    to { opacity: 1; transform: translateY(0); }
  }
  @keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
  }

  .reveal {
    opacity: 0;
    transform: translateY(35px);
    transition: opacity 0.7s ease, transform 0.7s ease;
  }
  .reveal.visible {
    opacity: 1;
    transform: translateY(0);
  }

  /* Boutons */
  .btn-primary {
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: 999px;
    padding: 14px 32px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s, transform 0.2s;
  }
  .btn-primary:hover { background: var(--primary-light); transform: translateY(-2px); }
  .btn-outline {
    background: transparent;
    color: var(--primary);
    border: 2px solid var(--primary);
    border-radius: 999px;
    padding: 12px 28px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s ease;
  }
  .btn-outline:hover { background: var(--primary); color: #fff; }
  .btn-sage {
    background: var(--secondary);
    color: #fff;
    border: none;
    border-radius: 999px;
    padding: 12px 28px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s, transform 0.2s;
  }
  .btn-sage:hover { background: #8aa09a; transform: translateY(-2px); }

  /* Product Detail */
  .product-detail-container {
    max-width: 1280px;
    margin: 2rem auto;
    padding: 0 2rem;
  }
  .product-gallery {
    border-radius: 24px;
    overflow: hidden;
    background: #fff;
    border: 1.5px solid #e8ddd0;
  }
  .product-main-image {
    width: 100%;
    height: 500px;
    object-fit: cover;
  }
  .product-info {
    background: #fff;
    border-radius: 24px;
    padding: 2rem;
    border: 1.5px solid #e8ddd0;
  }
  .product-category {
    display: inline-block;
    background: var(--secondary);
    color: #fff;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.2rem 0.8rem;
    border-radius: 999px;
    margin-bottom: 1rem;
  }
  .product-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.5rem;
  }
  .product-price {
    font-size: 2rem;
    font-weight: 700;
    color: var(--gold);
    margin: 1rem 0;
  }
  .product-stock {
    display: inline-block;
    padding: 0.3rem 1rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 1rem;
  }
  .stock-high { background: #d4edda; color: #155724; }
  .stock-low { background: #fff3cd; color: #856404; }
  .stock-out { background: #f8d7da; color: #721c24; }
  .product-description {
    color: var(--text-light);
    line-height: 1.6;
    margin: 1rem 0;
    padding: 1rem 0;
    border-top: 1px solid #e8ddd0;
    border-bottom: 1px solid #e8ddd0;
  }
  .shop-info {
    background: var(--bg);
    border-radius: 16px;
    padding: 1rem;
    margin: 1rem 0;
    display: flex;
    align-items: center;
    gap: 1rem;
    cursor: pointer;
    transition: transform 0.2s;
  }
  .shop-info:hover { transform: translateX(5px); }
  .shop-avatar {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    object-fit: cover;
    background: var(--secondary);
  }
  .shop-name { font-weight: 700; color: var(--primary); }
  .stars { color: #e0a82e; font-size: 0.8rem; }

  /* Quantity Selector */
  .quantity-selector {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 1.5rem 0;
  }
  .qty-btn {
    width: 40px;
    height: 40px;
    border-radius: 999px;
    border: 1.5px solid #e8ddd0;
    background: #fff;
    font-size: 1.2rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
  }
  .qty-btn:hover { background: var(--primary); color: #fff; border-color: var(--primary); }
  .qty-input {
    width: 60px;
    height: 40px;
    text-align: center;
    font-size: 1rem;
    border: 1.5px solid #e8ddd0;
    border-radius: 12px;
  }

  /* Product Cards Mini */
  .product-mini-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    border: 1.5px solid #e8ddd0;
    transition: transform 0.3s ease;
    cursor: pointer;
  }
  .product-mini-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(93,13,24,0.12);
  }
  .product-mini-img {
    width: 100%;
    height: 160px;
    object-fit: cover;
  }
  .product-mini-info { padding: 0.8rem; }
  .product-mini-name {
    font-family: 'Playfair Display', serif;
    font-weight: 700;
    font-size: 0.9rem;
  }
  .product-mini-price { color: var(--primary); font-weight: 700; }

  /* Toast */
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

  @media (max-width: 768px) {
    .product-detail-container { padding: 0 1rem; }
    .product-main-image { height: 300px; }
    .product-title { font-size: 1.5rem; }
  }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="product-detail-container">
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Galerie d'images -->
    <div class="reveal">
      <div class="product-gallery">
        <?php 
        $image_url = !empty($produit['photo_url']) ? $produit['photo_url'] : 'IMAGES/default-product.jpg';
        ?>
        <img src="<?php echo htmlspecialchars($image_url); ?>" 
             alt="<?php echo htmlspecialchars($produit['nom_produit']); ?>"
             class="product-main-image"
             onerror="this.src='https://placehold.co/600x500/5D0D18/white?text=<?php echo urlencode($produit['nom_produit']); ?>'">
      </div>
    </div>

    <!-- Informations produit -->
    <div class="reveal">
      <div class="product-info">
        <span class="product-category">
          <?php echo htmlspecialchars($produit['nom_categorie']); ?>
        </span>
        <h1 class="product-title"><?php echo htmlspecialchars($produit['nom_produit']); ?></h1>
        
        <div class="stars">
          <?php echo renderStars(4.8); ?>
          <span style="color: var(--text-light); font-size: 0.8rem;">(128 avis)</span>
        </div>

        <div class="product-price">
          <?php echo number_format($produit['prix_unitaire'], 0, ',', ' '); ?> DH
        </div>

        <?php
        $stock = $produit['stock_quantite'];
        $stock_class = '';
        $stock_text = '';
        if ($stock <= 0) {
            $stock_class = 'stock-out';
            $stock_text = '⚠️ Rupture de stock';
        } elseif ($stock < 5) {
            $stock_class = 'stock-low';
            $stock_text = '🔥 Plus que ' . $stock . ' exemplaires !';
        } else {
            $stock_class = 'stock-high';
            $stock_text = '✓ En stock (' . $stock . ' disponibles)';
        }
        ?>
        <div class="product-stock <?php echo $stock_class; ?>">
          <?php echo $stock_text; ?>
        </div>

        <div class="product-description">
          <p><?php echo nl2br(htmlspecialchars($produit['description'] ?? 'Aucune description disponible pour ce produit.')); ?></p>
        </div>

        <!-- Sélecteur quantité -->
        <div class="quantity-selector">
          <button class="qty-btn" id="qtyMinus">−</button>
          <input type="number" id="qtyInput" class="qty-input" value="1" min="1" max="<?php echo $stock; ?>">
          <button class="qty-btn" id="qtyPlus">+</button>
        </div>

        <!-- Boutons d'action -->
        <div class="flex gap-3 flex-wrap">
          <button class="btn-primary" id="addToCartBtn" <?php echo $stock <= 0 ? 'disabled style="opacity:0.5;cursor:not-allowed"' : ''; ?>>
            🛒 Ajouter au panier
          </button>
          <button class="btn-outline" onclick="window.location.href='store.php?id=<?php echo $produit['id_boutique']; ?>'">
            🏪 Voir la boutique
          </button>
        </div>

        <!-- Informations boutique -->
        <div class="shop-info" onclick="window.location.href='store.php?id=<?php echo $produit['id_boutique']; ?>'">
          <?php 
          $shop_img = !empty($produit['boutique_image']) ? $produit['boutique_image'] : 'https://placehold.co/50x50/5D0D18/white?text=🏪';
          ?>
          <img src="<?php echo htmlspecialchars($shop_img); ?>" alt="Boutique" class="shop-avatar" onerror="this.src='https://placehold.co/50x50/5D0D18/white?text=🏪'">
          <div>
            <div class="shop-name"><?php echo htmlspecialchars($produit['nom_boutique']); ?></div>
            <div class="stars">★★★★★ <span style="color: var(--text-light);">(4.8)</span></div>
            <small style="color: var(--text-light);">Artisan marocain certifié</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Autres produits de la même boutique -->
  <?php if (!empty($autres_produits)): ?>
  <div class="mt-16 reveal">
    <h2 class="text-2xl font-bold mb-6" style="color: var(--primary);">
      Autres produits de la même boutique
    </h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <?php foreach ($autres_produits as $p): ?>
      <div class="product-mini-card" onclick="window.location.href='info-produit.php?id=<?php echo $p['id_produit']; ?>'">
        <img src="<?php echo !empty($p['photo_url']) ? htmlspecialchars($p['photo_url']) : 'https://placehold.co/300x200/5D0D18/white?text=Produit'; ?>" 
             class="product-mini-img" 
             alt="<?php echo htmlspecialchars($p['nom_produit']); ?>"
             onerror="this.src='https://placehold.co/300x200/5D0D18/white?text=<?php echo urlencode($p['nom_produit']); ?>'">
        <div class="product-mini-info">
          <div class="product-mini-name"><?php echo htmlspecialchars($p['nom_produit']); ?></div>
          <div class="product-mini-price"><?php echo number_format($p['prix_unitaire'], 0, ',', ' '); ?> DH</div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Produits similaires -->
  <?php if (!empty($produits_similaires)): ?>
  <div class="mt-16 reveal">
    <h2 class="text-2xl font-bold mb-6" style="color: var(--primary);">
      Produits similaires
    </h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <?php foreach ($produits_similaires as $p): ?>
      <div class="product-mini-card" onclick="window.location.href='info-produit.php?id=<?php echo $p['id_produit']; ?>'">
        <img src="<?php echo !empty($p['photo_url']) ? htmlspecialchars($p['photo_url']) : 'https://placehold.co/300x200/5D0D18/white?text=Produit'; ?>" 
             class="product-mini-img" 
             alt="<?php echo htmlspecialchars($p['nom_produit']); ?>"
             onerror="this.src='https://placehold.co/300x200/5D0D18/white?text=<?php echo urlencode($p['nom_produit']); ?>'">
        <div class="product-mini-info">
          <div class="product-mini-name"><?php echo htmlspecialchars($p['nom_produit']); ?></div>
          <div class="product-mini-price"><?php echo number_format($p['prix_unitaire'], 0, ',', ' '); ?> DH</div>
          <small style="color: var(--text-light);"><?php echo htmlspecialchars($p['nom_boutique']); ?></small>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<div class="toast" id="toast">✓ Produit ajouté au panier !</div>

<?php include 'footer.php'; ?>

<script>
// ========== QUANTITY SELECTOR ==========
const qtyInput = document.getElementById('qtyInput');
const qtyMinus = document.getElementById('qtyMinus');
const qtyPlus = document.getElementById('qtyPlus');
const maxStock = <?php echo $stock; ?>;

if (qtyMinus) {
    qtyMinus.addEventListener('click', () => {
        let val = parseInt(qtyInput.value) || 1;
        if (val > 1) qtyInput.value = val - 1;
    });
}
if (qtyPlus) {
    qtyPlus.addEventListener('click', () => {
        let val = parseInt(qtyInput.value) || 1;
        if (val < maxStock) qtyInput.value = val + 1;
    });
}
if (qtyInput) {
    qtyInput.addEventListener('change', () => {
        let val = parseInt(qtyInput.value) || 1;
        if (val < 1) qtyInput.value = 1;
        if (val > maxStock) qtyInput.value = maxStock;
    });
}

// ========== PANIER ==========
function showToast(msg) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2800);
}

function updateCartCount() {
    const cart = JSON.parse(localStorage.getItem('greenmarket_cart') || '[]');
    const total = cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
    const badge = document.getElementById('cart-count');
    if (badge) badge.textContent = total;
}

function addToCart() {
    <?php if ($stock <= 0): ?>
    showToast('❌ Ce produit n\'est plus disponible');
    return;
    <?php endif; ?>
    
    const quantity = parseInt(qtyInput?.value) || 1;
    const product = {
        id: <?php echo $produit['id_produit']; ?>,
        name: <?php echo json_encode($produit['nom_produit']); ?>,
        price: <?php echo json_encode(number_format($produit['prix_unitaire'], 0, ',', ' ') . ' DH'); ?>,
        prixNumerique: <?php echo $produit['prix_unitaire']; ?>,
        image: <?php echo json_encode(!empty($produit['photo_url']) ? $produit['photo_url'] : 'IMAGES/default-product.jpg'); ?>,
        boutiqueId: <?php echo $produit['id_boutique']; ?>,
        boutiqueNom: <?php echo json_encode($produit['nom_boutique']); ?>,
        quantity: quantity
    };
    
    let cart = JSON.parse(localStorage.getItem('greenmarket_cart') || '[]');
    const existing = cart.find(item => item.id === product.id);
    
    if (existing) {
        existing.quantity = (existing.quantity || 1) + quantity;
    } else {
        cart.push(product);
    }
    
    localStorage.setItem('greenmarket_cart', JSON.stringify(cart));
    updateCartCount();
    showToast(`✓ ${product.name} (x${quantity}) ajouté au panier !`);
}

document.getElementById('addToCartBtn')?.addEventListener('click', addToCart);

// ========== SCROLL REVEAL ==========
function initReveal() {
    const elements = document.querySelectorAll('.reveal');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) e.target.classList.add('visible');
        });
    }, { threshold: 0.1 });
    elements.forEach(el => observer.observe(el));
}

// Initialisation
updateCartCount();
initReveal();
</script>
</body>
</html>