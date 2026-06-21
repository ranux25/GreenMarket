<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

include("connexion.php");

// Detectar tema guardado (por defecto claro)
$theme = $_COOKIE['theme'] ?? 'light';

#recuperar el panier del cliente desde la BD
try {
    $req = $pdo->prepare("SELECT pa.quantite, p.id_produit, p.nom_produit, p.prix_unitaire, 
                           p.photo_url, b.nom_boutique, b.id_boutique
                           FROM panier pa
                           JOIN produit p ON pa.id_produit = p.id_produit
                           JOIN boutique b ON p.id_boutique = b.id_boutique
                           WHERE pa.id_client = ?");
    $req->execute([$_SESSION['user_id']]);
    $panier = $req->fetchAll(PDO::FETCH_ASSOC);
}
catch(PDOException $e) { die("Erreur chargement panier : " . $e->getMessage()); }

#traitement des actions sur le panier
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    extract($_POST);
    $action = $action ?? '';

    #supprimer un article
    if ($action == 'supprimer' && isset($id_produit)) {
        try {
            $req = $pdo->prepare("DELETE FROM panier WHERE id_client = ? AND id_produit = ?");
            $req->execute([$_SESSION['user_id'], $id_produit]);
        }
        catch(PDOException $e) { die("Erreur suppression panier : " . $e->getMessage()); }
        header("Location: panier.php?msgs=Produit supprimé du panier");
        exit;
    }

    #modifier la quantite
    if ($action == 'modifier_qte' && isset($id_produit) && isset($quantite)) {
        $quantite = intval($quantite);
        if ($quantite < 1) $quantite = 1;
        try {
            $req = $pdo->prepare("UPDATE panier SET quantite = ? WHERE id_client = ? AND id_produit = ?");
            $req->execute([$quantite, $_SESSION['user_id'], $id_produit]);
        }
        catch(PDOException $e) { die("Erreur modification quantite : " . $e->getMessage()); }
        header("Location: panier.php");
        exit;
    }

    #vider le panier
    if ($action == 'vider') {
        try {
            $req = $pdo->prepare("DELETE FROM panier WHERE id_client = ?");
            $req->execute([$_SESSION['user_id']]);
        }
        catch(PDOException $e) { die("Erreur vidage panier : " . $e->getMessage()); }
        header("Location: panier.php?msgs=Panier vidé avec succès");
        exit;
    }
}

#calcul du total
$total = 0;
foreach ($panier as $item) {
    $total += $item['prix_unitaire'] * $item['quantite'];
}

#calcul du nombre total d'articles
$total_items = 0;
foreach ($panier as $item) {
    $total_items += $item['quantite'];
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $theme; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GreenMarket – Mon Panier</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
/* ========== VARIABLES DE TEMA GLOBAL ========== */
:root {
  --primary: #5D0D18;
  --primary-light: #7a1020;
  --secondary: #9FB2AC;
  --secondary-dark: #7a9490;
  --gold: #c07a1a;
  --bg: #FFF9EB;
  --bg-light: #f5f0e8;
  --bg-card: #ffffff;
  --bg-input: #ffffff;
  --text-dark: #2C2C2C;
  --text-light: #6B6B6B;
  --text-muted: #6B5055;
  --border-color: #e8ddd0;
  --shadow-color: rgba(93,13,24,0.12);
  --shadow-hover: rgba(93,13,24,0.18);
  --success: #155724;
  --success-bg: #d4edda;
  --danger: #721c24;
  --danger-bg: #f8d7da;
  --wine: #5d0d18;
  --wine-dark: #3e0910;
  --white: #fffdf7;
  --page-header-bg: var(--primary);
  --page-header-text: #fff;
  --page-header-sub: rgba(255,249,235,.7);
  --page-header-border: rgba(255,249,235,.05);
  --page-header-border-top: rgba(255,249,235,.15);
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
  --text-dark: #f0e6d8;
  --text-light: #b8a896;
  --text-muted: #b8a896;
  --border-color: #5a4a3a;
  --shadow-color: rgba(0,0,0,0.3);
  --shadow-hover: rgba(0,0,0,0.4);
  --success: #81c784;
  --success-bg: #1a3d2a;
  --danger: #ef9a9a;
  --danger-bg: #3d1a1a;
  --wine: #1a1410;
  --wine-dark: #0a0806;
  --white: #3d3229;
  --page-header-bg: #1a1410;
  --page-header-text: #f0e6d8;
  --page-header-sub: rgba(240,230,216,0.6);
  --page-header-border: rgba(240,230,216,0.05);
  --page-header-border-top: rgba(240,230,216,0.15);
}

*{ margin:0; padding:0; box-sizing:border-box; }
body{ 
  font-family:'Lato', sans-serif; 
  background:var(--bg); 
  color:var(--text-dark);
  min-height:100vh;
  transition: background-color 0.3s ease, color 0.3s ease;
}

/* ========== PAGE HEADER ========== */
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

/* ========== ESTILOS DE LA PÁGINA ========== */
.container{ max-width:1100px; margin:2rem auto; padding:0 1.5rem; }

/* Alertas */
.msg-success{ 
  background: var(--success-bg); 
  color: var(--success); 
  padding: 1rem 1.2rem; 
  border-radius: 8px; 
  margin-bottom:1rem;
  font-weight: 600;
  border: 1px solid var(--border-color);
  transition: background 0.3s, color 0.3s;
}
.msg-error{   
  background: var(--danger-bg); 
  color: var(--danger); 
  padding: 1rem 1.2rem; 
  border-radius: 8px; 
  margin-bottom:1rem;
  font-weight: 600;
  border: 1px solid var(--border-color);
  transition: background 0.3s, color 0.3s;
}

.cart-layout{ display:grid; grid-template-columns:2fr 1fr; gap:2rem; }
.cart-items{ 
  background: var(--bg-card); 
  border-radius: 10px; 
  padding:1.5rem; 
  box-shadow: 0 10px 30px var(--shadow-color);
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
}
.cart-item{ display:flex; gap:1rem; padding:1rem 0; border-bottom:1px solid var(--border-color); transition: border-color 0.3s; }
.cart-item:last-child{ border-bottom:none; }
.cart-item img{ 
  width:110px; 
  height:110px; 
  object-fit:cover; 
  border-radius:8px; 
  background: var(--bg-light);
  border: 1px solid var(--border-color);
  transition: background 0.3s, border-color 0.3s;
}
.item-info{ flex:1; }
.item-name{ 
  font-weight:700; 
  color: var(--text-dark); 
  font-size:1rem;
  transition: color 0.3s;
}
[data-theme="dark"] .item-name { color: var(--gold); }
.item-shop{ 
  font-size:.8rem; 
  color:var(--text-light); 
  margin:.3rem 0;
  transition: color 0.3s;
}
.item-price{ 
  font-size:1rem; 
  font-weight:700; 
  color: var(--primary);
  transition: color 0.3s;
}
[data-theme="dark"] .item-price { color: var(--gold); }

.quantity-form{ display:flex; align-items:center; gap:.5rem; margin-top:.7rem; flex-wrap:wrap; }
.quantity-form input[type=number]{ 
  width:60px; 
  padding:.3rem; 
  border:1.5px solid var(--border-color); 
  border-radius:6px; 
  text-align:center;
  background: var(--bg-input);
  color: var(--text-dark);
  transition: all 0.3s;
}
.quantity-form input[type=number]:focus { 
  outline: none;
  border-color: var(--primary);
}

.btn-qte{ 
  background: var(--primary); 
  color: #fff; 
  border:none; 
  padding:.4rem .8rem; 
  border-radius:6px; 
  cursor:pointer; 
  font-size:.85rem;
  font-weight: 600;
  transition: background 0.2s, transform 0.2s;
}
.btn-qte:hover{ 
  background: var(--primary-light); 
  transform: translateY(-2px);
}
[data-theme="dark"] .btn-qte { color: var(--bg); }

.btn-delete{ 
  background: var(--bg-light); 
  color: var(--text-dark); 
  border: 1px solid var(--border-color);
  padding:.5rem .8rem; 
  border-radius:6px; 
  cursor:pointer; 
  margin-top:.8rem; 
  font-size:.85rem;
  transition: all 0.2s;
}
.btn-delete:hover{ 
  background: var(--danger-bg); 
  color: var(--danger);
  border-color: var(--danger);
}

.summary{ 
  background: var(--bg-card); 
  border-radius:10px; 
  padding:1.5rem; 
  height:fit-content; 
  box-shadow:0 10px 30px var(--shadow-color);
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
}
.summary h2{ 
  font-family:'Playfair Display', serif; 
  margin-bottom:1rem; 
  color: var(--text-dark);
  transition: color 0.3s;
}
.summary-line{ 
  display:flex; 
  justify-content:space-between; 
  margin:.8rem 0;
  color: var(--text-light);
  transition: color 0.3s;
}
.total{ 
  font-size:1.3rem; 
  font-weight:700; 
  color: var(--primary);
  border-top:1px solid var(--border-color); 
  padding-top:1rem;
  transition: color 0.3s, border-color 0.3s;
}
[data-theme="dark"] .total { color: var(--gold); }

.btn-checkout{ 
  display:block; 
  width:100%; 
  margin-top:1.5rem; 
  background: var(--primary); 
  color:#fff; 
  border:none; 
  padding:1rem; 
  border-radius:8px; 
  font-weight:700; 
  cursor:pointer; 
  font-size:1rem; 
  text-align:center; 
  text-decoration:none;
  transition: background 0.3s, transform 0.2s;
}
.btn-checkout:hover{ 
  background: var(--primary-light); 
  transform: translateY(-2px);
}
[data-theme="dark"] .btn-checkout { color: var(--bg); }

.btn-clear{ 
  display:block; 
  width:100%; 
  margin-top:1rem; 
  background: var(--secondary); 
  color:#fff; 
  border:none; 
  padding:.8rem; 
  border-radius:8px; 
  cursor:pointer; 
  font-size:.9rem;
  transition: background 0.3s, transform 0.2s;
}
.btn-clear:hover{ 
  background: var(--secondary-dark); 
  transform: translateY(-2px);
}

.empty{ 
  text-align:center; 
  padding:4rem 2rem; 
  background: var(--bg-card);
  border-radius:10px; 
  border: 1px solid var(--border-color);
  box-shadow: 0 10px 30px var(--shadow-color);
  transition: all 0.3s;
}
.empty h2 { 
  font-family: 'Playfair Display', serif;
  color: var(--text-dark);
  margin-bottom: 0.5rem;
  transition: color 0.3s;
}
.empty p { 
  color: var(--text-light);
  margin-bottom: 1rem;
  transition: color 0.3s;
}

.btn-back{ 
  background: var(--primary); 
  color:#fff; 
  padding:.6rem 1.2rem; 
  border-radius:8px; 
  text-decoration:none; 
  font-size:.9rem; 
  display:inline-block; 
  margin-top:1rem;
  transition: background 0.3s, transform 0.2s;
}
.btn-back:hover{ 
  background: var(--primary-light); 
  transform: translateY(-2px);
}
[data-theme="dark"] .btn-back { color: var(--bg); }

@media(max-width:850px){ 
  .cart-layout{ grid-template-columns:1fr; } 
  .cart-item{ flex-direction:column; } 
  .cart-item img{ width:100%; height:200px; } 
}
@media(max-width:640px) {
  .page-header { padding: 2.5rem 1.2rem 2rem; }
  .container { padding: 0 1rem; }
  .header-stats {
    gap: 1.5rem;
    flex-wrap: wrap;
  }
}
</style>
</head>
<body>

<!-- ========== INCLUIR EL HEADER ========== -->
<?php include 'header.php'; ?>

<!-- ========== PAGE HEADER ========== -->
<div class="page-header">
  <div class="header-inner">
    <div class="header-eyebrow">🇲🇦 Artisanat &amp; Traditions marocaines</div>
    <h1>Mon Panier</h1>
    <p>Retrouvez tous vos produits sélectionnés</p>
  </div>
</div>

<div class="container">
  <?php if (isset($_GET['msgs']))   echo "<div class='msg-success'>✅ " . htmlspecialchars($_GET['msgs'])   . "</div>"; ?>
  <?php if (isset($_GET['msgerr'])) echo "<div class='msg-error'>❌ "   . htmlspecialchars($_GET['msgerr']) . "</div>"; ?>

  <?php if (empty($panier)): ?>
    <div class="empty">
      <h2>🛒 Votre panier est vide</h2>
      <p>Ajoutez des produits depuis la boutique pour commencer.</p>
      <a href="produits.php" class="btn-back">← Continuer les achats</a>
    </div>

  <?php else: ?>
    <div class="cart-layout">
      <div class="cart-items">
        <?php foreach ($panier as $item): ?>
          <div class="cart-item">
            <img src="<?php echo htmlspecialchars($item['photo_url'] ?? 'IMAGES/default-product.jpg'); ?>"
                 alt="<?php echo htmlspecialchars($item['nom_produit']); ?>"
                 onerror="this.src='IMAGES/default-product.jpg'">
            <div class="item-info">
              <div class="item-name"><?php echo htmlspecialchars($item['nom_produit']); ?></div>
              <div class="item-shop">🏪 Boutique : <?php echo htmlspecialchars($item['nom_boutique']); ?></div>
              <div class="item-price"><?php echo number_format($item['prix_unitaire'], 2); ?> DH</div>

              <form method="POST" class="quantity-form">
                <input type="hidden" name="action" value="modifier_qte">
                <input type="hidden" name="id_produit" value="<?php echo $item['id_produit']; ?>">
                <input type="number" name="quantite" value="<?php echo $item['quantite']; ?>" min="1">
                <button type="submit" class="btn-qte">🔄 Mettre à jour</button>
              </form>

              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="supprimer">
                <input type="hidden" name="id_produit" value="<?php echo $item['id_produit']; ?>">
                <button type="submit" class="btn-delete"
                  onclick="return confirm('Supprimer ce produit du panier ?')">🗑️ Supprimer</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="summary">
        <h2>📋 Résumé</h2>
        <div class="summary-line">
          <span>🛍️ Produits</span>
          <span><?php echo count($panier); ?></span>
        </div>
        <div class="summary-line">
          <span>📦 Articles</span>
          <span><?php echo $total_items; ?></span>
        </div>
        <div class="summary-line total">
          <span>💰 Total</span>
          <span><?php echo number_format($total, 2); ?> DH</span>
        </div>
        <a href="checkout.php" class="btn-checkout">✅ Passer la commande</a>
        <form method="POST">
          <input type="hidden" name="action" value="vider">
          <button type="submit" class="btn-clear"
            onclick="return confirm('⚠️ Vider tout le panier ?')">🗑️ Vider le panier</button>
        </form>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
</body>
</html>