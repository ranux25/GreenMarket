<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header("Location: signin.php");
    exit;
}

include("connexion.php");

// Recuperar el carrito del cliente desde la BD (incluyendo id_producteur)
try {
    $req = $pdo->prepare("
        SELECT pa.quantite, p.id_produit, p.nom_produit, p.prix_unitaire,
               p.photo_url, p.stock_quantite, b.nom_boutique, b.id_boutique,
               b.id_producteur, pr.nom_entreprise
        FROM panier pa
        JOIN produit p ON pa.id_produit = p.id_produit
        JOIN boutique b ON p.id_boutique = b.id_boutique
        JOIN producteur pr ON b.id_producteur = pr.id_producteur
        WHERE pa.id_client = ?
    ");
    $req->execute([$_SESSION['user_id']]);
    $panier = $req->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) { 
    die("Erreur chargement panier : " . $e->getMessage()); 
}

if (empty($panier)) {
    header("Location: panier.php?msgerr=Votre panier est vide");
    exit;
}

// Calcular el total
$sous_total = 0;
foreach ($panier as $item) {
    $sous_total += $item['prix_unitaire'] * $item['quantite'];
}

$err = [];
$success = false;
$numero_commande = '';
$frais_livraison = 250;

// Tratamiento de la commande
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $prenom = trim($_POST['prenom'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $mode_livraison = $_POST['mode_livraison'] ?? 'livraison';
    $mode_paiement = $_POST['mode_paiement'] ?? 'carte';
    $notes = trim($_POST['notes'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $ville = trim($_POST['ville'] ?? '');
    $code_postal = trim($_POST['code_postal'] ?? '');

    // Validación
    if (empty($prenom)) $err['prenom'] = "Veuillez entrer votre prénom";
    if (empty($nom)) $err['nom'] = "Veuillez entrer votre nom";
    if (empty($email)) $err['email'] = "Veuillez entrer votre email";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err['email'] = "Email invalide";
    if (empty($telephone)) $err['telephone'] = "Veuillez entrer votre téléphone";

    if ($mode_livraison == 'livraison') {
        if (empty($adresse)) $err['adresse'] = "Veuillez entrer votre adresse";
        if (empty($ville)) $err['ville'] = "Veuillez entrer votre ville";
        if (empty($code_postal)) $err['code_postal'] = "Veuillez entrer votre code postal";
    }

    $frais_livraison = ($mode_livraison == 'livraison') ? 250 : 0;
    $montant_total = $sous_total + $frais_livraison;

    if (empty($err)) {
        try {
            // Construir dirección
            if ($mode_livraison == 'livraison') {
                $adresse_livraison = htmlspecialchars($adresse) . ', ' . 
                                    htmlspecialchars($code_postal) . ' ' . 
                                    htmlspecialchars($ville);
            } else {
                $adresse_livraison = 'Retrait en boutique';
            }

            // Insertar la commande
            $ri = $pdo->prepare("
                INSERT INTO commande 
                (id_client, date_commande, montant_total, statut_commande, adresse_livraison) 
                VALUES (?, NOW(), ?, 'En attente', ?)
            ");
            $ri->execute([$_SESSION['user_id'], $montant_total, $adresse_livraison]);
            
            $id_commande = $pdo->lastInsertId();
            $numero_commande = 'CMD-' . str_pad($id_commande, 8, '0', STR_PAD_LEFT);

            // Insertar productos
            foreach ($panier as $item) {
                $ri2 = $pdo->prepare("
                    INSERT INTO contenir (id_commande, id_produit, quantite, prix_unitaire) 
                    VALUES (?, ?, ?, ?)
                ");
                $ri2->execute([$id_commande, $item['id_produit'], $item['quantite'], $item['prix_unitaire']]);

                // Actualizar stock
                $ri3 = $pdo->prepare("
                    UPDATE produit SET stock_quantite = stock_quantite - ? 
                    WHERE id_produit = ?
                ");
                $ri3->execute([$item['quantite'], $item['id_produit']]);
            }

            // ================================================================
            // 🔥 ENVIAR NOTIFICACIONES A LOS PRODUCTORES
            // ================================================================
            // Agrupar por productor para saber cuántos artículos compró de cada uno
            $stmtNotif = $pdo->prepare("
                SELECT DISTINCT p.id_producteur, p.nom_entreprise, 
                       COALESCE(SUM(co.quantite), 0) as total_articles
                FROM contenir co
                JOIN produit pr ON co.id_produit = pr.id_produit
                JOIN boutique b ON pr.id_boutique = b.id_boutique
                JOIN producteur p ON b.id_producteur = p.id_producteur
                WHERE co.id_commande = ?
                GROUP BY p.id_producteur, p.nom_entreprise
            ");
            $stmtNotif->execute([$id_commande]);
            $producteurs = $stmtNotif->fetchAll();

            // Enviar una notificación a cada productor
            $nom_client = htmlspecialchars($_SESSION['user_nom'] ?? 'Un client');
            $numero_cmd = str_pad($id_commande, 6, '0', STR_PAD_LEFT);
            
            foreach ($producteurs as $prod) {
                // Mensaje con formato claro
                $message = "🛒 Nouvelle commande de {$nom_client} !\n";
                $message .= "Commande #{$numero_cmd}\n";
                $message .= "Total : {$prod['total_articles']} article(s)\n";
                $message .= "Merci de traiter cette commande rapidement.";
                
                $stmtNotifInsert = $pdo->prepare("
                    INSERT INTO notification (id_producteur, type_notification, message, date_notification, est_lu) 
                    VALUES (?, 'order', ?, NOW(), 0)
                ");
                $stmtNotifInsert->execute([$prod['id_producteur'], $message]);
            }

            // ================================================================
            // 🔥 NOTIFICACIÓN PARA EL CLIENTE
            // ================================================================
            $message_client = "✅ Votre commande #{$numero_cmd} a été confirmée !\n";
            $message_client .= "Total : " . number_format($montant_total, 2) . " DH\n";
            $message_client .= "Merci pour votre achat sur GreenMarket 🌿";
            
            $stmtNotifClient = $pdo->prepare("
                INSERT INTO notification (id_client, type_notification, message, date_notification, est_lu) 
                VALUES (?, 'order', ?, NOW(), 0)
            ");
            $stmtNotifClient->execute([$_SESSION['user_id'], $message_client]);

            // Vaciar carrito
            $pdo->prepare("DELETE FROM panier WHERE id_client = ?")->execute([$_SESSION['user_id']]);

            $success = true;

        } catch(PDOException $e) { 
            $err['general'] = "Erreur insertion commande : " . $e->getMessage();
        }
    }
}

$total_items = 0;
foreach ($panier as $item) {
    $total_items += $item['quantite'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GreenMarket – Validation de commande</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
:root{
  --cream:#fff9eb; --sage:#9fb2ac; --sage-dark:#7a9490; --sage-light:#c8d8d4;
  --wine:#5d0d18; --wine-dark:#3e0910; --wine-pale:#f5e6e8;
  --text:#2a1a1c; --text-muted:#6b5055; --border:#e8ddd0;
  --white:#fffdf7; --shadow:rgba(93,13,24,.10);
}
*{ margin:0; padding:0; box-sizing:border-box; }
body{ font-family:'Lato', sans-serif; background:var(--cream); color:var(--text); min-height:100vh; }

.page-header{ background:var(--wine); padding:2rem 2.5rem; color:white; }
.page-header h1{ font-family:'Playfair Display', serif; font-size:2rem; }
.page-header p{ color:rgba(255,249,235,.7); font-size:.9rem; margin-top:.4rem; }

.container{ max-width:1100px; margin:2rem auto; padding:0 1.5rem; }
.err{ color:red; font-size:.8rem; margin-bottom:8px; }
.err-general{ color:red; font-size:1rem; background:#ffebee; padding:0.75rem; border-radius:8px; margin-bottom:1rem; }

.checkout-grid{ display:grid; grid-template-columns:1fr 380px; gap:2rem; }
.checkout-form{ background:var(--white); border-radius:12px; border:1.5px solid var(--border); padding:1.5rem; }

.form-section{ margin-bottom:1.5rem; padding-bottom:1.5rem; border-bottom:1px solid var(--border); }
.form-section:last-child{ border-bottom:none; margin-bottom:0; padding-bottom:0; }
.form-section h3{ font-family:'Playfair Display', serif; font-size:1.1rem; color:var(--text); margin-bottom:1rem; }

.form-row{ display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem; }
.form-group{ margin-bottom:1rem; }
.form-group label{ display:block; font-size:.75rem; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted); margin-bottom:.3rem; }
.form-group input, .form-group select, .form-group textarea{
  width:100%; padding:.7rem; border:1.5px solid var(--border); border-radius:8px;
  font-family:'Lato', sans-serif; background:var(--cream); outline:none; font-size:.9rem;
  transition: border-color 0.3s;
}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus{ border-color:var(--wine); }

.radio-group{ display:flex; gap:1.5rem; flex-wrap:wrap; padding:.5rem 0; }
.radio-group label{ display:flex; align-items:center; gap:.4rem; font-size:.85rem; cursor:pointer; }

.summary-card{ background:var(--white); border-radius:12px; border:1.5px solid var(--border); padding:1.5rem; position:sticky; top:90px; }
.summary-card h3{ font-family:'Playfair Display', serif; font-size:1.1rem; margin-bottom:1rem; padding-bottom:.5rem; border-bottom:1px solid var(--border); }

.order-item{ display:flex; align-items:center; gap:.8rem; padding:.6rem 0; border-bottom:1px solid var(--border); }
.order-item:last-child{ border-bottom:none; }
.order-item img{ width:45px; height:45px; border-radius:8px; object-fit:cover; flex-shrink:0; }
.order-item-name{ font-size:.85rem; font-weight:600; }
.order-item-price{ font-size:.75rem; color:var(--text-muted); }
.order-item-total{ font-size:.9rem; font-weight:700; color:var(--wine); flex-shrink:0; margin-left:auto; }

.total-row{ display:flex; justify-content:space-between; padding:.4rem 0; font-size:.9rem; }
.total-row .label{ color:var(--text-muted); }
.grand-total{ font-weight:700; font-size:1.1rem; color:var(--wine); padding-top:.8rem; margin-top:.5rem; border-top:1px solid var(--border); }

.btn-confirm{ width:100%; background:var(--wine); color:white; border:none; padding:.9rem; border-radius:40px; font-weight:700; font-size:1rem; cursor:pointer; margin-top:1rem; transition: background 0.3s; }
.btn-confirm:hover{ background:var(--wine-dark); }

.success-card{ text-align:center; padding:3rem 2rem; background:var(--white); border-radius:12px; border:1.5px solid var(--border); }
.success-card h2{ color:var(--wine); font-family:'Playfair Display', serif; font-size:2rem; margin-bottom:.5rem; }
.success-card p{ color:var(--text-muted); margin-bottom:.5rem; }
.order-number{ display:inline-block; background:var(--wine-pale); color:var(--wine); font-weight:700; padding:.4rem 1rem; border-radius:20px; margin:1rem 0; }
.success-actions{ margin-top:1.5rem; display:flex; gap:1rem; justify-content:center; flex-wrap:wrap; }
.btn-link{ background:var(--wine); color:white; padding:.7rem 1.5rem; border-radius:4px; text-decoration:none; font-size:.9rem; font-weight:600; transition: background 0.3s; }
.btn-link:hover{ background:var(--wine-dark); }

@media(max-width:800px){ 
  .checkout-grid{ grid-template-columns:1fr; } 
  .form-row{ grid-template-columns:1fr; } 
}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="page-header">
  <h1>Validation de commande</h1>
  <p>Vérifiez vos informations et confirmez votre achat</p>
</div>

<div class="container">

<?php if ($success): ?>
  <div class="success-card">
    <div style="font-size:4rem; margin-bottom:1rem;">🎉</div>
    <h2>Commande confirmée !</h2>
    <p>Merci pour votre commande, <strong><?php echo htmlspecialchars($_SESSION['user_nom']); ?></strong>.</p>
    <div class="order-number">N° <?php echo $numero_commande; ?></div>
    <p>Votre commande est <strong>en attente de traitement</strong>.</p>
    <p><strong>Total :</strong> <?php echo number_format($montant_total, 2); ?> DH</p>
    <div class="success-actions">
      <a href="produits.php" class="btn-link">🏠 Continuer les achats</a>
      <a href="mes-commandes.php" class="btn-link">📋 Mes commandes</a>
    </div>
  </div>

<?php else: ?>
  <?php if (isset($err['general'])): ?>
    <div class="err-general">❌ <?php echo $err['general']; ?></div>
  <?php endif; ?>

  <div class="checkout-grid">
    <div class="checkout-form">
      <form method="POST">

        <div class="form-section">
          <h3>📋 Informations personnelles</h3>
          <div class="form-row">
            <div class="form-group">
              <?php if (isset($err['prenom'])) echo "<div class='err'>" . $err['prenom'] . "</div>"; ?>
              <label>Prénom *</label>
              <input type="text" name="prenom" placeholder="Votre prénom" value="<?= htmlspecialchars($prenom ?? '') ?>">
            </div>
            <div class="form-group">
              <?php if (isset($err['nom'])) echo "<div class='err'>" . $err['nom'] . "</div>"; ?>
              <label>Nom *</label>
              <input type="text" name="nom" placeholder="Votre nom" value="<?= htmlspecialchars($nom ?? '') ?>">
            </div>
          </div>
          <div class="form-group">
            <?php if (isset($err['email'])) echo "<div class='err'>" . $err['email'] . "</div>"; ?>
            <label>Email *</label>
            <input type="email" name="email" placeholder="votre@email.com" value="<?= htmlspecialchars($email ?? $_SESSION['user_email'] ?? '') ?>">
          </div>
          <div class="form-group">
            <?php if (isset($err['telephone'])) echo "<div class='err'>" . $err['telephone'] . "</div>"; ?>
            <label>Téléphone *</label>
            <input type="tel" name="telephone" placeholder="06 00 00 00 00" value="<?= htmlspecialchars($telephone ?? '') ?>">
          </div>
        </div>

        <div class="form-section">
          <h3>Mode de livraison</h3>
          <div class="radio-group">
            <label><input type="radio" name="mode_livraison" value="livraison" <?= ($mode_livraison ?? 'livraison') == 'livraison' ? 'checked' : '' ?>> 📦 Livraison à domicile (+250 DH)</label>
            <label><input type="radio" name="mode_livraison" value="retrait" <?= ($mode_livraison ?? '') == 'retrait' ? 'checked' : '' ?>> 🏪 Retrait en boutique (Gratuit)</label>
          </div>
        </div>

        <div class="form-section" id="section_adresse" style="<?= ($mode_livraison ?? 'livraison') == 'livraison' ? '' : 'display:none;' ?>">
          <h3>📍 Adresse de livraison</h3>
          <div class="form-group">
            <?php if (isset($err['adresse'])) echo "<div class='err'>" . $err['adresse'] . "</div>"; ?>
            <label>Adresse *</label>
            <input type="text" name="adresse" placeholder="Numéro et rue" value="<?= htmlspecialchars($adresse ?? '') ?>">
          </div>
          <div class="form-row">
            <div class="form-group">
              <?php if (isset($err['code_postal'])) echo "<div class='err'>" . $err['code_postal'] . "</div>"; ?>
              <label>Code postal *</label>
              <input type="text" name="code_postal" placeholder="93000" value="<?= htmlspecialchars($code_postal ?? '') ?>">
            </div>
            <div class="form-group">
              <?php if (isset($err['ville'])) echo "<div class='err'>" . $err['ville'] . "</div>"; ?>
              <label>Ville *</label>
              <input type="text" name="ville" placeholder="Tétouan" value="<?= htmlspecialchars($ville ?? '') ?>">
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3>💳 Mode de paiement</h3>
          <div class="radio-group">
            <label><input type="radio" name="mode_paiement" value="carte" <?= ($mode_paiement ?? 'carte') == 'carte' ? 'checked' : '' ?>> 💳 Carte bancaire</label>
            <label><input type="radio" name="mode_paiement" value="espece" <?= ($mode_paiement ?? '') == 'espece' ? 'checked' : '' ?>> 💶 Espèce à la livraison</label>
          </div>
        </div>

        <div class="form-section">
          <h3>📝 Notes (optionnel)</h3>
          <textarea name="notes" rows="2" placeholder="Instructions particulières..."><?= htmlspecialchars($notes ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn-confirm">✅ Confirmer la commande</button>
      </form>
    </div>

    <div class="summary-card">
      <h3>🛍️ Récapitulatif</h3>
      <?php foreach ($panier as $item): ?>
        <div class="order-item">
          <img src="<?= htmlspecialchars($item['photo_url'] ?? 'IMAGES/default-product.jpg') ?>"
               onerror="this.src='IMAGES/default-product.jpg'">
          <div>
            <div class="order-item-name"><?= htmlspecialchars($item['nom_produit']); ?></div>
            <div class="order-item-price"><?= number_format($item['prix_unitaire'], 2); ?> DH × <?= $item['quantite']; ?></div>
          </div>
          <div class="order-item-total"><?= number_format($item['prix_unitaire'] * $item['quantite'], 2); ?> DH</div>
        </div>
      <?php endforeach; ?>

      <div style="margin-top:1rem;">
        <div class="total-row">
          <span class="label">Sous-total</span>
          <span><?= number_format($sous_total, 2); ?> DH</span>
        </div>
        <div class="total-row">
          <span class="label" id="label_livraison">Livraison</span>
          <span id="prix_livraison"><?= number_format($frais_livraison, 2); ?> DH</span>
        </div>
        <div class="total-row grand-total">
          <span>Total</span>
          <span id="grand_total"><?= number_format($sous_total + $frais_livraison, 2); ?> DH</span>
        </div>
      </div>
    </div>
  </div>

<script>
// Mise à jour du total selon le mode de livraison
var radios = document.querySelectorAll('input[name="mode_livraison"]');
var sousTotal = <?= $sous_total; ?>;
radios.forEach(function(radio) {
    radio.addEventListener('change', function() {
        var frais = (this.value == 'livraison') ? 250 : 0;
        document.getElementById('prix_livraison').textContent = frais.toFixed(2) + ' DH';
        document.getElementById('grand_total').textContent = (sousTotal + frais).toFixed(2) + ' DH';
        document.getElementById('label_livraison').textContent = (this.value == 'livraison') ? 'Livraison' : 'Retrait (gratuit)';
        document.getElementById('section_adresse').style.display = (this.value == 'livraison') ? 'block' : 'none';
    });
});
</script>

<?php endif; ?>
</div>
</body>
</html>