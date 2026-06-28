<?php
session_start();
include('connexion.php');

$theme = $_COOKIE['theme'] ?? 'light';

$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$isClient = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client';

try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               b.nom_boutique, b.id_boutique,
               c.nom_categorie, c.id_categorie
        FROM produit p
        JOIN boutique b ON p.id_boutique = b.id_boutique
        LEFT JOIN categorie c ON p.id_categorie = c.id_categorie
        WHERE p.est_valide_par_admin = 1 
        AND p.statut_publie = 'Publié'
        ORDER BY p.date_creation DESC
    ");
    $stmt->execute();
    $produits_db = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT * FROM categorie ORDER BY nom_categorie");
    $categories_db = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM produit WHERE est_valide_par_admin = 1 AND statut_publie = 'Publié'");
    $total_produits = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT id_boutique) as total FROM produit WHERE est_valide_par_admin = 1 AND statut_publie = 'Publié'");
    $total_boutiques = $stmt->fetch()['total'];

    $panier_count = 0;
    if (isset($_SESSION['user_id']) && $_SESSION['user_role'] == 'client') {
        $stmtP = $pdo->prepare("SELECT SUM(quantite) as total FROM panier WHERE id_client = ?");
        $stmtP->execute([$_SESSION['user_id']]);
        $panier_count = $stmtP->fetch()['total'] ?? 0;
    }
    
    $favoris_ids = [];
    if ($isClient) {
        $stmtF = $pdo->prepare("SELECT id_produit FROM favoris WHERE id_client = ?");
        $stmtF->execute([$_SESSION['user_id']]);
        $favoris_ids = $stmtF->fetchAll(PDO::FETCH_COLUMN);
    }
    
} catch(PDOException $e) {
    error_log("Error produits: " . $e->getMessage());
    $produits_db = [];
    $categories_db = [];
    $total_produits = 0;
    $total_boutiques = 0;
    $panier_count = 0;
    $favoris_ids = [];
}

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
        'category' => $p['nom_categorie'] ?? 'Sans catégorie',
        'categoryId' => $p['id_categorie'],
        'isFavori' => in_array($p['id_produit'], $favoris_ids)
    ];
}

$header_guard = [
    'produits_json' => $produits_json,
    'isAdmin' => $isAdmin,
    'isClient' => $isClient,
    'total_produits' => $total_produits,
    'total_boutiques' => $total_boutiques,
    'panier_count' => $panier_count,
    'favoris_ids' => $favoris_ids,
    'categories_db' => $categories_db,
    'theme' => $theme
];
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
  :root {
    --primary: #5D0D18;
    --primary-light: #7a1020;
    --secondary: #9FB2AC;
    --secondary-dark: #8aa09a;
    --gold: #c07a1a;
    --bg: #FFF9EB;
    --bg-light: #f5f0e8;
    --bg-card: #ffffff;
    --bg-input: #ffffff;
    --bg-white: #fffdf7;
    --text-dark: #2C2C2C;
    --text-light: #6B6B6B;
    --text-muted: #6B6B6B;
    --border-color: #e8ddd0;
    --card-border: #e8ddd0;
    --shadow-color: rgba(93, 13, 24, 0.08);
    --shadow-hover: rgba(93, 13, 24, 0.14);
    --footer-bg: #3A0A10;
    --footer-text: #d4b8a0;
    --footer-link: #c4a890;
    --footer-link-hover: #ffffff;
    --page-header-bg: var(--primary);
    --page-header-text: #fff;
    --page-header-sub: rgba(255,249,235,.7);
    --page-header-border: rgba(255,249,235,.05);
    --page-header-border-top: rgba(255,249,235,.15);
    --search-bg: #fff;
    --search-border: #e8ddd0;
    --search-input-bg: var(--bg);
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
    --toast-bg: var(--primary);
    --toast-text: #fff;
    --empty-color: var(--text-light);
    --pagination-bg: #fff;
    --pagination-border: #e8ddd0;
    --pagination-text: var(--text-dark);
    --pagination-active-bg: var(--primary);
    --pagination-active-text: #fff;
    --danger: #c62828;
    --danger-hover: #b71c1c;
  }

  [data-theme="dark"] {
    --primary: #8a6048;
    --primary-light: #a0785a;
    --secondary: #6d4c3a;
    --secondary-dark: #5a4a3a;
    --gold: #d4a85c;
    --bg: #2c241e;
    --bg-light: #3d3229;
    --bg-card: #3d3229;
    --bg-input: #4d3d32;
    --bg-white: #3d3229;
    --text-dark: #f0e6d8;
    --text-light: #b8a896;
    --text-muted: #b8a896;
    --border-color: #5a4a3a;
    --card-border: #5a4a3a;
    --shadow-color: rgba(0, 0, 0, 0.3);
    --shadow-hover: rgba(0, 0, 0, 0.4);
    --footer-bg: #1a1410;
    --footer-text: #b8a896;
    --footer-link: #b8a896;
    --footer-link-hover: #f0e6d8;
    --page-header-bg: #1a1410;
    --page-header-text: #f0e6d8;
    --page-header-sub: rgba(240,230,216,0.6);
    --page-header-border: rgba(240,230,216,0.05);
    --page-header-border-top: rgba(240,230,216,0.15);
    --search-bg: #3d3229;
    --search-border: #5a4a3a;
    --search-input-bg: #4d3d32;
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
    --toast-bg: #6d4c3a;
    --toast-text: #f0e6d8;
    --empty-color: #b8a896;
    --pagination-bg: #3d3229;
    --pagination-border: #5a4a3a;
    --pagination-text: #f0e6d8;
    --pagination-active-bg: #6d4c3a;
    --pagination-active-text: #f0e6d8;
    --danger: #ef5350;
    --danger-hover: #c62828;
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: 'Lato', sans-serif;
    background: var(--bg);
    color: var(--text-dark);
    min-height: 100vh;
    transition: background-color 0.3s ease, color 0.3s ease;
  }

  h1, h2, h3, .playfair { font-family: 'Playfair Display', serif; }

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
  .reveal-left {
    opacity: 0;
    transform: translateX(-40px);
    transition: opacity 0.7s ease, transform 0.7s ease;
  }
  .reveal-left.visible {
    opacity: 1;
    transform: translateX(0);
  }
  .reveal-right {
    opacity: 0;
    transform: translateX(40px);
    transition: opacity 0.7s ease, transform 0.7s ease;
  }
  .reveal-right.visible {
    opacity: 1;
    transform: translateX(0);
  }

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

  .main-layout {
    display: grid;
    grid-template-columns: 280px 1fr;
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 2.5rem;
    gap: 2rem;
  }

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
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: background 0.3s ease;
  }
  .sidebar-header h3 {
    font-family: 'Playfair Display', serif;
    color: var(--sidebar-header-text);
    font-size: 1.1rem;
    font-weight: 600;
    transition: color 0.3s ease;
  }
  .add-btn {
    background: rgba(255,255,255,0.15);
    color: var(--sidebar-header-text);
    border: 1px solid rgba(255,255,255,0.25);
    border-radius: 999px;
    padding: 0.3rem 0.8rem;
    font-size: 0.7rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    transition: background 0.2s;
  }
  .add-btn:hover { background: rgba(255,255,255,0.25); }

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

  .delete-cat-btn {
    background: none;
    border: none;
    color: #dc3545;
    cursor: pointer;
    font-size: 0.75rem;
    padding: 0.15rem 0.3rem;
    border-radius: 4px;
    transition: all 0.2s;
    opacity: 0.4;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .delete-cat-btn:hover {
    background: #fee !important;
    opacity: 1;
    transform: scale(1.1);
  }
  .category-item:hover .delete-cat-btn {
    opacity: 1;
  }
  [data-theme="dark"] .delete-cat-btn:hover {
    background: #4a2a2a !important;
  }
  .category-item .cat-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .add-cat-form {
    padding: 1.2rem;
    border-top: 1px solid var(--sidebar-border);
    display: none;
    transition: border-color 0.3s ease;
  }
  .add-cat-form.open { display: block; }
  .add-cat-form label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    color: var(--text-light);
    display: block;
    margin-bottom: 0.35rem;
    transition: color 0.3s ease;
  }
  .add-cat-form input {
    width: 100%;
    padding: 0.6rem 0.8rem;
    border: 1.5px solid var(--sidebar-border);
    border-radius: 12px;
    font-family: 'Lato', sans-serif;
    font-size: 0.85rem;
    background: var(--bg-input);
    color: var(--text-dark);
    margin-bottom: 0.75rem;
    transition: border-color 0.2s, background 0.3s ease, color 0.3s ease;
  }
  .add-cat-form input:focus { border-color: var(--primary); outline: none; }
  .form-actions { display: flex; gap: 0.5rem; }
  .btn-sm-wine {
    flex: 1;
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 0.55rem;
    border-radius: 999px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.2s;
  }
  .btn-sm-wine:hover { background: var(--primary-light); }
  .btn-sm-cancel {
    flex: 1;
    background: var(--category-count-bg);
    color: var(--text-light);
    border: none;
    padding: 0.55rem;
    border-radius: 999px;
    cursor: pointer;
    font-weight: 600;
    transition: background 0.3s ease, color 0.3s ease;
  }

  .stores-title-row {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 0.5rem;
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
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
  }

  .product-card {
    background: var(--product-bg);
    border: 1.5px solid var(--product-border);
    border-radius: 20px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease, border-color 0.3s ease;
    box-shadow: 0 4px 16px var(--product-shadow);
    position: relative;
  }
  .product-card:hover {
    box-shadow: 0 16px 36px var(--product-shadow-hover);
  }
  .product-card:not(:has(button:hover)):hover {
    transform: translateY(-6px);
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
    flex-wrap: wrap;
    border-top: 1px solid var(--product-stats-border);
    padding-top: 0.8rem;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
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

  .favori-btn {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    z-index: 5;
    background: rgba(255,255,255,0.95);
    border: none;
    border-radius: 50%;
    width: 38px;
    height: 38px;
    cursor: pointer;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.15);
    transition: all 0.2s;
    color: var(--text-light);
  }
  .favori-btn:hover {
    transform: scale(1.1);
    background: #fff;
  }
  .favori-btn.active {
    color: #c0392b;
  }
  [data-theme="dark"] .favori-btn {
    background: rgba(60,50,40,0.95);
  }
  [data-theme="dark"] .favori-btn:hover {
    background: #4d3d32;
  }

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
  .add-cart-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
  }

  .admin-btn-delete {
    background: #dc3545;
    color: #fff;
    border: none;
    padding: 0.4rem 0.8rem;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 0.3rem;
  }
  .admin-btn-delete:hover {
    background: #c82333;
    transform: scale(1.05);
  }

  .pagination-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 0.5rem;
    padding: 2rem 0 1rem;
    flex-wrap: wrap;
  }
  .pagination-wrapper button {
    min-width: 44px;
    height: 44px;
    border-radius: 999px;
    border: 2px solid var(--pagination-border);
    background: var(--pagination-bg);
    color: var(--pagination-text);
    font-weight: 600;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .pagination-wrapper button:hover:not(:disabled) {
    background: var(--pagination-active-bg);
    color: var(--pagination-active-text);
    border-color: var(--pagination-active-bg);
    transform: translateY(-2px);
  }
  .pagination-wrapper button.active {
    background: var(--pagination-active-bg);
    color: var(--pagination-active-text);
    border-color: var(--pagination-active-bg);
  }
  .pagination-wrapper button:disabled {
    opacity: 0.4;
    cursor: not-allowed;
  }
  .pagination-wrapper .page-btn {
    min-width: 44px;
  }
  .pagination-wrapper .nav-btn {
    padding: 0 1.2rem;
    gap: 0.3rem;
  }
  .pagination-info {
    font-size: 0.85rem;
    color: var(--text-light);
    text-align: center;
    padding: 0.5rem 0;
    transition: color 0.3s ease;
  }

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
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
  }
  .toast.show { transform: translateY(0); opacity: 1; }

  .empty-state {
    text-align: center;
    padding: 3rem;
    color: var(--empty-color);
    grid-column: 1/-1;
    transition: color 0.3s ease;
  }
  .empty-icon { font-size: 3rem; margin-bottom: 1rem; }

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
    .pagination-wrapper button {
      min-width: 38px;
      height: 38px;
      font-size: 0.8rem;
    }
    .pagination-wrapper .nav-btn {
      padding: 0 0.8rem;
    }
  }
  #confirm-modal {
    display: none; position: fixed; inset: 0; z-index: 9998;
    align-items: center; justify-content: center;
  }
  #confirm-modal.show { display: flex; }
  #confirm-overlay {
    position: absolute; inset: 0;
    background: rgba(0,0,0,0.45); backdrop-filter: blur(3px);
  }
  #confirm-box {
    position: relative; background: #fff; border-radius: 20px;
    padding: 2rem 1.8rem 1.5rem; max-width: 340px; width: 90%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2); text-align: center;
    animation: modalIn 0.25s cubic-bezier(.22,1,.36,1);
  }
  @keyframes modalIn {
    from { opacity: 0; transform: scale(0.88) translateY(20px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
  }
  #confirm-icon { font-size: 2.5rem; margin-bottom: 0.8rem; }
  #confirm-title {
    font-family: 'Playfair Display', serif; font-size: 1.15rem;
    font-weight: 700; color: #2C2C2C; margin-bottom: 0.4rem;
  }
  #confirm-msg { font-size: 0.88rem; color: #6B6B6B; margin-bottom: 1.4rem; }
  .confirm-btns { display: flex; gap: 0.8rem; justify-content: center; }
  .confirm-btns button {
    flex: 1; padding: 0.65rem 1rem; border-radius: 999px;
    font-weight: 700; font-size: 0.9rem; cursor: pointer; border: none; transition: all 0.2s;
  }
  #confirm-cancel { background: #f5f0e8; color: #2C2C2C; }
  #confirm-cancel:hover { opacity: 0.8; }
  #confirm-ok { background: #c0392b; color: #fff; }
  #confirm-ok:hover { background: #a93226; }
</style>
</head>
<body data-active-page="produits">

<?php 
include 'header.php';

$produits_json = $header_guard['produits_json'];
$isAdmin = $header_guard['isAdmin'];
$isClient = $header_guard['isClient'];
$total_produits = $header_guard['total_produits'];
$total_boutiques = $header_guard['total_boutiques'];
$panier_count = $header_guard['panier_count'];
$favoris_ids = $header_guard['favoris_ids'];
$categories_db = $header_guard['categories_db'];
$theme = $header_guard['theme'];
?>

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
  <select class="filter-select" id="perPageFilter">
    <option value="6">6 par page</option>
    <option value="9">9 par page</option>
    <option value="12" selected>12 par page</option>
    <option value="18">18 par page</option>
    <option value="24">24 par page</option>
  </select>
</div>

<div class="main-layout">
  <aside class="sidebar">
    <div class="sidebar-section reveal-left">
      <div class="sidebar-header">
        <h3>Catégories artisanales</h3>
        <?php if ($isAdmin): ?>
          <button class="add-btn" id="btnToggleCategory">➕ Ajouter</button>
        <?php endif; ?>
      </div>
      <div class="category-list" id="categoryList"></div>
      <?php if ($isAdmin): ?>
      <div class="add-cat-form" id="addCatForm">
        <label>Nom de la catégorie</label>
        <input type="text" id="catNameInput" placeholder="Ex: Poterie, Tissage...">
        <label>Icône (emoji)</label>
        <input type="text" id="catIconInput" placeholder="📦" maxlength="2">
        <div class="form-actions">
          <button class="btn-sm-cancel" id="btnCancelCategory">Annuler</button>
          <button class="btn-sm-wine" id="btnSaveCategory">Enregistrer</button>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </aside>
  
  <section class="stores-area">
    <div class="stores-title-row reveal">
      <h2 id="storesTitle">Tous les produits artisanaux</h2>
      <span class="stores-count" id="storesCount"></span>
    </div>
    <div class="stores-grid reveal" id="productsGrid"></div>
    
    <div class="pagination-info" id="paginationInfo"></div>
    <div class="pagination-wrapper" id="paginationWrapper"></div>
  </section>
</div>

<div class="toast" id="toast"></div>

<div id="confirm-modal">
  <div id="confirm-overlay"></div>
  <div id="confirm-box">
    <div id="confirm-icon">⚠️</div>
    <div id="confirm-title"></div>
    <div id="confirm-msg"></div>
    <div class="confirm-btns">
      <button id="confirm-cancel">Annuler</button>
      <button id="confirm-ok">Confirmer</button>
    </div>
  </div>
</div>

<script>
var produitsFromDB = <?php echo json_encode($produits_json); ?>;
var categoriesFromDB = <?php echo json_encode($categories_db); ?>;
var isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
var isClient = <?php echo $isClient ? 'true' : 'false'; ?>;

console.log('📦 Produits chargés:', produitsFromDB.length);
console.log('📂 Catégories chargées:', categoriesFromDB.length);
console.log('👑 Admin:', isAdmin);
console.log('👤 Client:', isClient);

var allProducts = produitsFromDB;
var filteredProducts = [];
var activeCategory = null;
var currentPage = 1;
var perPage = 12;
var categories = [];

function getCategoryIcon(categoryName) {
    var icons = {
        'Caftans & Vêtements traditionnels': '👘',
        'Tapis & Tissage': '🪑',
        'Poterie & Céramique': '🏺',
        'Marqueterie & Bois': '🪵',
        'Bijoux & Joaillerie': '💍',
        'Lampes & Fer forgé': '🕯️',
        'Cosmétiques naturels': '🧴',
        'Produits du terroir': '🍯'
    };
    return icons[categoryName] || '📦';
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/[&<>"]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        if (m === '"') return '&quot;';
        return m;
    });
}

function showToast(msg, isError) {
    var toast = document.getElementById('toast');
    if (!toast) return;
    toast.textContent = msg;
    toast.style.background = isError ? '#dc3545' : 'var(--toast-bg)';
    toast.classList.add('show');
    clearTimeout(toast._timer);
    toast._timer = setTimeout(function() {
        toast.classList.remove('show');
    }, 3000);
}

function toggleCategoryForm() {
    var form = document.getElementById('addCatForm');
    if (form) {
        form.classList.toggle('open');
    }
}

function cancelCategoryForm() {
    var form = document.getElementById('addCatForm');
    if (form) form.classList.remove('open');
    document.getElementById('catNameInput').value = '';
    document.getElementById('catIconInput').value = '';
}

function saveCategory() {
    var nameInput = document.getElementById('catNameInput');
    var iconInput = document.getElementById('catIconInput');
    var name = nameInput ? nameInput.value.trim() : '';
    var icon = iconInput ? iconInput.value.trim() || '📦' : '📦';
    
    if (!name) {
        showToast('⚠️ Veuillez saisir un nom', true);
        return;
    }
    
    var exists = categories.some(function(c) { 
        return c.name.toLowerCase() === name.toLowerCase(); 
    });
    if (exists) {
        showToast('⚠️ Cette catégorie existe déjà', true);
        return;
    }
    
    var saveBtn = document.getElementById('btnSaveCategory');
    if (saveBtn) {
        saveBtn.textContent = '⏳...';
        saveBtn.disabled = true;
    }
    
    fetch('add_category.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'nom_categorie=' + encodeURIComponent(name) + '&description=' + encodeURIComponent('Catégorie artisanale')
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (saveBtn) {
            saveBtn.textContent = 'Enregistrer';
            saveBtn.disabled = false;
        }
        
        if (data.success) {
            categories.push({ id: data.id, icon: icon, name: name });
            renderCategories();
            
            var catFilter = document.getElementById('catFilter');
            if (catFilter) {
                var opt = document.createElement('option');
                opt.value = name;
                opt.textContent = name;
                catFilter.appendChild(opt);
            }
            
            showToast('✅ Catégorie "' + name + '" ajoutée');
            cancelCategoryForm();
        } else {
            showToast('❌ ' + data.message, true);
        }
    })
    .catch(function(error) {
        if (saveBtn) {
            saveBtn.textContent = 'Enregistrer';
            saveBtn.disabled = false;
        }
        showToast('❌ Erreur: ' + error.message, true);
    });
}

function deleteCategory(categoryId, categoryName) {
    var productsInCategory = allProducts.filter(function(p) { return p.categoryId === categoryId; });
    
    if (productsInCategory.length > 0) {
        showToast('❌ ' + productsInCategory.length + ' produit(s) associés', true);
        return;
    }
    
    askConfirm('Supprimer la catégorie ?', '"' + categoryName + '" sera supprimée définitivement.', function() {
    fetch('supprimer_categorie.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id_categorie=' + categoryId
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            categories = categories.filter(function(c) { return c.id !== categoryId; });
            if (activeCategory === categoryName) {
                activeCategory = null;
                document.getElementById('catFilter').value = '';
            }
            renderCategories();
            renderProducts();
            showToast('✅ Catégorie supprimée');
        } else {
            showToast('❌ ' + data.message, true);
        }
    })
    .catch(function() {
        showToast('❌ Erreur de connexion', true);
    });
    });
}

function renderCategories() {
    var list = document.getElementById('categoryList');
    if (!list) return;
    
    var categoryCounts = {};
    allProducts.forEach(function(p) {
        categoryCounts[p.category] = (categoryCounts[p.category] || 0) + 1;
    });
    
    var html = '<div class="category-item ' + (activeCategory === null ? 'active' : '') + '" data-name="">' +
        '<div class="cat-left"><span class="cat-icon">🏪</span><span class="cat-name">Tous les produits</span></div>' +
        '<span class="cat-count">' + allProducts.length + '</span>' +
        '</div>';
    
    categories.forEach(function(c) {
        var count = categoryCounts[c.name] || 0;
        var deleteBtn = '';
        if (isAdmin) {
            deleteBtn = '<button class="delete-cat-btn" onclick="event.stopPropagation(); deleteCategory(' + c.id + ', \'' + escapeHtml(c.name) + '\')"><i class="bi bi-trash3"></i></button>';
        }
        
        html += '<div class="category-item ' + (activeCategory === c.name ? 'active' : '') + '" data-name="' + escapeHtml(c.name) + '" data-cat-id="' + c.id + '">' +
            '<div class="cat-left"><span class="cat-icon">' + c.icon + '</span><span class="cat-name">' + escapeHtml(c.name) + '</span></div>' +
            '<div class="cat-actions"><span class="cat-count">' + count + '</span>' + deleteBtn + '</div>' +
            '</div>';
    });
    
    list.innerHTML = html;
    
    list.querySelectorAll('.category-item').forEach(function(el) {
        el.addEventListener('click', function(e) {
            if (e.target.closest('.delete-cat-btn')) return;
            activeCategory = el.dataset.name || null;
            currentPage = 1;
            document.getElementById('catFilter').value = activeCategory || '';
            renderCategories();
            renderProducts();
        });
    });
}

function filterProducts() {
    var searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    var sortValue = document.getElementById('sortFilter')?.value || '';
    
    filteredProducts = allProducts.filter(function(p) {
        var matchSearch = !searchTerm || p.name.toLowerCase().includes(searchTerm) || p.shopName.toLowerCase().includes(searchTerm);
        var matchCategory = !activeCategory || p.category === activeCategory;
        return matchSearch && matchCategory;
    });
    
    if (sortValue === 'price_asc') {
        filteredProducts.sort(function(a, b) { return a.prix_numerique - b.prix_numerique; });
    } else if (sortValue === 'price_desc') {
        filteredProducts.sort(function(a, b) { return b.prix_numerique - a.prix_numerique; });
    } else if (sortValue === 'name') {
        filteredProducts.sort(function(a, b) { return a.name.localeCompare(b.name, 'fr'); });
    }
    
    document.getElementById('statProducts').textContent = filteredProducts.length;
}

function renderProducts() {
    var grid = document.getElementById('productsGrid');
    if (!grid) {
        console.error('❌ productsGrid no encontrado');
        return;
    }
    
    filterProducts();
    
    var totalItems = filteredProducts.length;
    var totalPages = Math.ceil(totalItems / perPage);
    if (currentPage > totalPages && totalPages > 0) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;
    
    var startIndex = (currentPage - 1) * perPage;
    var endIndex = Math.min(startIndex + perPage, totalItems);
    var pageItems = filteredProducts.slice(startIndex, endIndex);
    
    document.getElementById('storesTitle').textContent = activeCategory || 'Tous les produits artisanaux';
    document.getElementById('storesCount').textContent = totalItems + ' produit' + (totalItems > 1 ? 's' : '');
    
    if (pageItems.length === 0) {
        grid.innerHTML = '<div class="empty-state"><div class="empty-icon">🛍️</div><p>Aucun produit trouvé.</p></div>';
        renderPagination(0, 0);
        return;
    }
    
    var fallbackImg = 'IMAGES/default-product.jpg';
    var html = '';
    
    pageItems.forEach(function(p) {
        var stockText = p.stock <= 0 ? 'Rupture' : (p.stock < 5 ? 'Plus que ' + p.stock : p.stock + ' en stock');
        var stockClass = p.stock <= 0 ? 'stock-out' : (p.stock < 5 ? 'stock-low' : '');
        var isOutOfStock = p.stock <= 0;
        
        var favoriBtn = '';
        if (isClient) {
            var isFavori = p.isFavori || false;
            favoriBtn = '<button class="favori-btn ' + (isFavori ? 'active' : '') + '" onclick="event.stopPropagation(); toggleFavori(' + p.id + ', this)"><i class="bi ' + (isFavori ? 'bi-heart-fill' : 'bi-heart') + '"></i></button>';
        }
        
        var actionBtn = '';
        if (isAdmin) {
            actionBtn = '<button class="admin-btn-delete" onclick="deleteProduct(' + p.id + ', \'' + escapeHtml(p.name) + '\')"><i class="bi bi-trash3"></i> Supprimer</button>';
        } else {
            actionBtn = '<button class="add-cart-btn" data-id="' + p.id + '" onclick="addToCart(' + p.id + ', \'' + escapeHtml(p.name) + '\')" ' + (isOutOfStock ? 'disabled' : '') + '>🛒 ' + (isOutOfStock ? 'Rupture' : 'Ajouter') + '</button>';
        }
        
        html += '<div class="product-card">' +
            favoriBtn +
            '<div class="product-banner" onclick="window.location.href=\'info-produit.php?id=' + p.id + '\'">' +
            '<img src="' + escapeHtml(p.image) + '" alt="' + escapeHtml(p.name) + '" loading="lazy" onerror="this.src=\'' + fallbackImg + '\'">' +
            '<span class="product-price-badge">' + escapeHtml(p.price) + '</span>' +
            '</div>' +
            '<div class="product-body">' +
            '<div class="product-name">' + escapeHtml(p.name) + '</div>' +
            '<span class="product-shop-tag" onclick="event.stopPropagation();window.location.href=\'info-store.php?id=' + p.shopId + '\'">🏪 ' + escapeHtml(p.shopName) + '</span>' +
            '<p class="product-desc">' + escapeHtml(p.description.substring(0, 100)) + (p.description.length > 100 ? '…' : '') + '</p>' +
            '<div class="product-stats"><span class="product-stock ' + stockClass + '">' + stockText + '</span>' + actionBtn + '</div>' +
            '</div></div>';
    });
    
    grid.innerHTML = html;
    
    var infoEl = document.getElementById('paginationInfo');
    if (infoEl && totalItems > 0) {
        infoEl.textContent = 'Affichage ' + (startIndex + 1) + ' - ' + endIndex + ' sur ' + totalItems + ' produit' + (totalItems > 1 ? 's' : '');
    } else if (infoEl) {
        infoEl.textContent = '';
    }
    
    renderPagination(currentPage, totalPages);
}

function renderPagination(activePage, totalPages) {
    var wrapper = document.getElementById('paginationWrapper');
    if (!wrapper) return;
    if (totalPages <= 1) { wrapper.innerHTML = ''; return; }
    
    var html = '<button class="nav-btn" onclick="goToPage(' + (activePage - 1) + ')" ' + (activePage <= 1 ? 'disabled' : '') + '><i class="bi bi-chevron-left"></i> Préc</button>';
    
    var startPage = Math.max(1, activePage - 2);
    var endPage = Math.min(totalPages, activePage + 2);
    if (endPage - startPage < 4) {
        if (startPage === 1) endPage = Math.min(5, totalPages);
        else if (endPage === totalPages) startPage = Math.max(1, totalPages - 4);
    }
    
    if (startPage > 1) {
        html += '<button class="page-btn" onclick="goToPage(1)">1</button>';
        if (startPage > 2) html += '<button class="page-btn" disabled>…</button>';
    }
    
    for (var i = startPage; i <= endPage; i++) {
        html += '<button class="page-btn ' + (i === activePage ? 'active' : '') + '" onclick="goToPage(' + i + ')">' + i + '</button>';
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += '<button class="page-btn" disabled>…</button>';
        html += '<button class="page-btn" onclick="goToPage(' + totalPages + ')">' + totalPages + '</button>';
    }
    
    html += '<button class="nav-btn" onclick="goToPage(' + (activePage + 1) + ')" ' + (activePage >= totalPages ? 'disabled' : '') + '>Suiv <i class="bi bi-chevron-right"></i></button>';
    wrapper.innerHTML = html;
}

function goToPage(page) {
    var totalPages = Math.ceil(filteredProducts.length / perPage);
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    renderProducts();
    document.querySelector('.stores-area').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function addToCart(productId, productName) {
    var btn = document.querySelector('.add-cart-btn[data-id="' + productId + '"]');
    if (btn && btn.disabled) return;

    <?php if (!isset($_SESSION['user_id'])): ?>
        showToast('⚠️ Veuillez vous connecter pour ajouter au panier', true);
        setTimeout(function() { window.location.href = 'signin.php'; }, 1500);
        return;
    <?php endif; ?>

    <?php if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'client'): ?>
        showToast('⚠️ Seuls les clients peuvent acheter', true);
        return;
    <?php endif; ?>

    var productIdNum = parseInt(productId);
    var product = null;
    for (var i = 0; i < allProducts.length; i++) {
        if (parseInt(allProducts[i].id) === productIdNum) {
            product = allProducts[i];
            break;
        }
    }

    if (!product) {
        showToast('❌ Produit non disponible', true);
        return;
    }

    if (product.stock <= 0) {
        showToast('❌ Produit en rupture de stock', true);
        return;
    }

    if (btn) { btn.disabled = true; btn.textContent = '⏳...'; }

    var formData = new FormData();
    formData.append('id_produit', productId);
    formData.append('quantite', 1);

    fetch('ajouter_panier.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            if (btn) { btn.textContent = '✓ Ajouté'; }
            setTimeout(function() { if (btn) { btn.textContent = '🛒 Ajouter'; btn.disabled = false; } }, 2000);
            showToast('✓ ' + product.name + ' ajouté au panier');
            var badges = document.querySelectorAll('.cart-badge');
            badges.forEach(function(badge) {
                badge.textContent = data.total_panier;
                badge.classList.add('show');
            });
            panierCount = data.total_panier;
        } else {
            showToast('❌ ' + data.message, true);
            if (btn) { btn.textContent = '🛒 Ajouter'; btn.disabled = false; }
        }
    })
    .catch(function() {
        if (btn) { btn.textContent = '🛒 Ajouter'; btn.disabled = false; }
        showToast('❌ Erreur de connexion au serveur', true);
    });
}

function toggleFavori(productId, button) {
    <?php if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client'): ?>
        showToast('⚠️ Connectez-vous', true);
        setTimeout(function() { window.location.href = 'signin.php'; }, 1500);
        return;
    <?php endif; ?>
    
    var icon = button.querySelector('i');
    var isFavori = icon.classList.contains('bi-heart-fill');
    
    fetch('toggle_favori.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id_produit=' + productId
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            if (data.action === 'added') {
                icon.className = 'bi bi-heart-fill';
                button.classList.add('active');
                showToast('❤️ Ajouté aux favoris');
            } else {
                icon.className = 'bi bi-heart';
                button.classList.remove('active');
                showToast('Retiré des favoris');
            }
            var p = allProducts.find(function(p) { return p.id === productId; });
            if (p) p.isFavori = data.action === 'added';
        } else {
            showToast('❌ ' + data.message, true);
        }
    })
    .catch(function() {
        showToast('❌ Erreur de connexion', true);
    });
}

function deleteProduct(productId, productName) {
    askConfirm('Supprimer ce produit ?', '"' + productName + '" sera supprimé définitivement.', function() {
    fetch('supprimer_produit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id_produit=' + productId
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            allProducts = allProducts.filter(function(p) { return p.id !== productId; });
            renderProducts();
            renderCategories();
            showToast('✅ Produit supprimé');
        } else {
            showToast('❌ ' + data.message, true);
        }
    })
    .catch(function() {
        showToast('❌ Erreur de connexion', true);
    });
    });
}

function init() {
    console.log('🚀 Init...');
    
    categories = categoriesFromDB.map(function(c) {
        return {
            id: c.id_categorie,
            icon: getCategoryIcon(c.nom_categorie),
            name: c.nom_categorie
        };
    });
    
    console.log('📂 Categorías cargadas:', categories.length);
    console.log('📦 productsGrid:', document.getElementById('productsGrid'));
    console.log('📂 categoryList:', document.getElementById('categoryList'));
    
    var btnToggle = document.getElementById('btnToggleCategory');
    var btnCancel = document.getElementById('btnCancelCategory');
    var btnSave = document.getElementById('btnSaveCategory');
    var nameInput = document.getElementById('catNameInput');
    var iconInput = document.getElementById('catIconInput');
    
    if (btnToggle) btnToggle.addEventListener('click', toggleCategoryForm);
    if (btnCancel) btnCancel.addEventListener('click', cancelCategoryForm);
    if (btnSave) btnSave.addEventListener('click', saveCategory);
    
    if (nameInput) {
        nameInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') saveCategory();
        });
    }
    if (iconInput) {
        iconInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') saveCategory();
        });
    }
    
    var searchInput = document.getElementById('searchInput');
    var sortFilter = document.getElementById('sortFilter');
    var catFilter = document.getElementById('catFilter');
    var perPageFilter = document.getElementById('perPageFilter');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            currentPage = 1;
            renderProducts();
        });
    }
    
    if (sortFilter) {
        sortFilter.addEventListener('change', function() {
            currentPage = 1;
            renderProducts();
        });
    }
    
    if (catFilter) {
        catFilter.addEventListener('change', function(e) {
            activeCategory = e.target.value || null;
            currentPage = 1;
            renderCategories();
            renderProducts();
        });
    }
    
    if (perPageFilter) {
        perPageFilter.addEventListener('change', function() {
            perPage = parseInt(this.value) || 12;
            currentPage = 1;
            renderProducts();
        });
    }
    
    renderCategories();
    renderProducts();
    
    document.querySelectorAll('.reveal, .reveal-left, .reveal-right').forEach(function(el) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(e) {
                if (e.isIntersecting) e.target.classList.add('visible');
            });
        }, { threshold: 0.1 });
        observer.observe(el);
    });
    
    console.log('✅ Init terminé');
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

var _confirmCallback = null;

function askConfirm(title, msg, callback) {
    document.getElementById('confirm-title').textContent = title;
    document.getElementById('confirm-msg').textContent = msg;
    _confirmCallback = callback;
    document.getElementById('confirm-modal').classList.add('show');
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('confirm-ok').addEventListener('click', function() {
        document.getElementById('confirm-modal').classList.remove('show');
        if (_confirmCallback) _confirmCallback();
    });
    document.getElementById('confirm-cancel').addEventListener('click', function() {
        document.getElementById('confirm-modal').classList.remove('show');
        _confirmCallback = null;
    });
    document.getElementById('confirm-overlay').addEventListener('click', function() {
        document.getElementById('confirm-modal').classList.remove('show');
        _confirmCallback = null;
    });
});
</script>
</body>
</html>