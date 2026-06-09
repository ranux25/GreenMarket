<?php
session_start();

// Verificar que el usuario está conectado y es producteur
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'producteur') {
    header('Location: signin.php');
    exit;
}

// Conectar a la base de datos
require_once 'connexion.php';

// Verificar si el productor está validado
$est_valide = $_SESSION['est_valide'] ?? 0;

// Si no está validado, mostrar mensaje de espera
if ($est_valide != 1) {
    // Intentar obtener el estado actual desde la BD
    try {
        $stmt = $pdo->prepare("SELECT est_valide_par_admin FROM producteur WHERE id_producteur = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        if ($result && $result['est_valide_par_admin'] == 1) {
            $est_valide = 1;
            $_SESSION['est_valide'] = 1;
        }
    } catch(PDOException $e) {
        // Ignorar error
    }
}

// Obtener datos reales del producteur desde la BD
try {
    // Obtener información del producteur
    $stmt = $pdo->prepare("SELECT * FROM producteur WHERE id_producteur = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $producteur = $stmt->fetch();
    
    if (!$producteur) {
        session_destroy();
        header('Location: signin.php');
        exit;
    }
    
    // Si el estado de validación ha cambiado, actualizar sesión
    if ($producteur['est_valide_par_admin'] != $est_valide) {
        $est_valide = $producteur['est_valide_par_admin'];
        $_SESSION['est_valide'] = $est_valide;
    }
    
    // Solo mostrar datos si está validado
    if ($est_valide == 1) {
        // Obtener boutiques del producteur
        $stmt = $pdo->prepare("SELECT * FROM boutique WHERE id_producteur = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $boutiques = $stmt->fetchAll();
        
        // Obtener productos de las boutiques
        if (!empty($boutiques)) {
            $boutiqueIds = array_column($boutiques, 'id_boutique');
            $placeholders = implode(',', array_fill(0, count($boutiqueIds), '?'));
            $stmt = $pdo->prepare("SELECT p.*, c.nom_categorie FROM produit p 
                                   LEFT JOIN categorie c ON p.id_categorie = c.id_categorie
                                   WHERE p.id_boutique IN ($placeholders)");
            $stmt->execute($boutiqueIds);
            $produits = $stmt->fetchAll();
        } else {
            $produits = [];
        }
        
        // Obtener comandos de los productos
        $commandes = [];
        if (!empty($produits)) {
            $produitIds = array_column($produits, 'id_produit');
            $placeholders = implode(',', array_fill(0, count($produitIds), '?'));
            $stmt = $pdo->prepare("SELECT c.*, cl.nom_client, co.quantite, co.prix_unitaire, p.nom_produit
                                   FROM commande c
                                   JOIN client cl ON c.id_client = cl.id_client
                                   JOIN contenir co ON c.id_commande = co.id_commande
                                   JOIN produit p ON co.id_produit = p.id_produit
                                   WHERE p.id_produit IN ($placeholders)
                                   ORDER BY c.date_commande DESC");
            $stmt->execute($produitIds);
            $commandes = $stmt->fetchAll();
        }
        
        // Calcular estadísticas
        $ca_total = array_sum(array_column($commandes, 'montant_total'));
        $nb_commandes = count($commandes);
        $nb_boutiques = count($boutiques);
        $nb_produits = count($produits);
    } else {
        // Si no está validado, arrays vacíos
        $boutiques = [];
        $produits = [];
        $commandes = [];
        $ca_total = 0;
        $nb_commandes = 0;
        $nb_boutiques = 0;
        $nb_produits = 0;
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

  .badge {
    padding: 0.25rem 0.65rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
  }
  .badge-success { background: #d4edda; color: #27ae60; }
  .badge-info { background: #d1ecf1; color: #2980b9; }
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
  .btn-info { background: #2980b9; color: white; }

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

<!-- INCLUIR EL HEADER -->
<?php include 'header.php'; ?>

<!-- Mensaje de espera si no está validado -->
<?php if ($est_valide != 1): ?>
<div class="alert-warning">
  <i class="bi bi-hourglass-split" style="font-size: 1.2rem;"></i>
  <strong>⚠️ Compte en attente de validation</strong>
  <p style="margin: 0;">Votre compte producteur est en attente de validation par un administrateur. Vous recevrez une notification par email une fois votre compte activé.</p>
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
  <!-- Onglet Aperçu -->
  <div class="tab-panel active" id="tab-apercu">
    <div class="section-card">
      <div class="section-header">📈 Mes Statistiques</div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr><th>Indicateur</th><th>Valeur</th></tr>
          </thead>
          <tbody>
            <tr><td>Total produits vendus</td><td><strong><?php echo array_sum(array_column($commandes, 'quantite')); ?></strong></td></tr>
            <tr><td>Commandes traitées</td><td><strong><?php echo $nb_commandes; ?></strong></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Onglet Boutiques -->
  <div class="tab-panel" id="tab-boutiques">
    <div class="section-card">
      <div class="section-header">
        🏪 Mes Boutiques
        <span class="badge-count"><?php echo $nb_boutiques; ?> boutique(s)</span>
      </div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Nom</th><th>Description</th><th>Date création</th></tr></thead>
          <tbody>
            <?php if (empty($boutiques)): ?>
              <tr><td colspan="3" class="empty-state">Vous n'avez pas encore créé de boutique.</td></tr>
            <?php else: ?>
              <?php foreach ($boutiques as $boutique): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($boutique['nom_boutique']); ?></strong></td>
                <td><?php echo htmlspecialchars($boutique['description'] ?? 'Boutique artisanale'); ?></td>
                <td><?php echo date('d/m/Y', strtotime($boutique['date_creation'])); ?></td>
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
        📦 Mes Produits
        <span class="badge-count"><?php echo $nb_produits; ?> produit(s)</span>
      </div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Nom</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>Status</th></tr></thead>
          <tbody>
            <?php if (empty($produits)): ?>
              <tr><td colspan="5" class="empty-state">Aucun produit dans votre catalogue.</td></tr>
            <?php else: ?>
              <?php foreach ($produits as $produit): ?>
              <tr>
                <td><strong><?php echo htmlspecialchars($produit['nom_produit']); ?></strong></td>
                <td><?php echo htmlspecialchars($produit['nom_categorie'] ?? 'Non catégorisé'); ?></td>
                <td><?php echo number_format($produit['prix_unitaire'], 2); ?> DH</td>
                <td><span class="badge <?php echo $produit['stock_quantite'] <= 5 ? 'badge-danger' : 'badge-success'; ?>"><?php echo $produit['stock_quantite']; ?></span></td>
                <td><span class="badge badge-info"><?php echo $produit['statut_publie'] ?? 'Publié'; ?></span></td>
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
        🛒 Commandes Reçues
        <span class="badge-count"><?php echo $nb_commandes; ?> commande(s)</span>
      </div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>N° Commande</th><th>Client</th><th>Produit</th><th>Qté</th><th>Total</th><th>Date</th><th>Statut</th></tr></thead>
          <tbody>
            <?php if (empty($commandes)): ?>
              <tr><td colspan="7" class="empty-state">Aucune commande reçue pour le moment.</td></tr>
            <?php else: ?>
              <?php foreach ($commandes as $commande): ?>
              <tr>
                <td><strong>#<?php echo $commande['id_commande']; ?></strong></td>
                <td><?php echo htmlspecialchars($commande['nom_client']); ?></td>
                <td><?php echo htmlspecialchars($commande['nom_produit']); ?></td>
                <td><?php echo $commande['quantite']; ?></td>
                <td><strong><?php echo number_format($commande['montant_total'], 2); ?> DH</strong></td>
                <td><?php echo date('d/m/Y', strtotime($commande['date_commande'])); ?></td>
                <td><span class="badge badge-<?php echo $commande['statut_commande'] === 'Livrée' ? 'success' : 'info'; ?>"><?php echo $commande['statut_commande']; ?></span></td>
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

function showToast(msg) {
  const toast = document.getElementById('toast');
  toast.innerHTML = msg;
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3000);
}
</script>
</body>
</html>