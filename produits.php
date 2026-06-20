<?php
session_start();
include('connexion.php');

// Detectar tema guardado (por defecto claro)
$theme = $_COOKIE['theme'] ?? 'light';

// Récupérer les produits depuis la base de données
try {
    // Récupérer tous les produits validés avec leurs infos boutique et catégorie
    $stmt = $pdo->prepare("
        SELECT p.*, 
               b.nom_boutique, b.id_boutique,
               c.nom_categorie, c.id_categorie
        FROM produit p
        JOIN boutique b ON p.id_boutique = b.id_boutique
        JOIN categorie c ON p.id_categorie = c.id_categorie
        WHERE p.est_valide_par_admin = 1 
        AND p.statut_publie = 'Publié'
        ORDER BY p.date_creation DESC
    ");
    $stmt->execute();
    $produits_db = $stmt->fetchAll();
    
    // Récupérer toutes les catégories
    $stmt = $pdo->query("SELECT * FROM categorie ORDER BY nom_categorie");
    $categories_db = $stmt->fetchAll();
    
    // Récupérer les statistiques
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM produit WHERE est_valide_par_admin = 1 AND statut_publie = 'Publié'");
    $total_produits = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT id_boutique) as total FROM produit WHERE est_valide_par_admin = 1 AND statut_publie = 'Publié'");
    $total_boutiques = $stmt->fetch()['total'];

    #compter les articles dans le panier du client connecte (pour le badge)
    $panier_count = 0;
    if (isset($_SESSION['user_id']) && $_SESSION['user_role'] == 'client') {
        $stmtP = $pdo->prepare("SELECT SUM(quantite) as total FROM panier WHERE id_client = ?");
        $stmtP->execute([$_SESSION['user_id']]);
        $panier_count = $stmtP->fetch()['total'] ?? 0;
    }
    
} catch(PDOException $e) {
    error_log("Error produits: " . $e->getMessage());
    $produits_db = [];
    $categories_db = [];
    $total_produits = 0;
    $total_boutiques = 0;
    $panier_count = 0;
}

// Formater les produits pour le JavaScript
$produits_json = [];
foreach ($produits_db as $p) {
    $image_url = !empty($p['photo_url']) ? $p['photo_url'] : 'IMAGES/default-product.jpg';
    $produits_json[] = [
        'id' => $p['id_produit'],
        'name' => $p['nom_produit'],
        'price' => number_format($p['prix_unitaire'], 0, ',', ' ') . ' DH',
        'prix_numerique' => floatval($p['prix_unitaire']),
        'stock' => $p['stock_quantite'],
        'image' => $image_url,
        'description' => $p['description'] ?? 'Produit artisanal marocain de qualité',
        'shopId' => $p['id_boutique'],
        'shopName' => $p['nom_boutique'],
        'category' => $p['nom_categorie'],
        'categoryId' => $p['id_categorie']
    ];
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $theme; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
<title>GreenMarket – Tous les Produits Artisanaux</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  /* ========== VARIABLES DE TEMA GLOBAL ========== */
  :root {
    /* Colores principales */
    --primary: #5D0D18;
    --primary-light: #7a1020;
    --secondary: #9FB2AC;
    --secondary-dark: #8aa09a;
    --gold: #c07a1a;
    
    /* Fondos */
    --bg: #FFF9EB;
    --bg-light: #f5f0e8;
    --bg-card: #ffffff;
    --bg-input: #ffffff;
    --bg-white: #fffdf7;
    
    /* Textos */
    --text-dark: #2C2C2C;
    --text-light: #6B6B6B;
    --text-muted: #6B6B6B;
    
    /* Bordes y sombras */
    --border-color: #e8ddd0;
    --card-border: #e8ddd0;
    --shadow-color: rgba(93, 13, 24, 0.08);
    --shadow-hover: rgba(93, 13, 24, 0.14);
    
    /* Footer */
    --footer-bg: #3A0A10;
    --footer-text: #d4b8a0;
    --footer-link: #c4a890;
    --footer-link-hover: #ffffff;
    
    /* Page header */
    --page-header-bg: var(--primary);
    --page-header-text: #fff;
    --page-header-sub: rgba(255,249,235,.7);
    --page-header-border: rgba(255,249,235,.05);
    --page-header-border-top: rgba(255,249,235,.15);
    
    /* Search bar */
    --search-bg: #fff;
    --search-border: #e8ddd0;
    --search-input-bg: var(--bg);
    
    /* Sidebar */
    --sidebar-bg: #fff;
    --sidebar-border: #e8ddd0;
    --sidebar-shadow: rgba(93,13,24,0.08);
    --sidebar-header-bg: var(--primary);
    --sidebar-header-text: #fff;
    --category-hover: var(--bg);
    --category-active: rgba(93,13,24,0.05);
    --category-count-bg: #e8ddd0;
    --category-count-text: var(--text-light);
    --category-count-active: #fff;
    
    /* Product cards */
    --product-bg: #fff;
    --product-border: #e8ddd0;
    --product-shadow: rgba(93,13,24,0.08);
    --product-shadow-hover: rgba(93,13,24,0.14);
    --product-price-bg: var(--primary);
    --product-price-text: #fff;
    --product-shop-bg: var(--secondary);
    --product-shop-text: #fff;
    --product-stats-border: #e8ddd0;
    --product-stock-color: var(--primary);
    --product-stock-low: #e67e22;
    --product-stock-out: #e74c3c;
    
    /* Toast */
    --toast-bg: var(--primary);
    --toast-text: #fff;
    
    /* Empty state */
    --empty-color: var(--text-light);
  }

  /* ========== TEMA OSCURO BEIGE ========== */
  [data-theme="dark"] {
    /* Colores principales */
    --primary: #8a6048;
    --primary-light: #a0785a;
    --secondary: #6d4c3a;
    --secondary-dark: #5a4a3a;
    --gold: #d4a85c;
    
    /* Fondos */
    --bg: #2c241e;
    --bg-light: #3d3229;
    --bg-card: #3d3229;
    --bg-input: #4d3d32;
    --bg-white: #3d3229;
    
    /* Textos */
    --text-dark: #f0e6d8;
    --text-light: #b8a896;
    --text-muted: #b8a896;
    
    /* Bordes y sombras */
    --border-color: #5a4a3a;
    --card-border: #5a4a3a;
    --shadow-color: rgba(0, 0, 0, 0.3);
    --shadow-hover: rgba(0, 0, 0, 0.4);
    
    /* Footer */
    --footer-bg: #1a1410;
    --footer-text: #b8a896;
    --footer-link: #b8a896;
    --footer-link-hover: #f0e6d8;
    
    /* Page header */
    --page-header-bg: #1a1410;
    --page-header-text: #f0e6d8;
    --page-header-sub: rgba(240,230,216,0.6);
    --page-header-border: rgba(240,230,216,0.05);
    --page-header-border-top: rgba(240,230,216,0.15);
    
    /* Search bar */
    --search-bg: #3d3229;
    --search-border: #5a4a3a;
    --search-input-bg: #4d3d32;
    
    /* Sidebar */
    --sidebar-bg: #3d3229;
    --sidebar-border: #5a4a3a;
    --sidebar-shadow: rgba(0, 0, 0, 0.3);
    --sidebar-header-bg: #1a1410;
    --sidebar-header-text: #f0e6d8;
    --category-hover: #4d3d32;
    --category-active: rgba(240,230,216,0.05);
    --category-count-bg: #4d3d32;
    --category-count-text: #b8a896;
    --category-count-active: #f0e6d8;
    
    /* Product cards */
    --product-bg: #3d3229;
    --product-border: #5a4a3a;
    --product-shadow: rgba(0, 0, 0, 0.3);
    --product-shadow-hover: rgba(0, 0, 0, 0.4);
    --product-price-bg: #6d4c3a;
    --product-price-text: #f0e6d8;
    --product-shop-bg: #6d4c3a;
    --product-shop-text: #f0e6d8;
    --product-stats-border: #5a4a3a;
    --product-stock-color: var(--gold);
    --product-stock-low: #e6a822;
    --product-stock-out: #e85a4a;
    
    /* Toast */
    --toast-bg: #6d4c3a;
    --toast-text: #f0e6d8;
    
    /* Empty state */
    --empty-color: #b8a896;
  }

  /* ========== STYLES UNIFIÉS ========== */
  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'Lato', sans-serif;
    background: var(--bg);
    color: var(--text-dark);
    min-height: 100vh;
    transition: background-color 0.3s ease, color 0.3s ease;
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

  /* Page Header */
  .page-header {
    background: var(--page-header-bg);
    padding: 4rem 2.5rem 3rem;
    position: relative;
    overflow: hidden;
    transition: background 0.3s ease;
  }
  .page-header::before {
    content: '';
    position: absolute;
    right: -80px;
    top: -80px;
    width: 420px;
    height: 420px;
    border: 55px solid var(--page-header-border);
    border-radius: 50%;
    transition: border-color 0.3s ease;
  }
  .page-header::after {
    content: '';
    position: absolute;
    left: 4%;
    bottom: -70px;
    width: 240px;
    height: 240px;
    border: 40px solid rgba(159,178,172,.10);
    border-radius: 50%;
  }
  .header-inner { position: relative; z-index: 1; }
  .header-eyebrow {
    font-size: .75rem;
    font-weight: 600;
    letter-spacing: .2em;
    text-transform: uppercase;
    color: var(--page-header-sub);
    margin-bottom: 1rem;
    transition: color 0.3s ease;
  }
  .page-header h1 {
    font-family: 'Playfair Display', serif;
    font-size: clamp(32px, 5vw, 52px);
    font-weight: 700;
    line-height: 1.1;
    color: var(--page-header-text);
    margin-bottom: 1rem;
    transition: color 0.3s ease;
  }
  .page-header h1 em {
    font-style: italic;
    color: var(--gold);
    display: block;
  }
  .page-header p {
    color: var(--page-header-sub);
    font-size: 1rem;
    max-width: 500px;
    transition: color 0.3s ease;
  }
  .header-stats {
    display: flex;
    gap: 2.5rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--page-header-border-top);
    transition: border-color 0.3s ease;
  }
  .h-stat-val {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    font-weight: 700;
    color: var(--page-header-text);
    display: block;
    line-height: 1;
    transition: color 0.3s ease;
  }
  .h-stat-label {
    font-size: .7rem;
    color: var(--page-header-sub);
    letter-spacing: .1em;
    text-transform: uppercase;
    transition: color 0.3s ease;
  }

  /* Search Bar */
  .search-bar-wrap {
    background: var(--search-bg);
    padding: 1.2rem 2.5rem;
    border-bottom: 1px solid var(--search-border);
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
    transition: background 0.3s ease, border-color 0.3s ease;
  }
  .search-input-wrapper {
    flex: 1;
    position: relative;
    min-width: 200px;
  }
  .search-input-wrapper svg {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--secondary);
  }
  .search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.8rem;
    border: 1.5px solid var(--search-border);
    border-radius: 999px;
    background: var(--search-input-bg);
    font-family: 'Lato', sans-serif;
    font-size: 0.9rem;
    color: var(--text-dark);
    outline: none;
    transition: border-color 0.2s, background 0.3s ease, color 0.3s ease;
  }
  .search-input:focus { border-color: var(--primary); }
  .filter-select {
    padding: 0.75rem 2rem 0.75rem 1rem;
    border: 1.5px solid var(--search-border);
    border-radius: 999px;
    background: var(--search-input-bg);
    font-family: 'Lato', sans-serif;
    font-size: 0.85rem;
    color: var(--text-dark);
    outline: none;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 12 12'%3E%3Cpath fill='%235d0d18' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 1rem center;
    transition: background 0.3s ease, border-color 0.3s ease, color 0.3s ease;
  }
  [data-theme="dark"] .filter-select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 12 12'%3E%3Cpath fill='%23f0e6d8' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
  }
  [data-theme="dark"] .filter-select option {
    background: var(--bg-input);
    color: var(--text-dark);
  }

  /* Main Layout */
  .main-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 2.5rem;
    gap: 2rem;
  }

  /* Sidebar */
  .sidebar { position: sticky; top: 88px; align-self: start; }
  .sidebar-section {
    background: var(--sidebar-bg);
    border: 1.5px solid var(--sidebar-border);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 16px var(--sidebar-shadow);
    transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
  }
  .sidebar-header {
    background: var(--sidebar-header-bg);
    padding: 1rem 1.3rem;
    transition: background 0.3s ease;
  }
  .sidebar-header h3 {
    font-family: 'Playfair Display', serif;
    color: var(--sidebar-header-text);
    font-size: 1.1rem;
    font-weight: 600;
    transition: color 0.3s ease;
  }
  .category-list { padding: 0.5rem 0; }
  .category-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1.3rem;
    cursor: pointer;
    transition: all 0.2s;
    border-left: 3px solid transparent;
    color: var(--text-dark);
  }
  .category-item:hover { background: var(--category-hover); border-left-color: var(--secondary); }
  .category-item.active {
    background: var(--category-active);
    border-left-color: var(--primary);
  }
  .cat-left { display: flex; align-items: center; gap: 0.7rem; }
  .cat-icon { font-size: 1.1rem; }
  .cat-name { font-size: 0.85rem; font-weight: 500; }
  .cat-count {
    background: var(--category-count-bg);
    color: var(--category-count-text);
    padding: 0.15rem 0.55rem;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 600;
    transition: background 0.3s ease, color 0.3s ease;
  }
  .category-item.active .cat-count {
    background: var(--primary);
    color: var(--category-count-active);
  }
  [data-theme="dark"] .category-item.active .cat-count {
    background: var(--gold);
    color: var(--bg);
  }

  /* Products Grid */
  .stores-title-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    margin-bottom: 1.5rem;
  }
  .stores-area h2 {
    font-family: 'Playfair Display', serif;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-dark);
    transition: color 0.3s ease;
  }
  .stores-count {
    font-size: 0.85rem;
    color: var(--text-light);
    transition: color 0.3s ease;
  }
  .stores-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
  }

  /* Product Card */
  .product-card {
    background: var(--product-bg);
    border: 1.5px solid var(--product-border);
    border-radius: 20px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease, border-color 0.3s ease;
    box-shadow: 0 4px 16px var(--product-shadow);
  }
  .product-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 36px var(--product-shadow-hover);
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
    transition: transform 0.5s ease;
  }
  .product-card:hover .product-banner img { transform: scale(1.07); }
  .product-price-badge {
    position: absolute;
    top: 0.8rem;
    right: 0.8rem;
    background: var(--product-price-bg);
    color: var(--product-price-text);
    font-size: 0.9rem;
    font-weight: 700;
    padding: 0.3rem 0.8rem;
    border-radius: 999px;
    z-index: 2;
    transition: background 0.3s ease, color 0.3s ease;
  }
  .product-body { padding: 1.2rem; }
  .product-name {
    font-family: 'Playfair Display', serif;
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--text-dark);
    line-height: 1.3;
    margin-bottom: 0.3rem;
    transition: color 0.3s ease;
  }
  .product-shop-tag {
    display: inline-block;
    background: var(--product-shop-bg);
    color: var(--product-shop-text);
    font-size: 0.65rem;
    font-weight: 600;
    letter-spacing: 0.05em;
    padding: 0.15rem 0.7rem;
    border-radius: 999px;
    margin-bottom: 0.6rem;
    cursor: pointer;
    transition: background 0.2s, color 0.3s ease;
  }
  .product-shop-tag:hover { background: var(--primary); }
  .product-desc {
    font-size: 0.85rem;
    color: var(--text-light);
    line-height: 1.5;
    margin-bottom: 1rem;
    transition: color 0.3s ease;
  }
  .product-stats {
    display: flex;
    border-top: 1px solid var(--product-stats-border);
    padding-top: 0.8rem;
    align-items: center;
    justify-content: space-between;
    transition: border-color 0.3s ease;
  }
  .product-stock {
    font-family: 'Playfair Display', serif;
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--product-stock-color);
    transition: color 0.3s ease;
  }
  .stock-label {
    font-size: 0.65rem;
    color: var(--text-light);
    text-transform: uppercase;
    transition: color 0.3s ease;
  }
  .stock-low { color: var(--product-stock-low); }
  .stock-out { color: var(--product-stock-out); }

  /* Buttons */
  .add-cart-btn {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 0.5rem 1.2rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, transform 0.2s;
  }
  .add-cart-btn:hover {
    background: var(--primary-light);
    transform: translateY(-2px);
  }
  [data-theme="dark"] .add-cart-btn {
    color: var(--bg);
  }

  /* Toast */
  .toast {
    position: fixed;
    bottom: 28px;
    right: 28px;
    background: var(--toast-bg);
    color: var(--toast-text);
    padding: 14px 22px;
    border-radius: 14px;
    font-weight: 700;
    z-index: 9999;
    transform: translateY(80px);
    opacity: 0;
    transition: 0.4s cubic-bezier(.22,1,.36,1);
  }
  .toast.show { transform: translateY(0); opacity: 1; }

  /* Empty state */
  .empty-state {
    text-align: center;
    padding: 3rem;
    color: var(--empty-color);
    grid-column: 1/-1;
    transition: color 0.3s ease;
  }
  .empty-icon { font-size: 3rem; margin-bottom: 1rem; }

  /* Responsive */
  @media (max-width: 900px) {
    .main-layout { grid-template-columns: 1fr; }
    .sidebar { position: static; }
  }
  @media (max-width: 768px) {
    .page-header { padding: 2.5rem 1.2rem 2rem; }
    .search-bar-wrap { padding: 1rem; flex-direction: column; }
    .search-input-wrapper { width: 100%; }
    .filter-select { width: 100%; }
    .main-layout { padding: 1.2rem 1rem; }
    .header-stats {
      gap: 1.5rem;
      flex-wrap: wrap;
    }
    .stores-grid {
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    }
  }
</style>
</head>
<body data-active-page="produits">

<?php include 'header.php'; ?>

<!-- HEADER SECTION -->
<div class="page-header">
  <div class="header-inner">
    <div class="header-eyebrow">🇲🇦 Artisanat &amp; Traditions marocaines</div>
    <h1>Nos Produits<br><em>Artisanaux</em></h1>
    <p>Découvrez toutes les créations de nos artisans marocains, des pièces uniques faites avec passion.</p>
    <div class="header-stats">
      <div><span class="h-stat-val" id="statProducts"><?php echo $total_produits; ?></span><span class="h-stat-label">Produits disponibles</span></div>
      <div><span class="h-stat-val" id="statStores"><?php echo $total_boutiques; ?></span><span class="h-stat-label">Boutiques partenaires</span></div>
    </div>
  </div>
</div>

<!-- SEARCH BAR -->
<div class="search-bar-wrap">
  <div class="search-input-wrapper">
    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
      <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
    </svg>
    <input class="search-input" id="searchInput" type="text" placeholder="Rechercher un produit, un artisan...">
  </div>
  <select class="filter-select" id="catFilter">
    <option value="">Toutes les catégories</option>
    <?php foreach ($categories_db as $cat): ?>
      <option value="<?php echo htmlspecialchars($cat['nom_categorie']); ?>">
        <?php echo htmlspecialchars($cat['nom_categorie']); ?>
      </option>
    <?php endforeach; ?>
  </select>
  <select class="filter-select" id="sortFilter">
    <option value="">Trier par</option>
    <option value="price_asc">Prix : croissant</option>
    <option value="price_desc">Prix : décroissant</option>
    <option value="name">Nom A–Z</option>
  </select>
</div>

<!-- MAIN CONTENT -->
<div class="main-layout">
  <aside class="sidebar">
    <div class="sidebar-section reveal">
      <div class="sidebar-header">
        <h3>Catégories artisanales</h3>
      </div>
      <div class="category-list" id="categoryList"></div>
    </div>
  </aside>
  
  <section class="stores-area">
    <div class="stores-title-row reveal">
      <h2 id="storesTitle">Tous les produits artisanaux</h2>
      <span class="stores-count" id="storesCount"></span>
    </div>
    <div class="stores-grid reveal" id="productsGrid"></div>
  </section>
</div>

<div class="toast" id="toast"></div>

<script>
// ========== DONNÉES DEPUIS PHP ==========
const produitsFromDB = <?php echo json_encode($produits_json); ?>;
const categoriesFromDB = <?php echo json_encode($categories_db); ?>;

let allProducts = produitsFromDB;

// Configuration des icônes pour catégories
const categoryIcons = {
    'Caftans & Vêtements traditionnels': '👘',
    'Tapis & Tissage': '🪑',
    'Poterie & Céramique': '🏺',
    'Marqueterie & Bois': '🪵',
    'Bijoux & Joaillerie': '💍',
    'Lampes & Fer forgé': '🕯️',
    'Cosmétiques naturels': '🧴',
    'Produits du terroir': '🍯'
};

function getCategoryIcon(categoryName) {
    return categoryIcons[categoryName] || '📦';
}

let activeCategory = null;

// Lire paramètres URL
(function() {
    const params = new URLSearchParams(window.location.search);
    const catParam = params.get('cat');
    if (catParam) activeCategory = catParam;
})();

// ========== FONCTIONS PANIER ==========
function showToast(msg) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2800);
}

let panierCount = <?php echo json_encode($panier_count); ?>;

function updateCartCount() {
    const badge = document.getElementById('cart-count');
    if (badge) badge.textContent = panierCount;
}

function addToCart(product) {
    fetch('ajouter_panier.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id_produit=' + encodeURIComponent(product.id) + '&quantite=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            panierCount = data.total_panier;
            const badge = document.getElementById('cart-count');
            if (badge) badge.textContent = data.total_panier;
            showToast(`✓ ${product.name} ajouté au panier !`);
        } else {
            showToast(data.message || 'Erreur lors de l\'ajout au panier');
        }
    })
    .catch(() => showToast('Erreur de connexion au serveur'));
}

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[m]));
}

function getStockClass(stock) {
    if (stock <= 0) return 'stock-out';
    if (stock < 5) return 'stock-low';
    return '';
}

function getStockText(stock) {
    if (stock <= 0) return 'Rupture';
    if (stock < 5) return 'Plus que ' + stock;
    return stock + ' en stock';
}

// ========== RENDU CATÉGORIES ==========
function renderCategories() {
    const list = document.getElementById('categoryList');
    if (!list) return;
    
    const categoryCounts = {};
    allProducts.forEach(p => {
        categoryCounts[p.category] = (categoryCounts[p.category] || 0) + 1;
    });
    
    list.innerHTML = `
        <div class="category-item ${activeCategory === null ? 'active' : ''}" data-name="">
            <div class="cat-left">
                <span class="cat-icon">🏪</span>
                <span class="cat-name">Tous les produits</span>
            </div>
            <span class="cat-count">${allProducts.length}</span>
        </div>
        ${categoriesFromDB.map(c => `
            <div class="category-item ${activeCategory === c.nom_categorie ? 'active' : ''}" data-name="${escapeHtml(c.nom_categorie)}">
                <div class="cat-left">
                    <span class="cat-icon">${getCategoryIcon(c.nom_categorie)}</span>
                    <span class="cat-name">${escapeHtml(c.nom_categorie)}</span>
                </div>
                <span class="cat-count">${categoryCounts[c.nom_categorie] || 0}</span>
            </div>
        `).join('')}
    `;
    
    list.querySelectorAll('.category-item').forEach(el => {
        el.addEventListener('click', () => {
            activeCategory = el.dataset.name || null;
            const catFilter = document.getElementById('catFilter');
            if (catFilter) catFilter.value = activeCategory || '';
            renderCategories();
            renderProducts();
        });
    });
}

// ========== RENDU PRODUITS ==========
function renderProducts() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const sortValue = document.getElementById('sortFilter')?.value || '';
    
    let filtered = allProducts.filter(p => {
        const matchSearch = !searchTerm || 
            p.name.toLowerCase().includes(searchTerm) || 
            p.shopName.toLowerCase().includes(searchTerm);
        const matchCategory = !activeCategory || p.category === activeCategory;
        return matchSearch && matchCategory;
    });
    
    // Tri
    if (sortValue === 'price_asc') {
        filtered.sort((a, b) => a.prix_numerique - b.prix_numerique);
    } else if (sortValue === 'price_desc') {
        filtered.sort((a, b) => b.prix_numerique - a.prix_numerique);
    } else if (sortValue === 'name') {
        filtered.sort((a, b) => a.name.localeCompare(b.name, 'fr'));
    }
    
    // Mettre à jour les titres
    const titleEl = document.getElementById('storesTitle');
    const countEl = document.getElementById('storesCount');
    if (titleEl) titleEl.textContent = activeCategory || 'Tous les produits artisanaux';
    if (countEl) countEl.textContent = `${filtered.length} produit${filtered.length > 1 ? 's' : ''}`;
    
    const grid = document.getElementById('productsGrid');
    if (!grid) return;
    
    if (filtered.length === 0) {
        grid.innerHTML = `<div class="empty-state"><div class="empty-icon">🛍️</div><p>Aucun produit trouvé.</p></div>`;
        return;
    }
    
    const fallbackImg = 'https://placehold.co/500x400/5D0D18/white?text=Produit';
    const stockClass = getStockClass;
    const stockText = getStockText;
    
    grid.innerHTML = filtered.map(p => `
        <div class="product-card">
            <div class="product-banner" onclick="window.location.href='info-produit.php?id=${p.id}'">
                <img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.name)}" loading="lazy" 
                     onerror="this.src='${fallbackImg}'">
                <span class="product-price-badge">${escapeHtml(p.price)}</span>
            </div>
            <div class="product-body">
                <div class="product-name">${escapeHtml(p.name)}</div>
                <span class="product-shop-tag" onclick="window.location.href='info-store.php?id=${p.shopId}'">
                    🏪 ${escapeHtml(p.shopName)}
                </span>
                <p class="product-desc">${escapeHtml(p.description.substring(0, 100))}${p.description.length > 100 ? '…' : ''}</p>
                <div class="product-stats">
                    <div>
                        <span class="product-stock ${stockClass(p.stock)}">${stockText(p.stock)}</span>
                    </div>
                    <button class="add-cart-btn" onclick='addToCart(${JSON.stringify(p)})'>
                        🛒 Ajouter
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

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

// ========== INITIALISATION ==========
function init() {
    renderCategories();
    renderProducts();
    updateCartCount();
    initReveal();
}

// Événements
document.getElementById('searchInput')?.addEventListener('input', renderProducts);
document.getElementById('sortFilter')?.addEventListener('change', renderProducts);
document.getElementById('catFilter')?.addEventListener('change', (e) => {
    activeCategory = e.target.value || null;
    renderCategories();
    renderProducts();
});

// Démarrer
init();
</script>
</body>
</html>