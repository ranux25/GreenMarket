<?php
session_start();

#verifier que l'utilisateur est connecte et est client
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'client') {
    header('Location: signin.php');
    exit;
}

#connexion a la base de donnees
include('connexion.php');

#recuperer les donnees du client depuis la BD
try {
    $stmt = $pdo->prepare("SELECT * FROM client WHERE id_client = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $client = $stmt->fetch();
    
    if (!$client) {
        session_destroy();
        header('Location: signin.php');
        exit;
    }
    
    #recuperer les commandes du client
    $stmt = $pdo->prepare("SELECT * FROM commande WHERE id_client = ? ORDER BY date_commande DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $commandes = $stmt->fetchAll();
    
    #recuperer les favoris
    $stmt = $pdo->prepare("SELECT p.* FROM produit p 
                           JOIN favoris f ON p.id_produit = f.id_produit 
                           WHERE f.id_client = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $favoris = $stmt->fetchAll();
    
    #recuperer les articles du panier
    $stmt = $pdo->prepare("SELECT p.*, pa.quantite FROM panier pa 
                           JOIN produit p ON pa.id_produit = p.id_produit 
                           WHERE pa.id_client = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $panier = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Error dashboard client: " . $e->getMessage());
    $commandes = [];
    $favoris = [];
    $panier = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GreenMarket – Mon Espace Client</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  /* Styles spécifiques au dashboard client */
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
  }
  .badge-count {
    background: rgba(255,255,255,0.2);
    border-radius: 20px;
    padding: 2px 10px;
    font-size: 0.8rem;
  }

  .table-wrapper { overflow-x: auto; }
  table { width: 100%; border-collapse: collapse; }
  th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #e8ddd0; }
  th { background: #fff9eb; font-weight: 600; }
  .empty-state { text-align: center; padding: 3rem; color: #6b5055; }

  .badge {
    padding: 0.25rem 0.65rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 600;
  }
  .badge-success { background: #d4edda; color: #27ae60; }
  .badge-info { background: #d1ecf1; color: #2980b9; }

  .btn {
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-size: 0.8rem;
    border: none;
    cursor: pointer;
  }
  .btn-wine { background: #5d0d18; color: white; }
  .btn-wine:hover { background: #3e0910; }

  .form-group { margin-bottom: 1rem; }
  .form-group label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.3rem;
    color: #6b5055;
  }
  .form-control {
    width: 100%;
    padding: 0.6rem;
    border: 1px solid #e8ddd0;
    border-radius: 6px;
  }
  .profile-form { padding: 1.5rem; }

  .favorites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
    padding: 1.5rem;
  }
  .fav-card {
    border: 1px solid #e8ddd0;
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
  }
  .fav-name { font-weight: 600; margin-bottom: 0.5rem; }
  .fav-price { color: #5d0d18; font-weight: 700; }

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
  .toast.show { transform: translateY(0); opacity: 1; }

  @media (max-width: 768px) {
    .dashboard-grid, .tabs-container, .main-content { padding: 1rem; }
  }
</style>
</head>
<body>

<!-- INCLUIR EL HEADER -->
<?php include 'header.php'; ?>

<!-- DASHBOARD CONTENT -->
<div class="dashboard-grid">
  <div class="stat-card">
    <div class="stat-icon">📦</div>
    <div class="stat-val"><?php echo count($commandes); ?></div>
    <div class="stat-label">Commandes</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">❤️</div>
    <div class="stat-val"><?php echo count($favoris); ?></div>
    <div class="stat-label">Favoris</div>
  </div>
  <div class="stat-card">
    <div class="stat-icon">🛒</div>
    <div class="stat-val"><?php echo array_sum(array_column($panier, 'quantite')); ?></div>
    <div class="stat-label">Panier</div>
  </div>
</div>

<div class="tabs-container">
  <div class="tabs">
    <button class="tab-btn active" data-tab="commandes">📦 Mes Commandes</button>
    <button class="tab-btn" data-tab="favoris">❤️ Favoris</button>
    <button class="tab-btn" data-tab="profil">👤 Mon Profil</button>
  </div>
</div>

<div class="main-content">
  <!-- Onglet Commandes -->
  <div class="tab-panel active" id="tab-commandes">
    <div class="section-card">
      <div class="section-header">📦 Mes Commandes</div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>N° Commande</th><th>Date</th><th>Total</th><th>Statut</th></tr></thead>
          <tbody>
            <?php if (empty($commandes)): ?>
              <tr><td colspan="4" class="empty-state">Aucune commande pour le moment.</td></tr>
            <?php else: ?>
              <?php foreach ($commandes as $commande): ?>
              <tr>
                <td><strong>#<?php echo $commande['id_commande']; ?></strong></td>
                <td><?php echo date('d/m/Y', strtotime($commande['date_commande'])); ?></td>
                <td><strong><?php echo number_format($commande['montant_total'], 2); ?> DH</strong></td>
                <td><span class="badge badge-<?php echo $commande['statut_commande'] === 'Livrée' ? 'success' : 'info'; ?>"><?php echo $commande['statut_commande']; ?></span></td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Onglet Favoris -->
  <div class="tab-panel" id="tab-favoris">
    <div class="section-card">
      <div class="section-header">❤️ Mes Produits Favoris</div>
      <div class="favorites-grid">
        <?php if (empty($favoris)): ?>
          <div class="empty-state" style="grid-column:1/-1;">Aucun produit favori.</div>
        <?php else: ?>
          <?php foreach ($favoris as $fav): ?>
          <div class="fav-card">
            <div class="fav-name"><?php echo htmlspecialchars($fav['nom_produit']); ?></div>
            <div class="fav-price"><?php echo number_format($fav['prix_unitaire'], 2); ?> DH</div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Onglet Profil -->
  <div class="tab-panel" id="tab-profil">
    <div class="section-card">
      <div class="section-header">👤 Mon Profil</div>
      <div class="profile-form">
        <div class="form-group"><label>Nom complet</label><input type="text" id="profileName" value="<?php echo htmlspecialchars($client['nom_client']); ?>" class="form-control"></div>
        <div class="form-group"><label>Email</label><input type="email" id="profileEmail" value="<?php echo htmlspecialchars($client['email']); ?>" class="form-control"></div>
        <div class="form-group"><label>Téléphone</label><input type="tel" id="profilePhone" placeholder="+212 6XX XX XX XX" class="form-control"></div>
        <div class="form-group"><label>Adresse</label><textarea id="profileAddress" rows="3" class="form-control" placeholder="Votre adresse complète"></textarea></div>
        <button class="btn btn-wine" onclick="saveProfile()">💾 Enregistrer</button>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
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

function saveProfile() {
  showToast('✅ Profil mis à jour avec succès !');
}
</script>
</body>
</html>