<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: signin.php");
    exit;
}

$theme = $_COOKIE['theme'] ?? 'light';

include("connexion.php");

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
$tous_avis = [];

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

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
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM producteur WHERE statut = 'valide'");
        $active_producers = $stmt->fetch()['total'] ?? 0;
    }

    if (in_array('producteur', $tables)) {
        $stmt = $pdo->query("SELECT * FROM producteur WHERE statut = 'en_attente' ORDER BY date_inscription DESC");
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

    if (in_array('boutique', $tables) && in_array('producteur', $tables)) {
        $stmt = $pdo->query("
            SELECT b.*, p.nom_entreprise as producteur_nom, p.email as producteur_email 
            FROM boutique b 
            LEFT JOIN producteur p ON b.id_producteur = p.id_producteur 
            WHERE b.statut = 'en_attente'
            ORDER BY b.date_creation DESC
        ");
        $boutiques_attente = $stmt->fetchAll();
        $nb_boutiques_attente = count($boutiques_attente);
    }

    if (in_array('produit', $tables) && in_array('boutique', $tables) && in_array('producteur', $tables)) {
        $stmt = $pdo->query("
            SELECT p.*, b.nom_boutique,
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

    if (in_array('produit', $tables) && in_array('boutique', $tables) && in_array('producteur', $tables)) {
        $stmt = $pdo->query("
            SELECT p.*, b.nom_boutique,
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

    if (in_array('producteur', $tables)) {
        $stmt = $pdo->query("
            SELECT id_producteur as id, nom_entreprise as nom, email, statut, date_inscription 
            FROM producteur 
            ORDER BY date_inscription DESC
        ");
        $producteurs = $stmt->fetchAll();
    }

    if (in_array('client', $tables)) {
        $stmt = $pdo->query("
            SELECT id_client as id, nom_client as nom, email, telephone, date_inscription, est_actif
            FROM client 
            ORDER BY date_inscription DESC
        ");
        $clients = $stmt->fetchAll();
    }

    if (in_array('boutique', $tables) && in_array('producteur', $tables)) {
        $stmt = $pdo->query("
            SELECT b.*, p.nom_entreprise as producteur_nom, p.email as producteur_email
            FROM boutique b 
            LEFT JOIN producteur p ON b.id_producteur = p.id_producteur 
            ORDER BY b.date_creation DESC
        ");
        $boutiques = $stmt->fetchAll();
    }

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

    if (in_array('administrateur', $tables)) {
        $stmt = $pdo->query("SELECT * FROM administrateur LIMIT 1");
        $administrateur = $stmt->fetch();
    }

    if (in_array('evaluation_produit', $tables)) {
        try {
            $stmt = $pdo->query("SELECT * FROM evaluation_produit ORDER BY date_evaluation DESC");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $row['type_avis'] = 'Produit';
                $tous_avis[] = $row;
            }
        } catch(PDOException $e) {}
    }
    if (in_array('evaluation_boutique', $tables)) {
        try {
            $stmt = $pdo->query("SELECT * FROM evaluation_boutique ORDER BY date_evaluation DESC");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $row['type_avis'] = 'Boutique';
                $tous_avis[] = $row;
            }
        } catch(PDOException $e) {}
    }
    if (in_array('avis', $tables)) {
        try {
            $stmt = $pdo->query("SELECT * FROM avis");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $row['type_avis'] = 'Général';
                $tous_avis[] = $row;
            }
        } catch(PDOException $e) {}
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

  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Lato',sans-serif; background:var(--bg); color:var(--text-dark); transition:background .3s,color .3s; min-height:100vh; }

  .dashboard-grid {
    display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
    gap:1.5rem; padding:2rem 2.5rem; max-width:1400px; margin:0 auto;
  }
  .stat-card {
    background:var(--bg-card); border:1px solid var(--border); border-radius:12px;
    padding:1.5rem; text-align:center; transition:transform .2s,box-shadow .2s;
  }
  .stat-card:hover { transform:translateY(-5px); box-shadow:0 4px 20px var(--shadow-color); }
  .stat-icon { font-size:2rem; }
  .stat-val { font-family:'Playfair Display',serif; font-size:2rem; font-weight:700; color:var(--wine); margin:.5rem 0; }
  .stat-label { font-size:.8rem; color:var(--text-muted); text-transform:uppercase; }

  .tabs-container { max-width:1400px; margin:0 auto; padding:0 2.5rem; }
  .tabs { display:flex; gap:.5rem; border-bottom:2px solid var(--border); flex-wrap:wrap; }
  .tab-btn {
    padding:.75rem 1.5rem; border:none; background:none; cursor:pointer;
    font-size:.9rem; color:var(--text-muted); transition:all .2s;
    border-bottom:2px solid transparent; margin-bottom:-2px;
  }
  .tab-btn:hover { color:var(--wine); }
  .tab-btn.active { color:var(--wine); border-bottom:2px solid var(--wine); font-weight:600; }
  [data-theme="dark"] .tab-btn.active { color:var(--gold); border-bottom-color:var(--gold); }
  .tab-btn .badge-tab {
    background:var(--danger); color:white; border-radius:50%;
    padding:.1rem .5rem; font-size:.7rem; margin-left:.3rem;
  }

  .main-content { max-width:1400px; margin:0 auto; padding:2rem 2.5rem; }
  .tab-panel { display:none; }
  .tab-panel.active { display:block; }

  .section-card { background:var(--bg-card); border:1px solid var(--border); border-radius:12px; overflow:hidden; margin-bottom:2rem; }
  .section-header {
    background:var(--wine); color:var(--cream); padding:1rem 1.5rem;
    font-family:'Playfair Display',serif; font-size:1.2rem; font-weight:600;
    display:flex; justify-content:space-between; align-items:center;
  }
  .badge-count { background:rgba(255,255,255,0.2); border-radius:20px; padding:2px 10px; font-size:.8rem; }

  .table-wrapper { overflow-x:auto; }
  table { width:100%; border-collapse:collapse; }
  th,td { padding:.8rem 1rem; text-align:left; border-bottom:1px solid var(--border); color:var(--text-dark); font-size:.9rem; }
  th { background:var(--bg-light); font-weight:600; }
  [data-theme="dark"] th { background:var(--bg); }
  .empty-state { text-align:center; padding:3rem; color:var(--text-muted); }

  .badge { padding:.25rem .65rem; border-radius:4px; font-size:.7rem; font-weight:600; display:inline-block; }
  .badge-success { background:#d4edda; color:var(--success); }
  .badge-info    { background:#d1ecf1; color:var(--info); }
  .badge-warning { background:#fff3cd; color:var(--warning); }
  .badge-danger  { background:#f8d7da; color:var(--danger); }
  [data-theme="dark"] .badge-success { background:#1e4620; color:#8fdf9f; }
  [data-theme="dark"] .badge-warning { background:#4a3a1a; color:#f0d080; }
  [data-theme="dark"] .badge-info    { background:#1a3a4a; color:#80d0f0; }
  [data-theme="dark"] .badge-danger  { background:#4a1a1a; color:#f08080; }

  .btn {
    padding:.3rem .7rem; border-radius:6px; font-size:.75rem; border:none;
    cursor:pointer; transition:all .2s; text-decoration:none; display:inline-block;
  }
  .btn:hover { transform:translateY(-2px); }
  .btn-wine    { background:var(--wine); color:white; }
  .btn-wine:hover { background:var(--wine-dark); }
  .btn-success { background:var(--success); color:white; }
  .btn-success:hover { background:#219653; }
  .btn-danger  { background:var(--danger); color:white; }
  .btn-danger:hover  { background:#a93226; }
  .btn-info    { background:var(--info); color:white; }
  .btn-info:hover    { background:#2471a3; }
  .btn-warning { background:var(--warning); color:white; }
  .btn-warning:hover { background:#d68910; }

  .action-group { display:flex; gap:.5rem; flex-wrap:wrap; }

  .boutique-img { width:50px; height:50px; object-fit:cover; border-radius:8px; border:1px solid var(--border); }
  .boutique-img-placeholder {
    width:50px; height:50px; display:flex; align-items:center; justify-content:center;
    background:var(--bg-light); border-radius:8px; border:1px solid var(--border);
    color:var(--text-muted); font-size:1.5rem;
  }

  .settings-grid { display:grid; grid-template-columns:1fr 1fr; gap:2rem; padding:1.5rem; }
  .settings-group { background:var(--bg); border-radius:8px; padding:1.2rem; border:1px solid var(--border); }
  .settings-group h4 { font-family:'Playfair Display',serif; color:var(--wine); margin-bottom:.8rem; font-size:1rem; }
  .theme-toggle-wrapper { display:flex; align-items:center; gap:1rem; padding:.5rem 0; }
  .theme-toggle-label { font-size:.85rem; color:var(--text-muted); font-weight:500; }
  .theme-switch { position:relative; display:inline-block; width:60px; height:34px; flex-shrink:0; cursor:pointer; }
  .theme-switch input { opacity:0; width:0; height:0; }
  .theme-slider { position:absolute; top:0; left:0; right:0; bottom:0; background:#ccc; transition:.4s; border-radius:34px; box-shadow:inset 0 2px 4px rgba(0,0,0,.2); }
  .theme-slider:before { position:absolute; content:""; height:26px; width:26px; left:4px; bottom:4px; background:white; transition:.4s; border-radius:50%; box-shadow:0 2px 8px rgba(0,0,0,.3); }
  .theme-switch input:checked + .theme-slider { background:#2c241e; }
  .theme-switch input:checked + .theme-slider:before { transform:translateX(26px); background:#f0e6d8; }

  #toast {
    position:fixed; bottom:28px; right:28px; padding:14px 22px;
    border-radius:14px; font-weight:700; font-size:.95rem; z-index:9999;
    transform:translateY(80px); opacity:0;
    transition:.4s cubic-bezier(.22,1,.36,1); max-width:340px; color:#fff;
  }
  #toast.show { transform:translateY(0); opacity:1; }

  #confirm-modal { display:none; position:fixed; inset:0; z-index:9998; align-items:center; justify-content:center; }
  #confirm-modal.show { display:flex; }
  #confirm-overlay { position:absolute; inset:0; background:rgba(0,0,0,0.45); backdrop-filter:blur(3px); }
  #confirm-box {
    position:relative; background:var(--bg-card,#fff); border-radius:20px;
    padding:2rem 1.8rem 1.5rem; max-width:360px; width:90%;
    box-shadow:0 20px 60px rgba(0,0,0,.2); text-align:center;
    animation:modalIn .25s cubic-bezier(.22,1,.36,1);
  }
  @keyframes modalIn { from{opacity:0;transform:scale(.88) translateY(20px)} to{opacity:1;transform:scale(1) translateY(0)} }
  #confirm-icon  { font-size:2.5rem; margin-bottom:.8rem; }
  #confirm-title { font-family:'Playfair Display',serif; font-size:1.15rem; font-weight:700; color:var(--text-dark,#2C2C2C); margin-bottom:.4rem; }
  #confirm-msg   { font-size:.88rem; color:var(--text-muted,#6B6B6B); margin-bottom:1.4rem; }
  .confirm-btns  { display:flex; gap:.8rem; justify-content:center; }
  .confirm-btns button { flex:1; padding:.65rem 1rem; border-radius:999px; font-weight:700; font-size:.9rem; cursor:pointer; border:none; transition:all .2s; }
  #confirm-cancel { background:var(--bg-light,#f5f0e8); color:var(--text-dark,#2C2C2C); }
  #confirm-cancel:hover { opacity:.8; }
  #confirm-ok { background:#c0392b; color:#fff; }
  #confirm-ok:hover { background:#a93226; }

  @media(max-width:768px) {
    .dashboard-grid,.tabs-container,.main-content { padding:1rem; }
    .settings-grid { grid-template-columns:1fr; }
    .tab-btn { padding:.5rem .8rem; font-size:.8rem; }
    th,td { padding:.4rem; font-size:.75rem; }
    .btn { font-size:.65rem; padding:.2rem .5rem; }
  }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div id="toast"></div>

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

<div class="tabs-container">
  <div class="tabs">
    <button class="tab-btn active" data-tab="producteurs">👥 Producteurs
      <?php if($nb_attente > 0): ?><span class="badge-tab"><?php echo $nb_attente; ?></span><?php endif; ?>
    </button>
    <button class="tab-btn" data-tab="boutiques">🏪 Boutiques
      <?php if($nb_boutiques_attente > 0): ?><span class="badge-tab"><?php echo $nb_boutiques_attente; ?></span><?php endif; ?>
    </button>
    <button class="tab-btn" data-tab="produits">📦 Produits
      <?php if(count($produits_attente) > 0): ?><span class="badge-tab"><?php echo count($produits_attente); ?></span><?php endif; ?>
    </button>
    <button class="tab-btn" data-tab="commandes">🛒 Commandes</button>
    <button class="tab-btn" data-tab="clients">👤 Clients</button>
    <button class="tab-btn" data-tab="avis">💬 Avis</button>
    <button class="tab-btn" data-tab="parametres">⚙️ Paramètres</button>
  </div>
</div>

<div class="main-content">

  <div class="tab-panel active" id="tab-producteurs">

    <div class="section-card">
      <div class="section-header">
        👥 Producteurs en attente de validation
        <span class="badge-count"><?php echo $nb_attente; ?> en attente</span>
      </div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Entreprise</th><th>Email</th><th>Date inscription</th><th>Statut</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if(empty($producteurs_attente)): ?>
              <tr><td colspan="5" class="empty-state">🎉 Aucun producteur en attente.</td></tr>
            <?php else: ?>
              <?php foreach($producteurs_attente as $p): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($p['nom_entreprise']); ?></strong></td>
                <td><?php echo htmlspecialchars($p['email']); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($p['date_inscription'])); ?></td>
                <td><span class="badge badge-warning">⏳ En attente</span></td>
                <td>
                  <div class="action-group">
                    <button class="btn btn-success" onclick="validerProducteur(<?php echo $p['id_producteur']; ?>)">✅ Valider</button>
                    <button class="btn btn-danger"  onclick="refuserProducteur(<?php echo $p['id_producteur']; ?>)">❌ Refuser</button>
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
          <thead><tr><th>Entreprise</th><th>Email</th><th>Statut</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if(empty($producteurs)): ?>
              <tr><td colspan="4" class="empty-state">Aucun producteur.</td></tr>
            <?php else: ?>
              <?php foreach($producteurs as $p): ?>
              <tr data-producteur-id="<?php echo $p['id']; ?>">
                <td><strong><?php echo htmlspecialchars($p['nom']); ?></strong></td>
                <td><?php echo htmlspecialchars($p['email']); ?></td>
                <td>
                  <?php if($p['statut'] === 'valide'): ?>
                    <span class="badge badge-success badge-statut">✅ Validé</span>
                  <?php elseif($p['statut'] === 'suspendu'): ?>
                    <span class="badge badge-danger badge-statut">⛔ Suspendu</span>
                  <?php elseif($p['statut'] === 'refuse'): ?>
                    <span class="badge badge-danger badge-statut">❌ Refusé</span>
                  <?php else: ?>
                    <span class="badge badge-warning badge-statut">⏳ En attente</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="action-group">
                    <?php if($p['statut'] === 'valide'): ?>
                      <button class="btn btn-danger btn-suspendre" onclick="suspendreProducteur(<?php echo $p['id']; ?>)">⛔ Suspendre</button>
                    <?php elseif($p['statut'] === 'suspendu'): ?>
                      <button class="btn btn-success btn-activer" onclick="suspendreProducteur(<?php echo $p['id']; ?>)">✅ Réactiver</button>
                    <?php elseif($p['statut'] === 'refuse'): ?>
                      <button class="btn btn-success" onclick="validerProducteur(<?php echo $p['id']; ?>)">✅ Réactiver</button>
                    <?php else: ?>
                      <button class="btn btn-success" onclick="validerProducteur(<?php echo $p['id']; ?>)">✅ Valider</button>
                      <button class="btn btn-danger"  onclick="refuserProducteur(<?php echo $p['id']; ?>)">❌ Refuser</button>
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

  <div class="tab-panel" id="tab-boutiques">

    <div class="section-card">
      <div class="section-header">
        🏪 Boutiques en attente de validation
        <span class="badge-count" id="count-attente-boutiques"><?php echo $nb_boutiques_attente; ?> en attente</span>
      </div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Image</th><th>Nom boutique</th><th>Producteur</th><th>Email</th><th>Date création</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if(empty($boutiques_attente)): ?>
              <tr><td colspan="6" class="empty-state">🎉 Aucune boutique en attente.</td></tr>
            <?php else: ?>
              <?php foreach($boutiques_attente as $b): ?>
              <tr id="row-attente-boutique-<?php echo $b['id_boutique']; ?>">
                <td>
                  <?php if(!empty($b['image']) && file_exists($b['image'])): ?>
                    <img src="<?php echo htmlspecialchars($b['image']); ?>" class="boutique-img">
                  <?php else: ?>
                    <div class="boutique-img-placeholder"><i class="bi bi-shop"></i></div>
                  <?php endif; ?>
                </td>
                <td><strong><?php echo htmlspecialchars($b['nom_boutique']); ?></strong></td>
                <td><?php echo htmlspecialchars($b['producteur_nom']); ?></td>
                <td><?php echo htmlspecialchars($b['producteur_email']); ?></td>
                <td><?php echo date('d/m/Y', strtotime($b['date_creation'])); ?></td>
                <td>
                  <div class="action-group">
                    <button class="btn btn-success" onclick="validerBoutique(<?php echo $b['id_boutique']; ?>)">✅ Valider</button>
                    <button class="btn btn-danger"  onclick="refuserBoutique(<?php echo $b['id_boutique']; ?>)">❌ Refuser</button>
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
          <thead><tr><th>Nom</th><th>Producteur</th><th>Email</th><th>Date création</th><th>Statut</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if(empty($boutiques)): ?>
              <tr><td colspan="6" class="empty-state">Aucune boutique.</td></tr>
            <?php else: ?>
              <?php foreach($boutiques as $b): ?>
              <tr data-boutique-id="<?php echo $b['id_boutique']; ?>">
                <td><strong><?php echo htmlspecialchars($b['nom_boutique']); ?></strong></td>
                <td><?php echo htmlspecialchars($b['producteur_nom']); ?></td>
                <td><?php echo htmlspecialchars($b['producteur_email']); ?></td>
                <td><?php echo date('d/m/Y', strtotime($b['date_creation'])); ?></td>
                <td>
                  <?php if($b['statut'] === 'valide'): ?>
                    <span class="badge badge-success badge-statut">✅ Validée</span>
                  <?php elseif($b['statut'] === 'refuse'): ?>
                    <span class="badge badge-danger badge-statut">❌ Refusée</span>
                  <?php else: ?>
                    <span class="badge badge-warning badge-statut">⏳ En attente</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="action-group">
                    <?php if($b['statut'] === 'valide'): ?>
                      <button class="btn btn-danger btn-revoquer-b" onclick="revoquerBoutique(<?php echo $b['id_boutique']; ?>)">⛔ Révoquer</button>
                    <?php elseif($b['statut'] === 'refuse'): ?>
                      <button class="btn btn-success btn-valider-b" onclick="validerBoutique(<?php echo $b['id_boutique']; ?>)">✅ Réactiver</button>
                    <?php else: ?>
                      <button class="btn btn-success btn-valider-b" onclick="validerBoutique(<?php echo $b['id_boutique']; ?>)">✅ Valider</button>
                      <button class="btn btn-danger btn-refuser-b"  onclick="refuserBoutique(<?php echo $b['id_boutique']; ?>)">❌ Refuser</button>
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

  <div class="tab-panel" id="tab-produits">

    <div class="section-card">
      <div class="section-header">
        📦 Tous les produits
        <span class="badge-count"><?php echo count($tous_produits); ?> produit(s)</span>
      </div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr><th>ID</th><th>Produit</th><th>Boutique</th><th>Producteur</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>Statut</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if(empty($tous_produits)): ?>
              <tr><td colspan="9" class="empty-state">Aucun produit.</td></tr>
            <?php else: ?>
              <?php foreach($tous_produits as $p): ?>
              <tr>
                <td><span class="badge badge-info">#<?php echo $p['id_produit']; ?></span></td>
                <td><strong><?php echo htmlspecialchars($p['nom_produit']); ?></strong></td>
                <td><?php echo htmlspecialchars($p['nom_boutique'] ?? 'Sans boutique'); ?></td>
                <td><?php echo htmlspecialchars($p['nom_producteur'] ?? 'Inconnu'); ?></td>
                <td><?php echo htmlspecialchars($p['nom_categorie'] ?? 'Non catégorisé'); ?></td>
                <td><?php echo number_format($p['prix_unitaire'], 2); ?> DH</td>
                <td>
                  <?php if($p['stock_quantite'] <= 0): ?>
                    <span class="badge badge-danger">❌ Rupture</span>
                  <?php elseif($p['stock_quantite'] <= 5): ?>
                    <span class="badge badge-warning">⚠️ <?php echo $p['stock_quantite']; ?></span>
                  <?php else: ?>
                    <span class="badge badge-success">✅ <?php echo $p['stock_quantite']; ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if($p['est_valide_par_admin'] == 1): ?>
                    <span class="badge badge-success">✅ Validé</span>
                  <?php else: ?>
                    <span class="badge badge-warning">⏳ En attente</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="action-group">
                    <?php if($p['est_valide_par_admin'] != 1): ?>
                      <button class="btn btn-success" onclick="validerProduit(<?php echo $p['id_produit']; ?>)">✅ Valider</button>
                      <button class="btn btn-danger"  onclick="refuserProduit(<?php echo $p['id_produit']; ?>)">❌ Refuser</button>
                    <?php else: ?>
                      <button class="btn btn-info" onclick="voirProduit(<?php echo $p['id_produit']; ?>)">👁️ Voir</button>
                    <?php endif; ?>
                    <button class="btn btn-danger" onclick="supprimerProduit(<?php echo $p['id_produit']; ?>, '<?php echo addslashes($p['nom_produit']); ?>')">🗑️ Supprimer</button>
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
          <thead><tr><th>Produit</th><th>Boutique</th><th>Producteur</th><th>Prix</th><th>Date création</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if(empty($produits_attente)): ?>
              <tr><td colspan="6" class="empty-state">🎉 Aucun produit en attente.</td></tr>
            <?php else: ?>
              <?php foreach($produits_attente as $p): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($p['nom_produit']); ?></strong></td>
                <td><?php echo htmlspecialchars($p['nom_boutique']); ?></td>
                <td><?php echo htmlspecialchars($p['nom_producteur']); ?></td>
                <td><?php echo number_format($p['prix_unitaire'], 2); ?> DH</td>
                <td><?php echo date('d/m/Y H:i', strtotime($p['date_creation'])); ?></td>
                <td>
                  <div class="action-group">
                    <button class="btn btn-success" onclick="validerProduit(<?php echo $p['id_produit']; ?>)">✅ Valider</button>
                    <button class="btn btn-danger"  onclick="refuserProduit(<?php echo $p['id_produit']; ?>)">❌ Refuser</button>
                    <button class="btn btn-danger"  onclick="supprimerProduit(<?php echo $p['id_produit']; ?>, '<?php echo addslashes($p['nom_produit']); ?>')">🗑️ Supprimer</button>
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

  <div class="tab-panel" id="tab-commandes">
    <div class="section-card">
      <div class="section-header">
        🛒 Dernières commandes
        <span class="badge-count"><?php echo count($commandes); ?> commande(s)</span>
      </div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>N° Commande</th><th>Client</th><th>Total</th><th>Date</th><th>Statut</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if(empty($commandes)): ?>
              <tr><td colspan="6" class="empty-state">Aucune commande.</td></tr>
            <?php else: ?>
              <?php foreach($commandes as $cmd): ?>
              <tr data-commande-id="<?php echo $cmd['id_commande']; ?>">
                <td><strong>#<?php echo $cmd['id_commande']; ?></strong></td>
                <td><?php echo htmlspecialchars($cmd['nom_client']); ?></td>
                <td><?php echo number_format($cmd['montant_total'], 2); ?> DH</td>
                <td><?php echo date('d/m/Y', strtotime($cmd['date_commande'])); ?></td>
                <td>
                  <?php
                  $bc = 'info';
                  if($cmd['statut_commande']==='Livrée')    $bc='success';
                  elseif($cmd['statut_commande']==='Annulée') $bc='danger';
                  elseif($cmd['statut_commande']==='En attente') $bc='warning';
                  ?>
                  <span class="badge badge-<?php echo $bc; ?> badge-statut"><?php echo htmlspecialchars($cmd['statut_commande']); ?></span>
                </td>
                <td>
                  <div class="action-group">
                    <?php if($cmd['statut_commande'] !== 'Livrée' && $cmd['statut_commande'] !== 'Annulée'): ?>
                      <button class="btn btn-warning btn-annuler-cmd" onclick="annulerCommande(<?php echo $cmd['id_commande']; ?>)">🚫 Annuler</button>
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

  <div class="tab-panel" id="tab-clients">
    <div class="section-card">
      <div class="section-header">
        👤 Tous les clients
        <span class="badge-count"><?php echo count($clients); ?> client(s)</span>
      </div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Nom</th><th>Email</th><th>Téléphone</th><th>Date inscription</th><th>Statut</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if(empty($clients)): ?>
              <tr><td colspan="6" class="empty-state">Aucun client.</td></tr>
            <?php else: ?>
              <?php foreach($clients as $c): ?>
              <tr data-client-id="<?php echo $c['id']; ?>">
                <td><strong><?php echo htmlspecialchars($c['nom']); ?></strong></td>
                <td><?php echo htmlspecialchars($c['email']); ?></td>
                <td><?php echo htmlspecialchars($c['telephone'] ?? 'Non renseigné'); ?></td>
                <td><?php echo date('d/m/Y', strtotime($c['date_inscription'] ?? 'now')); ?></td>
                <td>
                  <?php if($c['est_actif'] ?? 1): ?>
                    <span class="badge badge-success badge-statut">✅ Actif</span>
                  <?php else: ?>
                    <span class="badge badge-danger badge-statut">⛔ Suspendu</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="action-group">
                    <?php if($c['est_actif'] ?? 1): ?>
                      <button class="btn btn-danger btn-susp-client" onclick="suspendreClient(<?php echo $c['id']; ?>, '<?php echo addslashes($c['nom']); ?>')">⛔ Suspendre</button>
                    <?php else: ?>
                      <button class="btn btn-success btn-activ-client" onclick="activerClient(<?php echo $c['id']; ?>, '<?php echo addslashes($c['nom']); ?>')">✅ Réactiver</button>
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

  <div class="tab-panel" id="tab-avis">
    <div class="section-card">
      <div class="section-header">
        💬 Gestion des Avis
        <span class="badge-count"><?php echo count($tous_avis); ?> avis</span>
      </div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Type</th><th>Note</th><th>Commentaire</th><th>Date</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if(empty($tous_avis)): ?>
              <tr><td colspan="5" class="empty-state">Aucun avis.</td></tr>
            <?php else: ?>
              <?php foreach($tous_avis as $a):
                $id_avis    = $a['id_evaluation'] ?? $a['id_avis'] ?? $a['id'] ?? 0;
                $commentaire = $a['commentaire'] ?? $a['avis'] ?? 'Sans commentaire';
                $note       = $a['note'] ?? $a['rating'] ?? '-';
                $date_eval  = $a['date_evaluation'] ?? $a['date_avis'] ?? $a['date_creation'] ?? '';
              ?>
              <tr>
                <td><span class="badge badge-info"><?php echo htmlspecialchars($a['type_avis']); ?></span></td>
                <td><strong><?php echo htmlspecialchars($note); ?>/5</strong></td>
                <td><?php echo htmlspecialchars(substr($commentaire,0,60)).(strlen($commentaire)>60?'...':''); ?></td>
                <td><?php echo !empty($date_eval) ? date('d/m/Y',strtotime($date_eval)) : 'N/A'; ?></td>
                <td>
                  <button class="btn btn-danger"
                    onclick="supprimerAvis(<?php echo $id_avis; ?>, '<?php echo urlencode($a['type_avis']); ?>')">
                    🗑️ Supprimer
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="tab-panel" id="tab-parametres">
    <div class="section-card">
      <div class="section-header">⚙️ Paramètres</div>
      <div class="settings-grid">
        <div class="settings-group">
          <h4>🎨 Thème Clair / Sombre</h4>
          <div class="theme-toggle-wrapper">
            <span class="theme-toggle-label">☀️ Clair</span>
            <label class="theme-switch">
              <input type="checkbox" id="themeToggle" <?php echo $theme==='dark'?'checked':''; ?> onchange="toggleTheme()">
              <span class="theme-slider"></span>
            </label>
            <span class="theme-toggle-label">🌙 Sombre</span>
          </div>
          <p style="font-size:.75rem;color:var(--text-muted);margin-top:.5rem;">
            <?php echo $theme==='dark'?'🌙 Mode sombre activé':'☀️ Mode clair activé'; ?>
          </p>
        </div>
        <div class="settings-group">
          <h4>👤 Informations administrateur</h4>
          <p style="font-size:.9rem;color:var(--text-dark);">
            <strong>Nom :</strong> <?php echo htmlspecialchars($administrateur['nom_admin'] ?? $administrateur['nom'] ?? 'Administrateur'); ?><br>
            <strong>Email :</strong> <?php echo htmlspecialchars($administrateur['email'] ?? 'admin@greenmarket.com'); ?><br>
            <strong>Rôle :</strong> <span class="badge badge-success">✅ Administrateur</span>
          </p>
        </div>
        <div class="settings-group">
          <h4>📊 Statistiques</h4>
          <p style="font-size:.9rem;color:var(--text-dark);">
            <strong>Utilisateurs :</strong> <?php echo $total_users; ?><br>
            <strong>Boutiques :</strong> <?php echo $total_boutiques; ?><br>
            <strong>Produits :</strong> <?php echo $total_produits; ?><br>
            <strong>Commandes :</strong> <?php echo $total_commandes; ?><br>
            <strong>CA Total :</strong> <?php echo number_format($ca_total,0,',',' '); ?> DH
          </p>
        </div>
      </div>
    </div>
  </div>

</div>
<script>
function showToast(msg, bg) {
  const toast = document.getElementById('toast');
  toast.textContent = msg;
  toast.style.background = bg || 'var(--wine)';
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3200);
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

function switchTab(tabName) {
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  const btn = document.querySelector('[data-tab="' + tabName + '"]');
  const panel = document.getElementById('tab-' + tabName);
  if (btn) btn.classList.add('active');
  if (panel) panel.classList.add('active');
  history.replaceState(null, '', '?tab=' + tabName);
}

document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    switchTab(this.getAttribute('data-tab'));
  });
});

document.addEventListener('DOMContentLoaded', () => {
  const tab = new URLSearchParams(window.location.search).get('tab');
  if (tab) switchTab(tab);
  const scrollY = sessionStorage.getItem('dashScrollY');
  if (scrollY) { window.scrollTo(0, parseInt(scrollY)); sessionStorage.removeItem('dashScrollY'); }
});

window.addEventListener('beforeunload', () => {
  sessionStorage.setItem('dashScrollY', window.scrollY);
});

function validerProducteur(id) {
  askConfirm('Valider ce producteur ?', 'Le compte sera activé et pourra accéder à la plateforme.',
    () => { 
      fetch('valider_producteur.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id + '&action=valider'
      })
      .then(res => res.json())
      .then(data => {
        if(data.success) {
          showToast('✅ ' + data.message);
          setTimeout(() => location.reload(), 1000);
        } else {
          showToast('❌ ' + data.message, 'var(--danger)');
        }
      })
      .catch(() => showToast('❌ Erreur de connexion', 'var(--danger)'));
    }, '✅');
}

function refuserProducteur(id) {
  askConfirm('Refuser ce producteur ?', 'La demande sera refusée et apparaîtra comme "Refusé".',
    () => { 
      fetch('valider_producteur.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id + '&action=refuser'
      })
      .then(res => res.json())
      .then(data => {
        if(data.success) {
          showToast('✅ ' + data.message);
          setTimeout(() => location.reload(), 1000);
        } else {
          showToast('❌ ' + data.message, 'var(--danger)');
        }
      })
      .catch(() => showToast('❌ Erreur de connexion', 'var(--danger)'));
    }, '❌');
}

function suspendreProducteur(id) {
  askConfirm('Suspendre / Réactiver ?', 'Le statut du producteur sera basculé.',
    () => {
      fetch('suspendre_producteur.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          showToast('✅ ' + data.message);
          const isSuspendu = (data.new_statut !== 'valide');

          const row = document.querySelector('[data-producteur-id="' + id + '"]');
          if (row) {
            const badge = row.querySelector('.badge-statut');
            if (isSuspendu) {
              if (badge) { badge.textContent = '⛔ Suspendu'; badge.className = 'badge badge-danger badge-statut'; }
              const btnSusp = row.querySelector('.btn-suspendre');
              if (btnSusp) {
                btnSusp.textContent = '✅ Réactiver';
                btnSusp.className = 'btn btn-success btn-activer';
                btnSusp.onclick = function() { suspendreProducteur(id); };
              }
            } else {
              if (badge) { badge.textContent = '✅ Validé'; badge.className = 'badge badge-success badge-statut'; }
              const btnActiv = row.querySelector('.btn-activer');
              if (btnActiv) {
                btnActiv.textContent = '⛔ Suspendre';
                btnActiv.className = 'btn btn-danger btn-suspendre';
                btnActiv.onclick = function() { suspendreProducteur(id); };
              }
            }
          }

          if (data.boutiques_ids && Array.isArray(data.boutiques_ids)) {
            data.boutiques_ids.forEach(bid => {
              const bRow = document.querySelector('[data-boutique-id="' + bid + '"]');
              if (bRow) {
                const bBadge = bRow.querySelector('.badge-statut');
                if (isSuspendu) {
                  if (bBadge) { bBadge.textContent = '⛔ Suspendue'; bBadge.className = 'badge badge-danger badge-statut'; }
                  bRow.querySelectorAll('.btn-revoquer-b').forEach(b => b.style.display = 'none');
                } else {
                  if (bBadge) { bBadge.textContent = '✅ Validée'; bBadge.className = 'badge badge-success badge-statut'; }
                  bRow.querySelectorAll('.btn-revoquer-b').forEach(b => b.style.display = '');
                }
              }
            });
          } else {
            setTimeout(() => location.reload(), 1200);
          }

        } else {
          showToast('❌ ' + data.message, 'var(--danger)');
        }
      })
      .catch(() => showToast('❌ Erreur de connexion', 'var(--danger)'));
    }, '⛔');
}

function _fetchBoutique(id, action, msg, icon) {
  askConfirm(msg, action === 'valider' ? 'La boutique sera visible par les clients.' : 'La boutique sera désactivée.',
    () => {
      fetch('valider_boutique.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id + '&action=' + action
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          showToast('✅ ' + data.message);

          const row = document.querySelector('[data-boutique-id="' + id + '"]');
          if (row) {
            const badge = row.querySelector('.badge-statut');
            const actionGroup = row.querySelector('.action-group');
            if (action === 'valider') {
              if (badge) { badge.textContent = '✅ Validée'; badge.className = 'badge badge-success badge-statut'; }
              if (actionGroup) {
                actionGroup.innerHTML = '<button class="btn btn-danger btn-revoquer-b" onclick="revoquerBoutique(' + id + ')">⛔ Révoquer</button>';
              }
            } else {
              if (badge) { badge.textContent = '❌ Refusée'; badge.className = 'badge badge-danger badge-statut'; }
              if (actionGroup) {
                actionGroup.innerHTML = '<button class="btn btn-success btn-valider-b" onclick="validerBoutique(' + id + ')">✅ Réactiver</button>';
              }
            }
          }

          const pendingRow = document.getElementById('row-attente-boutique-' + id);
          if (pendingRow) {
            pendingRow.remove();

            const tabBtn = document.querySelector('.tab-btn[data-tab="boutiques"]');
            const badgeTab = tabBtn.querySelector('.badge-tab');
            if (badgeTab) {
              let count = parseInt(badgeTab.textContent) - 1;
              if (count > 0) {
                badgeTab.textContent = count;
              } else {
                badgeTab.remove();
              }
            }

            const sectionCount = document.getElementById('count-attente-boutiques');
            if (sectionCount) {
              let currentCount = parseInt(sectionCount.textContent) - 1;
              sectionCount.textContent = Math.max(0, currentCount) + ' en attente';
              
              if (currentCount <= 0) {
                  const tbody = document.querySelector('#row-attente-boutique-' + id)?.closest('tbody');
                  if(tbody) {
                     tbody.innerHTML = '<tr><td colspan="6" class="empty-state">🎉 Aucune boutique en attente.</td></tr>';
                  }
              }
            }
          }

        } else {
          showToast('❌ ' + data.message, 'var(--danger)');
        }
      })
      .catch(() => showToast('❌ Erreur de connexion', 'var(--danger)'));
    }, icon);
}
function validerBoutique(id)  { _fetchBoutique(id, 'valider', 'Valider cette boutique ?', '🏪'); }
function refuserBoutique(id)  { _fetchBoutique(id, 'refuser', 'Refuser cette boutique ?', '❌'); }
function revoquerBoutique(id) { _fetchBoutique(id, 'refuser', 'Révoquer cette boutique ?', '⛔'); }

function validerProduit(id) {
  askConfirm('Valider ce produit ?', 'Le produit sera publié dans la boutique.',
    () => { window.location.href = 'valider_produit.php?id=' + id + '&action=valider'; }, '✅');
}
function refuserProduit(id) {
  askConfirm('Refuser ce produit ?', 'Le produit ne sera pas publié.',
    () => { window.location.href = 'valider_produit.php?id=' + id + '&action=refuser'; }, '❌');
}
function voirProduit(id) {
  window.location.href = 'info-produit.php?id=' + id;
}
function supprimerProduit(id, nom) {
  askConfirm('Supprimer ce produit ?', '« ' + nom + ' » sera supprimé définitivement.',
    () => { window.location.href = 'supprimer_produit.php?id=' + id; }, '🗑️');
}

function annulerCommande(id) {
  askConfirm('Annuler cette commande ?', 'La commande #' + id + ' sera marquée comme Annulée.',
    () => {
      fetch('annuler_commande_admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          showToast('✅ Commande #' + id + ' annulée');
          const row = document.querySelector('[data-commande-id="' + id + '"]');
          if (row) {
            const badge = row.querySelector('.badge-statut');
            if (badge) { badge.textContent = 'Annulée'; badge.className = 'badge badge-danger badge-statut'; }
            const btn = row.querySelector('.btn-annuler-cmd');
            if (btn) btn.style.display = 'none';
          }
        } else {
          showToast('❌ ' + data.message, 'var(--danger)');
        }
      })
      .catch(() => showToast('❌ Erreur de connexion', 'var(--danger)'));
    }, '🚫');
}

function suspendreClient(id, nom) {
  askConfirm('Suspendre ce client ?', '« ' + nom + ' » ne pourra plus se connecter.',
    () => {
      fetch('suspendre_client.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id + '&action=suspendre'
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          showToast('✅ ' + data.message);
          const row = document.querySelector('[data-client-id="' + id + '"]');
          if (row) {
            const badge = row.querySelector('.badge-statut');
            const ag    = row.querySelector('.action-group');
            if (badge) { badge.textContent = '⛔ Suspendu'; badge.className = 'badge badge-danger badge-statut'; }
            if (ag) ag.innerHTML = '<button class="btn btn-success btn-activ-client" onclick="activerClient(' + id + ', \'' + nom.replace(/'/g,"\\'")+'\')">✅ Réactiver</button>';
          }
        } else { showToast('❌ ' + data.message, 'var(--danger)'); }
      })
      .catch(() => showToast('❌ Erreur de connexion', 'var(--danger)'));
    }, '⛔');
}
function activerClient(id, nom) {
  askConfirm('Réactiver ce client ?', '« ' + nom + ' » pourra de nouveau accéder à son compte.',
    () => {
      fetch('suspendre_client.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + id + '&action=activer'
      })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          showToast('✅ ' + data.message);
          const row = document.querySelector('[data-client-id="' + id + '"]');
          if (row) {
            const badge = row.querySelector('.badge-statut');
            const ag    = row.querySelector('.action-group');
            if (badge) { badge.textContent = '✅ Actif'; badge.className = 'badge badge-success badge-statut'; }
            if (ag) ag.innerHTML = '<button class="btn btn-danger btn-susp-client" onclick="suspendreClient(' + id + ', \'' + nom.replace(/'/g,"\\'")+'\')">⛔ Suspendre</button>';
          }
        } else { showToast('❌ ' + data.message, 'var(--danger)'); }
      })
      .catch(() => showToast('❌ Erreur de connexion', 'var(--danger)'));
    }, '✅');
}

function supprimerAvis(id, type) {
  askConfirm('Supprimer cet avis ?', 'Cette action est irréversible.',
    () => { window.location.href = 'supprimer_avis_admin.php?id=' + id + '&type=' + type; }, '🗑️');
}

function toggleTheme() {
  const isDark = document.getElementById('themeToggle').checked;
  const theme = isDark ? 'dark' : 'light';
  document.cookie = 'theme=' + theme + '; path=/; max-age=31536000';
  document.documentElement.setAttribute('data-theme', theme);
  showToast('✅ Thème changé en ' + (isDark ? 'sombre' : 'clair'));
  setTimeout(() => location.reload(), 600);
}
</script>
</body>
</html>
