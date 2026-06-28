<?php
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'client') {
    header('Location: signin.php');
    exit;
}

$theme = $_COOKIE['theme'] ?? 'light';
$lang = $_COOKIE['lang'] ?? 'fr';

include('connexion.php');

try {
    $stmt = $pdo->prepare("SELECT * FROM client WHERE id_client = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $client = $stmt->fetch();
    
    if (!$client) {
        session_destroy();
        header('Location: signin.php');
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM commande WHERE id_client = ? ORDER BY date_commande DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $commandes = $stmt->fetchAll();

    foreach ($commandes as &$cmd) {
        $stmtP = $pdo->prepare("
            SELECT ct.quantite, ct.prix_unitaire, p.nom_produit, p.photo_url
            FROM contenir ct
            JOIN produit p ON ct.id_produit = p.id_produit
            WHERE ct.id_commande = ?
        ");
        $stmtP->execute([$cmd['id_commande']]);
        $cmd['produits'] = $stmtP->fetchAll();
    }
    unset($cmd);
    
    $stmt = $pdo->prepare("SELECT p.*, b.nom_boutique FROM produit p 
                           JOIN favoris f ON p.id_produit = f.id_produit 
                           LEFT JOIN boutique b ON p.id_boutique = b.id_boutique
                           WHERE f.id_client = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $favoris = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT p.*, pa.quantite FROM panier pa 
                           JOIN produit p ON pa.id_produit = p.id_produit 
                           WHERE pa.id_client = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $panier = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT b.id_boutique, b.nom_boutique, b.image, b.description,
                                  p.nom_entreprise as producteur_nom
                           FROM favoris_boutique fb
                           JOIN boutique b ON fb.id_boutique = b.id_boutique
                           LEFT JOIN producteur p ON b.id_producteur = p.id_producteur
                           WHERE fb.id_client = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $favoris_boutiques = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log("Erreur dashboard client: " . $e->getMessage());
    $commandes = [];
    $favoris = [];
    $panier = [];
    $favoris_boutiques = [];
}

$total_depense = array_sum(array_column(array_filter($commandes, fn($c) => $c['statut_commande'] !== 'Annulée'), 'montant_total'));

$t = [
    'dashboard_title' => 'Mon Espace Client',
    'orders' => 'Mes Commandes',
    'favorites' => 'Favoris',
    'profile' => 'Mon Profil',
    'settings' => 'Paramètres',
    'no_orders' => 'Aucune commande pour le moment.',
    'no_favorites' => 'Aucun produit favori.',
    'order_number' => 'N° Commande',
    'date' => 'Date',
    'total' => 'Total',
    'status' => 'Statut',
    'save' => '💾 Enregistrer',
    'saved' => '✅ Profil mis à jour avec succès !',
    'full_name' => 'Nom complet',
    'email' => 'Email',
    'phone' => 'Téléphone',
    'address' => 'Adresse',
    'theme_light' => '☀️ Clair',
    'theme_dark' => '🌙 Sombre',
    'my_orders' => '📦 Mes Commandes',
    'my_favorites' => '❤️ Mes Produits Favoris',
    'my_profile' => '👤 Mon Profil',
    'cart' => 'Panier',
    'shop' => 'Boutique',
    'price' => 'Prix',
    'view_product' => 'Voir le produit',
    'remove_favorite' => 'Retirer des favoris',
    'my_fav_boutiques' => '🏪 Mes Boutiques Favorites',
    'no_fav_boutiques' => 'Aucune boutique favorite.',
    'remove_fav_boutique' => 'Retirer',
    'view_boutique' => 'Voir la boutique',
    'delivered' => 'Livrée',
    'pending' => 'En attente',
    'confirmed' => 'Confirmée',
    'shipped' => 'Expédiée',
    'cancelled' => 'Annulée',
    'theme_changed' => '✅ Thème changé en ',
    'light' => 'clair',
    'dark' => 'sombre',
    'total_spent' => 'Total Dépensé'
];

function getStatusBadge($status, $t) {
    $map = [
        'Livrée' => 'success',
        'En attente' => 'warning',
        'Confirmée' => 'info',
        'Expédiée' => 'info',
        'Annulée' => 'danger'
    ];
    $class = $map[$status] ?? 'info';
    $label = $t[strtolower(str_replace(['é', 'è', 'ê'], ['e', 'e', 'e'], $status))] ?? $status;
    return '<span class="badge badge-' . $class . '">' . htmlspecialchars($label) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $theme; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GreenMarket – <?php echo $t['dashboard_title']; ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  :root {
    --primary: #5D0D18;
    --primary-light: #7a1020;
    --secondary: #9FB2AC;
    --bg: #FFF9EB;
    --bg-light: #f5f0e8;
    --bg-card: #ffffff;
    --bg-input: #ffffff;
    --text-dark: #2C2C2C;
    --text-light: #6B6B6B;
    --border-color: #e8ddd0;
    --shadow-color: rgba(93,13,24,0.1);
    --gold: #c07a1a;
  }
  
  [data-theme="dark"] {
    --primary: #8a6048;
    --primary-light: #a0785a;
    --secondary: #6d4c3a;
    --bg: #2c241e;
    --bg-light: #3d3229;
    --bg-card: #3d3229;
    --bg-input: #4d3d32;
    --text-dark: #f0e6d8;
    --text-light: #b8a896;
    --border-color: #5a4a3a;
    --shadow-color: rgba(0,0,0,0.4);
    --gold: #d4a85c;
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { 
    font-family: 'Lato', sans-serif; 
    background: var(--bg); 
    color: var(--text-dark);
    transition: background 0.3s, color 0.3s;
  }
  
  h1, h2, h3, .playfair { font-family: 'Playfair Display', serif; }

  .dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    padding: 2rem 2.5rem;
    max-width: 1400px;
    margin: 0 auto;
  }
  
  .stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s, background 0.3s;
  }
  .stat-card:hover { transform: translateY(-5px); box-shadow: 0 4px 20px var(--shadow-color); }
  .stat-icon { font-size: 2rem; }
  .stat-val {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary);
    margin: 0.5rem 0;
  }
  .stat-label {
    font-size: 0.8rem;
    color: var(--text-light);
    text-transform: uppercase;
  }

  .tabs-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2.5rem;
  }
  .tabs {
    display: flex;
    gap: 0.5rem;
    border-bottom: 2px solid var(--border-color);
    flex-wrap: wrap;
  }
  .tab-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 0.9rem;
    color: var(--text-light);
    transition: all 0.2s;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
  }
  .tab-btn:hover { color: var(--primary); }
  .tab-btn.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    font-weight: 600;
  }
  [data-theme="dark"] .tab-btn.active { color: var(--gold); border-bottom-color: var(--gold); }

  .main-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 2.5rem;
  }
  .tab-panel { display: none; }
  .tab-panel.active { display: block; }

  .section-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 2rem;
    transition: background 0.3s, border-color 0.3s;
  }
  .section-header {
    background: var(--primary);
    color: #fff9eb;
    padding: 1rem 1.5rem;
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    font-weight: 600;
  }
  [data-theme="dark"] .section-header { background: var(--primary-light); }
  
  .table-wrapper { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; }
  th, td { 
    padding: 1rem; 
    text-align: left; 
    border-bottom: 1px solid var(--border-color);
    color: var(--text-dark);
  }
  th { 
    background: var(--bg-light); 
    font-weight: 600;
    color: var(--text-dark);
  }
  [data-theme="dark"] th { background: var(--bg); }
  
  .empty-state { 
    text-align: center; 
    padding: 3rem; 
    color: var(--text-light);
  }

  .badge {
    padding: 0.25rem 0.65rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
  }
  .badge-success { background: #d4edda; color: #155724; }
  .badge-warning { background: #fff3cd; color: #856404; }
  .badge-info { background: #d1ecf1; color: #0c5460; }
  .badge-danger, .badge-annulee { background: #f8d7da; color: #721c24; }
  
  [data-theme="dark"] .badge-success { background: #1e4620; color: #8fdf9f; }
  [data-theme="dark"] .badge-warning { background: #4a3a1a; color: #f0d080; }
  [data-theme="dark"] .badge-info { background: #1a3a4a; color: #80d0f0; }
  [data-theme="dark"] .badge-danger, [data-theme="dark"] .badge-annulee { background: #4a1a1a; color: #f08080; }

  .btn {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.85rem;
    border: none;
    cursor: pointer;
    transition: background 0.2s, transform 0.2s;
  }
  .btn:hover { transform: translateY(-2px); }
  .btn-wine { background: var(--primary); color: white; }
  .btn-wine:hover { background: var(--primary-light); }
  .btn-outline-wine { 
    background: transparent; 
    color: var(--primary); 
    border: 1.5px solid var(--primary);
  }
  .btn-outline-wine:hover { background: var(--primary); color: white; }
  .btn-danger-outline { 
    background: transparent; 
    color: #c0392b; 
    border: 1.5px solid #c0392b;
  }
  .btn-danger-outline:hover { background: #c0392b; color: white; }

  .form-group { margin-bottom: 1rem; }
  .form-group label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 0.3rem;
    color: var(--text-light);
  }
  .form-control {
    width: 100%;
    padding: 0.6rem 0.8rem;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    background: var(--bg-input);
    color: var(--text-dark);
    transition: border-color 0.2s;
  }
  .form-control:focus {
    outline: none;
    border-color: var(--primary);
  }
  .profile-form { padding: 1.5rem; }

  .favorites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1rem;
    padding: 1.5rem;
  }
  .fav-card {
    background: var(--bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
  }
  .fav-card:hover { transform: translateY(-4px); box-shadow: 0 4px 15px var(--shadow-color); }
  .fav-card .fav-img {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 6px;
    margin-bottom: 0.5rem;
  }
  .fav-card .fav-name { font-weight: 600; margin-bottom: 0.3rem; }
  .fav-card .fav-price { color: var(--primary); font-weight: 700; }
  .fav-card .fav-shop { font-size: 0.75rem; color: var(--text-light); }
  .fav-card .fav-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    margin-top: 0.5rem;
    flex-wrap: wrap;
  }
  .fav-card .fav-actions .btn { font-size: 0.7rem; padding: 0.3rem 0.6rem; }

  .section-subheader {
    font-family: 'Playfair Display', serif;
    font-size: 1rem;
    font-weight: 700;
    color: #fff;
    background: var(--primary);
    padding: 1rem 1.5rem;
    margin-top: 0.5rem;
  }
  .boutique-fav-card {
    background: var(--bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .boutique-fav-card:hover { transform: translateY(-4px); box-shadow: 0 4px 15px var(--shadow-color); }
  .boutique-fav-card .b-img {
    width: 100%;
    height: 110px;
    object-fit: cover;
  }
  .boutique-fav-card .b-body { padding: 0.75rem; text-align: center; }
  .boutique-fav-card .b-name { font-weight: 700; font-size: 0.9rem; margin-bottom: 0.2rem; color: var(--primary); }
  .boutique-fav-card .b-producer { font-size: 0.72rem; color: var(--text-light); margin-bottom: 0.5rem; }
  .boutique-fav-card .fav-actions {
    display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap;
  }
  .boutique-fav-card .fav-actions .btn { font-size: 0.7rem; padding: 0.3rem 0.6rem; }

  .settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    padding: 1.5rem;
  }
  .settings-group {
    background: var(--bg);
    border-radius: 8px;
    padding: 1.2rem;
    border: 1px solid var(--border-color);
  }
  .settings-group h4 {
    font-family: 'Playfair Display', serif;
    color: var(--primary);
    margin-bottom: 0.8rem;
    font-size: 1rem;
  }

  .theme-toggle-wrapper {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem 0;
  }
  .theme-toggle-label {
    font-size: 0.85rem;
    color: var(--text-light);
    font-weight: 500;
  }
  
  .theme-switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
    flex-shrink: 0;
    cursor: pointer;
  }
  .theme-switch input {
    opacity: 0;
    width: 0;
    height: 0;
  }
  
  .theme-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: #ccc;
    transition: 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    border-radius: 34px;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
  }
  
  .theme-slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background: white;
    transition: 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
  }
  
  .theme-slider .slider-icons {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 100%;
    padding: 0 8px;
    display: flex;
    justify-content: space-between;
    pointer-events: none;
    font-size: 14px;
    color: #fff;
    z-index: 1;
  }
  .theme-slider .slider-icons .icon-sun { opacity: 1; }
  .theme-slider .slider-icons .icon-moon { opacity: 0.3; }
  
  .theme-switch input:checked + .theme-slider {
    background: #2c241e;
  }
  
  .theme-switch input:checked + .theme-slider:before {
    transform: translateX(26px);
    background: #f0e6d8;
  }

  #order-modal {
    display: none; position: fixed; inset: 0; z-index: 9997;
    align-items: center; justify-content: center;
  }
  #order-modal.show { display: flex; }
  #order-modal-overlay {
    position: absolute; inset: 0;
    background: rgba(0,0,0,0.5); backdrop-filter: blur(3px);
  }
  #order-modal-box {
    position: relative; background: var(--bg-card); border-radius: 20px;
    padding: 1.8rem; max-width: 480px; width: 93%;
    max-height: 80vh; overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25);
    animation: modalIn 0.25s cubic-bezier(.22,1,.36,1);
  }
  #order-modal-title {
    font-family: 'Playfair Display', serif; font-size: 1.1rem;
    font-weight: 700; color: var(--primary); margin-bottom: 1rem;
    padding-bottom: 0.75rem; border-bottom: 1px solid var(--border-color);
    display: flex; justify-content: space-between; align-items: center;
  }
  #order-modal-close {
    background: none; border: none; font-size: 1.4rem;
    cursor: pointer; color: var(--text-light); line-height: 1;
  }
  .modal-product-row {
    display: flex; align-items: center; gap: 0.8rem;
    padding: 0.6rem 0; border-bottom: 1px solid var(--border-color);
  }
  .modal-product-row:last-child { border-bottom: none; }
  .modal-product-row img {
    width: 55px; height: 55px; object-fit: cover; border-radius: 8px; flex-shrink: 0;
  }
  .modal-product-name { font-weight: 600; font-size: 0.88rem; }
  .modal-product-detail { font-size: 0.78rem; color: var(--text-light); }
  .modal-product-price { margin-left: auto; font-weight: 700; color: var(--primary); font-size: 0.88rem; white-space: nowrap; }
  .modal-total {
    margin-top: 1rem; padding-top: 0.75rem; border-top: 2px solid var(--border-color);
    display: flex; justify-content: space-between; align-items: center;
    font-weight: 700; font-size: 1rem; color: var(--primary);
  }
  .modal-print-btn {
    background: var(--primary); color: #fff; border: none;
    border-radius: 999px; padding: 0.5rem 1.1rem; font-weight: 700;
    font-size: 0.85rem; cursor: pointer; transition: background 0.2s;
    text-decoration: none; display: inline-block;
  }
  .modal-print-btn:hover { background: var(--primary-light, #7a1020); }

  #confirm-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9998;
    align-items: center;
    justify-content: center;
  }
  #confirm-modal.show { display: flex; }
  #confirm-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.45);
    backdrop-filter: blur(3px);
  }
  #confirm-box {
    position: relative;
    background: var(--bg-card, #fff);
    border-radius: 20px;
    padding: 2rem 1.8rem 1.5rem;
    max-width: 360px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    text-align: center;
    animation: modalIn 0.25s cubic-bezier(.22,1,.36,1);
  }
  @keyframes modalIn {
    from { opacity: 0; transform: scale(0.88) translateY(20px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
  }
  #confirm-icon { font-size: 2.5rem; margin-bottom: 0.8rem; }
  #confirm-title {
    font-family: 'Playfair Display', serif;
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--text-dark, #2C2C2C);
    margin-bottom: 0.4rem;
  }
  #confirm-msg {
    font-size: 0.88rem;
    color: var(--text-light, #6B6B6B);
    margin-bottom: 1.4rem;
  }
  .confirm-btns { display: flex; gap: 0.8rem; justify-content: center; }
  .confirm-btns button {
    flex: 1;
    padding: 0.65rem 1rem;
    border-radius: 999px;
    font-weight: 700;
    font-size: 0.9rem;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
  }
  #confirm-cancel { background: var(--bg-light, #f5f0e8); color: var(--text-dark, #2C2C2C); }
  #confirm-cancel:hover { opacity: 0.8; }
  #confirm-ok { background: #c0392b; color: #fff; }
  #confirm-ok:hover { background: #a93226; }

  .toast {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: var(--primary);
    color: white;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s;
    z-index: 9999;
  }
  .toast.show { transform: translateY(0); opacity: 1; }
  .toast.error { background: #c0392b; }

  @media (max-width: 768px) {
    .dashboard-grid, .tabs-container, .main-content { padding: 1rem; }
    .settings-grid { grid-template-columns: 1fr; }
    .favorites-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); }
    .tabs .tab-btn { font-size: 0.8rem; padding: 0.5rem 0.8rem; }
    .section-header { font-size: 1rem; padding: 0.8rem 1rem; }
    .theme-switch { width: 50px; height: 28px; }
    .theme-slider:before { height: 20px; width: 20px; left: 4px; bottom: 4px; }
    .theme-switch input:checked + .theme-slider:before { transform: translateX(22px); }
    .theme-slider .slider-icons { font-size: 11px; padding: 0 5px; }
  }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="dashboard-grid">
  <div class="stat-card">
    <div class="stat-icon">📦</div>
    <div class="stat-val"><?php echo count(array_filter($commandes, fn($c) => $c['statut_commande'] !== 'Annulée')); ?></div>
    <div class="stat-label"><?php echo $t['orders']; ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">❤️</div>
    <div class="stat-val"><?php echo count($favoris) + count($favoris_boutiques); ?></div>
    <div class="stat-label"><?php echo $t['favorites']; ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🛒</div>
    <div class="stat-val"><?php echo array_sum(array_column($panier, 'quantite')); ?></div>
    <div class="stat-label"><?php echo $t['cart']; ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">💰</div>
    <div class="stat-val"><?php echo number_format($total_depense, 0, ',', ' '); ?> DH</div>
    <div class="stat-label"><?php echo $t['total_spent']; ?></div>
  </div>
</div>

<div class="tabs-container">
  <div class="tabs">
    <button class="tab-btn active" data-tab="commandes">📦 <?php echo $t['orders']; ?></button>
    <button class="tab-btn" data-tab="favoris">❤️ <?php echo $t['favorites']; ?></button>
    <button class="tab-btn" data-tab="profil">👤 <?php echo $t['profile']; ?></button>
    <button class="tab-btn" data-tab="parametres">⚙️ <?php echo $t['settings']; ?></button>
  </div>
</div>

<div class="main-content">

  <div class="tab-panel active" id="tab-commandes">
    <div class="section-card">
      <div class="section-header">📦 <?php echo $t['my_orders']; ?></div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th><?php echo $t['order_number']; ?></th>
              <th><?php echo $t['date']; ?></th>
              <th><?php echo $t['total']; ?></th>
              <th><?php echo $t['status']; ?></th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($commandes)): ?>
              <tr><td colspan="5" class="empty-state"><?php echo $t['no_orders']; ?></td></tr>
            <?php else: ?>
              <?php foreach ($commandes as $commande): ?>
              <tr>
                <td><strong>#<?php echo $commande['id_commande']; ?></strong></td>
                <td><?php echo date('d/m/Y', strtotime($commande['date_commande'])); ?></td>
                <td><strong><?php echo number_format($commande['montant_total'], 0, ',', ' '); ?> DH</strong></td>
                <td><?php echo getStatusBadge($commande['statut_commande'], $t); ?></td>
                <td>
                  <div style="display:flex;flex-wrap:nowrap;gap:0.3rem;align-items:center;">
                    <button onclick="voirDetailsCommande(<?php echo $commande['id_commande']; ?>, '<?php echo addslashes(date('d/m/Y', strtotime($commande['date_commande']))); ?>', <?php echo $commande['montant_total']; ?>, <?php echo htmlspecialchars(json_encode($commande['produits']), ENT_QUOTES); ?>)"
                            class="btn btn-wine" style="font-size:0.7rem;padding:0.25rem 0.6rem;white-space:nowrap;">
                      Détails
                    </button>
                    <?php if ($commande['statut_commande'] === 'En attente'): ?>
                      <button onclick="annulerCommande(<?php echo $commande['id_commande']; ?>)" 
                              class="btn btn-danger-outline" style="font-size:0.7rem;padding:0.25rem 0.6rem;white-space:nowrap;">
                        Annuler
                      </button>
                    <?php elseif ($commande['statut_commande'] === 'Annulée'): ?>
                      <button onclick="supprimerCommande(<?php echo $commande['id_commande']; ?>, this, <?php echo (float)$commande['montant_total']; ?>)" 
                              class="btn btn-danger-outline" style="font-size:0.7rem;padding:0.25rem 0.6rem;white-space:nowrap;">
                        Supprimer
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="tab-panel" id="tab-favoris">
    <div class="section-card">
      <div class="section-header">❤️ <?php echo $t['my_favorites']; ?></div>
      <div class="favorites-grid">
        <?php if (empty($favoris)): ?>
          <div class="empty-state" style="grid-column:1/-1;"><?php echo $t['no_favorites']; ?></div>
        <?php else: ?>
          <?php foreach ($favoris as $fav): ?>
          <div class="fav-card">
            <img src="<?php echo !empty($fav['photo_url']) ? htmlspecialchars($fav['photo_url']) : 'IMAGES/default-product.jpg'; ?>" 
                 class="fav-img" 
                 alt="<?php echo htmlspecialchars($fav['nom_produit']); ?>"
                 onerror="this.src='IMAGES/default-product.jpg'">
            <div class="fav-name"><?php echo htmlspecialchars($fav['nom_produit']); ?></div>
            <div class="fav-price"><?php echo number_format($fav['prix_unitaire'], 0, ',', ' '); ?> DH</div>
            <div class="fav-shop">🏪 <?php echo htmlspecialchars($fav['nom_boutique'] ?? 'Boutique'); ?></div>
            <div class="fav-actions">
              <a href="info-produit.php?id=<?php echo $fav['id_produit']; ?>" class="btn btn-wine">
                👁️ <?php echo $t['view_product']; ?>
              </a>
              <button class="btn btn-danger-outline" onclick="removeFavorite(<?php echo $fav['id_produit']; ?>, this)">
                ❌ <?php echo $t['remove_favorite']; ?>
              </button>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="section-subheader">🏪 <?php echo $t['my_fav_boutiques']; ?></div>
      <div class="favorites-grid">
        <?php if (empty($favoris_boutiques)): ?>
          <div class="empty-state" style="grid-column:1/-1;"><?php echo $t['no_fav_boutiques']; ?></div>
        <?php else: ?>
          <?php foreach ($favoris_boutiques as $b): 
            $bimg = !empty($b['image']) ? htmlspecialchars($b['image']) : 'IMAGES/default-boutique.jpg';
          ?>
          <div class="boutique-fav-card">
            <img src="<?php echo $bimg; ?>" class="b-img"
                 alt="<?php echo htmlspecialchars($b['nom_boutique']); ?>"
                 onerror="this.src='IMAGES/default-boutique.jpg'">
            <div class="b-body">
              <div class="b-name"><?php echo htmlspecialchars($b['nom_boutique']); ?></div>
              <div class="b-producer">🧑‍🎨 <?php echo htmlspecialchars($b['producteur_nom'] ?? ''); ?></div>
              <div class="fav-actions">
                <a href="info-store.php?id=<?php echo $b['id_boutique']; ?>" class="btn btn-wine">
                  👁️ <?php echo $t['view_boutique']; ?>
                </a>
                <button class="btn btn-danger-outline" onclick="removeFavBoutique(<?php echo $b['id_boutique']; ?>, this)">
                  ❌ <?php echo $t['remove_fav_boutique']; ?>
                </button>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="tab-panel" id="tab-profil">
    <div class="section-card">
      <div class="section-header">👤 <?php echo $t['my_profile']; ?></div>
      <div class="profile-form">
        <form id="profileForm" onsubmit="return saveProfile(event)">
          <div class="form-group">
            <label><?php echo $t['full_name']; ?></label>
            <input type="text" id="profileName" name="nom_client" value="<?php echo htmlspecialchars($client['nom_client']); ?>" class="form-control" required>
          </div>
          <div class="form-group">
            <label><?php echo $t['email']; ?></label>
            <input type="email" id="profileEmail" name="email" value="<?php echo htmlspecialchars($client['email']); ?>" class="form-control" required>
          </div>
          <div class="form-group">
            <label><?php echo $t['phone']; ?></label>
            <input type="tel" id="profilePhone" name="telephone" placeholder="+212 6XX XX XX XX" class="form-control" value="<?php echo htmlspecialchars($client['telephone'] ?? ''); ?>">
          </div>
          <div class="form-group">
            <label><?php echo $t['address']; ?></label>
            <textarea id="profileAddress" name="adresse" rows="3" class="form-control" placeholder="Votre adresse complète"><?php echo htmlspecialchars($client['adresse'] ?? ''); ?></textarea>
          </div>
          <button type="submit" class="btn btn-wine">💾 <?php echo $t['save']; ?></button>
        </form>
      </div>
    </div>
  </div>

  <div class="tab-panel" id="tab-parametres">
    <div class="section-card">
      <div class="section-header">⚙️ <?php echo $t['settings']; ?></div>
      <div class="settings-grid">
        <div class="settings-group">
          <h4>🎨 <?php echo $t['theme_light']; ?> / <?php echo $t['theme_dark']; ?></h4>
          <div class="theme-toggle-wrapper">
            <span class="theme-toggle-label">☀️ <?php echo $t['theme_light']; ?></span>
            <label class="theme-switch">
              <input type="checkbox" id="themeToggle" <?php echo $theme === 'dark' ? 'checked' : ''; ?> onchange="toggleTheme()">
              <span class="theme-slider">
                <span class="slider-icons">
                  <span class="icon-sun">☀️</span>
                  <span class="icon-moon">🌙</span>
                </span>
              </span>
            </label>
            <span class="theme-toggle-label">🌙 <?php echo $t['theme_dark']; ?></span>
          </div>
          <p style="font-size:0.75rem;color:var(--text-light);margin-top:0.5rem;">
            <?php echo $theme === 'dark' ? '🌙 Mode sombre activé' : '☀️ Mode clair activé'; ?>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="order-modal">
  <div id="order-modal-overlay"></div>
  <div id="order-modal-box">
    <div id="order-modal-title">
      <span id="order-modal-label"></span>
      <button id="order-modal-close">✕</button>
    </div>
    <div id="order-modal-products"></div>
    <div class="modal-total" id="order-modal-total"></div>
  </div>
</div>

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

<div class="toast" id="toast"></div>

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const tabName = this.getAttribute('data-tab');
    localStorage.setItem('activeDashboardTab', tabName);
    
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    this.classList.add('active');
    document.getElementById('tab-' + tabName).classList.add('active');
  });
});

function showToast(msg, isError = false) {
  const toast = document.getElementById('toast');
  toast.innerHTML = msg;
  toast.className = 'toast' + (isError ? ' error' : '');
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3000);
}

let _confirmCallback = null;

function askConfirm(title, msg, callback, icon) {
  document.getElementById('confirm-icon').textContent = icon || '⚠️';
  document.getElementById('confirm-title').textContent = title;
  document.getElementById('confirm-msg').textContent = msg;
  _confirmCallback = callback;
  document.getElementById('confirm-modal').classList.add('show');
}

document.getElementById('confirm-ok').addEventListener('click', () => {
  document.getElementById('confirm-modal').classList.remove('show');
  if (_confirmCallback) _confirmCallback();
});

document.getElementById('confirm-cancel').addEventListener('click', () => {
  document.getElementById('confirm-modal').classList.remove('show');
  _confirmCallback = null;
});

document.getElementById('confirm-overlay').addEventListener('click', () => {
  document.getElementById('confirm-modal').classList.remove('show');
  _confirmCallback = null;
});

function saveProfile(event) {
  event.preventDefault();
  const formData = new FormData(document.getElementById('profileForm'));
  fetch('update_client_profile.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showToast('<?php echo $t['saved']; ?>');
    } else {
      showToast('❌ ' + (data.message || 'Erreur'), true);
    }
  })
  .catch(error => {
    showToast('❌ Erreur de connexion', true);
  });
  return false;
}

function annulerCommande(id) {
  askConfirm('Annuler la commande ?', 'Cette action est irréversible.', () => {
    fetch('annuler_commande.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: 'id_commande=' + id
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast('✅ Commande annulée avec succès !');
        const btn = document.querySelector(`button[onclick*="annulerCommande(${id})"]`);
        if (btn) {
          const row = btn.closest('tr');
          if (row) {
            const totalCell = row.cells[2];
            const montantText = totalCell ? totalCell.textContent.replace(/[^\d]/g, '') : '0';
            const montant = parseFloat(montantText) || 0;

            const badge = row.querySelector('.badge');
            if (badge) { badge.className = 'badge badge-danger'; badge.textContent = 'Annulée'; }

            const td = btn.closest('td');
            if (td) {
              const detailBtn = td.querySelector('button:first-child');
              const detailHTML = detailBtn ? detailBtn.outerHTML : '';
              td.innerHTML = detailHTML +
                '<button onclick="supprimerCommande(' + id + ', this, ' + montant + ')" ' +
                'class="btn btn-danger-outline" style="font-size:0.7rem;padding:0.25rem 0.6rem;white-space:nowrap;">' +
                'Supprimer</button>';
            }

            const totalDepEl = document.querySelectorAll('.stat-val')[3];
            if (totalDepEl) {
              const current = parseFloat(totalDepEl.textContent.replace(/[^\d]/g, '')) || 0;
              const newVal = Math.max(0, current - montant);
              totalDepEl.textContent = newVal.toLocaleString('fr-FR') + ' DH';
            }
          }
        }
        const statVal = document.querySelectorAll('.stat-val')[0];
        if (statVal) {
          let n = parseInt(statVal.textContent);
          if (n > 0) statVal.textContent = n - 1;
        }
      } else {
        showToast('❌ ' + data.message, true);
      }
    })
    .catch(() => showToast('❌ Erreur de connexion', true));
  });
}

function supprimerCommande(id, btn, montant) {
  montant = parseFloat(montant) || 0;
  askConfirm('Supprimer la commande ?', 'Cette commande sera supprimée définitivement.', () => {
    fetch('supprimer_commande.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: 'id_commande=' + id
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast('✅ Commande supprimée !');
        const row = btn.closest('tr');
        if (row) row.remove();

        if (montant > 0) {
          const totalDepEl = document.querySelectorAll('.stat-val')[3];
          if (totalDepEl) {
            const current = parseFloat(totalDepEl.textContent.replace(/[^\d]/g, '')) || 0;
            const newVal = Math.max(0, current - montant);
            totalDepEl.textContent = newVal.toLocaleString('fr-FR') + ' DH';
          }
        }
      } else {
        showToast('❌ ' + data.message, true);
      }
    })
    .catch(() => showToast('❌ Erreur de connexion', true));
  }, '🗑️');
}

function ajouterAuPanier(id_produit, quantite = 1) {
  fetch('ajouter_panier.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id_produit=' + id_produit + '&quantite=' + quantite
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      showToast('🛒 Produit ajouté au panier');
      const badge = document.getElementById('cart-count');
      if (badge) {
        badge.textContent = data.total_panier;
        if (data.total_panier > 0) badge.classList.add('show');
      }
    } else {
      showToast('❌ ' + (data.message || 'Erreur'), true);
    }
  })
  .catch(() => showToast('❌ Erreur de connexion', true));
}

function removeFavorite(productId, btnElement) {
  askConfirm('Retirer des favoris', 'Voulez-vous vraiment supprimer ce produit de vos favoris ?', () => {
    fetch('remove_favorite.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'id_produit=' + productId
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showToast('✅ Produit retiré des favoris');
        if (btnElement) {
          btnElement.closest('.fav-card').remove();
          const favCountEls = document.querySelectorAll('.stat-val');
          if (favCountEls.length > 1) {
            let currentCount = parseInt(favCountEls[1].textContent);
            if (currentCount > 0) {
              favCountEls[1].textContent = currentCount - 1;
            }
          }
        }
      } else {
        showToast('❌ ' + (data.message || 'Erreur'), true);
      }
    })
    .catch(error => {
      showToast('❌ Erreur de connexion', true);
    });
  }, '❤️');
}

function removeFavBoutique(boutiqueId, btnElement) {
  askConfirm('Retirer des favoris', 'Voulez-vous retirer cette boutique de vos favoris ?', () => {
    fetch('remove_favorite_boutique.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'id_boutique=' + boutiqueId
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showToast('✅ Boutique retirée des favoris');
        if (btnElement) {
          btnElement.closest('.boutique-fav-card').remove();
          const favCountEl = document.querySelectorAll('.stat-val')[1];
          if (favCountEl) {
            let current = parseInt(favCountEl.textContent);
            if (current > 0) favCountEl.textContent = current - 1;
          }
        }
      } else {
        showToast('❌ ' + (data.message || 'Erreur'), true);
      }
    })
    .catch(() => showToast('❌ Erreur de connexion', true));
  }, '🏪');
}

function voirDetailsCommande(id, date, total, produits) {
  document.getElementById('order-modal-label').textContent = 'Commande #' + id + ' — ' + date;
  
  let html = '';
  produits.forEach(function(p) {
    const img = p.photo_url || 'IMAGES/default-product.jpg';
    const sous_total = (p.prix_unitaire * p.quantite).toFixed(2).replace('.', ',');
    html += '<div class="modal-product-row">' +
      '<img src="' + img + '" onerror="this.src=\'IMAGES/default-product.jpg\'">' +
      '<div><div class="modal-product-name">' + p.nom_produit + '</div>' +
      '<div class="modal-product-detail">Qté : ' + p.quantite + ' × ' + parseFloat(p.prix_unitaire).toFixed(2).replace('.', ',') + ' DH</div></div>' +
      '<div class="modal-product-price">' + sous_total + ' DH</div>' +
      '</div>';
  });
  if (!produits.length) html = '<p style="color:var(--text-light);text-align:center;padding:1rem;">Aucun produit trouvé.</p>';

  document.getElementById('order-modal-products').innerHTML = html;
  document.getElementById('order-modal-total').innerHTML = 
    '<a class="modal-print-btn" href="facture.php?id=' + id + '" target="_blank">🖨️ Imprimer</a>' +
    '<span>Total : ' + parseFloat(total).toFixed(2).replace('.', ',') + ' DH</span>';
  document.getElementById('order-modal').classList.add('show');
}

document.getElementById('order-modal-close').addEventListener('click', function() {
  document.getElementById('order-modal').classList.remove('show');
});
document.getElementById('order-modal-overlay').addEventListener('click', function() {
  document.getElementById('order-modal').classList.remove('show');
});

function toggleTheme() {
  const checkbox = document.getElementById('themeToggle');
  const theme = checkbox.checked ? 'dark' : 'light';
  document.cookie = 'theme=' + theme + '; path=/; max-age=31536000';
  document.documentElement.setAttribute('data-theme', theme);
  const statusText = document.querySelector('.settings-group p');
  if (statusText) {
    statusText.textContent = theme === 'dark' ? '🌙 Mode sombre activé' : '☀️ Mode clair activé';
  }
  const themeName = theme === 'light' ? 'clair' : 'sombre';
  showToast('✅ Thème changé en ' + themeName);
  setTimeout(() => location.reload(), 600);
}

document.addEventListener('DOMContentLoaded', function() {
  const theme = '<?php echo $theme; ?>';
  document.documentElement.setAttribute('data-theme', theme);
  
  const savedTab = localStorage.getItem('activeDashboardTab');
  if (savedTab) {
    const tabToActivate = document.querySelector(`.tab-btn[data-tab="${savedTab}"]`);
    if (tabToActivate) {
      tabToActivate.click();
    }
  }
});
</script>
</body>
</html>