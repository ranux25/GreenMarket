<?php
session_start();
include('connexion.php');

$id_produit = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_produit <= 0) {
    header('Location: produits.php');
    exit;
}

$isClient = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client';
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

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
    
    $stmtEvals = $pdo->prepare("
        SELECT e.*, c.nom_client
        FROM evaluer e
        JOIN client c ON e.id_client = c.id_client
        WHERE e.id_produit = ? AND e.est_publie = 1
        ORDER BY e.date_evaluation DESC
    ");
    $stmtEvals->execute([$id_produit]);
    $evaluaciones = $stmtEvals->fetchAll(PDO::FETCH_ASSOC);
    
    $promedio = 0;
    $total_eval = count($evaluaciones);
    if ($total_eval > 0) {
        $suma = array_sum(array_column($evaluaciones, 'note'));
        $promedio = round($suma / $total_eval, 1);
    }
    
    $ya_evaluado = false;
    $evaluacion_cliente = null;
    if ($isClient && $userId) {
        $stmtCheck = $pdo->prepare("
            SELECT * FROM evaluer WHERE id_client = ? AND id_produit = ?
        ");
        $stmtCheck->execute([$userId, $id_produit]);
        $evaluacion_cliente = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        $ya_evaluado = $evaluacion_cliente ? true : false;
    }
    
    $a_achete = false;
    if ($isClient && $userId) {
        $stmtCheck = $pdo->prepare("
            SELECT COUNT(*) FROM commande c
            JOIN contenir ct ON c.id_commande = ct.id_commande
            WHERE c.id_client = ? AND ct.id_produit = ? AND c.statut_commande = 'Livrée'
        ");
        $stmtCheck->execute([$userId, $id_produit]);
        $a_achete = $stmtCheck->fetchColumn() > 0;
    }
    
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

function renderStars($rating = 4.5) {
    $full = floor($rating);
    $half = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    return str_repeat('★', $full) . str_repeat('½', $half) . str_repeat('☆', $empty);
}

function renderStarsSize($rating, $size = '1rem') {
    $full = floor($rating);
    $half = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    $html = '<span style="color: #e0a82e; font-size: ' . $size . ';">';
    $html .= str_repeat('★', $full);
    if ($half) $html .= '½';
    $html .= str_repeat('☆', $empty);
    $html .= '</span>';
    return $html;
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
  .btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }
  
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
  .toast.error { background: #d9534f; }

  .star-rating {
    display: inline-flex;
    flex-direction: row-reverse;
    gap: 0.3rem;
  }
  .star-rating input {
    display: none;
  }
  .star-rating label {
    font-size: 2rem;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s;
  }
  .star-rating label:hover,
  .star-rating label:hover ~ label,
  .star-rating input:checked ~ label {
    color: #e0a82e;
  }
  
  .evaluation-card {
    background: #fff;
    border-radius: 16px;
    padding: 1rem;
    border: 1px solid #e8ddd0;
    margin-bottom: 0.75rem;
    transition: all 0.2s;
  }
  .evaluation-card:hover {
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(93,13,24,0.08);
  }
  
  .evaluation-section {
    background: #fff;
    border-radius: 24px;
    padding: 2rem;
    border: 1.5px solid #e8ddd0;
    margin-top: 3rem;
  }

  @media (max-width: 768px) {
    .product-detail-container { padding: 0 1rem; }
    .product-main-image { height: 300px; }
    .product-title { font-size: 1.5rem; }
    .evaluation-section { padding: 1rem; }
  }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="product-detail-container">
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
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

    <div class="reveal">
      <div class="product-info">
        <span class="product-category">
          <?php echo htmlspecialchars($produit['nom_categorie']); ?>
        </span>
        <h1 class="product-title"><?php echo htmlspecialchars($produit['nom_produit']); ?></h1>
        
        <div class="stars">
          <?php echo renderStars($promedio > 0 ? $promedio : 4.8); ?>
          <span style="color: var(--text-light); font-size: 0.8rem;">(<?php echo $total_eval; ?> avis)</span>
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

        <div class="quantity-selector">
          <button class="qty-btn" id="qtyMinus">−</button>
          <input type="number" id="qtyInput" class="qty-input" value="1" min="1" max="<?php echo $stock; ?>">
          <button class="qty-btn" id="qtyPlus">+</button>
        </div>

        <div class="flex gap-3 flex-wrap">
          <button class="btn-primary" id="addToCartBtn" <?php echo $stock <= 0 ? 'disabled style="opacity:0.5;cursor:not-allowed"' : ''; ?>>
            🛒 Ajouter au panier
          </button>
          <button class="btn-outline" onclick="window.location.href='store.php?id=<?php echo $produit['id_boutique']; ?>'">
            🏪 Voir la boutique
          </button>
        </div>

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

  <div class="evaluation-section reveal">
    <h2 class="text-2xl font-bold mb-6" style="color: var(--primary);">
      <i class="bi bi-star"></i> Avis et évaluations
    </h2>
    
    <div class="flex items-center gap-6 mb-6 flex-wrap">
      <div class="flex items-center gap-2">
        <span class="text-3xl font-bold text-[var(--gold)]"><?php echo $promedio > 0 ? $promedio : '4.5'; ?></span>
        <div>
          <div class="stars text-xl"><?php echo renderStars($promedio > 0 ? $promedio : 4.5); ?></div>
          <span class="text-sm text-gray-400"><?php echo $total_eval; ?> avis</span>
        </div>
      </div>
      <div class="text-sm text-gray-500">
        <i class="bi bi-check-circle text-green-500"></i>
        <?php echo $total_eval; ?> client(s) ont évalué ce produit
      </div>
    </div>
    
    <?php if ($isClient && $a_achete): ?>
      <div class="bg-[var(--bg)] rounded-2xl p-6 border border-[#e8ddd0] mb-8">
        <h3 class="font-bold text-lg mb-3" style="color: var(--primary);">
          <?php echo $ya_evaluado ? '✏️ Modifier votre avis' : '⭐ Donnez votre avis'; ?>
        </h3>
        
        <form id="evaluationForm" onsubmit="return submitEvaluation(event)">
          <input type="hidden" name="id_produit" value="<?php echo $produit['id_produit']; ?>">
          
          <div class="mb-4">
            <label class="block font-semibold mb-2" style="color: var(--text-dark);">Votre note :</label>
            <div class="star-rating" id="starRating">
              <input type="radio" name="note" id="star5" value="5" <?php echo ($ya_evaluado && isset($evaluacion_cliente['note']) && $evaluacion_cliente['note'] == 5) ? 'checked' : ''; ?>>
              <label for="star5" title="5 étoiles">★</label>
              
              <input type="radio" name="note" id="star4" value="4" <?php echo ($ya_evaluado && isset($evaluacion_cliente['note']) && $evaluacion_cliente['note'] == 4) ? 'checked' : ''; ?>>
              <label for="star4" title="4 étoiles">★</label>
              
              <input type="radio" name="note" id="star3" value="3" <?php echo ($ya_evaluado && isset($evaluacion_cliente['note']) && $evaluacion_cliente['note'] == 3) ? 'checked' : ''; ?>>
              <label for="star3" title="3 étoiles">★</label>
              
              <input type="radio" name="note" id="star2" value="2" <?php echo ($ya_evaluado && isset($evaluacion_cliente['note']) && $evaluacion_cliente['note'] == 2) ? 'checked' : ''; ?>>
              <label for="star2" title="2 étoiles">★</label>
              
              <input type="radio" name="note" id="star1" value="1" <?php echo ($ya_evaluado && isset($evaluacion_cliente['note']) && $evaluacion_cliente['note'] == 1) ? 'checked' : ''; ?>>
              <label for="star1" title="1 étoile">★</label>
            </div>
            <div id="starError" class="text-red-500 text-sm mt-1 hidden">Veuillez sélectionner une note.</div>
          </div>
          
          <div class="mb-4">
            <label class="block font-semibold mb-2" style="color: var(--text-dark);">Votre commentaire :</label>
            <textarea name="commentaire" rows="3" 
                      class="w-full border border-gray-300 rounded-xl p-3 focus:outline-none focus:border-[var(--primary)]"
                      placeholder="Partagez votre expérience avec ce produit..."><?php echo ($ya_evaluado && isset($evaluacion_cliente['commentaire'])) ? htmlspecialchars($evaluacion_cliente['commentaire']) : ''; ?></textarea>
          </div>
          
          <button type="submit" id="submitEvalBtn" class="btn-primary">
            <?php echo $ya_evaluado ? '✏️ Mettre à jour' : '⭐ Envoyer mon avis'; ?>
          </button>
        </form>
      </div>
    <?php elseif ($isClient && !$a_achete): ?>
      <div class="bg-yellow-50 rounded-2xl p-6 border border-yellow-200 mb-8 text-center">
        <p class="text-gray-700">📦 Vous devez avoir acheté ce produit pour laisser un avis.</p>
        <p class="text-sm text-gray-500 mt-1">La commande doit être <span class="font-bold text-green-600">"Livrée"</span></p>
      </div>
    <?php elseif (!$isClient): ?>
      <div class="bg-yellow-50 rounded-2xl p-6 border border-yellow-200 mb-8 text-center">
        <p class="text-gray-700">🔑 <a href="signin.php" class="text-[var(--primary)] hover:underline font-bold">Connectez-vous</a> pour laisser un avis.</p>
      </div>
    <?php endif; ?>
    
    <div id="evaluationsList">
      <?php if (empty($evaluaciones)): ?>
        <div class="text-center py-8 bg-white rounded-2xl border border-[#e8ddd0]">
          <p class="text-gray-400">Aucun avis pour ce produit pour le moment.</p>
          <p class="text-sm text-gray-400">Soyez le premier à donner votre avis !</p>
        </div>
      <?php else: ?>
        <?php foreach ($evaluaciones as $eval): ?>
          <div class="evaluation-card">
            <div class="flex items-center justify-between">
              <div>
                <span class="font-bold"><?php echo htmlspecialchars($eval['nom_client']); ?></span>
                <span class="text-sm text-gray-400 ml-2"><?php echo date('d/m/Y', strtotime($eval['date_evaluation'])); ?></span>
              </div>
              <div><?php echo renderStarsSize($eval['note'], '1.2rem'); ?></div>
            </div>
            <?php if (!empty($eval['commentaire'])): ?>
              <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($eval['commentaire']); ?></p>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

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

function showToast(msg, isError = false) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.className = 'toast' + (isError ? ' error' : '');
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2800);
}

function updateCartCount(total) {
    const badge = document.getElementById('cart-count');
    if (badge && total !== undefined) {
        badge.textContent = total;
        if (total > 0) badge.classList.add('show');
        else badge.classList.remove('show');
    }
}

function addToCart() {
    <?php if ($stock <= 0): ?>
    showToast('❌ Ce produit n\'est plus disponible', true);
    return;
    <?php endif; ?>

    const btn = document.getElementById('addToCartBtn');
    if (btn && btn.disabled) return;
    if (btn) { btn.disabled = true; btn.textContent = '⏳ ...'; btn.style.opacity = '0.7'; }

    const quantity = parseInt(qtyInput?.value) || 1;
    const formData = new FormData();
    formData.append('id_produit', <?php echo $produit['id_produit']; ?>);
    formData.append('quantite', quantity);

    fetch('ajouter_panier.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateCartCount(data.total_panier);
                showToast(`✓ <?php echo addslashes($produit['nom_produit']); ?> (x${quantity}) ajouté au panier !`);
                if (btn) { btn.textContent = '✓ Ajouté !'; btn.style.opacity = '1'; }
                setTimeout(() => { if (btn) { btn.innerHTML = '🛒 Ajouter au panier'; btn.disabled = false; } }, 2000);
            } else if (data.message && data.message.includes('connecter')) {
                showToast('⚠️ Connectez-vous pour ajouter au panier', true);
                setTimeout(() => { window.location.href = 'signin.php'; }, 1500);
            } else {
                showToast('❌ ' + (data.message || 'Erreur'), true);
                if (btn) { btn.innerHTML = '🛒 Ajouter au panier'; btn.disabled = false; btn.style.opacity = '1'; }
            }
        })
        .catch(() => {
            showToast('❌ Erreur de connexion au serveur', true);
            if (btn) { btn.innerHTML = '🛒 Ajouter au panier'; btn.disabled = false; btn.style.opacity = '1'; }
        });
}

document.getElementById('addToCartBtn')?.addEventListener('click', addToCart);

function submitEvaluation(event) {
    event.preventDefault();
    
    const form = document.getElementById('evaluationForm');
    const formData = new FormData(form);
    const note = formData.get('note');
    const id_produit = formData.get('id_produit');
    
    if (!note) {
        document.getElementById('starError').classList.remove('hidden');
        showToast('⚠️ Veuillez sélectionner une note', true);
        return false;
    }
    document.getElementById('starError').classList.add('hidden');
    
    if (!id_produit || id_produit <= 0) {
        showToast('❌ Erreur: ID produit invalide', true);
        return false;
    }
    
    const submitBtn = document.getElementById('submitEvalBtn');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = '⏳ Envoi...';
    
    fetch('evaluation_produit.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('✅ ' + data.message);
            setTimeout(() => { window.location.reload(); }, 1500);
        } else {
            showToast('❌ ' + (data.message || 'Erreur inconnue'), true);
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showToast('❌ Erreur de connexion au serveur', true);
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
    
    return false;
}

function initReveal() {
    const elements = document.querySelectorAll('.reveal');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) e.target.classList.add('visible');
        });
    }, { threshold: 0.1 });
    elements.forEach(el => observer.observe(el));
}

updateCartCount();
initReveal();

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('evaluationForm');
    if (form) {
        console.log('✅ Formulaire d\'évaluation trouvé');
    } else {
        console.log('ℹ️ Pas de formulaire d\'évaluation');
    }
});
</script>
</body>
</html>