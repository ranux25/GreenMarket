<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: signin.php");
    exit;
}

// ===== THEME (COOKIES) =====
$theme = $_COOKIE['theme'] ?? 'light';

if (isset($_GET['msgs']))   echo "<div style='color:green;padding:10px;'>" . htmlspecialchars($_GET['msgs']) . "</div>";
if (isset($_GET['msgerr'])) echo "<div style='color:red;padding:10px;'>" . htmlspecialchars($_GET['msgerr']) . "</div>";

include("connexion.php");

// ============================================
// INICIALIZAR VARIABLES
// ============================================
$total_clients = 0;
$total_producteurs = 0;
$total_users = 0;
$active_producers = 0;
$producteurs_attente = [];
$nb_attente = 0;
$ca_total = 0;
$total_commandes = 0;
$total_boutiques = 0;
$total_produits = 0;
$boutiques_attente = [];
$nb_boutiques_attente = 0;
$produits_attente = [];
$tous_produits = [];
$clients = [];
$producteurs = [];
$boutiques = [];
$commandes = [];
$administrateur = null;

try {
    // Verificar tablas existentes
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // ============================================
    // 1. ESTADÍSTICAS
    // ============================================
    if (in_array('client', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM client");
        $total_clients = $stmt->fetch()['total'] ?? 0;
    }
    
    if (in_array('producteur', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM producteur");
        $total_producteurs = $stmt->fetch()['total'] ?? 0;
    }
    $total_users = $total_clients + $total_producteurs;
    
    if (in_array('producteur', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM producteur WHERE est_valide_par_admin = 1");
        $active_producers = $stmt->fetch()['total'] ?? 0;
    }
    
    if (in_array('producteur', $tables)) {
        $stmt = $pdo->query("SELECT * FROM producteur WHERE est_valide_par_admin = 0 OR est_valide_par_admin IS NULL ORDER BY date_inscription DESC");
        $producteurs_attente = $stmt->fetchAll();
        $nb_attente = count($producteurs_attente);
    }
    
    if (in_array('commande', $tables)) {
        $stmt = $pdo->query("SELECT SUM(montant_total) as total FROM commande WHERE statut_commande = 'Livrée'");
        $ca_total = $stmt->fetch()['total'] ?? 0;
    }
    
    if (in_array('commande', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM commande");
        $total_commandes = $stmt->fetch()['total'] ?? 0;
    }
    
    if (in_array('boutique', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM boutique");
        $total_boutiques = $stmt->fetch()['total'] ?? 0;
    }
    
    if (in_array('produit', $tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM produit");
        $total_produits = $stmt->fetch()['total'] ?? 0;
    }
    
    // ============================================
    // 2. BOUTIQUES EN ATTENTE
    // ============================================
    if (in_array('boutique', $tables) && in_array('producteur', $tables)) {
        $stmt = $pdo->query("
            SELECT b.*, p.nom_entreprise as producteur_nom, p.email as producteur_email 
            FROM boutique b 
            LEFT JOIN producteur p ON b.id_producteur = p.id_producteur 
            WHERE b.est_valide_par_admin = 0 OR b.est_valide_par_admin IS NULL
            ORDER BY b.date_creation DESC
        ");
        $boutiques_attente = $stmt->fetchAll();
        $nb_boutiques_attente = count($boutiques_attente);
    }
    
    // ============================================
    // 3. TODOS LOS PRODUCTOS
    // ============================================
    if (in_array('produit', $tables) && in_array('boutique', $tables) && in_array('producteur', $tables)) {
        $stmt = $pdo->query("
            SELECT p.*, 
                   b.nom_boutique,
                   pr.nom_entreprise as nom_producteur,
                   pr.email as email_producteur,
                   c.nom_categorie
            FROM produit p 
            LEFT JOIN boutique b ON p.id_boutique = b.id_boutique 
            LEFT JOIN producteur pr ON b.id_producteur = pr.id_producteur
            LEFT JOIN categorie c ON p.id_categorie = c.id_categorie
            ORDER BY p.date_creation DESC
        ");
        $tous_produits = $stmt->fetchAll();
    }
    
    // ============================================
    // 4. PRODUCTOS EN ATTENTE
    // ============================================
    if (in_array('produit', $tables) && in_array('boutique', $tables) && in_array('producteur', $tables)) {
        $stmt = $pdo->query("
            SELECT p.*, 
                   b.nom_boutique,
                   pr.nom_entreprise as nom_producteur,
                   pr.email as email_producteur,
                   c.nom_categorie
            FROM produit p 
            LEFT JOIN boutique b ON p.id_boutique = b.id_boutique 
            LEFT JOIN producteur pr ON b.id_producteur = pr.id_producteur
            LEFT JOIN categorie c ON p.id_categorie = c.id_categorie
            WHERE p.est_valide_par_admin = 0 OR p.est_valide_par_admin IS NULL
            ORDER BY p.date_creation DESC
        ");
        $produits_attente = $stmt->fetchAll();
    }
    
    // ============================================
    // 5. TODOS LOS PRODUCTEURS
    // ============================================
    if (in_array('producteur', $tables)) {
        $stmt = $pdo->query("
            SELECT id_producteur as id, 
                   nom_entreprise as nom, 
                   email, 
                   est_valide_par_admin as actif, 
                   date_inscription 
            FROM producteur 
            ORDER BY date_inscription DESC
        ");
        $producteurs = $stmt->fetchAll();
    }
    
    // ============================================
    // 6. TODOS LOS CLIENTES
    // ============================================
    if (in_array('client', $tables)) {
        $stmt = $pdo->query("
            SELECT id_client as id, 
                   nom_client as nom, 
                   email, 
                   telephone,
                   date_inscription 
            FROM client 
            ORDER BY date_inscription DESC
        ");
        $clients = $stmt->fetchAll();
    }
    
    // ============================================
    // 7. TODAS LAS BOUTIQUES
    // ============================================
    if (in_array('boutique', $tables) && in_array('producteur', $tables)) {
        $stmt = $pdo->query("
            SELECT b.*, 
                   p.nom_entreprise as producteur_nom,
                   p.email as producteur_email
            FROM boutique b 
            LEFT JOIN producteur p ON b.id_producteur = p.id_producteur 
            ORDER BY b.date_creation DESC
        ");
        $boutiques = $stmt->fetchAll();
    }
    
    // ============================================
    // 8. COMMANDES RECENTES
    // ============================================
    if (in_array('commande', $tables) && in_array('client', $tables)) {
        $stmt = $pdo->query("
            SELECT c.*, cl.nom_client 
            FROM commande c 
            LEFT JOIN client cl ON c.id_client = cl.id_client 
            ORDER BY c.date_commande DESC 
            LIMIT 20
        ");
        $commandes = $stmt->fetchAll();
    }
    
    // ============================================
    // 9. ADMINISTRATEUR
    // ============================================
    if (in_array('administrateur', $tables)) {
        $stmt = $pdo->query("SELECT * FROM administrateur LIMIT 1");
        $administrateur = $stmt->fetch();
    }
    
} catch(PDOException $e) {
    error_log("Error dashboard admin: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $theme; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GreenMarket – Dashboard Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  /* ===== VARIABLES THEME ===== */
  :root {
    --cream: #fff9eb;
    --wine: #5d0d18;
    --wine-dark: #3e0910;
    --text-muted: #6b5055;
    --border: #e8ddd0;
    --white: #fffdf7;
    --success: #27ae60;
    --danger: #c0392b;
    --warning: #f39c12;
    --info: #2980b9;
    --bg: #FFF9EB;
    --bg-light: #f5f0e8;
    --bg-card: #ffffff;
    --text-dark: #2C2C2C;
    --shadow-color: rgba(93,13,24,0.1);
    --gold: #c07a1a;
  }
  
  [data-theme="dark"] {
    --cream: #2c241e;
    --wine: #8a6048;
    --wine-dark: #6d4c3a;
    --text-muted: #b8a896;
    --border: #5a4a3a;
    --white: #3d3229;
    --success: #2e7d32;
    --danger: #c0392b;
    --warning: #f39c12;
    --info: #2980b9;
    --bg: #2c241e;
    --bg-light: #3d3229;
    --bg-card: #3d3229;
    --text-dark: #f0e6d8;
    --shadow-color: rgba(0,0,0,0.4);
    --gold: #d4a85c;
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { 
    font-family: 'Lato', sans-serif; 
    background: var(--bg); 
    color: var(--text-dark);
    transition: background 0.3s, color 0.3s;
    min-height: 100vh; 
  }

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
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
  }
  .stat-card:hover { transform: translateY(-5px); box-shadow: 0 4px 20px var(--shadow-color); }
  .stat-icon { font-size: 2rem; }
  .stat-val {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    font-weight: 700;
    color: var(--wine);
    margin: 0.5rem 0;
  }
  .stat-label {
    font-size: 0.8rem;
    color: var(--text-muted);
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
    border-bottom: 2px solid var(--border);
    flex-wrap: wrap;
  }
  .tab-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 0.9rem;
    color: var(--text-muted);
    transition: all 0.2s;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
  }
  .tab-btn:hover { color: var(--wine); }
  .tab-btn.active {
    color: var(--wine);
    border-bottom: 2px solid var(--wine);
    font-weight: 600;
  }
  [data-theme="dark"] .tab-btn.active { color: var(--gold); border-bottom-color: var(--gold); }
  .tab-btn .badge-tab {
    background: var(--danger);
    color: white;
    border-radius: 50%;
    padding: 0.1rem 0.5rem;
    font-size: 0.7rem;
    margin-left: 0.3rem;
  }

  .main-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 2.5rem;
  }
  .tab-panel { display: none; }
  .tab-panel.active { display: block; }

  .section-card {
    background: var(--bg-card);
    border: 1px solid var(--border);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 2rem;
  }
  .section-header {
    background: var(--wine);
    color: var(--cream);
    padding: 1rem 1.5rem;
    font-family: 'Playfair Display', serif;
    font-size: 1.2rem;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .badge-count {
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
    padding: 2px 10px;
    font-size: 0.8rem;
  }

  .table-wrapper { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; }
  th, td { 
    padding: 0.8rem 1rem; 
    text-align: left; 
    border-bottom: 1px solid var(--border);
    color: var(--text-dark);
    font-size: 0.9rem;
  }
  th { 
    background: var(--bg-light); 
    font-weight: 600;
    color: var(--text-dark);
  }
  [data-theme="dark"] th { background: var(--bg); }
  
  .empty-state { text-align: center; padding: 3rem; color: var(--text-muted); }
  .empty-state .empty-icon { font-size: 3rem; display: block; margin-bottom: 1rem; }

  .badge {
    padding: 0.25rem 0.65rem;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 600;
    display: inline-block;
  }
  .badge-success { background: #d4edda; color: var(--success); }
  .badge-info { background: #d1ecf1; color: var(--info); }
  .badge-warning { background: #fff3cd; color: var(--warning); }
  .badge-danger { background: #f8d7da; color: var(--danger); }
  
  [data-theme="dark"] .badge-success { background: #1e4620; color: #8fdf9f; }
  [data-theme="dark"] .badge-warning { background: #4a3a1a; color: #f0d080; }
  [data-theme="dark"] .badge-info { background: #1a3a4a; color: #80d0f0; }
  [data-theme="dark"] .badge-danger { background: #4a1a1a; color: #f08080; }

  .btn {
    padding: 0.3rem 0.7rem;
    border-radius: 6px;
    font-size: 0.75rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
  }
  .btn:hover { transform: translateY(-2px); }
  .btn-wine { background: var(--wine); color: white; }
  .btn-wine:hover { background: var(--wine-dark); }
  .btn-success { background: var(--success); color: white; }
  .btn-success:hover { background: #219653; }
  .btn-danger { background: var(--danger); color: white; }
  .btn-danger:hover { background: #a93226; }
  .btn-info { background: var(--info); color: white; }
  .btn-info:hover { background: #2471a3; }

  .action-group { display: flex; gap: 0.5rem; flex-wrap: wrap; }

  /* ===== SETTINGS ===== */
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
    border: 1px solid var(--border);
  }
  .settings-group h4 {
    font-family: 'Playfair Display', serif;
    color: var(--wine);
    margin-bottom: 0.8rem;
    font-size: 1rem;
  }
  .settings-group p { color: var(--text-muted); }

  .theme-toggle-wrapper {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.5rem 0;
  }
  .theme-toggle-label {
    font-size: 0.85rem;
    color: var(--text-muted);
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
  .theme-switch input { opacity: 0; width: 0; height: 0; }
  .theme-slider {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: #ccc;
    transition: 0.4s;
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
    transition: 0.4s;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
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
  .theme-switch input:checked + .theme-slider { background: #2c241e; }
  .theme-switch input:checked + .theme-slider:before { transform: translateX(26px); background: #f0e6d8; }
  .theme-switch input:checked + .theme-slider .slider-icons .icon-sun { opacity: 0.3; }
  .theme-switch input:checked + .theme-slider .slider-icons .icon-moon { opacity: 1; }

  .boutique-img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid var(--border);
  }
  .boutique-img-placeholder {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg-light);
    border-radius: 8px;
    border: 1px solid var(--border);
    color: var(--text-muted);
    font-size: 1.5rem;
  }

  .toast {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: var(--wine);
    color: white;
    padding: 0.8rem 1.5rem;
    border-radius: 8px;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.3s;
    z-index: 9999;
  }
  .toast.show { transform: translateY(0); opacity: 1; }
  .toast.error { background: var(--danger); }
  .toast.success { background: var(--success); }

  @media (max-width: 768px) {
    .dashboard-grid, .tabs-container, .main-content { padding: 1rem; }
    .settings-grid { grid-template-columns: 1fr; }
    .tab-btn { padding: 0.5rem 0.8rem; font-size: 0.8rem; }
    .theme-switch { width: 50px; height: 28px; }
    .theme-slider:before { height: 20px; width: 20px; left: 4px; bottom: 4px; }
    .theme-switch input:checked + .theme-slider:before { transform: translateX(22px); }
    .theme-slider .slider-icons { font-size: 11px; padding: 0 5px; }
    th, td { padding: 0.4rem; font-size: 0.75rem; }
    .btn { font-size: 0.65rem; padding: 0.2rem 0.5rem; }
  }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<!-- ========================================== -->
<!-- STATISTIQUES                               -->
<!-- ========================================== -->
<div class="dashboard-grid">
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-val"><?php echo $total_users; ?></div>
        <div class="stat-label">Utilisateurs</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🌿</div>
        <div class="stat-val"><?php echo $active_producers; ?></div>
        <div class="stat-label">Producteurs Actifs</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">💰</div>
        <div class="stat-val"><?php echo number_format($ca_total, 0, ',', ' '); ?> DH</div>
        <div class="stat-label">CA Total</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🚨</div>
        <div class="stat-val"><?php echo $nb_attente + $nb_boutiques_attente; ?></div>
        <div class="stat-label">En Attente</div>
    </div>
</div>

<!-- ========================================== -->
<!-- TABS                                       -->
<!-- ========================================== -->
<div class="tabs-container">
    <div class="tabs">
        <button class="tab-btn active" data-tab="producteurs">👥 Producteurs</button>
        <button class="tab-btn" data-tab="boutiques">
            🏪 Boutiques
            <?php if ($nb_boutiques_attente > 0): ?>
                <span class="badge-tab"><?php echo $nb_boutiques_attente; ?></span>
            <?php endif; ?>
        </button>
        <button class="tab-btn" data-tab="produits">
            📦 Produits
            <?php if (count($produits_attente) > 0): ?>
                <span class="badge-tab"><?php echo count($produits_attente); ?></span>
            <?php endif; ?>
        </button>
        <button class="tab-btn" data-tab="commandes">🛒 Commandes</button>
        <button class="tab-btn" data-tab="clients">👤 Clients</button>
        <button class="tab-btn" data-tab="parametres">⚙️ Paramètres</button>
    </div>
</div>

<!-- ========================================== -->
<!-- MAIN CONTENT                               -->
<!-- ========================================== -->
<div class="main-content">

    <!-- ========================================================== -->
    <!-- ONGLET PRODUCTEURS                                         -->
    <!-- ========================================================== -->
    <div class="tab-panel active" id="tab-producteurs">
        
        <div class="section-card">
            <div class="section-header">
                👥 Producteurs en attente de validation
                <span class="badge-count"><?php echo $nb_attente; ?> en attente</span>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr><th>Entreprise</th><th>Email</th><th>Date d'inscription</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($producteurs_attente)): ?>
                            <tr><td colspan="5" class="empty-state">🎉 Aucun producteur en attente.</td></tr>
                        <?php else: ?>
                            <?php foreach ($producteurs_attente as $p): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($p['nom_entreprise']); ?></strong></td>
                                <td><?php echo htmlspecialchars($p['email']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($p['date_inscription'])); ?></td>
                                <td><span class="badge badge-warning">⏳ En attente</span></td>
                                <td>
                                    <div class="action-group">
                                        <button class="btn btn-success" onclick="validerProducteur(<?php echo $p['id_producteur']; ?>)">✅ Valider</button>
                                        <button class="btn btn-danger" onclick="refuserProducteur(<?php echo $p['id_producteur']; ?>)">❌ Refuser</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">
                📋 Tous les producteurs
                <span class="badge-count"><?php echo count($producteurs); ?> producteur(s)</span>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr><th>Entreprise</th><th>Email</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($producteurs)): ?>
                            <tr><td colspan="4" class="empty-state">Aucun producteur trouvé.</td></tr>
                        <?php else: ?>
                            <?php foreach ($producteurs as $p): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($p['nom']); ?></strong></td>
                                <td><?php echo htmlspecialchars($p['email']); ?></td>
                                <td>
                                    <?php if ($p['actif'] == 1): ?>
                                        <span class="badge badge-success">✅ Validé</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">⏳ En attente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <?php if ($p['actif'] != 1): ?>
                                            <button class="btn btn-success" onclick="validerProducteur(<?php echo $p['id']; ?>)">✅ Valider</button>
                                        <?php else: ?>
                                            <button class="btn btn-danger" onclick="suspendreProducteur(<?php echo $p['id']; ?>)">⛔ Suspendre</button>
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

    <!-- ========================================================== -->
    <!-- ONGLET BOUTIQUES                                           -->
    <!-- ========================================================== -->
    <div class="tab-panel" id="tab-boutiques">
        
        <div class="section-card">
            <div class="section-header">
                🏪 Boutiques en attente de validation
                <span class="badge-count"><?php echo $nb_boutiques_attente; ?> en attente</span>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr><th>Image</th><th>Nom de la boutique</th><th>Producteur</th><th>Email</th><th>Date création</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($boutiques_attente)): ?>
                            <tr><td colspan="6" class="empty-state">🎉 Aucune boutique en attente de validation.</td></tr>
                        <?php else: ?>
                            <?php foreach ($boutiques_attente as $b): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($b['image']) && file_exists($b['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($b['image']); ?>" alt="<?php echo htmlspecialchars($b['nom_boutique']); ?>" class="boutique-img">
                                    <?php else: ?>
                                        <div class="boutique-img-placeholder">
                                            <i class="bi bi-shop"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($b['nom_boutique']); ?></strong></td>
                                <td><?php echo htmlspecialchars($b['producteur_nom']); ?></td>
                                <td><?php echo htmlspecialchars($b['producteur_email']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($b['date_creation'])); ?></td>
                                <td>
                                    <div class="action-group">
                                        <button class="btn btn-success" onclick="validerBoutique(<?php echo $b['id_boutique']; ?>)">
                                            <i class="bi bi-check-circle"></i> Valider
                                        </button>
                                        <button class="btn btn-danger" onclick="refuserBoutique(<?php echo $b['id_boutique']; ?>)">
                                            <i class="bi bi-x-circle"></i> Refuser
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">
                📋 Toutes les boutiques
                <span class="badge-count"><?php echo count($boutiques); ?> boutique(s)</span>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr><th>Nom</th><th>Producteur</th><th>Email</th><th>Description</th><th>Date création</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($boutiques)): ?>
                            <tr><td colspan="6" class="empty-state">Aucune boutique.</td></tr>
                        <?php else: ?>
                            <?php foreach ($boutiques as $b): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($b['nom_boutique']); ?></strong></td>
                                <td><?php echo htmlspecialchars($b['producteur_nom']); ?></td>
                                <td><?php echo htmlspecialchars($b['producteur_email']); ?></td>
                                <td><?php echo htmlspecialchars($b['description'] ?? 'Boutique artisanale'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($b['date_creation'])); ?></td>
                                <td>
                                    <?php if (isset($b['est_valide_par_admin']) && $b['est_valide_par_admin'] == 1): ?>
                                        <span class="badge badge-success">✅ Validée</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">⏳ En attente</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- ONGLET PRODUITS - TOUS LES PRODUITS                        -->
    <!-- ========================================================== -->
    <div class="tab-panel" id="tab-produits">
        
        <div class="section-card">
            <div class="section-header">
                📦 Tous les produits
                <span class="badge-count"><?php echo count($tous_produits); ?> produit(s)</span>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Produit</th>
                            <th>Boutique</th>
                            <th>Producteur</th>
                            <th>Catégorie</th>
                            <th>Prix</th>
                            <th>Stock</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tous_produits)): ?>
                            <tr>
                                <td colspan="9" class="empty-state">
                                    <span class="empty-icon">📭</span>
                                    Aucun produit trouvé dans la base de données.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tous_produits as $p): ?>
                            <tr>
                                <td><span class="badge badge-info">#<?php echo $p['id_produit']; ?></span></td>
                                <td><strong><?php echo htmlspecialchars($p['nom_produit']); ?></strong></td>
                                <td><?php echo htmlspecialchars($p['nom_boutique'] ?? 'Sans boutique'); ?></td>
                                <td><?php echo htmlspecialchars($p['nom_producteur'] ?? 'Inconnu'); ?></td>
                                <td><?php echo htmlspecialchars($p['nom_categorie'] ?? 'Non catégorisé'); ?></td>
                                <td><?php echo number_format($p['prix_unitaire'], 2); ?> DH</td>
                                <td>
                                    <?php if ($p['stock_quantite'] <= 0): ?>
                                        <span class="badge badge-danger">❌ Rupture</span>
                                    <?php elseif ($p['stock_quantite'] <= 5): ?>
                                        <span class="badge badge-warning">⚠️ <?php echo $p['stock_quantite']; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-success">✅ <?php echo $p['stock_quantite']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($p['est_valide_par_admin'] == 1): ?>
                                        <span class="badge badge-success">✅ Validé</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">⏳ En attente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <?php if ($p['est_valide_par_admin'] != 1): ?>
                                            <button class="btn btn-success" onclick="validerProduit(<?php echo $p['id_produit']; ?>)">✅ Valider</button>
                                            <button class="btn btn-danger" onclick="refuserProduit(<?php echo $p['id_produit']; ?>)">❌ Refuser</button>
                                        <?php else: ?>
                                            <button class="btn btn-info" onclick="voirProduit(<?php echo $p['id_produit']; ?>)">👁️ Voir</button>
                                        <?php endif; ?>
                                        <!-- ===== BOUTON SUPPRIMER AJOUTÉ ===== -->
                                        <a href="supprimer_produit.php?id=<?php echo $p['id_produit']; ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirmerSuppression('<?php echo addslashes($p['nom_produit']); ?>')">
                                           🗑️ Supprimer
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="section-card">
            <div class="section-header">
                ⏳ Produits en attente de validation
                <span class="badge-count"><?php echo count($produits_attente); ?> en attente</span>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Produit</th>
                            <th>Boutique</th>
                            <th>Producteur</th>
                            <th>Prix</th>
                            <th>Date création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($produits_attente)): ?>
                            <tr><td colspan="6" class="empty-state">🎉 Aucun produit en attente de validation.</td></tr>
                        <?php else: ?>
                            <?php foreach ($produits_attente as $p): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($p['nom_produit']); ?></strong></td>
                                <td><?php echo htmlspecialchars($p['nom_boutique']); ?></td>
                                <td><?php echo htmlspecialchars($p['nom_producteur']); ?></td>
                                <td><?php echo number_format($p['prix_unitaire'], 2); ?> DH</td>
                                <td><?php echo date('d/m/Y H:i', strtotime($p['date_creation'])); ?></td>
                                <td>
                                    <div class="action-group">
                                        <button class="btn btn-success" onclick="validerProduit(<?php echo $p['id_produit']; ?>)">✅ Valider</button>
                                        <button class="btn btn-danger" onclick="refuserProduit(<?php echo $p['id_produit']; ?>)">❌ Refuser</button>
                                        <!-- ===== BOUTON SUPPRIMER AJOUTÉ ===== -->
                                        <a href="supprimer_produit.php?id=<?php echo $p['id_produit']; ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirmerSuppression('<?php echo addslashes($p['nom_produit']); ?>')">
                                           🗑️ Supprimer
                                        </a>
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

    <!-- ========================================================== -->
    <!-- ONGLET COMMANDES                                           -->
    <!-- ========================================================== -->
    <div class="tab-panel" id="tab-commandes">
        <div class="section-card">
            <div class="section-header">
                🛒 Dernières commandes
                <span class="badge-count"><?php echo count($commandes); ?> commande(s)</span>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr><th>N° Commande</th><th>Client</th><th>Total</th><th>Date</th><th>Statut</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($commandes)): ?>
                            <tr><td colspan="5" class="empty-state">Aucune commande.</td></tr>
                        <?php else: ?>
                            <?php foreach ($commandes as $cmd): ?>
                            <tr>
                                <td><strong>#<?php echo $cmd['id_commande']; ?></strong></td>
                                <td><?php echo htmlspecialchars($cmd['nom_client']); ?></td>
                                <td><?php echo number_format($cmd['montant_total'], 2); ?> DH</td>
                                <td><?php echo date('d/m/Y', strtotime($cmd['date_commande'])); ?></td>
                                <td>
                                    <?php 
                                    $badgeClass = 'info';
                                    if ($cmd['statut_commande'] === 'Livrée') $badgeClass = 'success';
                                    elseif ($cmd['statut_commande'] === 'Annulée') $badgeClass = 'danger';
                                    elseif ($cmd['statut_commande'] === 'En attente') $badgeClass = 'warning';
                                    ?>
                                    <span class="badge badge-<?php echo $badgeClass; ?>"><?php echo $cmd['statut_commande']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- ONGLET CLIENTS                                             -->
    <!-- ========================================================== -->
    <div class="tab-panel" id="tab-clients">
        <div class="section-card">
            <div class="section-header">
                👤 Tous les clients
                <span class="badge-count"><?php echo count($clients); ?> client(s)</span>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr><th>Nom</th><th>Email</th><th>Téléphone</th><th>Date d'inscription</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clients)): ?>
                            <tr><td colspan="5" class="empty-state">Aucun client.</td></tr>
                        <?php else: ?>
                            <?php foreach ($clients as $c): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($c['nom']); ?></strong></td>
                                <td><?php echo htmlspecialchars($c['email']); ?></td>
                                <td><?php echo htmlspecialchars($c['telephone'] ?? 'Non renseigné'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($c['date_inscription'] ?? 'now')); ?></td>
                                <td><span class="badge badge-success">✅ Actif</span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ========================================================== -->
    <!-- ONGLET PARAMÈTRES                                          -->
    <!-- ========================================================== -->
    <div class="tab-panel" id="tab-parametres">
        <div class="section-card">
            <div class="section-header">⚙️ Paramètres</div>
            <div class="settings-grid">
                
                <div class="settings-group">
                    <h4>🎨 Thème Clair / Sombre</h4>
                    <div class="theme-toggle-wrapper">
                        <span class="theme-toggle-label">☀️ Clair</span>
                        <label class="theme-switch">
                            <input type="checkbox" id="themeToggle" <?php echo $theme === 'dark' ? 'checked' : ''; ?> onchange="toggleTheme()">
                            <span class="theme-slider">
                                <span class="slider-icons">
                                    <span class="icon-sun">☀️</span>
                                    <span class="icon-moon">🌙</span>
                                </span>
                            </span>
                        </label>
                        <span class="theme-toggle-label">🌙 Sombre</span>
                    </div>
                    <p style="font-size:0.75rem;color:var(--text-muted);margin-top:0.5rem;">
                        <?php echo $theme === 'dark' ? '🌙 Mode sombre activé' : '☀️ Mode clair activé'; ?>
                    </p>
                </div>
                
                <div class="settings-group">
                    <h4>👤 Informations administrateur</h4>
                    <p style="font-size:0.9rem;color:var(--text-dark);">
                        <strong>Nom :</strong> <?php echo htmlspecialchars($administrateur['nom_admin'] ?? $administrateur['nom'] ?? 'Administrateur'); ?><br>
                        <strong>Email :</strong> <?php echo htmlspecialchars($administrateur['email'] ?? 'admin@greenmarket.com'); ?><br>
                        <strong>Rôle :</strong> <span class="badge badge-success">✅ Administrateur</span><br>
                        <strong>Statistiques :</strong><br>
                        &nbsp;&nbsp;• <?php echo $total_users; ?> utilisateurs<br>
                        &nbsp;&nbsp;• <?php echo $total_boutiques; ?> boutiques<br>
                        &nbsp;&nbsp;• <?php echo $total_produits; ?> produits<br>
                        &nbsp;&nbsp;• <?php echo $total_commandes; ?> commandes
                    </p>
                </div>
                
                <div class="settings-group">
                    <h4>📊 Statistiques générales</h4>
                    <p style="font-size:0.9rem;color:var(--text-dark);">
                        <strong>Chiffre d'affaires :</strong> <?php echo number_format($ca_total, 0, ',', ' '); ?> DH<br>
                        <strong>Producteurs actifs :</strong> <?php echo $active_producers; ?><br>
                        <strong>En attente :</strong> <?php echo $nb_attente + $nb_boutiques_attente; ?><br>
                        <strong>Produits en attente :</strong> <?php echo count($produits_attente); ?>
                    </p>
                </div>
                
            </div>
        </div>
    </div>

</div>

<!-- ========================================== -->
<!-- TOAST                                     -->
<!-- ========================================== -->
<div class="toast" id="toast"></div>

<script>
// ============================================
// TABS
// ============================================
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tabName = this.getAttribute('data-tab');
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('tab-' + tabName).classList.add('active');
    });
});

// ============================================
// TOAST
// ============================================
function showToast(msg, isError = false) {
    const toast = document.getElementById('toast');
    toast.innerHTML = msg;
    toast.className = 'toast' + (isError ? ' error' : '');
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

// ============================================
// CONFIRMATION SUPPRESSION
// ============================================
function confirmerSuppression(nomProduit) {
    return confirm('⚠️ Voulez-vous vraiment supprimer le produit "' + nomProduit + '" ?\n\nCette action est irréversible !');
}

// ============================================
// GESTION PRODUCTEURS
// ============================================
function validerProducteur(id) {
    if(confirm('Valider ce compte producteur ?')) {
        window.location.href = 'valider_producteur.php?id=' + id + '&action=valider';
    }
}

function refuserProducteur(id) {
    if(confirm('Refuser ce compte producteur ?')) {
        window.location.href = 'valider_producteur.php?id=' + id + '&action=refuser';
    }
}

function suspendreProducteur(id) {
    if(confirm('Suspendre ce compte producteur ?')) {
        window.location.href = 'suspendre_producteur.php?id=' + id;
    }
}

// ============================================
// GESTION BOUTIQUES
// ============================================
function validerBoutique(id) {
    if(confirm('Valider cette boutique ?')) {
        window.location.href = 'valider_boutique.php?id=' + id + '&action=valider';
    }
}

function refuserBoutique(id) {
    if(confirm('Refuser cette boutique ?')) {
        window.location.href = 'valider_boutique.php?id=' + id + '&action=refuser';
    }
}

// ============================================
// GESTION PRODUITS
// ============================================
function validerProduit(id) {
    if(confirm('Valider ce produit ?')) {
        window.location.href = 'valider_produit.php?id=' + id + '&action=valider';
    }
}

function refuserProduit(id) {
    if(confirm('Refuser ce produit ?')) {
        window.location.href = 'valider_produit.php?id=' + id + '&action=refuser';
    }
}

function voirProduit(id) {
    window.location.href = 'info-produit.php?id=' + id;
}

// ============================================
// THEME
// ============================================
function toggleTheme() {
    const checkbox = document.getElementById('themeToggle');
    const theme = checkbox.checked ? 'dark' : 'light';
    
    document.cookie = 'theme=' + theme + '; path=/; max-age=31536000';
    document.documentElement.setAttribute('data-theme', theme);
    
    const statusText = document.querySelector('.settings-group p');
    if (statusText) {
        statusText.textContent = theme === 'dark' ? '🌙 Mode sombre activé' : '☀️ Mode clair activé';
    }
    
    showToast('✅ Thème changé en ' + (theme === 'light' ? 'clair' : 'sombre'));
    setTimeout(() => location.reload(), 600);
}

// ============================================
// INIT
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const theme = '<?php echo $theme; ?>';
    document.documentElement.setAttribute('data-theme', theme);
    
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    if (tab) {
        document.querySelectorAll('.tab-btn').forEach(b => {
            if (b.getAttribute('data-tab') === tab) {
                b.click();
            }
        });
    }
});
</script>
</body>
</html>