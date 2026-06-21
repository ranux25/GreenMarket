<?php
session_start();
include('connexion.php');

// Detectar tema guardado (por defecto claro)
$theme = $_COOKIE['theme'] ?? 'light';

// Verificar si el usuario es admin o cliente
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$isClient = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client';

// Récupérer les produits depuis la base de données
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
    
    // Récupérer toutes les catégories
    $stmt = $pdo->query("SELECT * FROM categorie ORDER BY nom_categorie");
    $categories_db = $stmt->fetchAll();
    
    // Récupérer les statistiques
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM produit WHERE est_valide_par_admin = 1 AND statut_publie = 'Publié'");
    $total_produits = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(DISTINCT id_boutique) as total FROM produit WHERE est_valide_par_admin = 1 AND statut_publie = 'Publié'");
    $total_boutiques = $stmt->fetch()['total'];

    // Compter les articles dans le panier du client connecté
    $panier_count = 0;
    if (isset($_SESSION['user_id']) && $_SESSION['user_role'] == 'client') {
        $stmtP = $pdo->prepare("SELECT SUM(quantite) as total FROM panier WHERE id_client = ?");
        $stmtP->execute([$_SESSION['user_id']]);
        $panier_count = $stmtP->fetch()['total'] ?? 0;
    }
    
    // Si le client est connecté, récupérer ses favoris
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

// ====== DEBUG: Verificar producto 75 ======
error_log("=== DEBUG PRODUCTO 75 ===");

// 1. Verificar si el producto existe en la base de datos
try {
    $stmtDebug = $pdo->prepare("SELECT * FROM produit WHERE id_produit = 75");
    $stmtDebug->execute();
    $producto75 = $stmtDebug->fetch(PDO::FETCH_ASSOC);
    
    if ($producto75) {
        error_log("✅ Producto 75 encontrado en BD:");
        error_log("  - est_valide_par_admin: " . $producto75['est_valide_par_admin']);
        error_log("  - statut_publie: " . $producto75['statut_publie']);
        error_log("  - id_boutique: " . $producto75['id_boutique']);
        error_log("  - id_categorie: " . $producto75['id_categorie']);
        
        // Verificar boutique
        $stmtB = $pdo->prepare("SELECT * FROM boutique WHERE id_boutique = ?");
        $stmtB->execute([$producto75['id_boutique']]);
        $boutique = $stmtB->fetch(PDO::FETCH_ASSOC);
        if ($boutique) {
            error_log("  ✅ Boutique encontrada: " . $boutique['nom_boutique']);
            error_log("  - est_valide_par_admin: " . $boutique['est_valide_par_admin']);
        } else {
            error_log("  ❌ Boutique NO encontrada para id_boutique: " . $producto75['id_boutique']);
        }
        
        // Verificar categoría
        $stmtC = $pdo->prepare("SELECT * FROM categorie WHERE id_categorie = ?");
        $stmtC->execute([$producto75['id_categorie']]);
        $categoria = $stmtC->fetch(PDO::FETCH_ASSOC);
        if ($categoria) {
            error_log("  ✅ Categoría encontrada: " . $categoria['nom_categorie']);
        } else {
            error_log("  ❌ Categoría NO encontrada para id_categorie: " . $producto75['id_categorie']);
        }
    } else {
        error_log("❌ Producto 75 NO existe en la BD");
    }
} catch(Exception $e) {
    error_log("Error debug: " . $e->getMessage());
}

// 2. Verificar qué productos se cargaron
$ids_cargados = array_column($produits_db, 'id_produit');
error_log("IDs de productos cargados: " . implode(', ', $ids_cargados));
error_log("Total: " . count($produits_db));

if (in_array(75, $ids_cargados)) {
    error_log("✅ Producto 75 ESTÁ en la lista de productos cargados");
} else {
    error_log("❌ Producto 75 NO ESTÁ en la lista de productos cargados");
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
        'category' => $p['nom_categorie'] ?? 'Sans catégorie',
        'categoryId' => $p['id_categorie'],
        'isFavori' => in_array($p['id_produit'], $favoris_ids)
    ];
}

// ====== GUARDAR VARIABLES ANTES DEL HEADER ======
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
  /* ========== VARIABLES DE TEMA GLOBAL ========== */
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

  /* Add category form */
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

  /* Products Grid */
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

  /* Product Card */
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

  /* 🔥 Botón de favoritos */
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

  /* Pagination */
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
    box-shadow: 0 8px 24px rgba(0,0,0,0.2);
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
    .pagination-wrapper button {
      min-width: 38px;
      height: 38px;
      font-size: 0.8rem;
    }
    .pagination-wrapper .nav-btn {
      padding: 0 0.8rem;
    }
  }
</style>
</head>
<body data-active-page="produits">

<?php 
// ====== RESTAURAR VARIABLES DESPUÉS DEL HEADER ======
// El header se incluye aquí, pero como ya tenemos las variables guardadas,
// las restauramos después de incluirlo
include 'header.php';

// Restaurar variables (por si el header las modificó)
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

<!-- PAGE HEADER -->
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
  <select class="filter-select" id="perPageFilter">
    <option value="6">6 par page</option>
    <option value="9">9 par page</option>
    <option value="12" selected>12 par page</option>
    <option value="18">18 par page</option>
    <option value="24">24 par page</option>
  </select>
</div>

<!-- MAIN CONTENT -->
<div class="main-layout">
  <aside class="sidebar">
    <div class="sidebar-section reveal-left">
      <div class="sidebar-header">
        <h3>Catégories artisanales</h3>
        <?php if ($isAdmin): ?>
          <button class="add-btn" id="toggleAddCat">➕ Ajouter</button>
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
          <button class="btn-sm-cancel" id="cancelCat">Annuler</button>
          <button class="btn-sm-wine" id="saveCat">Enregistrer</button>
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
    
    <!-- Pagination -->
    <div class="pagination-info" id="paginationInfo"></div>
    <div class="pagination-wrapper" id="paginationWrapper"></div>
  </section>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
// ========== DONNÉES DEPUIS PHP ==========
const produitsFromDB = <?php echo json_encode($produits_json); ?>;
const categoriesFromDB = <?php echo json_encode($categories_db); ?>;
const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
const isClient = <?php echo $isClient ? 'true' : 'false'; ?>;

console.log('=== 🔍 INICIO DE DEPURACIÓN ===');
console.log('isClient:', isClient);
console.log('Produits chargés:', produitsFromDB.length);

let allProducts = produitsFromDB;
let filteredProducts = [];
let activeCategory = null;
let deleteProductId = null;

// ====== SISTEMA DE DEPURACIÓN COMPLETO ======
console.log('📦 Total productos en allProducts:', allProducts.length);
console.log('🔑 IDs de productos cargados:', allProducts.map(p => p.id));

// Buscar producto ID 75
const productoBuscado = 75;
const productoEncontrado = allProducts.find(p => p.id === productoBuscado);

if (productoEncontrado) {
    console.log('✅ PRODUCTO 75 ENCONTRADO:', productoEncontrado);
    console.log('   Nombre:', productoEncontrado.name);
    console.log('   Stock:', productoEncontrado.stock);
    console.log('   Precio:', productoEncontrado.price);
} else {
    console.error('❌ PRODUCTO 75 NO ENCONTRADO en allProducts');
    console.log('📋 IDs disponibles:', allProducts.map(p => p.id));
    console.log('💡 Posibles causas:');
    console.log('   1. El producto no está publicado (statut_publie != "Publié")');
    console.log('   2. El producto no está validado por admin (est_valide_par_admin != 1)');
    console.log('   3. La boutique o categoría no existen o no están validadas');
    console.log('   4. El ID 75 no existe en la base de datos');
    console.log('💡 Revisa el error_log de PHP para más información');
}

// Mostrar primeros 5 productos para ejemplo
console.log('📝 Primeros 5 productos cargados:');
allProducts.slice(0, 5).forEach((p, i) => {
    console.log(`   ${i+1}. ID:${p.id} - ${p.name} (${p.category})`);
});

// ====== FUNCIÓN DE DEBUG PARA PROBAR ======
window.debugProducto = function(id) {
    console.log(`🔍 Buscando producto ID ${id}...`);
    const p = allProducts.find(item => item.id === id);
    if (p) {
        console.log('✅ Encontrado:', p);
        return p;
    } else {
        console.error('❌ No encontrado. IDs disponibles:', allProducts.map(item => item.id));
        return null;
    }
};

console.log('💡 Para buscar un producto específico, usa: debugProducto(75)');
console.log('=== 🔍 FIN DE DEPURACIÓN ===');

// ====== PAGINATION VARIABLES ======
let currentPage = 1;
let perPage = 12;

// Initialiser les catégories depuis la base de données
let categories = [];
if (categoriesFromDB.length > 0) {
    categories = categoriesFromDB.map(c => ({
        id: c.id_categorie,
        icon: getCategoryIcon(c.nom_categorie),
        name: c.nom_categorie
    }));
}

// Configuration des icônes pour catégories
function getCategoryIcon(categoryName) {
    const icons = {
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
    return str.replace(/[&<>"]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        if (m === '"') return '&quot;';
        return m;
    });
}

function showToast(msg, isError = false) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    if (isError) {
        toast.style.background = '#dc3545';
    } else {
        toast.style.background = 'var(--toast-bg)';
    }
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            toast.style.background = 'var(--toast-bg)';
        }, 400);
    }, 2800);
}

let panierCount = <?php echo json_encode($panier_count); ?>;

function updateCartCount() {
    const badge = document.getElementById('cart-count');
    if (badge) badge.textContent = panierCount;
}

// ========== ADD TO CART (VERSIÓN CORREGIDA) ==========
function addToCart(productId, productName) {
    console.log('🛒 === addToCart llamado ===');
    console.log('📌 ID recibido:', productId, '(tipo:', typeof productId, ')');
    console.log('📌 Nombre recibido:', productName);
    console.log('📦 allProducts contiene', allProducts.length, 'productos');
    
    // Verificar sesión
    <?php if (!isset($_SESSION['user_id'])): ?>
        showToast('⚠️ Veuillez vous connecter pour ajouter au panier', true);
        setTimeout(() => { window.location.href = 'signin.php'; }, 1500);
        return;
    <?php endif; ?>
    
    <?php if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'client'): ?>
        showToast('⚠️ Seuls les clients peuvent acheter', true);
        return;
    <?php endif; ?>
    
    // 🔥 Buscar el producto - CONVERSIÓN A NÚMERO PARA COMPARACIÓN
    const product = allProducts.find(p => parseInt(p.id) === parseInt(productId));
    
    if (!product) {
        console.error('❌ Producto no encontrado. ID buscado:', productId);
        console.log('🔑 IDs disponibles:', allProducts.map(p => p.id));
        console.log('🔍 Comparación:', parseInt(productId), 'vs', allProducts.map(p => parseInt(p.id)));
        showToast('❌ Produit non disponible (ID: ' + productId + ')', true);
        return;
    }
    
    console.log('✅ Producto encontrado:', product);
    
    if (product.stock <= 0) {
        showToast('❌ Produit en rupture de stock', true);
        return;
    }
    
    // Deshabilitar botones
    const buttons = document.querySelectorAll('.add-cart-btn');
    buttons.forEach(btn => {
        if (btn.textContent.includes('Ajouter') || btn.textContent.includes('🛒')) {
            btn.textContent = '⏳...';
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
    .then(res => res.json())
    .then(data => {
        // Reactivar botones
        buttons.forEach(btn => {
            btn.textContent = '🛒 Ajouter';
            btn.disabled = false;
        });
        
        if (data.success) {
            showToast(`✓ ${product.name} ajouté au panier !`);
            const badge = document.getElementById('cart-count');
            if (badge && data.total_panier !== undefined) {
                badge.textContent = data.total_panier;
                panierCount = data.total_panier;
            }
        } else {
            showToast(data.message || '❌ Erreur lors de l\'ajout', true);
        }
    })
    .catch(error => {
        buttons.forEach(btn => {
            btn.textContent = '🛒 Ajouter';
            btn.disabled = false;
        });
        showToast('❌ Erreur de connexion au serveur', true);
    });
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

// ========== TOGGLE FAVORI PRODUIT ==========
function toggleFavoriProduit(productId, button) {
    <?php if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client'): ?>
        showToast('⚠️ Veuillez vous connecter en tant que client', true);
        setTimeout(() => { window.location.href = 'signin.php'; }, 1500);
        return;
    <?php endif; ?>
    
    const icon = button.querySelector('i');
    const isFavori = icon.classList.contains('bi-heart-fill');
    
    if (isFavori) {
        icon.className = 'bi bi-heart';
        button.style.color = 'var(--text-light)';
        button.classList.remove('active');
    } else {
        icon.className = 'bi bi-heart-fill';
        button.style.color = '#c0392b';
        button.classList.add('active');
    }
    
    fetch('toggle_favori.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id_produit=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.action === 'added') {
                icon.className = 'bi bi-heart-fill';
                button.style.color = '#c0392b';
                button.classList.add('active');
                showToast('❤️ Produit ajouté aux favoris');
            } else {
                icon.className = 'bi bi-heart';
                button.style.color = 'var(--text-light)';
                button.classList.remove('active');
                showToast('Produit retiré des favoris');
            }
            const product = allProducts.find(p => p.id === productId);
            if (product) {
                product.isFavori = data.action === 'added';
            }
        } else {
            if (isFavori) {
                icon.className = 'bi bi-heart-fill';
                button.style.color = '#c0392b';
                button.classList.add('active');
            } else {
                icon.className = 'bi bi-heart';
                button.style.color = 'var(--text-light)';
                button.classList.remove('active');
            }
            showToast('❌ ' + data.message, true);
        }
    })
    .catch(() => {
        if (isFavori) {
            icon.className = 'bi bi-heart-fill';
            button.style.color = '#c0392b';
            button.classList.add('active');
        } else {
            icon.className = 'bi bi-heart';
            button.style.color = 'var(--text-light)';
            button.classList.remove('active');
        }
        showToast('❌ Erreur de connexion', true);
    });
}

// ========== RENDU CATÉGORIES ==========
function renderCategories() {
    const list = document.getElementById('categoryList');
    if (!list) return;
    
    const categoryCounts = {};
    allProducts.forEach(p => {
        categoryCounts[p.category] = (categoryCounts[p.category] || 0) + 1;
    });
    
    let html = `
        <div class="category-item ${activeCategory === null ? 'active' : ''}" data-name="">
            <div class="cat-left">
                <span class="cat-icon">🏪</span>
                <span class="cat-name">Tous les produits</span>
            </div>
            <span class="cat-count">${allProducts.length}</span>
        </div>
    `;
    
    categories.forEach(c => {
        const count = categoryCounts[c.name] || 0;
        html += `
            <div class="category-item ${activeCategory === c.name ? 'active' : ''}" data-name="${escapeHtml(c.name)}" data-cat-id="${c.id}">
                <div class="cat-left">
                    <span class="cat-icon">${c.icon}</span>
                    <span class="cat-name">${escapeHtml(c.name)}</span>
                </div>
                <span class="cat-count">${count}</span>
            </div>
        `;
    });
    
    list.innerHTML = html;
    
    list.querySelectorAll('.category-item').forEach(el => {
        el.addEventListener('click', () => {
            activeCategory = el.dataset.name || null;
            currentPage = 1;
            const catFilter = document.getElementById('catFilter');
            if (catFilter) catFilter.value = activeCategory || '';
            renderCategories();
            renderProducts();
        });
    });
}

// ====== FILTRER LES PRODUITS ======
function filterProducts() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const sortValue = document.getElementById('sortFilter')?.value || '';
    
    filteredProducts = allProducts.filter(p => {
        const matchSearch = !searchTerm || 
            p.name.toLowerCase().includes(searchTerm) || 
            p.shopName.toLowerCase().includes(searchTerm);
        const matchCategory = !activeCategory || p.category === activeCategory;
        return matchSearch && matchCategory;
    });
    
    if (sortValue === 'price_asc') {
        filteredProducts.sort((a, b) => a.prix_numerique - b.prix_numerique);
    } else if (sortValue === 'price_desc') {
        filteredProducts.sort((a, b) => b.prix_numerique - a.prix_numerique);
    } else if (sortValue === 'name') {
        filteredProducts.sort((a, b) => a.name.localeCompare(b.name, 'fr'));
    }
    
    document.getElementById('statProducts').textContent = filteredProducts.length;
}

// ====== RENDU PRODUITS AVEC PAGINATION ======
function renderProducts() {
    const grid = document.getElementById('productsGrid');
    if (!grid) return;
    
    filterProducts();
    
    const totalItems = filteredProducts.length;
    const totalPages = Math.ceil(totalItems / perPage);
    
    if (currentPage > totalPages && totalPages > 0) {
        currentPage = totalPages;
    }
    if (currentPage < 1) currentPage = 1;
    
    const startIndex = (currentPage - 1) * perPage;
    const endIndex = Math.min(startIndex + perPage, totalItems);
    const pageItems = filteredProducts.slice(startIndex, endIndex);
    
    const titleEl = document.getElementById('storesTitle');
    const countEl = document.getElementById('storesCount');
    if (titleEl) titleEl.textContent = activeCategory || 'Tous les produits artisanaux';
    if (countEl) countEl.textContent = `${totalItems} produit${totalItems > 1 ? 's' : ''}`;
    
    if (pageItems.length === 0) {
        grid.innerHTML = `<div class="empty-state"><div class="empty-icon">🛍️</div><p>Aucun produit trouvé.</p></div>`;
        renderPagination(0, 0);
        return;
    }
    
    const fallbackImg = 'IMAGES/default-product.jpg';
    
    grid.innerHTML = pageItems.map(p => {
        const stockClass = getStockClass(p.stock);
        const stockText = getStockText(p.stock);
        const isOutOfStock = p.stock <= 0;
        
        let favoriBtn = '';
        if (isClient) {
            const isFavori = p.isFavori || false;
            favoriBtn = `
                <button class="favori-btn ${isFavori ? 'active' : ''}" onclick="event.stopPropagation(); toggleFavoriProduit(${p.id}, this)" title="Ajouter aux favoris">
                    <i class="bi ${isFavori ? 'bi-heart-fill' : 'bi-heart'}"></i>
                </button>
            `;
        }
        
        let buttons = '';
        if (isAdmin) {
            buttons = `
                <button class="admin-btn-delete" onclick='deleteProduct(${p.id}, "${escapeHtml(p.name)}")'>
                    <i class="bi bi-trash3"></i> Supprimer
                </button>
            `;
        } else {
            buttons = `
                <button class="add-cart-btn" onclick="addToCart(${p.id}, '${escapeHtml(p.name)}')" ${isOutOfStock ? 'disabled' : ''}>
                    🛒 ${isOutOfStock ? 'Rupture' : 'Ajouter'}
                </button>
            `;
        }
        
        return `
            <div class="product-card">
                ${favoriBtn}
                <div class="product-banner" onclick="window.location.href='info-produit.php?id=${p.id}'">
                    <img src="${escapeHtml(p.image)}" alt="${escapeHtml(p.name)}" loading="lazy" 
                         onerror="this.src='${fallbackImg}'">
                    <span class="product-price-badge">${escapeHtml(p.price)}</span>
                </div>
                <div class="product-body">
                    <div class="product-name">${escapeHtml(p.name)}</div>
                    <span class="product-shop-tag" onclick="event.stopPropagation();window.location.href='info-store.php?id=${p.shopId}'">
                        🏪 ${escapeHtml(p.shopName)}
                    </span>
                    <p class="product-desc">${escapeHtml(p.description.substring(0, 100))}${p.description.length > 100 ? '…' : ''}</p>
                    <div class="product-stats">
                        <div>
                            <span class="product-stock ${stockClass}">${stockText}</span>
                        </div>
                        ${buttons}
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    const infoEl = document.getElementById('paginationInfo');
    if (infoEl && totalItems > 0) {
        infoEl.textContent = `Affichage ${startIndex + 1} - ${endIndex} sur ${totalItems} produit${totalItems > 1 ? 's' : ''}`;
    } else if (infoEl) {
        infoEl.textContent = '';
    }
    
    renderPagination(currentPage, totalPages);
}

// ====== RENDU DE LA PAGINATION ======
function renderPagination(activePage, totalPages) {
    const wrapper = document.getElementById('paginationWrapper');
    if (!wrapper) return;

    if (totalPages <= 1) {
        wrapper.innerHTML = '';
        return;
    }

    let html = '';

    html += `
        <button class="nav-btn" onclick="goToPage(${activePage - 1})" ${activePage <= 1 ? 'disabled' : ''}>
            <i class="bi bi-chevron-left"></i> Préc
        </button>
    `;

    let startPage = Math.max(1, activePage - 2);
    let endPage   = Math.min(totalPages, activePage + 2);

    if (endPage - startPage < 4) {
        if (startPage === 1) endPage = Math.min(5, totalPages);
        else if (endPage === totalPages) startPage = Math.max(1, totalPages - 4);
    }

    if (startPage > 1) {
        html += `<button class="page-btn" onclick="goToPage(1)">1</button>`;
        if (startPage > 2) html += `<button class="page-btn" disabled>…</button>`;
    }

    for (let i = startPage; i <= endPage; i++) {
        html += `<button class="page-btn ${i === activePage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
    }

    if (endPage < totalPages) {
        if (endPage < totalPages - 1) html += `<button class="page-btn" disabled>…</button>`;
        html += `<button class="page-btn" onclick="goToPage(${totalPages})">${totalPages}</button>`;
    }

    html += `
        <button class="nav-btn" onclick="goToPage(${activePage + 1})" ${activePage >= totalPages ? 'disabled' : ''}>
            Suiv <i class="bi bi-chevron-right"></i>
        </button>
    `;

    wrapper.innerHTML = html;
}

function goToPage(page) {
    const totalPages = Math.ceil(filteredProducts.length / perPage);
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    renderProducts();
    document.querySelector('.stores-area').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ========== ADMIN FUNCTIONS ==========
function addCategory() {
    const name = document.getElementById('catNameInput').value.trim();
    const icon = document.getElementById('catIconInput').value.trim() || '📦';
    
    if (!name) {
        showToast('⚠️ Veuillez saisir un nom', true);
        return;
    }
    
    if (categories.find(c => c.name.toLowerCase() === name.toLowerCase())) {
        showToast('⚠️ Cette catégorie existe déjà', true);
        return;
    }
    
    fetch('add_category.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'nom_categorie=' + encodeURIComponent(name) + '&description=' + encodeURIComponent('Catégorie ' + name)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            categories.push({ id: data.id, icon: icon, name: name });
            renderCategories();
            const catFilter = document.getElementById('catFilter');
            if (catFilter) {
                const opt = document.createElement('option');
                opt.value = name;
                opt.textContent = name;
                catFilter.appendChild(opt);
            }
            document.getElementById('addCatForm').classList.remove('open');
            document.getElementById('catNameInput').value = '';
            document.getElementById('catIconInput').value = '';
            showToast('✅ Catégorie ajoutée avec succès');
        } else {
            showToast('❌ ' + data.message, true);
        }
    })
    .catch(error => {
        showToast('❌ Erreur lors de l\'ajout', true);
    });
}

function deleteProduct(productId, productName) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer "' + productName + '" ? Cette action est irréversible.')) {
        return;
    }
    
    fetch('supprimer_produit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id_produit=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('✅ Produit supprimé avec succès');
            allProducts = allProducts.filter(p => p.id !== productId);
            renderProducts();
            renderCategories();
        } else {
            showToast('❌ ' + data.message, true);
        }
    })
    .catch(() => showToast('❌ Erreur de connexion au serveur', true));
}

// ========== SCROLL REVEAL ==========
function initReveal() {
    const elements = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) e.target.classList.add('visible');
        });
    }, { threshold: 0.1 });
    elements.forEach(el => observer.observe(el));
}

// ========== ÉVÉNEMENTS ==========
document.getElementById('searchInput')?.addEventListener('input', () => {
    currentPage = 1;
    renderProducts();
});

document.getElementById('sortFilter')?.addEventListener('change', () => {
    currentPage = 1;
    renderProducts();
});

const catFilter = document.getElementById('catFilter');
if (catFilter) {
    catFilter.addEventListener('change', (e) => {
        activeCategory = e.target.value || null;
        currentPage = 1;
        renderCategories();
        renderProducts();
    });
}

const perPageFilter = document.getElementById('perPageFilter');
if (perPageFilter) {
    perPage = parseInt(perPageFilter.value) || 12;
    perPageFilter.addEventListener('change', function(e) {
        perPage = parseInt(e.target.value) || 12;
        currentPage = 1;
        renderProducts();
    });
}

document.getElementById('toggleAddCat')?.addEventListener('click', () => {
    document.getElementById('addCatForm').classList.toggle('open');
});

document.getElementById('cancelCat')?.addEventListener('click', () => {
    document.getElementById('addCatForm').classList.remove('open');
    document.getElementById('catNameInput').value = '';
    document.getElementById('catIconInput').value = '';
});

document.getElementById('saveCat')?.addEventListener('click', addCategory);

// ========== INITIALISATION ==========
function init() {
    renderCategories();
    renderProducts();
    updateCartCount();
    initReveal();
}

document.addEventListener('DOMContentLoaded', init);
</script>

<!-- ===== BOTÓN DE DEBUG (opcional) ===== -->
<div style="position: fixed; bottom: 80px; right: 20px; z-index: 9999; display: flex; gap: 10px;">
    <button onclick="console.log('allProducts:', allProducts); debugProducto(75);" 
            style="background: #333; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 12px;">
        🐛 Debug ID 75
    </button>
    <button onclick="console.log('IDs:', allProducts.map(p => p.id));" 
            style="background: #555; color: #fff; border: none; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 12px;">
        📋 Mostrar IDs
    </button>
</div>

</body>
</html>