<?php
session_start();

// Verificar que el usuario está conectado y es admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: signin.php');
    exit;
}

// Conectar a la base de datos
require_once 'connexion.php';

// Obtener todos los datos de la BD
try {
    // === ESTADÍSTICAS ===
    // Total de utilisateurs
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM client");
    $total_clients = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM producteur");
    $total_producteurs = $stmt->fetch()['total'];
    
    $total_users = $total_clients + $total_producteurs;
    
    // Producteurs actifs (validés)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM producteur WHERE est_valide_par_admin = 1");
    $active_producers = $stmt->fetch()['total'];
    
    // Producteurs en attente
    $stmt = $pdo->query("SELECT * FROM producteur WHERE est_valide_par_admin = 0 ORDER BY date_inscription DESC");
    $producteurs_attente = $stmt->fetchAll();
    $nb_attente = count($producteurs_attente);
    
    // Chiffre d'affaires total
    $stmt = $pdo->query("SELECT SUM(montant_total) as total FROM commande WHERE statut_commande = 'Livrée'");
    $ca_total = $stmt->fetch()['total'] ?? 0;
    
    // Nombre de commandes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM commande");
    $total_commandes = $stmt->fetch()['total'];
    
    // Nombre de boutiques
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM boutique");
    $total_boutiques = $stmt->fetch()['total'];
    
    // Nombre de produits
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM produit");
    $total_produits = $stmt->fetch()['total'];
    
    // Produits en attente de validation
    $stmt = $pdo->query("SELECT p.*, b.nom_boutique FROM produit p 
                         JOIN boutique b ON p.id_boutique = b.id_boutique 
                         WHERE p.est_valide_par_admin = 0 
                         ORDER BY p.date_creation DESC");
    $produits_attente = $stmt->fetchAll();
    
    // Tous les clients
    $stmt = $pdo->query("SELECT id_client as id, nom_client as nom, email, 'client' as role, 1 as actif FROM client ORDER BY date_inscription DESC");
    $clients = $stmt->fetchAll();
    
    // Tous les producteurs
    $stmt = $pdo->query("SELECT id_producteur as id, nom_entreprise as nom, email, 'producteur' as role, est_valide_par_admin as actif FROM producteur ORDER BY date_inscription DESC");
    $producteurs = $stmt->fetchAll();
    
    // Toutes les boutiques
    $stmt = $pdo->query("SELECT b.*, p.nom_entreprise as producteur_nom, p.est_valide_par_admin as producteur_valide 
                         FROM boutique b 
                         JOIN producteur p ON b.id_producteur = p.id_producteur 
                         ORDER BY b.date_creation DESC");
    $boutiques = $stmt->fetchAll();
    
    // Catégories
    $stmt = $pdo->query("SELECT * FROM categorie ORDER BY nom_categorie");
    $categories = $stmt->fetchAll();
    
    // Commandes récentes
    $stmt = $pdo->query("SELECT c.*, cl.nom_client FROM commande c 
                         JOIN client cl ON c.id_client = cl.id_client 
                         ORDER BY c.date_commande DESC LIMIT 20");
    $commandes = $stmt->fetchAll();
    
    // Produits populaires (plus vendus via contenir)
    $stmt = $pdo->query("SELECT p.id_produit, p.nom_produit, p.prix_unitaire, b.nom_boutique, 
                                COALESCE(SUM(co.quantite), 0) as total_vendus
                         FROM produit p
                         LEFT JOIN contenir co ON p.id_produit = co.id_produit
                         JOIN boutique b ON p.id_boutique = b.id_boutique
                         WHERE p.est_valide_par_admin = 1
                         GROUP BY p.id_produit
                         ORDER BY total_vendus DESC LIMIT 5");
    $produits_populaires = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Error dashboard admin: " . $e->getMessage());
    $producteurs_attente = [];
    $clients = [];
    $producteurs = [];
    $boutiques = [];
    $categories = [];
    $commandes = [];
    $produits_populaires = [];
    $produits_attente = [];
    $total_users = 0;
    $active_producers = 0;
    $ca_total = 0;
    $total_commandes = 0;
    $total_boutiques = 0;
    $total_produits = 0;
    $nb_attente = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GreenMarket – Dashboard Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  :root {
    --cream: #fff9eb;
    --sage: #9fb2ac;
    --wine: #5d0d18;
    --wine-dark: #3e0910;
    --text: #2a1a1c;
    --text-muted: #6b5055;
    --border: #e8ddd0;
    --white: #fffdf7;
    --success: #27ae60;
    --danger: #c0392b;
    --warning: #f39c12;
    --info: #2980b9;
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Lato', sans-serif; background: var(--cream); min-height: 100vh; }

  .dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    padding: 2rem 2.5rem;
    max-width: 1400px;
    margin: 0 auto;
  }
  .stat-card {
    background: var(--white);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    transition: transform 0.2s;
  }
  .stat-card:hover { transform: translateY(-5px); }
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
  }
  .tab-btn:hover { color: var(--wine); }
  .tab-btn.active {
    color: var(--wine);
    border-bottom: 2px solid var(--wine);
    font-weight: 600;
  }

  .main-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2rem 2.5rem;
  }
  .tab-panel { display: none; }
  .tab-panel.active { display: block; }

  .section-card {
    background: var(--white);
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
  table {
    width: 100%;
    border-collapse: collapse;
  }
  th, td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border);
  }
  th {
    background: var(--cream);
    font-weight: 600;
  }
  .empty-state {
    text-align: center;
    padding: 3rem;
    color: var(--text-muted);
  }

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

  .btn {
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-size: 0.8rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
  }
  .btn-success { background: var(--success); color: white; }
  .btn-success:hover { background: #219653; }
  .btn-danger { background: var(--danger); color: white; }
  .btn-danger:hover { background: #a93226; }
  .btn-wine { background: var(--wine); color: white; }
  .btn-wine:hover { background: var(--wine-dark); }
  .btn-info { background: var(--info); color: white; }

  .action-group { display: flex; gap: 0.5rem; flex-wrap: wrap; }

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
  .toast.show {
    transform: translateY(0);
    opacity: 1;
  }

  @media (max-width: 768px) {
    .dashboard-grid, .tabs-container, .main-content {
      padding: 1rem;
    }
  }
</style>
</head>
<body>

<?php include 'header.php'; ?>

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
    <div class="stat-val"><?php echo $nb_attente; ?></div>
    <div class="stat-label">En Attente</div>
  </div>
</div>

<div class="tabs-container">
  <div class="tabs">
    <button class="tab-btn active" data-tab="producteurs">👥 Producteurs</button>
    <button class="tab-btn" data-tab="boutiques">🏪 Boutiques</button>
    <button class="tab-btn" data-tab="produits">📦 Produits</button>
    <button class="tab-btn" data-tab="commandes">🛒 Commandes</button>
    <button class="tab-btn" data-tab="clients">👤 Clients</button>
  </div>
</div>

<div class="main-content">
  <!-- Onglet Producteurs avec validation -->
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
              <tr><td colspan="5" class="empty-state">Aucun producteur en attente.</td></tr>
            <?php else: ?>
              <?php foreach ($producteurs_attente as $p): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($p['nom_entreprise']); ?></strong></td>
                <td><?php echo htmlspecialchars($p['email']); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($p['date_inscription'])); ?></td>
                <td><span class="badge badge-warning">En attente</span></td>
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
              <tr><td colspan="4" class="empty-state">Aucun producteur.</td></tr>
            <?php else: ?>
              <?php foreach ($producteurs as $p): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($p['nom']); ?></strong></td>
                <td><?php echo htmlspecialchars($p['email']); ?></td>
                <td><?php if ($p['actif'] == 1): ?>
                  <span class="badge badge-success">Validé</span>
                <?php else: ?>
                  <span class="badge badge-warning">En attente</span>
                <?php endif; ?></td>
                <td>
                  <div class="action-group">
                    <?php if ($p['actif'] != 1): ?>
                      <button class="btn btn-success" onclick="validerProducteur(<?php echo $p['id']; ?>)">Valider</button>
                    <?php else: ?>
                      <button class="btn btn-danger" onclick="suspendreProducteur(<?php echo $p['id']; ?>)">Suspendre</button>
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

  <!-- Onglet Boutiques -->
  <div class="tab-panel" id="tab-boutiques">
    <div class="section-card">
      <div class="section-header">
        🏪 Toutes les boutiques
        <span class="badge-count"><?php echo count($boutiques); ?> boutique(s)</span>
      </div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr><th>Nom</th><th>Producteur</th><th>Description</th><th>Date création</th></tr>
          </thead>
          <tbody>
            <?php if (empty($boutiques)): ?>
              <tr><td colspan="5" class="empty-state">Aucune boutique.</td></tr>
            <?php else: ?>
              <?php foreach ($boutiques as $b): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($b['nom_boutique']); ?></strong></td>
                <td><?php echo htmlspecialchars($b['producteur_nom']); ?></td>
                <td><?php echo htmlspecialchars($b['description'] ?? 'Boutique artisanale'); ?></td>
                <td><?php echo date('d/m/Y', strtotime($b['date_creation'])); ?></td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Onglet Produits -->
  <div class="tab-panel" id="tab-produits">
    <div class="section-card">
      <div class="section-header">
        📦 Produits en attente de validation
        <span class="badge-count"><?php echo count($produits_attente); ?> en attente</span>
      </div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr><th>Produit</th><th>Boutique</th><th>Prix</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if (empty($produits_attente)): ?>
              <tr><td colspan="4" class="empty-state">Aucun produit en attente. </div>
            <?php else: ?>
              <?php foreach ($produits_attente as $p): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($p['nom_produit']); ?></strong></td>
                <td><?php echo htmlspecialchars($p['nom_boutique']); ?></td>
                <td><?php echo number_format($p['prix_unitaire'], 2); ?> DH</div>
                <td>
                  <div class="action-group">
                    <button class="btn btn-success" onclick="validerProduit(<?php echo $p['id_produit']; ?>)">✅ Valider</button>
                    <button class="btn btn-danger" onclick="refuserProduit(<?php echo $p['id_produit']; ?>)">❌ Refuser</button>
                  </div>
                 </div>
               </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Onglet Commandes -->
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
              <tr><td colspan="5" class="empty-state">Aucune commande. </div>
            <?php else: ?>
              <?php foreach ($commandes as $cmd): ?>
              <tr>
                <td><strong>#<?php echo $cmd['id_commande']; ?></strong></div>
                <td><?php echo htmlspecialchars($cmd['nom_client']); ?></div>
                <td><?php echo number_format($cmd['montant_total'], 2); ?> DH</div>
                <td><?php echo date('d/m/Y', strtotime($cmd['date_commande'])); ?></div>
                <td><span class="badge badge-<?php echo $cmd['statut_commande'] === 'Livrée' ? 'success' : 'info'; ?>"><?php echo $cmd['statut_commande']; ?></span></div>
               </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Onglet Clients -->
  <div class="tab-panel" id="tab-clients">
    <div class="section-card">
      <div class="section-header">
        👤 Tous les clients
        <span class="badge-count"><?php echo count($clients); ?> client(s)</span>
      </div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr><th>Nom</th><th>Email</th><th>Date d'inscription</th><th>Status</th></tr>
          </thead>
          <tbody>
            <?php if (empty($clients)): ?>
              <tr><td colspan="4" class="empty-state">Aucun client. </div>
            <?php else: ?>
              <?php foreach ($clients as $c): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($c['nom']); ?></strong></div>
                <td><?php echo htmlspecialchars($c['email']); ?></div>
                <td><?php echo date('d/m/Y', strtotime($c['date_inscription'] ?? 'now')); ?></div>
                <td><span class="badge badge-success">Actif</span></div>
               </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
// Gestion des onglets
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    const tabName = this.getAttribute('data-tab');
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    this.classList.add('active');
    document.getElementById('tab-' + tabName).classList.add('active');
  });
});

function showToast(msg) {
  const toast = document.getElementById('toast');
  toast.innerHTML = msg;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3000);
}

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
</script>
</body>
</html>