<?php
session_start();
include('connexion.php');

// Detectar tema guardado (por defecto claro)
$theme = $_COOKIE['theme'] ?? 'light';

// Verificar si el usuario es admin
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Función para normalizar URLs de imágenes
function normalizeImageUrl($url) {
    if (empty($url)) {
        return 'IMAGES/default-boutique.jpg';
    }
    $url = str_replace('\\', '/', $url);
    $url = str_replace(' ', '%20', $url);
    if (strpos($url, 'http') !== 0 && strpos($url, '/') !== 0) {
        $url = './' . $url;
    }
    return $url;
}

// Récupérer les boutiques desde la base de données con su catégorie
try {
    $stmt = $pdo->prepare("
        SELECT b.*, p.nom_entreprise as producteur_nom, 
               p.est_valide_par_admin as producteur_valide,
               c.id_categorie, c.nom_categorie, c.description as categorie_description
        FROM boutique b
        JOIN producteur p ON b.id_producteur = p.id_producteur
        LEFT JOIN categorie c ON b.id_categorie = c.id_categorie
        WHERE p.est_valide_par_admin = 1
        ORDER BY b.date_creation DESC
    ");
    $stmt->execute();
    $boutiques_db = $stmt->fetchAll();
    
    // Récupérer toutes las categorías disponibles
    $stmt = $pdo->query("SELECT * FROM categorie ORDER BY nom_categorie");
    $categories_db = $stmt->fetchAll();
    
    // Récupérer las estadísticas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM boutique b JOIN producteur p ON b.id_producteur = p.id_producteur WHERE p.est_valide_par_admin = 1");
    $total_boutiques = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM produit WHERE est_valide_par_admin = 1");
    $total_produits = $stmt->fetch()['total'];
    
} catch(PDOException $e) {
    error_log("Error store: " . $e->getMessage());
    $boutiques_db = [];
    $categories_db = [];
    $total_boutiques = 0;
    $total_produits = 0;
}

// Convertir les données pour le JavaScript
$boutiques_json = [];
foreach ($boutiques_db as $b) {
    $banner_url = normalizeImageUrl($b['image']);
    
    $boutiques_json[] = [
        'id' => $b['id_boutique'],
        'name' => $b['nom_boutique'],
        'categoryId' => $b['id_categorie'],
        'category' => $b['nom_categorie'] ?? 'Artisanat',
        'categoryName' => $b['nom_categorie'] ?? 'Non catégorisé',
        'badge' => 'Artisan',
        'badgeClass' => 'artisan',
        'banner' => $banner_url,
        'desc' => $b['description'] ?? 'Boutique artisanale marocaine',
        'rating' => 4.5,
        'reviews' => rand(10, 200),
        'products' => rand(5, 30),
        'sales' => rand(100, 5000),
        'producerId' => $b['id_producteur'],
        'producerName' => $b['producteur_nom'],
        'location' => 'Maroc',
        'since' => date('Y', strtotime($b['date_creation']))
    ];
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $theme; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GreenMarket – Boutiques Artisanales Marocaines</title>
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
    --card-border: #f0e8d5;
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
    --category-count-text: var(--text-muted);
    --store-bg: #fff;
    --store-border: #f0e8d5;
    --store-shadow: rgba(93,13,24,0.08);
    --store-shadow-hover: rgba(93,13,24,0.14);
    --store-badge-bg: var(--primary);
    --store-badge-text: #fff;
    --store-stats-border: #e8ddd0;
    --store-stat-color: var(--primary);
    --pagination-bg: #fff;
    --pagination-border: #e8ddd0;
    --pagination-text: var(--text-dark);
    --pagination-active-bg: var(--primary);
    --pagination-active-text: #fff;
    --toast-bg: var(--primary);
    --toast-text: #fff;
    --badge-text: #fff;
    --danger: #c62828;
    --danger-hover: #b71c1c;
  }

  /* ========== TEMA OSCURO BEIGE ========== */
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
    --store-bg: #3d3229;
    --store-border: #5a4a3a;
    --store-shadow: rgba(0, 0, 0, 0.3);
    --store-shadow-hover: rgba(0, 0, 0, 0.4);
    --store-badge-bg: #6d4c3a;
    --store-badge-text: #f0e6d8;
    --store-stats-border: #5a4a3a;
    --store-stat-color: var(--gold);
    --pagination-bg: #3d3229;
    --pagination-border: #5a4a3a;
    --pagination-text: #f0e6d8;
    --pagination-active-bg: #6d4c3a;
    --pagination-active-text: #f0e6d8;
    --toast-bg: #6d4c3a;
    --toast-text: #f0e6d8;
    --danger: #ef5350;
    --danger-hover: #c62828;
  }

  /* ========== STYLES UNIFIÉS ========== */
  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    background-color: var(--bg);
    color: var(--text-dark);
    font-family: 'Lato', sans-serif;
    overflow-x: hidden;
    transition: background-color 0.3s ease, color 0.3s ease;
  }

  h1, h2, h3, .playfair { font-family: 'Playfair Display', serif; }

  /* Animations */
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(40px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  /* Scroll-reveal */
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

  /* Section title */
  .section-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(22px, 3vw, 34px);
    font-weight: 700;
    position: relative;
    display: inline-block;
    color: var(--text-dark);
    transition: color 0.3s ease;
  }
  .section-title::after {
    content: '';
    display: block;
    height: 3px;
    background: linear-gradient(90deg, var(--primary), var(--secondary), transparent);
    width: 70%;
    margin-top: 8px;
    transition: background 0.3s ease;
  }

  /* Buttons */
  .btn-primary {
    background: var(--primary);
    color: #fff;
    border: none;
    border-radius: 999px;
    padding: 11px 24px;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s, transform 0.2s;
  }
  .btn-primary:hover { background: var(--primary-light); transform: translateY(-2px); }
  
  /* 🔥 Botón de eliminar para admin */
  .btn-danger-admin {
    background: var(--danger);
    color: white;
    border: none;
    border-radius: 6px;
    padding: 0.25rem 0.6rem;
    font-size: 0.7rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
  }
  .btn-danger-admin:hover {
    background: var(--danger-hover);
    transform: scale(1.05);
  }

  /* Page header */
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

  /* Search bar */
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

  /* Layout */
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
    margin-bottom: 1.2rem;
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

  /* Store cards */
  .stores-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
  }
  .store-card {
    background: var(--store-bg);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 4px 16px var(--store-shadow);
    transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease, border-color 0.3s ease;
    border: 1.5px solid var(--store-border);
    position: relative;
  }
  .store-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 36px var(--store-shadow-hover);
  }
  .store-banner {
    height: 180px;
    position: relative;
    overflow: hidden;
    cursor: pointer;
  }
  .store-banner img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
  }
  .store-card:hover .store-banner img { transform: scale(1.07); }
  .store-badge {
    position: absolute;
    top: 0.8rem;
    right: 0.8rem;
    background: var(--store-badge-bg);
    color: var(--store-badge-text);
    font-size: 0.7rem;
    font-weight: 700;
    padding: 0.2rem 0.7rem;
    border-radius: 999px;
    transition: background 0.3s ease, color 0.3s ease;
  }
  .store-body { padding: 1.2rem; }
  .store-name {
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: 0.3rem;
    transition: color 0.3s ease;
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
    transition: background 0.3s ease;
  }
  .store-desc {
    font-size: 0.85rem;
    color: var(--text-light);
    line-height: 1.5;
    margin-bottom: 1rem;
    transition: color 0.3s ease;
  }
  .store-stats {
    display: flex;
    justify-content: space-between;
    border-top: 1px solid var(--store-stats-border);
    padding-top: 0.8rem;
    margin-top: 0.5rem;
    transition: border-color 0.3s ease;
  }
  .store-stat { text-align: center; flex: 1; }
  .store-stat-val {
    font-family: 'Playfair Display', serif;
    font-size: 1rem;
    font-weight: 700;
    color: var(--store-stat-color);
    transition: color 0.3s ease;
  }
  .store-stat-label { 
    font-size: 0.65rem; 
    color: var(--text-light);
    transition: color 0.3s ease;
  }
  .stars { color: #e0a82e; font-size: 0.7rem; }

  /* 🔥 Admin delete button en la tarjeta */
  .admin-delete-btn {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    background: var(--danger);
    color: white;
    border: none;
    border-radius: 999px;
    padding: 0.3rem 0.7rem;
    font-size: 0.65rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    z-index: 10;
    display: flex;
    align-items: center;
    gap: 0.3rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
  }
  .admin-delete-btn:hover {
    background: var(--danger-hover);
    transform: scale(1.05);
  }
  [data-theme="dark"] .admin-delete-btn {
    box-shadow: 0 2px 8px rgba(0,0,0,0.4);
  }

  /* ====== PAGINATION STYLES ====== */
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

  /* Empty state */
  .empty-state {
    text-align: center;
    padding: 3rem;
    color: var(--text-light);
    transition: color 0.3s ease;
  }
  .empty-icon { font-size: 3rem; margin-bottom: 1rem; }

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
    .pagination-wrapper button {
      min-width: 38px;
      height: 38px;
      font-size: 0.8rem;
    }
    .pagination-wrapper .nav-btn {
      padding: 0 0.8rem;
    }
    .header-stats {
      gap: 1.5rem;
      flex-wrap: wrap;
    }
  }
</style>
</head>
<body data-active-page="store">

<?php include 'header.php'; ?>

<!-- HEADER SECTION STORE -->
<div class="page-header">
  <div class="header-inner">
    <div class="header-eyebrow">🇲🇦 Artisanat &amp; Traditions marocaines</div>
    <h1>Nos Boutiques<br><em>Artisanales</em></h1>
    <p>Découvrez des artisans marocains passionnés, perpétuant un savoir-faire unique et authentique.</p>
    <div class="header-stats">
      <div><span class="h-stat-val" id="statStores"><?php echo $total_boutiques; ?></span><span class="h-stat-label">Boutiques actives</span></div>
      <div><span class="h-stat-val" id="statProducts"><?php echo $total_produits; ?></span><span class="h-stat-label">Produits artisanaux</span></div>
      <div><span class="h-stat-val" id="statRating">4.8 ★</span><span class="h-stat-label">Note moyenne</span></div>
    </div>
  </div>
</div>

<!-- ============================================================ -->
<!-- SEARCH BAR                                                   -->
<!-- ============================================================ -->
<div class="search-bar-wrap">
  <div class="search-input-wrapper">
    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
      <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
    </svg>
    <input class="search-input" id="searchInput" type="text" placeholder="Rechercher une boutique...">
  </div>
  
  <!-- Selector: Catégorie -->
  <select class="filter-select" id="catFilter">
    <option value="">Toutes les catégories</option>
    <?php foreach ($categories_db as $cat): ?>
      <option value="<?php echo htmlspecialchars($cat['nom_categorie']); ?>">
        <?php echo htmlspecialchars($cat['nom_categorie']); ?>
      </option>
    <?php endforeach; ?>
  </select>
  
  <!-- Selector de tri -->
  <select class="filter-select" id="sortFilter">
    <option value="">Trier par</option>
    <option value="name">Nom A–Z</option>
    <option value="products">Plus de produits</option>
    <option value="sales">Plus vendues</option>
  </select>
  
  <!-- Selector: Nombre par page -->
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
    <div class="stores-grid reveal" id="storesGrid"></div>
    
    <!-- ====== PAGINATION ====== -->
    <div class="pagination-info" id="paginationInfo"></div>
    <div class="pagination-wrapper" id="paginationWrapper"></div>
  </section>
</div>

<div class="toast" id="toast"></div>

<script>
// Données PHP converties en JavaScript
const boutiquesFromDB = <?php echo json_encode($boutiques_json); ?>;
const categoriesFromDB = <?php echo json_encode($categories_db); ?>;
const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;

console.log('Boutiques chargées:', boutiquesFromDB.length);
console.log('Catégories chargées:', categoriesFromDB.length);

let stores = boutiquesFromDB.length > 0 ? boutiquesFromDB : [];

// ====== PAGINATION VARIABLES ======
let currentPage = 1;
let perPage = 12;
let filteredStores = [];

// Initialiser les catégories depuis la base de données
let categories = [];
if (categoriesFromDB.length > 0) {
    categories = categoriesFromDB.map(c => ({
        id: c.id_categorie,
        icon: getCategoryIcon(c.nom_categorie),
        name: c.nom_categorie
    }));
}

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

let currentCategory = null;

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[m]));
}

function showToast(msg, isError = false) {
    const toast = document.getElementById('toast');
    toast.innerHTML = msg;
    if (isError) {
        toast.style.background = '#c0392b';
    } else {
        toast.style.background = 'var(--toast-bg)';
    }
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
        if (isError) {
            setTimeout(() => {
                toast.style.background = 'var(--toast-bg)';
            }, 300);
        }
    }, 3000);
}

function renderStars(rating) {
    const full = Math.floor(rating);
    const empty = 5 - full;
    return '★'.repeat(full) + '☆'.repeat(empty);
}

// ====== RENDER CATEGORIES ======
function renderCategories() {
    const list = document.getElementById('categoryList');
    if (!list) return;
    
    const categoryCounts = {};
    stores.forEach(store => {
        const catName = store.categoryName;
        if (catName) {
            categoryCounts[catName] = (categoryCounts[catName] || 0) + 1;
        }
    });
    
    list.innerHTML = `
        <div class="category-item ${currentCategory === null ? 'active' : ''}" data-name="" data-cat-id="">
            <div class="cat-left"><span class="cat-icon">🏪</span><span class="cat-name">Toutes les boutiques</span></div>
            <span class="cat-count">${stores.length}</span>
        </div>
        ${categories.map(c => `
            <div class="category-item ${currentCategory === c.name ? 'active' : ''}" data-name="${escapeHtml(c.name)}" data-cat-id="${c.id}">
                <div class="cat-left"><span class="cat-icon">${c.icon}</span><span class="cat-name">${escapeHtml(c.name)}</span></div>
                <span class="cat-count">${categoryCounts[c.name] || 0}</span>
            </div>
        `).join('')}
    `;
    
    list.querySelectorAll('.category-item').forEach(el => {
        el.addEventListener('click', () => {
            const catName = el.dataset.name;
            currentCategory = catName || null;
            currentPage = 1;
            renderCategories();
            renderStores();
            const catFilter = document.getElementById('catFilter');
            if (catFilter) catFilter.value = currentCategory || '';
        });
    });
}

// ====== FILTER STORES ======
function filterStores() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    const sortValue = document.getElementById('sortFilter')?.value || '';
    
    filteredStores = stores.filter(s => {
        const matchSearch = !searchTerm || 
            s.name.toLowerCase().includes(searchTerm) || 
            (s.producerName || '').toLowerCase().includes(searchTerm);
        const matchCategory = !currentCategory || s.categoryName === currentCategory;
        return matchSearch && matchCategory;
    });
    
    // Tri
    if (sortValue === 'name') {
        filteredStores.sort((a, b) => a.name.localeCompare(b.name, 'fr'));
    } else if (sortValue === 'products') {
        filteredStores.sort((a, b) => (b.products || 0) - (a.products || 0));
    } else if (sortValue === 'sales') {
        filteredStores.sort((a, b) => (b.sales || 0) - (a.sales || 0));
    }
    
    document.getElementById('statStores').textContent = filteredStores.length;
}

// 🔥 FUNCIÓN PARA ELIMINAR TIENDA (SOLO ADMIN)
function deleteStoreAdmin(storeId, storeName) {
    if (!isAdmin) {
        showToast('❌ Non autorisé', true);
        return;
    }
    
    if (confirm('⚠️ Êtes-vous sûr de vouloir supprimer la boutique "' + storeName + '" ?\n\nCette action supprimera également tous ses produits et est irréversible.')) {
        if (confirm('Confirmation finale : Voulez-vous vraiment supprimer cette boutique ?')) {
            fetch('supprimer_boutique_admin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id_boutique=' + storeId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('✅ Boutique supprimée avec succès !');
                    // Eliminar la tienda de la lista
                    stores = stores.filter(s => s.id !== storeId);
                    renderCategories();
                    renderStores();
                } else {
                    showToast('❌ Erreur : ' + data.message, true);
                }
            })
            .catch(() => showToast('❌ Erreur de connexion au serveur', true));
        }
    }
}

// ====== RENDER STORES ======
function renderStores() {
    const grid = document.getElementById('storesGrid');
    if (!grid) return;
    
    filterStores();
    
    const totalItems = filteredStores.length;
    const totalPages = Math.ceil(totalItems / perPage);
    
    if (currentPage > totalPages && totalPages > 0) {
        currentPage = totalPages;
    }
    if (currentPage < 1) currentPage = 1;
    
    const startIndex = (currentPage - 1) * perPage;
    const endIndex = Math.min(startIndex + perPage, totalItems);
    const pageItems = filteredStores.slice(startIndex, endIndex);
    
    if (pageItems.length === 0) {
        grid.innerHTML = `<div class="empty-state"><div class="empty-icon">🏪</div><p>Aucune boutique trouvée.</p></div>`;
        renderPagination(0, 0);
        return;
    }
    
    const fallbackImg = 'IMAGES/default-boutique.jpg';
    
    grid.innerHTML = pageItems.map(s => {
        // 🔥 Botón de eliminar solo para admin
        let deleteBtn = '';
        if (isAdmin) {
            deleteBtn = `
                <button class="admin-delete-btn" onclick="event.stopPropagation(); deleteStoreAdmin(${s.id}, '${escapeHtml(s.name)}')" title="Supprimer cette boutique">
                    <i class="bi bi-trash3"></i> Supprimer
                </button>
            `;
        }
        
        return `
            <div class="store-card">
                ${deleteBtn}
                <div class="store-banner" onclick="window.location.href='info-store.php?id=${s.id}'">
                    <img src="${escapeHtml(s.banner || fallbackImg)}" alt="${escapeHtml(s.name)}" onerror="this.src='${fallbackImg}'">
                    ${s.badge ? `<span class="store-badge">${escapeHtml(s.badge)}</span>` : ''}
                </div>
                <div class="store-body">
                    <div class="store-name">${escapeHtml(s.name)}</div>
                    <span class="store-category-tag">${escapeHtml(s.categoryName)}</span>
                    <p class="store-desc">${escapeHtml((s.desc || '').substring(0, 100))}${(s.desc || '').length > 100 ? '…' : ''}</p>
                    <div class="store-stats">
                        <div class="store-stat">
                            <div class="stars">${renderStars(s.rating || 4.5)}</div>
                            <span class="store-stat-label">${s.reviews || 0} avis</span>
                        </div>
                        <div class="store-stat">
                            <div class="store-stat-val">${s.products || 0}</div>
                            <span class="store-stat-label">Produits</span>
                        </div>
                        <div class="store-stat">
                            <div class="store-stat-val">${typeof s.sales === 'number' ? s.sales.toLocaleString() : s.sales || '0'}</div>
                            <span class="store-stat-label">Ventes</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    const infoEl = document.getElementById('paginationInfo');
    if (infoEl && totalItems > 0) {
        infoEl.textContent = `Affichage ${startIndex + 1} - ${endIndex} sur ${totalItems} boutique${totalItems > 1 ? 's' : ''}`;
    } else if (infoEl) {
        infoEl.textContent = '';
    }
    
    renderPagination(currentPage, totalPages);
}

// ====== RENDER PAGINATION ======
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
    const totalPages = Math.ceil(filteredStores.length / perPage);
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    renderStores();
    document.querySelector('.stores-area').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ====== ADD CATEGORY ======
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

// ====== SCROLL REVEAL ======
function initReveal() {
    const elements = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => {
            if (e.isIntersecting) e.target.classList.add('visible');
        });
    }, { threshold: 0.1 });
    elements.forEach(el => observer.observe(el));
}

// ====== ÉVÉNEMENTS ======
document.getElementById('searchInput')?.addEventListener('input', () => {
    currentPage = 1;
    renderStores();
});

document.getElementById('sortFilter')?.addEventListener('change', () => {
    currentPage = 1;
    renderStores();
});

document.getElementById('toggleAddCat')?.addEventListener('click', () => {
    document.getElementById('addCatForm').classList.toggle('open');
});

document.getElementById('cancelCat')?.addEventListener('click', () => {
    document.getElementById('addCatForm').classList.remove('open');
    document.getElementById('catNameInput').value = '';
    document.getElementById('catIconInput').value = '';
});

document.getElementById('saveCat')?.addEventListener('click', addCategory);

// Filtre par catégorie
const catFilter = document.getElementById('catFilter');
if (catFilter) {
    categories.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.name;
        opt.textContent = c.name;
        catFilter.appendChild(opt);
    });
    catFilter.addEventListener('change', (e) => {
        currentCategory = e.target.value || null;
        currentPage = 1;
        renderCategories();
        renderStores();
    });
}

// Filtre par nombre d'éléments par page
const perPageFilter = document.getElementById('perPageFilter');
if (perPageFilter) {
    perPage = parseInt(perPageFilter.value) || 12;
    
    perPageFilter.addEventListener('change', function(e) {
        perPage = parseInt(e.target.value) || 12;
        currentPage = 1;
        renderStores();
    });
}

// Initialisation
renderCategories();
renderStores();
initReveal();

// Mettre à jour les statistiques
document.getElementById('statProducts').textContent = stores.reduce((sum, s) => sum + (s.products || 0), 0);
</script>
</body>
</html>