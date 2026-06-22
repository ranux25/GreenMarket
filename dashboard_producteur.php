<?php
session_start();

#verifier que l'utilisateur est connecte et est producteur
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'producteur') {
    header('Location: signin.php');
    exit;
}

#connexion a la base de donnees
include('connexion.php');

# ===== THEME (COOKIES) =====
$theme = $_COOKIE['theme'] ?? 'light';

# 🔥 DEBUG - Verificar sesión
$debug_info = [];
$debug_info['session_user_id'] = $_SESSION['user_id'] ?? 'NO SET';
$debug_info['session_user_email'] = $_SESSION['user_email'] ?? 'NO SET';

#verifier si le producteur est valide
$est_valide = $_SESSION['est_valide'] ?? 0;

#si non valide, verifier en BD
if ($est_valide != 1) {
    try {
        $stmt = $pdo->prepare("SELECT est_valide_par_admin FROM producteur WHERE id_producteur = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        if ($result && $result['est_valide_par_admin'] == 1) {
            $est_valide = 1;
            $_SESSION['est_valide'] = 1;
        }
    } catch(PDOException $e) {
        // Ignorer
    }
}

#recuperer les donnees du producteur depuis la BD
try {
    #recuperer les informations du producteur
    $stmt = $pdo->prepare("SELECT * FROM producteur WHERE id_producteur = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $producteur = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producteur) {
        session_destroy();
        header('Location: signin.php');
        exit;
    }
    
    $debug_info['producteur_trouve'] = $producteur['nom_entreprise'] ?? 'NO';
    $debug_info['est_valide_db'] = $producteur['est_valide_par_admin'] ?? 0;
    
    #si l'etat de validation a change, mettre a jour la session
    if ($producteur['est_valide_par_admin'] != $est_valide) {
        $est_valide = $producteur['est_valide_par_admin'];
        $_SESSION['est_valide'] = $est_valide;
    }
    
    #afficher les donnees seulement si valide
    if ($est_valide == 1) {
        #recuperer les boutiques du producteur
        $stmt = $pdo->prepare("SELECT * FROM boutique WHERE id_producteur = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $boutiques = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $nb_boutiques = count($boutiques);
        $debug_info['nb_boutiques'] = $nb_boutiques;
        
        #recuperer les produits des boutiques
        if (!empty($boutiques)) {
            $boutiqueIds = array_column($boutiques, 'id_boutique');
            $placeholders = implode(',', array_fill(0, count($boutiqueIds), '?'));
            $stmt = $pdo->prepare("SELECT p.*, c.nom_categorie FROM produit p 
                                   LEFT JOIN categorie c ON p.id_categorie = c.id_categorie
                                   WHERE p.id_boutique IN ($placeholders) AND p.est_valide_par_admin = 1");
            $stmt->execute($boutiqueIds);
            $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $nb_produits = count($produits);
            $debug_info['nb_produits'] = $nb_produits;
        } else {
            $produits = [];
            $nb_produits = 0;
            $debug_info['nb_produits'] = 0;
        }
        
        #recuperer les commandes des produits (groupées par commande)
        $commandes = [];
        if (!empty($produits)) {
            $produitIds = array_column($produits, 'id_produit');
            $placeholders = implode(',', array_fill(0, count($produitIds), '?'));
            
            $stmt = $pdo->prepare("
                SELECT DISTINCT c.id_commande, c.date_commande, c.statut_commande, c.montant_total,
                       cl.nom_client, cl.email,
                       (SELECT COUNT(*) FROM contenir WHERE id_commande = c.id_commande) as nb_produits
                FROM commande c
                JOIN client cl ON c.id_client = cl.id_client
                JOIN contenir co ON c.id_commande = co.id_commande
                JOIN produit p ON co.id_produit = p.id_produit
                WHERE p.id_produit IN ($placeholders)
                GROUP BY c.id_commande
                ORDER BY c.date_commande DESC
            ");
            $stmt->execute($produitIds);
            $commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $nb_commandes = count($commandes);
            
            #calculer le CA total
            $ca_total = array_sum(array_column($commandes, 'montant_total'));
            $debug_info['nb_commandes'] = $nb_commandes;
            $debug_info['ca_total'] = $ca_total;
        } else {
            $commandes = [];
            $nb_commandes = 0;
            $ca_total = 0;
            $debug_info['nb_commandes'] = 0;
            $debug_info['ca_total'] = 0;
        }
        
    } else {
        #si non valide, tableaux vides
        $boutiques = [];
        $produits = [];
        $commandes = [];
        $ca_total = 0;
        $nb_commandes = 0;
        $nb_boutiques = 0;
        $nb_produits = 0;
        $debug_info['est_valide'] = 0;
    }
    
} catch(PDOException $e) {
    error_log("Error dashboard producteur: " . $e->getMessage());
    $boutiques = [];
    $produits = [];
    $commandes = [];
    $ca_total = 0;
    $nb_commandes = 0;
    $nb_boutiques = 0;
    $nb_produits = 0;
    $debug_info['error'] = $e->getMessage();
}

// Récupérer les notifications non lues pour le compteur
$unreadCount = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM notification WHERE id_producteur = ? AND est_lu = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $unreadCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
} catch(PDOException $e) {
    $unreadCount = 0;
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $theme; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GreenMarket – Mon Espace Producteur</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  /* ===== VARIABLES THEME ===== */
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
    --success: #27ae60;
    --danger: #c0392b;
    --warning: #f39c12;
    --info: #2980b9;
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
    --success: #2e7d32;
    --danger: #c0392b;
    --warning: #f39c12;
    --info: #2980b9;
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
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  [data-theme="dark"] .section-header { background: var(--primary-light); }
  
  .badge-count {
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
    padding: 2px 10px;
    font-size: 0.8rem;
  }

  .alert-warning {
    background: #fff3cd;
    border: 1px solid #ffc107;
    color: #856404;
    padding: 1rem;
    border-radius: 8px;
    margin: 2rem;
    text-align: center;
  }
  [data-theme="dark"] .alert-warning {
    background: #4a3a1a;
    border-color: #f0d080;
    color: #f0d080;
  }

  .alert-success {
    background: #d4edda;
    color: #155724;
    padding: 1.5rem;
    border-radius: 12px;
    margin: 1.5rem;
  }
  [data-theme="dark"] .alert-success {
    background: #1e4620;
    color: #8fdf9f;
  }

  .table-wrapper { overflow-x: auto; }
  table {
    width: 100%;
    border-collapse: collapse;
  }
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
  .empty-state .empty-icon {
    font-size: 3rem;
    display: block;
    margin-bottom: 0.5rem;
    opacity: 0.5;
  }
  .empty-state .btn-primary {
    display: inline-block;
    margin-top: 1rem;
    padding: 0.6rem 1.5rem;
    background: var(--primary);
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
  }
  .empty-state .btn-primary:hover { background: var(--primary-light); }

  .badge {
    padding: 0.25rem 0.65rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
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
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-size: 0.8rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
  }
  .btn:hover { transform: translateY(-2px); }
  .btn-wine { background: var(--primary); color: white; }
  .btn-wine:hover { background: var(--primary-light); }
  .btn-success { background: var(--success); color: white; }
  .btn-success:hover { background: #219653; }
  .btn-danger { background: var(--danger); color: white; }
  .btn-danger:hover { background: #a93226; }
  .btn-info { background: var(--info); color: white; }
  .btn-info:hover { background: #2471a3; }
  .btn-outline-wine { 
    background: transparent; 
    color: var(--primary); 
    border: 1.5px solid var(--primary);
  }
  .btn-outline-wine:hover { background: var(--primary); color: white; }

  .action-group { display: flex; gap: 0.5rem; flex-wrap: wrap; }

  /* ===== SETTINGS PANEL ===== */
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
  .settings-group p {
    color: var(--text-light);
  }

  /* ===== TOGGLE THEME AVEC ANIMATION ===== */
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

  .theme-switch input:checked + .theme-slider .slider-icons .icon-sun {
    opacity: 0.3;
  }
  .theme-switch input:checked + .theme-slider .slider-icons .icon-moon {
    opacity: 1;
  }

  .theme-switch input:active + .theme-slider:before {
    width: 32px;
  }

  .theme-switch:hover .theme-slider {
    box-shadow: 0 0 0 4px rgba(93,13,24,0.15);
  }
  [data-theme="dark"] .theme-switch:hover .theme-slider {
    box-shadow: 0 0 0 4px rgba(212,168,92,0.2);
  }

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
  .toast.show {
    transform: translateY(0);
    opacity: 1;
  }
  .toast.error { background: var(--danger); }
  .toast.success { background: var(--success); }

  @media (max-width: 768px) {
    .dashboard-grid, .tabs-container, .main-content {
      padding: 1rem;
    }
    .settings-grid {
      grid-template-columns: 1fr;
    }
    .tab-btn {
      padding: 0.5rem 0.8rem;
      font-size: 0.8rem;
    }
    .section-header {
      font-size: 1rem;
      padding: 0.8rem 1rem;
    }
    .theme-switch {
      width: 50px;
      height: 28px;
    }
    .theme-slider:before {
      height: 20px;
      width: 20px;
      left: 4px;
      bottom: 4px;
    }
    .theme-switch input:checked + .theme-slider:before {
      transform: translateX(22px);
    }
    .theme-slider .slider-icons {
      font-size: 11px;
      padding: 0 5px;
    }
    th, td {
      padding: 0.5rem;
      font-size: 0.8rem;
    }
    .btn {
      font-size: 0.7rem;
      padding: 0.3rem 0.6rem;
    }
  }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<!-- Mensaje de éxito al crear boutique -->
<?php if (isset($_GET['boutique_creation']) && $_GET['boutique_creation'] == 'success'): ?>
    <div class="alert-success" style="margin:1.5rem;border:1px solid #c3e6cb;">
        <h3 style="font-family:'Playfair Display',serif;font-size:1.2rem;margin-bottom:0.5rem;">
            <i class="bi bi-check-circle"></i> Boutique créée avec succès !
        </h3>
        <p>Votre boutique est maintenant en <strong>attente de validation</strong> par un administrateur.</p>
        <p style="margin-top:0.3rem;font-size:0.9rem;">
            Vous serez notifié une fois qu'elle sera approuvée. 
        </p>
    </div>
<?php endif; ?>

<!-- Mensaje de espera si no está validado -->
<?php if ($est_valide != 1): ?>
<div class="alert-warning">
  <i class="bi bi-hourglass-split" style="font-size: 1.2rem;"></i>
  <strong>⚠️ Compte en attente de validation</strong>
  <p style="margin: 0;">Votre compte producteur est en attente de validation par un administrateur. Vous recevrez une notification une fois votre compte activé.</p>
</div>
<?php else: ?>

<!-- Dashboard Content -->
<div class="dashboard-grid">
  <div class="stat-card">
    <div class="stat-icon">💰</div>
    <div class="stat-val"><?php echo number_format($ca_total, 0, ',', ' '); ?> DH</div>
    <div class="stat-label">Chiffre d'Affaires</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">📦</div>
    <div class="stat-val"><?php echo $nb_commandes; ?></div>
    <div class="stat-label">Commandes Reçues</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🏪</div>
    <div class="stat-val"><?php echo $nb_boutiques; ?></div>
    <div class="stat-label">Mes Boutiques</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">📦</div>
    <div class="stat-val"><?php echo $nb_produits; ?></div>
    <div class="stat-label">Produits</div>
  </div>
</div>

<div class="tabs-container">
  <div class="tabs">
    <button class="tab-btn active" data-tab="apercu">📊 Vue d'ensemble</button>
    <button class="tab-btn" data-tab="boutiques">🏪 Mes Boutiques</button>
    <button class="tab-btn" data-tab="produits">📦 Mes Produits</button>
    <button class="tab-btn" data-tab="commandes">🛒 Commandes</button>
    <button class="tab-btn" data-tab="parametres">⚙️ Paramètres</button>
  </div>
</div>

<div class="main-content">
  
  <!-- ============================== -->
  <!-- ONGLET APERÇU                  -->
  <!-- ============================== -->
  <div class="tab-panel active" id="tab-apercu">
    <div class="section-card">
      <div class="section-header">📈 Mes Statistiques</div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr><th>Indicateur</th><th>Valeur</th></tr>
          </thead>
          <tbody>
            <tr><td>Statut de votre compte</td>
              <td><strong>
                <?php if ($est_valide == 1): ?>
                  <span class="badge badge-success">✅ Validé</span>
                <?php else: ?>
                  <span class="badge badge-warning">⏳ En attente</span>
                <?php endif; ?>
              </strong></td>
            </tr>
            <tr><td>Nombre de boutiques</td><td><strong><?php echo $nb_boutiques; ?></strong></td></tr>
            <tr><td>Nombre de produits</td><td><strong><?php echo $nb_produits; ?></strong></td></tr>
            <tr><td>Commandes reçues</td><td><strong><?php echo $nb_commandes; ?></strong></td></tr>
            <?php if ($nb_commandes > 0): ?>
            <tr><td>Chiffre d'affaires total</td><td><strong><?php echo number_format($ca_total, 2); ?> DH</strong></td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ============================== -->
  <!-- ONGLET BOUTIQUES               -->
  <!-- ============================== -->
  <div class="tab-panel" id="tab-boutiques">
    <div class="section-card">
      <div class="section-header">
        🏪 Mes Boutiques
        <span class="badge-count"><?php echo $nb_boutiques; ?> boutique(s)</span>
      </div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Nom</th><th>Description</th><th>Status</th><th>Date création</th></tr></thead>
          <tbody>
            <?php if (empty($boutiques)): ?>
              <tr>
                <td colspan="4" class="empty-state">
                  <span class="empty-icon">🏪</span>
                  Vous n'avez pas encore créé de boutique.
                  <br>
                  <a href="creer-boutique.php" class="btn-primary">Créer ma boutique</a>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($boutiques as $boutique): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($boutique['nom_boutique']); ?></strong></td>
                <td><?php echo htmlspecialchars($boutique['description'] ?? 'Boutique artisanale'); ?></td>
                <td>
                  <?php if (isset($boutique['est_valide_par_admin']) && $boutique['est_valide_par_admin'] == 1): ?>
                    <span class="badge badge-success">✅ Validée</span>
                  <?php else: ?>
                    <span class="badge badge-warning">⏳ En attente</span>
                  <?php endif; ?>
                </td>
                <td><?php echo date('d/m/Y', strtotime($boutique['date_creation'])); ?></td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ============================== -->
  <!-- ONGLET PRODUITS                -->
  <!-- ============================== -->
  <div class="tab-panel" id="tab-produits">
    <div class="section-card">
      <div class="section-header">
        📦 Mes Produits
        <span class="badge-count"><?php echo $nb_produits; ?> produit(s)</span>
      </div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Nom</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>Status</th></tr></thead>
          <tbody>
            <?php if (empty($produits)): ?>
              <tr>
                <td colspan="5" class="empty-state">
                  <span class="empty-icon">📦</span>
                  Aucun produit dans votre catalogue.
                  <br>
                  <?php if ($nb_boutiques > 0): ?>
                    <a href="ajouter-produit.php?boutique=<?php echo $boutiques[0]['id_boutique']; ?>" class="btn-primary">Ajouter un produit</a>
                  <?php else: ?>
                    <a href="creer-boutique.php" class="btn-primary">Créer une boutique</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($produits as $produit): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($produit['nom_produit']); ?></strong></td>
                <td><?php echo htmlspecialchars($produit['nom_categorie'] ?? 'Non catégorisé'); ?></td>
                <td><?php echo number_format($produit['prix_unitaire'], 2); ?> DH</td>
                <td><span class="badge <?php echo $produit['stock_quantite'] <= 5 ? 'badge-danger' : 'badge-success'; ?>"><?php echo $produit['stock_quantite']; ?></span></td>
                <td>
                  <?php if ($produit['est_valide_par_admin'] == 1): ?>
                    <span class="badge badge-success">✅ Validé</span>
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

  <!-- ============================== -->
  <!-- ONGLET COMMANDES               -->
  <!-- ============================== -->
  <div class="tab-panel" id="tab-commandes">
    <div class="section-card">
      <div class="section-header">
        🛒 Commandes Reçues
        <span class="badge-count"><?php echo $nb_commandes; ?> commande(s)</span>
      </div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>N° Commande</th>
              <th>Client</th>
              <th>Total</th>
              <th>Date</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($commandes)): ?>
              <tr>
                <td colspan="6" class="empty-state">
                  <span class="empty-icon">🛒</span>
                  Aucune commande reçue pour le moment.
                  <br>
                  <span style="font-size:0.9rem;">Les commandes apparaîtront ici une fois que des clients auront acheté vos produits.</span>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($commandes as $commande): ?>
              <tr>
                <td><strong>#<?php echo str_pad($commande['id_commande'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                <td><?php echo htmlspecialchars($commande['nom_client']); ?></td>
                <td><strong><?php echo number_format($commande['montant_total'], 2); ?> DH</strong></td>
                <td><?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?></td>
                <td>
                  <span class="badge <?php 
                    echo $commande['statut_commande'] === 'Livrée' ? 'badge-success' : 
                        ($commande['statut_commande'] === 'Annulée' ? 'badge-danger' : 
                        ($commande['statut_commande'] === 'Confirmée' ? 'badge-info' : 'badge-warning')); 
                  ?>">
                    <?php echo $commande['statut_commande']; ?>
                  </span>
                </td>
                <td>
                  <div class="action-group">
                    <?php if ($commande['statut_commande'] === 'En attente'): ?>
                    <button class="btn btn-success" onclick="updateStatut(<?php echo $commande['id_commande']; ?>, 'Confirmée')">
                      ✅ Confirmer
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($commande['statut_commande'] === 'Confirmée'): ?>
                    <button class="btn btn-info" onclick="updateStatut(<?php echo $commande['id_commande']; ?>, 'Expédiée')">
                      📦 Expédier
                    </button>
                    <?php endif; ?>
                    
                    <?php if ($commande['statut_commande'] === 'Expédiée'): ?>
                    <button class="btn btn-success" onclick="updateStatut(<?php echo $commande['id_commande']; ?>, 'Livrée')">
                      ✅ Livrer
                    </button>
                    <?php endif; ?>
                    
                    <?php if (in_array($commande['statut_commande'], ['En attente', 'Confirmée', 'Expédiée'])): ?>
                    <button class="btn btn-danger" onclick="updateStatut(<?php echo $commande['id_commande']; ?>, 'Annulée')">
                      ❌ Annuler
                    </button>
                    <?php endif; ?>
                    
                    <button class="btn btn-wine" onclick="voirDetails(<?php echo $commande['id_commande']; ?>)">
                      <i class="bi bi-eye"></i> Détails
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
  </div>

  <!-- ============================== -->
  <!-- ONGLET PARAMÈTRES              -->
  <!-- ============================== -->
  <div class="tab-panel" id="tab-parametres">
    <div class="section-card">
      <div class="section-header">⚙️ Paramètres</div>
      <div class="settings-grid">
        
        <!-- ===== THEME TOGGLE AVEC ANIMATION ===== -->
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
          <p style="font-size:0.75rem;color:var(--text-light);margin-top:0.5rem;">
            <?php echo $theme === 'dark' ? '🌙 Mode sombre activé' : '☀️ Mode clair activé'; ?>
          </p>
        </div>
        
        <!-- ===== INFORMATIONS DU COMPTE ===== -->
        <div class="settings-group">
          <h4>👤 Informations du compte</h4>
          <p style="font-size:0.9rem;color:var(--text-dark);">
            <strong>Entreprise :</strong> <?php echo htmlspecialchars($producteur['nom_entreprise']); ?><br>
            <strong>Email :</strong> <?php echo htmlspecialchars($producteur['email']); ?><br>
            <strong>Statut :</strong> 
            <?php if ($producteur['est_valide_par_admin'] == 1): ?>
              <span class="badge badge-success">✅ Validé</span>
            <?php else: ?>
              <span class="badge badge-warning">⏳ En attente de validation</span>
            <?php endif; ?>
            <br>
            <strong>Date d'inscription :</strong> <?php echo date('d/m/Y', strtotime($producteur['date_inscription'])); ?>
          </p>
        </div>
        
      </div>
    </div>
  </div>

</div>

<?php endif; ?>

<div class="toast" id="toast"></div>

<script>
// ============================================
// ONGLETS
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
// UPDATE STATUT COMMANDE
// ============================================
function updateStatut(id, statut) {
  if (confirm('Voulez-vous vraiment changer le statut de cette commande en "' + statut + '" ?')) {
    fetch('update_commande.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'id_commande=' + id + '&statut=' + encodeURIComponent(statut)
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showToast('✅ ' + data.message);
        setTimeout(() => location.reload(), 1000);
      } else {
        showToast('❌ ' + data.message, true);
      }
    })
    .catch(() => showToast('❌ Erreur de connexion au serveur', true));
  }
}

// ============================================
// VOIR DÉTAILS COMMANDE
// ============================================
function voirDetails(id) {
  window.location.href = 'details_commande.php?id=' + id;
}

// ============================================
// TOGGLE THEME AVEC ANIMATION
// ============================================
function toggleTheme() {
  const checkbox = document.getElementById('themeToggle');
  const theme = checkbox.checked ? 'dark' : 'light';
  
  // Sauvegarder dans un cookie
  document.cookie = 'theme=' + theme + '; path=/; max-age=31536000';
  
  // Appliquer le thème immédiatement
  document.documentElement.setAttribute('data-theme', theme);
  
  // Mettre à jour le texte de statut
  const statusText = document.querySelector('.settings-group p');
  if (statusText) {
    statusText.textContent = theme === 'dark' ? '🌙 Mode sombre activé' : '☀️ Mode clair activé';
  }
  
  // Afficher le message
  const themeName = theme === 'light' ? 'clair' : 'sombre';
  showToast('✅ Thème changé en ' + themeName);
  
  // Recharger la page pour appliquer les changements dans le header également
  setTimeout(() => location.reload(), 600);
}

// ============================================
// INIT - Appliquer le thème au chargement
// ============================================
document.addEventListener('DOMContentLoaded', function() {
  const theme = '<?php echo $theme; ?>';
  document.documentElement.setAttribute('data-theme', theme);
});
</script>
</body>
</html>