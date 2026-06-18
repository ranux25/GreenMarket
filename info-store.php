<?php
session_start();
include('connexion.php');

// Vérifier si un ID boutique est fourni
$id_boutique = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_boutique <= 0) {
    header('Location: store.php');
    exit;
}

// Récupérer les détails de la boutique
try {
    $stmt = $pdo->prepare("
        SELECT b.*, 
               p.nom_entreprise as producteur_nom, 
               p.email as producteur_email,
               p.est_valide_par_admin as producteur_valide,
               c.id_categorie, c.nom_categorie, c.description as categorie_description
        FROM boutique b
        INNER JOIN producteur p ON b.id_producteur = p.id_producteur
        LEFT JOIN categorie c ON b.id_categorie = c.id_categorie
        WHERE b.id_boutique = ?
    ");
    $stmt->execute([$id_boutique]);
    $boutique = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$boutique) {
        header('Location: store.php?error=not_found');
        exit;
    }
    
    // Récupérer tous les produits de cette boutique
    $stmt = $pdo->prepare("
        SELECT p.*, c.nom_categorie
        FROM produit p
        LEFT JOIN categorie c ON p.id_categorie = c.id_categorie
        WHERE p.id_boutique = ? 
        AND p.est_valide_par_admin = 1 
        AND p.statut_publie = 'Publié'
        ORDER BY p.date_creation DESC
    ");
    $stmt->execute([$id_boutique]);
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les statistiques de la boutique
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_produits,
            COALESCE(SUM(p.stock_quantite), 0) as stock_total,
            COALESCE(MIN(p.prix_unitaire), 0) as prix_min,
            COALESCE(MAX(p.prix_unitaire), 0) as prix_max
        FROM produit p
        WHERE p.id_boutique = ? 
        AND p.est_valide_par_admin = 1 
        AND p.statut_publie = 'Publié'
    ");
    $stmt->execute([$id_boutique]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Error info-store: " . $e->getMessage());
    die("Erreur : " . $e->getMessage());
}

// Fonction pour générer les étoiles
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
<title>GreenMarket – <?php echo htmlspecialchars($boutique['nom_boutique']); ?></title>
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
    padding: 12px 28px;
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
    padding: 10px 24px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s ease;
  }
  .btn-outline:hover { background: var(--primary); color: #fff; }

  /* Store Banner */
  .store-banner-section {
    position: relative;
    height: 350px;
    overflow: hidden;
  }
  .store-banner-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
  .store-banner-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(93,13,24,0.85) 0%, rgba(93,13,24,0.4) 100%);
  }
  .store-banner-content {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 2rem;
    color: #fff;
  }
  .store-banner-content h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
  }
  .store-category-tag {
    display: inline-block;
    background: var(--gold);
    color: #fff;
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.2rem 0.8rem;
    border-radius: 999px;
    margin-bottom: 1rem;
  }

  /* Store Info Card */
  .store-info-card {
    background: #fff;
    border-radius: 24px;
    padding: 1.5rem;
    margin-top: -60px;
    position: relative;
    z-index: 2;
    box-shadow: 0 8px 30px rgba(93,13,24,0.12);
    border: 1.5px solid #e8ddd0;
  }
  .store-stats {
    display: flex;
    justify-content: space-around;
    padding: 1rem 0;
    border-bottom: 1px solid #e8ddd0;
  }
  .store-stat {
    text-align: center;
  }
  .store-stat-value {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--primary);
  }
  .store-stat-label {
    font-size: 0.7rem;
    color: var(--text-light);
    text-transform: uppercase;
  }

  /* Products Grid */
  .products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
  }
  .product-card {
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    border: 1.5px solid #e8ddd0;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }
  .product-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 36px rgba(93,13,24,0.14);
  }
  .product-img {
    width: 100%;
    height: 200px;
    object-fit: cover;
    transition: transform 0.5s ease;
  }
  .product-card:hover .product-img { transform: scale(1.07); }
  .product-body { padding: 1rem; }
  .product-name {
    font-family: 'Playfair Display', serif;
    font-size: 1rem;
    font-weight: 700;
    color: var(--primary);
  }
  .product-price {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--gold);
    margin: 0.5rem 0;
  }
  .product-stock {
    font-size: 0.7rem;
    color: var(--text-light);
  }
  .stock-low { color: #e67e22; }
  
  /* Stars */
  .stars { color: #e0a82e; font-size: 0.8rem; }

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

  /* Ajouter au panier button en las tarjetas de productos */
  .add-to-cart-btn {
    width: 100%;
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 999px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s;
  }
  .add-to-cart-btn:hover {
    background: var(--primary-light);
  }
  .add-to-cart-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  @media (max-width: 768px) {
    .store-banner-section { height: 250px; }
    .store-banner-content h1 { font-size: 1.5rem; }
    .store-info-card { margin-top: -30px; }
  }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<!-- Bannière de la boutique -->
<div class="store-banner-section">
  <?php 
  $banner_img = !empty($boutique['image']) ? $boutique['image'] : 'https://placehold.co/1400x350/5D0D18/white?text=' . urlencode($boutique['nom_boutique']);
  ?>
  <img src="<?php echo htmlspecialchars($banner_img); ?>" alt="Bannière" class="store-banner-img" onerror="this.src='https://placehold.co/1400x350/5D0D18/white?text=Boutique'">
  <div class="store-banner-overlay"></div>
  <div class="store-banner-content">
    <span class="store-category-tag">
      <?php echo htmlspecialchars($boutique['nom_categorie'] ?? 'Artisanat marocain'); ?>
    </span>
    <h1><?php echo htmlspecialchars($boutique['nom_boutique']); ?></h1>
    <p><?php echo htmlspecialchars($boutique['producteur_nom']); ?></p>
  </div>
</div>

<div class="max-w-7xl mx-auto px-4 pb-16">
  <!-- Carte d'information -->
  <div class="store-info-card reveal">
    <div class="store-stats">
      <div class="store-stat">
        <div class="store-stat-value"><?php echo $stats['total_produits'] ?? 0; ?></div>
        <div class="store-stat-label">Produits</div>
      </div>
      <div class="store-stat">
        <div class="store-stat-value"><?php echo $stats['stock_total'] ?? 0; ?></div>
        <div class="store-stat-label">Articles en stock</div>
      </div>
      <div class="store-stat">
        <div class="store-stat-value">
          <?php 
          if (($stats['prix_min'] ?? 0) > 0 && ($stats['prix_max'] ?? 0) > 0) {
              echo number_format($stats['prix_min'], 0, ',', ' ') . ' - ' . number_format($stats['prix_max'], 0, ',', ' ') . ' DH';
          } else {
              echo '-';
          }
          ?>
        </div>
        <div class="store-stat-label">Prix (min - max)</div>
      </div>
    </div>
    
    <div class="flex items-center gap-4 mt-4 flex-wrap">
      <div class="stars"><?php echo renderStars(4.8); ?> <span style="color: var(--text-light);">(4.8 ★)</span></div>
      <div style="color: var(--text-light);">📍 Maroc</div>
      <div style="color: var(--text-light);">📅 <?php echo date('Y', strtotime($boutique['date_creation'] ?? 'now')); ?></div>
    </div>
    
    <div class="mt-4 p-4 bg-[var(--bg)] rounded-xl">
      <p class="text-gray-600"><?php echo htmlspecialchars($boutique['description'] ?? 'Boutique artisanale marocaine proposant des produits authentiques de qualité.'); ?></p>
    </div>
    
    <?php if (!empty($boutique['producteur_email'])): ?>
    <div class="mt-4 flex gap-3 flex-wrap">
      <a href="mailto:<?php echo htmlspecialchars($boutique['producteur_email']); ?>" class="btn-outline">📧 Contacter l'artisan</a>
      <button class="btn-outline" onclick="window.location.href='produits.php?search=<?php echo urlencode($boutique['nom_boutique']); ?>'">🔍 Voir tous les produits</button>
    </div>
    <?php endif; ?>
  </div>

  <!-- Liste des produits -->
  <div class="mt-12 reveal">
    <h2 class="text-2xl font-bold mb-6" style="color: var(--primary);">
      Nos produits artisanaux
    </h2>
    
    <?php if (empty($produits)): ?>
      <div class="text-center py-12 bg-white rounded-2xl border border-[#e8ddd0]">
        <div class="text-4xl mb-3">🏪</div>
        <p class="text-gray-500">Aucun produit disponible pour le moment dans cette boutique.</p>
        <p class="text-gray-400 text-sm mt-2">Revenez bientôt pour découvrir nos créations !</p>
      </div>
    <?php else: ?>
      <div class="products-grid">
        <?php foreach ($produits as $produit): ?>
        <div class="product-card">
          <?php 
          $prod_img = !empty($produit['photo_url']) ? $produit['photo_url'] : 'https://placehold.co/400x300/5D0D18/white?text=' . urlencode($produit['nom_produit']);
          ?>
          <img src="<?php echo htmlspecialchars($prod_img); ?>" 
               alt="<?php echo htmlspecialchars($produit['nom_produit']); ?>"
               class="product-img"
               onclick="window.location.href='info-produit.php?id=<?php echo $produit['id_produit']; ?>'"
               onerror="this.src='https://placehold.co/400x300/5D0D18/white?text=Produit'">
          <div class="product-body">
            <div class="product-name" onclick="window.location.href='info-produit.php?id=<?php echo $produit['id_produit']; ?>'">
              <?php echo htmlspecialchars($produit['nom_produit']); ?>
            </div>
            <div class="product-price"><?php echo number_format($produit['prix_unitaire'], 0, ',', ' '); ?> DH</div>
            <div class="product-stock <?php echo $produit['stock_quantite'] < 5 ? 'stock-low' : ''; ?>">
              <?php 
              if ($produit['stock_quantite'] <= 0) echo '⚠️ Rupture de stock';
              elseif ($produit['stock_quantite'] < 5) echo '🔥 Plus que ' . $produit['stock_quantite'] . ' exemplaires';
              else echo '✓ ' . $produit['stock_quantite'] . ' disponibles';
              ?>
            </div>
            <div class="stars mt-2"><?php echo renderStars(4.5); ?></div>
            
            <!-- Botón para añadir al carrito -->
            <button class="add-to-cart-btn" 
                    onclick="addToCart(<?php echo $produit['id_produit']; ?>, '<?php echo addslashes($produit['nom_produit']); ?>', <?php echo $produit['prix_unitaire']; ?>)"
                    <?php echo $produit['stock_quantite'] <= 0 ? 'disabled' : ''; ?>>
              <?php echo $produit['stock_quantite'] <= 0 ? '❌ Indisponible' : '🛒 Ajouter au panier'; ?>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="toast" id="toast"></div>

<?php include 'footer.php'; ?>

<script>
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

// ========== TOAST NOTIFICATION ==========
function showToast(msg, isError = false) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.style.background = isError ? '#d9534f' : 'var(--primary)';
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
        toast.style.background = 'var(--primary)';
    }, 2800);
}

// ========== UPDATE CART COUNT ==========
function updateCartCount() {
    fetch('get_cart_count.php')
        .then(res => res.json())
        .then(data => {
            const badge = document.getElementById('cart-count');
            if (badge && data.total !== undefined) {
                badge.textContent = data.total;
            }
        })
        .catch(() => {});
}

// ========== ADD TO CART FUNCTION ==========
function addToCart(productId, productName, price) {
    // Verificar si el usuario está conectado
    <?php if (!isset($_SESSION['user_id'])): ?>
        showToast('⚠️ Veuillez vous connecter pour ajouter au panier', true);
        setTimeout(() => {
            window.location.href = 'signin.php';
        }, 1500);
        return;
    <?php endif; ?>
    
    // Mostrar loading en el botón
    const buttons = document.querySelectorAll('.add-to-cart-btn');
    buttons.forEach(btn => {
        if (btn.textContent.includes('Ajouter')) {
            btn.textContent = '⏳ Ajout...';
            btn.disabled = true;
        }
    });

    const formData = new FormData();
    formData.append('id_produit', productId);
    formData.append('quantite', 1);

    fetch('ajouter_panier.php', {
        method: 'POST',
        body: formData
    })
    .then(res => {
        if (!res.ok) throw new Error('Erreur réseau');
        return res.json();
    })
    .then(data => {
        // Restaurer los botones
        buttons.forEach(btn => {
            btn.textContent = '🛒 Ajouter au panier';
            btn.disabled = false;
        });

        if (data.success) {
            showToast(`✓ ${productName} ajouté au panier !`);
            updateCartCount();
            
            // Actualizar el badge en el header si existe
            const badge = document.getElementById('cart-count');
            if (badge && data.total_panier !== undefined) {
                badge.textContent = data.total_panier;
            }
        } else {
            showToast(data.message || '❌ Erreur lors de l\'ajout', true);
        }
    })
    .catch(error => {
        buttons.forEach(btn => {
            btn.textContent = '🛒 Ajouter au panier';
            btn.disabled = false;
        });
        showToast('❌ Erreur de connexion au serveur', true);
        console.error('Erreur:', error);
    });
}

// ========== INICIALIZACIÓN ==========
document.addEventListener('DOMContentLoaded', function() {
    updateCartCount();
    initReveal();
});
</script>
</body>
</html>