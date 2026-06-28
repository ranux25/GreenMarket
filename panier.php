<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

include("connexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ajax'])) {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'supprimer' && isset($_POST['id_produit'])) {
        $req = $pdo->prepare("DELETE FROM panier WHERE id_client = ? AND id_produit = ?");
        $req->execute([$_SESSION['user_id'], $_POST['id_produit']]);
    } 
    elseif ($action == 'modifier_qte' && isset($_POST['id_produit']) && isset($_POST['quantite'])) {
        $qte = max(1, intval($_POST['quantite']));
        $req = $pdo->prepare("UPDATE panier SET quantite = ? WHERE id_client = ? AND id_produit = ?");
        $req->execute([$qte, $_SESSION['user_id'], $_POST['id_produit']]);
    } 
    elseif ($action == 'vider') {
        $req = $pdo->prepare("DELETE FROM panier WHERE id_client = ?");
        $req->execute([$_SESSION['user_id']]);
    }

    $req = $pdo->prepare("SELECT pa.quantite, p.prix_unitaire FROM panier pa JOIN produit p ON pa.id_produit = p.id_produit WHERE pa.id_client = ?");
    $req->execute([$_SESSION['user_id']]);
    $panier_actuel = $req->fetchAll(PDO::FETCH_ASSOC);

    $new_total = 0;
    $new_items = 0;
    foreach ($panier_actuel as $item) {
        $new_total += $item['prix_unitaire'] * $item['quantite'];
        $new_items += $item['quantite'];
    }

    echo json_encode([
        'success' => true,
        'new_total' => number_format($new_total, 2, '.', ''),
        'new_items' => $new_items,
        'count_distinct' => count($panier_actuel)
    ]);
    exit;
}

$theme = $_COOKIE['theme'] ?? 'light';

try {
    $req = $pdo->prepare("SELECT pa.quantite, p.id_produit, p.nom_produit, p.prix_unitaire, p.photo_url, b.nom_boutique, b.id_boutique FROM panier pa JOIN produit p ON pa.id_produit = p.id_produit JOIN boutique b ON p.id_boutique = b.id_boutique WHERE pa.id_client = ?");
    $req->execute([$_SESSION['user_id']]);
    $panier = $req->fetchAll(PDO::FETCH_ASSOC);
}
catch(PDOException $e) { die(); }

$total = 0;
$total_items = 0;
foreach ($panier as $item) {
    $total += $item['prix_unitaire'] * $item['quantite'];
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
  --border-color: #e8ddd0;
  --shadow-color: rgba(93,13,24,0.12);
  --success: #155724;
  --danger: #721c24;
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
  --border-color: #5a4a3a;
  --shadow-color: rgba(0,0,0,0.3);
  --page-header-bg: #1a1410;
  --page-header-text: #f0e6d8;
  --page-header-sub: rgba(240,230,216,0.6);
  --page-header-border: rgba(240,230,216,0.05);
  --page-header-border-top: rgba(240,230,216,0.15);
}
*{ margin:0; padding:0; box-sizing:border-box; }
body{ font-family:'Lato', sans-serif; background:var(--bg); color:var(--text-dark); min-height:100vh; transition: background-color 0.3s ease, color 0.3s ease; }
.page-header { background: var(--page-header-bg); padding: 4rem 2.5rem 3rem; position: relative; overflow: hidden; transition: background 0.3s ease; }
.page-header::before { content: ''; position: absolute; right: -80px; top: -80px; width: 420px; height: 420px; border: 55px solid var(--page-header-border); border-radius: 50%; transition: border-color 0.3s ease; }
.page-header::after { content: ''; position: absolute; left: 4%; bottom: -70px; width: 240px; height: 240px; border: 40px solid rgba(159,178,172,.10); border-radius: 50%; }
.header-inner { position: relative; z-index: 1; }
.header-eyebrow { font-size: .75rem; font-weight: 600; letter-spacing: .2em; text-transform: uppercase; color: var(--page-header-sub); margin-bottom: 1rem; transition: color 0.3s ease; }
.page-header h1 { font-family: 'Playfair Display', serif; font-size: clamp(32px, 5vw, 52px); font-weight: 700; line-height: 1.1; color: var(--page-header-text); margin-bottom: 1rem; transition: color 0.3s ease; }
.page-header h1 em { font-style: italic; color: var(--gold); display: block; }
.page-header p { color: var(--page-header-sub); font-size: 1rem; max-width: 500px; transition: color 0.3s ease; }
.container{ max-width:1100px; margin:2rem auto; padding:0 1.5rem; }
.cart-layout{ display:grid; grid-template-columns:2fr 1fr; gap:2rem; }
.cart-items{ background: var(--bg-card); border-radius: 10px; padding:1.5rem; box-shadow: 0 10px 30px var(--shadow-color); border: 1px solid var(--border-color); transition: all 0.3s ease; }
.cart-item{ display:flex; gap:1rem; padding:1rem 0; border-bottom:1px solid var(--border-color); transition: border-color 0.3s; }
.cart-item:last-child{ border-bottom:none; }
.cart-item img{ width:110px; height:110px; object-fit:cover; border-radius:8px; background: var(--bg-light); border: 1px solid var(--border-color); transition: background 0.3s, border-color 0.3s; }
.item-info{ flex:1; }
.item-name{ font-weight:700; color: var(--text-dark); font-size:1rem; transition: color 0.3s; }
[data-theme="dark"] .item-name { color: var(--gold); }
.item-shop{ font-size:.8rem; color:var(--text-light); margin:.3rem 0; transition: color 0.3s; }
.item-price{ font-size:1rem; font-weight:700; color: var(--primary); transition: color 0.3s; }
[data-theme="dark"] .item-price { color: var(--gold); }
.quantity-form{ display:flex; align-items:center; gap:.5rem; margin-top:.7rem; flex-wrap:wrap; }
.quantity-form input[type=number]{ width:60px; padding:.3rem; border:1.5px solid var(--border-color); border-radius:6px; text-align:center; background: var(--bg-input); color: var(--text-dark); transition: all 0.3s; }
.quantity-form input[type=number]:focus { outline: none; border-color: var(--primary); }
.btn-qte{ background: var(--primary); color: #fff; border:none; padding:.4rem .8rem; border-radius:6px; cursor:pointer; font-size:.85rem; font-weight: 600; transition: background 0.2s, transform 0.2s; }
.btn-qte:hover{ background: var(--primary-light); transform: translateY(-2px); }
[data-theme="dark"] .btn-qte { color: var(--bg); }
.btn-delete{ background: var(--bg-light); color: var(--text-dark); border: 1px solid var(--border-color); padding:.5rem .8rem; border-radius:6px; cursor:pointer; margin-top:.8rem; font-size:.85rem; transition: all 0.2s; }
.btn-delete:hover{ background: #f8d7da; color: #721c24; border-color: #721c24; }
[data-theme="dark"] .btn-delete:hover{ background: #3d1a1a; color: #ef9a9a; border-color: #ef9a9a; }
.summary{ background: var(--bg-card); border-radius:10px; padding:1.5rem; height:fit-content; box-shadow:0 10px 30px var(--shadow-color); border: 1px solid var(--border-color); transition: all 0.3s ease; }
.summary h2{ font-family:'Playfair Display', serif; margin-bottom:1rem; color: var(--text-dark); transition: color 0.3s; }
.summary-line{ display:flex; justify-content:space-between; margin:.8rem 0; color: var(--text-light); transition: color 0.3s; }
.total{ font-size:1.3rem; font-weight:700; color: var(--primary); border-top:1px solid var(--border-color); padding-top:1rem; transition: color 0.3s, border-color 0.3s; }
[data-theme="dark"] .total { color: var(--gold); }
.btn-checkout{ display:block; width:100%; margin-top:1.5rem; background: var(--primary); color:#fff; border:none; padding:1rem; border-radius:8px; font-weight:700; cursor:pointer; font-size:1rem; text-align:center; text-decoration:none; transition: background 0.3s, transform 0.2s; }
.btn-checkout:hover{ background: var(--primary-light); transform: translateY(-2px); }
[data-theme="dark"] .btn-checkout { color: var(--bg); }
.btn-clear{ display:block; width:100%; margin-top:1rem; background: var(--secondary); color:#fff; border:none; padding:.8rem; border-radius:8px; cursor:pointer; font-size:.9rem; transition: background 0.3s, transform 0.2s; }
.btn-clear:hover{ background: var(--secondary-dark); transform: translateY(-2px); }
.empty{ text-align:center; padding:4rem 2rem; background: var(--bg-card); border-radius:10px; border: 1px solid var(--border-color); box-shadow: 0 10px 30px var(--shadow-color); transition: all 0.3s; }
.empty h2 { font-family: 'Playfair Display', serif; color: var(--text-dark); margin-bottom: 0.5rem; transition: color 0.3s; }
.empty p { color: var(--text-light); margin-bottom: 1rem; transition: color 0.3s; }
.btn-back{ background: var(--primary); color:#fff; padding:.6rem 1.2rem; border-radius:8px; text-decoration:none; font-size:.9rem; display:inline-block; margin-top:1rem; transition: background 0.3s, transform 0.2s; }
.btn-back:hover{ background: var(--primary-light); transform: translateY(-2px); }
[data-theme="dark"] .btn-back { color: var(--bg); }
@media(max-width:850px){ .cart-layout{ grid-template-columns:1fr; } .cart-item{ flex-direction:column; } .cart-item img{ width:100%; height:200px; } }
@media(max-width:640px) { .page-header { padding: 2.5rem 1.2rem 2rem; } .container { padding: 0 1rem; } }
#toast { position: fixed; bottom: 28px; right: 28px; background: var(--primary); color: #fff; padding: 14px 22px; border-radius: 14px; font-weight: 700; font-size: 0.95rem; z-index: 9999; transform: translateY(80px); opacity: 0; transition: 0.4s cubic-bezier(.22,1,.36,1); max-width: 340px; }
#toast.show { transform: translateY(0); opacity: 1; }
#confirm-modal { display: none; position: fixed; inset: 0; z-index: 9998; align-items: center; justify-content: center; }
#confirm-modal.show { display: flex; }
#confirm-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.45); backdrop-filter: blur(3px); }
#confirm-box { position: relative; background: var(--bg-card, #fff); border-radius: 20px; padding: 2rem 1.8rem 1.5rem; max-width: 340px; width: 90%; box-shadow: 0 20px 60px rgba(0,0,0,0.2); text-align: center; animation: modalIn 0.25s cubic-bezier(.22,1,.36,1); }
@keyframes modalIn { from { opacity: 0; transform: scale(0.88) translateY(20px); } to { opacity: 1; transform: scale(1) translateY(0); } }
#confirm-icon { font-size: 2.5rem; margin-bottom: 0.8rem; }
#confirm-title { font-family: 'Playfair Display', serif; font-size: 1.15rem; font-weight: 700; color: var(--text-dark, #2C2C2C); margin-bottom: 0.4rem; }
#confirm-msg { font-size: 0.88rem; color: var(--text-light, #6B6B6B); margin-bottom: 1.4rem; }
.confirm-btns { display: flex; gap: 0.8rem; justify-content: center; }
.confirm-btns button { flex: 1; padding: 0.65rem 1rem; border-radius: 999px; font-weight: 700; font-size: 0.9rem; cursor: pointer; border: none; transition: all 0.2s; }
#confirm-cancel { background: var(--bg-light, #f5f0e8); color: var(--text-dark, #2C2C2C); }
#confirm-cancel:hover { opacity: 0.8; }
#confirm-ok { background: #c0392b; color: #fff; }
#confirm-ok:hover { background: #a93226; }
</style>
</head>
<body>

<?php include 'header.php'; ?>

<div id="toast"></div>

<div id="confirm-modal">
  <div id="confirm-overlay"></div>
  <div id="confirm-box">
    <div id="confirm-icon">🗑️</div>
    <div id="confirm-title"></div>
    <div id="confirm-msg"></div>
    <div class="confirm-btns">
      <button id="confirm-cancel">Annuler</button>
      <button id="confirm-ok">Confirmer</button>
    </div>
  </div>
</div>

<div class="page-header">
  <div class="header-inner">
    <div class="header-eyebrow">🇲🇦 Artisanat &amp; Traditions marocaines</div>
    <h1>Mon Panier</h1>
    <p>Retrouvez tous vos produits sélectionnés</p>
  </div>
</div>

<div class="container">
  
  <div id="empty-state" class="empty" style="<?php echo empty($panier) ? 'display:block;' : 'display:none;'; ?>">
    <h2>🛒 Votre panier est vide</h2>
    <p>Ajoutez des produits depuis la boutique pour commencer.</p>
    <a href="produits.php" class="btn-back">← Continuer les achats</a>
  </div>

  <div class="cart-layout" id="cart-layout" style="<?php echo empty($panier) ? 'display:none;' : 'display:grid;'; ?>">
    <div class="cart-items">
      <?php foreach ($panier as $item): ?>
        <div class="cart-item" id="cart-item-<?php echo $item['id_produit']; ?>">
          <img src="<?php echo htmlspecialchars($item['photo_url'] ?? 'IMAGES/default-product.jpg'); ?>" onerror="this.src='IMAGES/default-product.jpg'">
          <div class="item-info">
            <div class="item-name"><?php echo htmlspecialchars($item['nom_produit']); ?></div>
            <div class="item-shop">🏪 Boutique : <?php echo htmlspecialchars($item['nom_boutique']); ?></div>
            <div class="item-price"><?php echo number_format($item['prix_unitaire'], 2, '.', ''); ?> DH</div>

            <form class="quantity-form" onsubmit="event.preventDefault(); updateQte(<?php echo $item['id_produit']; ?>)">
              <input type="number" id="qte-<?php echo $item['id_produit']; ?>" value="<?php echo $item['quantite']; ?>" min="1">
              <button type="button" class="btn-qte" onclick="updateQte(<?php echo $item['id_produit']; ?>)">🔄 Mettre à jour</button>
            </form>

            <button type="button" class="btn-delete" onclick="askConfirm('Supprimer ce produit ?', 'Cette action retirera l\'article de votre panier.', () => supprimerArticle(<?php echo $item['id_produit']; ?>, this))">
              🗑️ Supprimer
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="summary">
      <h2>📋 Résumé</h2>
      <div class="summary-line">
        <span>🛍️ Produits</span>
        <span id="summary-products"><?php echo count($panier); ?></span>
      </div>
      <div class="summary-line">
        <span>📦 Articles</span>
        <span id="summary-items"><?php echo $total_items; ?></span>
      </div>
      <div class="summary-line total">
        <span>💰 Total</span>
        <span id="summary-total"><?php echo number_format($total, 2, '.', ''); ?> DH</span>
      </div>
      <a href="checkout.php" class="btn-checkout">✅ Passer la commande</a>
      <button type="button" class="btn-clear" id="btn-vider">🗑️ Vider le panier</button>
    </div>
  </div>
</div>

<?php include 'footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has('msgs')) {
    showToast('✅ ' + urlParams.get('msgs'), 'var(--primary)');
    window.history.replaceState(null, null, window.location.pathname);
  }
  if (urlParams.has('msgerr')) {
    showToast('❌ ' + urlParams.get('msgerr'), '#c0392b');
    window.history.replaceState(null, null, window.location.pathname);
  }
});

function showToast(msg, bg) {
  const toast = document.getElementById('toast');
  toast.textContent = msg;
  toast.style.background = bg || 'var(--primary)';
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 3000);
}

let _confirmCallback = null;

function askConfirm(title, msg, callback) {
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

function updateTotals(data) {
  document.getElementById('summary-products').textContent = data.count_distinct;
  document.getElementById('summary-items').textContent = data.new_items;
  document.getElementById('summary-total').textContent = parseFloat(data.new_total).toFixed(2) + ' DH';

  const badges = document.querySelectorAll('.cart-badge');
  badges.forEach(badge => {
    badge.textContent = data.new_items;
    if (data.new_items > 0) {
      badge.classList.add('show');
    } else {
      badge.classList.remove('show');
    }
  });

  if (data.count_distinct === 0) {
    document.getElementById('cart-layout').style.display = 'none';
    document.getElementById('empty-state').style.display = 'block';
  }
}

function updateQte(id_produit) {
  const qteInput = document.getElementById('qte-' + id_produit);
  const qte = qteInput ? qteInput.value : 1;

  const formData = new FormData();
  formData.append('action', 'modifier_qte');
  formData.append('id_produit', id_produit);
  formData.append('quantite', qte);
  formData.append('ajax', '1'); 

  fetch('panier.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        updateTotals(data);
        showToast('✅ Quantité mise à jour', 'var(--primary)');
      }
    })
    .catch(() => showToast('❌ Erreur de connexion', '#c0392b'));
}

function supprimerArticle(id_produit, btn) {
  const formData = new FormData();
  formData.append('action', 'supprimer');
  formData.append('id_produit', id_produit);
  formData.append('ajax', '1');

  fetch('panier.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        updateTotals(data);
        const card = btn.closest('.cart-item');
        if (card) {
          card.style.transition = 'opacity 0.3s, transform 0.3s';
          card.style.opacity = '0';
          card.style.transform = 'translateX(-20px)';
          setTimeout(() => {
            card.remove();
          }, 300);
        }
        showToast('✅ Article supprimé', 'var(--primary)');
      }
    })
    .catch(() => showToast('❌ Erreur', '#c0392b'));
}

const btnVider = document.getElementById('btn-vider');
if (btnVider) {
  btnVider.addEventListener('click', () => {
    askConfirm('Vider le panier ?', 'Tous les articles seront supprimés.', () => {
      const formData = new FormData();
      formData.append('action', 'vider');
      formData.append('ajax', '1');

      fetch('panier.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            updateTotals(data);
            const items = document.querySelectorAll('.cart-item');
            items.forEach((item, i) => {
              item.style.transition = `opacity 0.3s ${i * 0.05}s, transform 0.3s ${i * 0.05}s`;
              item.style.opacity = '0';
              item.style.transform = 'translateX(-20px)';
            });
            setTimeout(() => {
              items.forEach(item => item.remove());
              showToast('✅ Panier vidé !', 'var(--primary)');
            }, items.length * 50 + 300);
          }
        })
        .catch(() => showToast('❌ Erreur', '#c0392b'));
    });
  });
}
</script>
</body>
</html>