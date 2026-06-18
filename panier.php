<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

include("connexion.php");

#recuperer le panier du client depuis la BD
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GreenMarket – Mon Panier</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<style>
:root{
  --cream:#fff9eb;
  --sage:#9fb2ac;
  --sage-dark:#7a9490;
  --wine:#5d0d18;
  --wine-dark:#3e0910;
  --border:#e8ddd0;
  --white:#fffdf7;
  --text:#2a1a1c;
  --text-muted:#6b5055;
  --shadow:rgba(93,13,24,.12);
}
*{ margin:0; padding:0; box-sizing:border-box; }
body{ font-family:'Lato', sans-serif; background:var(--cream); color:var(--text); }
.topbar{ background:var(--wine); color:white; padding:.8rem 2rem; display:flex; justify-content:space-between; align-items:center; }
.topbar a{ color:white; text-decoration:none; font-size:.9rem; }
.topbar a:hover{ text-decoration:underline; }
.page-header{ background:var(--wine); color:white; padding:3rem 2rem; }
.page-header h1{ font-family:'Playfair Display', serif; font-size:3rem; }
.container{ max-width:1100px; margin:2rem auto; padding:0 1rem; }
.msg-success{ background:#d4edda; color:#155724; padding:10px 16px; border-radius:8px; margin-bottom:1rem; }
.msg-error{   background:#f8d7da; color:#721c24; padding:10px 16px; border-radius:8px; margin-bottom:1rem; }
.cart-layout{ display:grid; grid-template-columns:2fr 1fr; gap:2rem; }
.cart-items{ background:var(--white); border-radius:10px; padding:1.5rem; box-shadow:0 10px 30px var(--shadow); }
.cart-item{ display:flex; gap:1rem; padding:1rem 0; border-bottom:1px solid var(--border); }
.cart-item img{ width:110px; height:110px; object-fit:cover; border-radius:8px; }
.item-info{ flex:1; }
.item-name{ font-weight:700; color:var(--wine); font-size:1rem; }
.item-shop{ font-size:.8rem; color:var(--text-muted); margin:.3rem 0; }
.item-price{ font-size:1rem; font-weight:700; color:var(--sage-dark); }
.quantity-form{ display:flex; align-items:center; gap:.5rem; margin-top:.7rem; }
.quantity-form input[type=number]{ width:60px; padding:.3rem; border:1px solid var(--border); border-radius:4px; text-align:center; }
.btn-qte{ background:var(--wine); color:white; border:none; padding:.4rem .8rem; border-radius:4px; cursor:pointer; font-size:.85rem; }
.btn-delete{ background:#eee; color:var(--wine); border:none; padding:.5rem .8rem; border-radius:4px; cursor:pointer; margin-top:.8rem; font-size:.85rem; }
.btn-delete:hover{ background:#f8d7da; }
.summary{ background:var(--white); border-radius:10px; padding:1.5rem; height:fit-content; box-shadow:0 10px 30px var(--shadow); }
.summary h2{ font-family:'Playfair Display', serif; margin-bottom:1rem; }
.summary-line{ display:flex; justify-content:space-between; margin:.8rem 0; }
.total{ font-size:1.3rem; font-weight:700; color:var(--wine); border-top:1px solid var(--border); padding-top:1rem; }
.btn-checkout{ display:block; width:100%; margin-top:1.5rem; background:var(--wine); color:white; border:none; padding:1rem; border-radius:6px; font-weight:700; cursor:pointer; font-size:1rem; text-align:center; text-decoration:none; }
.btn-checkout:hover{ background:var(--wine-dark); }
.btn-clear{ display:block; width:100%; margin-top:1rem; background:var(--sage); color:white; border:none; padding:.8rem; border-radius:6px; cursor:pointer; font-size:.9rem; }
.btn-clear:hover{ background:var(--sage-dark); }
.empty{ text-align:center; padding:4rem; background:var(--white); border-radius:10px; }
.btn-back{ background:var(--wine); color:white; padding:.6rem 1.2rem; border-radius:4px; text-decoration:none; font-size:.9rem; display:inline-block; margin-top:1rem; }
@media(max-width:850px){ .cart-layout{ grid-template-columns:1fr; } .cart-item{ flex-direction:column; } .cart-item img{ width:100%; height:200px; } }
</style>
</head>
<body>

<div class="topbar">
  <span>👋 <?php echo htmlspecialchars($_SESSION['user_nom']); ?></span>
  <div>
    <a href="produits.php">🛍️ Produits</a> &nbsp;|&nbsp;
    <a href="dashboard_client.php">Mon espace</a> &nbsp;|&nbsp;
    <a href="logout.php">Se déconnecter</a>
  </div>
</div>

<div class="page-header">
  <h1>🛒 Mon Panier</h1>
  <p>Retrouvez tous vos produits sélectionnés</p>
</div>

<div class="container">
  <?php if (isset($_GET['msgs']))   echo "<div class='msg-success'>" . htmlspecialchars($_GET['msgs'])   . "</div>"; ?>
  <?php if (isset($_GET['msgerr'])) echo "<div class='msg-error'>"   . htmlspecialchars($_GET['msgerr']) . "</div>"; ?>

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
            <img src="<?php echo htmlspecialchars($item['photo_url'] ?? ''); ?>"
                 alt="<?php echo htmlspecialchars($item['nom_produit']); ?>"
                 onerror="this.src='https://placehold.co/110x110?text=Produit'">
            <div class="item-info">
              <div class="item-name"><?php echo htmlspecialchars($item['nom_produit']); ?></div>
              <div class="item-shop">Boutique : <?php echo htmlspecialchars($item['nom_boutique']); ?></div>
              <div class="item-price"><?php echo number_format($item['prix_unitaire'], 2); ?> DH</div>

              <form method="POST" class="quantity-form">
                <input type="hidden" name="action" value="modifier_qte">
                <input type="hidden" name="id_produit" value="<?php echo $item['id_produit']; ?>">
                <input type="number" name="quantite" value="<?php echo $item['quantite']; ?>" min="1">
                <button type="submit" class="btn-qte">Mettre à jour</button>
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
        <h2>Résumé</h2>
        <div class="summary-line">
          <span>Produits</span>
          <span><?php echo count($panier); ?></span>
        </div>
        <div class="summary-line total">
          <span>Total</span>
          <span><?php echo number_format($total, 2); ?> DH</span>
        </div>
        <a href="checkout.php" class="btn-checkout">✅ Passer la commande</a>
        <form method="POST">
          <input type="hidden" name="action" value="vider">
          <button type="submit" class="btn-clear"
            onclick="return confirm('Vider tout le panier ?')">🗑️ Vider le panier</button>
        </form>
      </div>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
