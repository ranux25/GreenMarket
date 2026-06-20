<?php
session_start();

#verifier que l'utilisateur est connecte et est producteur
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'producteur') {
    header('Location: signin.php');
    exit;
}

#connexion a la base de donnees
include('connexion.php');

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
            
            // 🔥 CORRIGÉ: Supprimé cl.telephone qui n'existe pas dans la table client
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
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GreenMarket – Mon Espace Producteur</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  /* Styles spécifiques au dashboard producteur */
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Lato', sans-serif; background: #fff9eb; }

  .dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    padding: 2rem 2.5rem;
    max-width: 1400px;
    margin: 0 auto;
  }
  .stat-card {
    background: white;
    border: 1px solid #e8ddd0;
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
    color: #5d0d18;
    margin: 0.5rem 0;
  }
  .stat-label {
    font-size: 0.8rem;
    color: #6b5055;
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
    border-bottom: 2px solid #e8ddd0;
    flex-wrap: wrap;
  }
  .tab-btn {
    padding: 0.75rem 1.5rem;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 0.9rem;
    color: #6b5055;
    transition: all 0.2s;
  }
  .tab-btn:hover { color: #5d0d18; }
  .tab-btn.active {
    color: #5d0d18;
    border-bottom: 2px solid #5d0d18;
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
    background: white;
    border: 1px solid #e8ddd0;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 2rem;
  }
  .section-header {
    background: #5d0d18;
    color: #fff9eb;
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

  .alert-warning {
    background: #fff3cd;
    border: 1px solid #ffc107;
    color: #856404;
    padding: 1rem;
    border-radius: 8px;
    margin: 2rem;
    text-align: center;
  }

  .alert-success {
    background: #d4edda;
    color: #155724;
    padding: 1.5rem;
    border-radius: 12px;
    margin: 1.5rem;
  }

  /* 🔥 DEBUG INFO */
  .debug-info {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    margin: 1rem 2.5rem;
    font-family: monospace;
    font-size: 12px;
    color: #333;
    display: block;
  }
  .debug-info strong { color: #5d0d18; }

  .table-wrapper { overflow-x: auto; }
  table {
    width: 100%;
    border-collapse: collapse;
  }
  th, td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid #e8ddd0;
  }
  th {
    background: #fff9eb;
    font-weight: 600;
  }
  .empty-state {
    text-align: center;
    padding: 3rem;
    color: #6b5055;
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
    background: #5d0d18;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
  }
  .empty-state .btn-primary:hover { background: #3e0910; }

  .badge {
    padding: 0.25rem 0.65rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
  }
  .badge-success { background: #d4edda; color: #27ae60; }
  .badge-info { background: #d1ecf1; color: #2980b9; }
  .badge-warning { background: #fff3cd; color: #856404; }
  .badge-danger { background: #f8d7da; color: #c0392b; }

  .btn {
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-size: 0.8rem;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
  }
  .btn-wine { background: #5d0d18; color: white; }
  .btn-wine:hover { background: #3e0910; }
  .btn-success { background: #27ae60; color: white; }
  .btn-success:hover { background: #219653; }
  .btn-danger { background: #c0392b; color: white; }
  .btn-danger:hover { background: #a93226; }
  .btn-info { background: #2980b9; color: white; }
  .btn-info:hover { background: #1f6fa5; }

  .action-group { display: flex; gap: 0.5rem; flex-wrap: wrap; }

  .toast {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: #5d0d18;
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

<!-- 🔥 DEBUG INFO -->

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
  <!-- ONGLET COMMANDES CON GESTION   -->
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

</div>

<?php endif; ?>

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

function showToast(msg, isError = false) {
  const toast = document.getElementById('toast');
  toast.innerHTML = msg;
  if (isError) {
    toast.style.background = '#c0392b';
  } else {
    toast.style.background = '#5d0d18';
  }
  toast.classList.add('show');
  setTimeout(() => {
    toast.classList.remove('show');
    toast.style.background = '#5d0d18';
  }, 3000);
}

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

function voirDetails(id) {
  window.location.href = 'details_commande.php?id=' + id;
}
</script>
</body>
</html>