<?php
session_start();
require_once 'connexion.php';

// Récupérer les boutiques depuis la base de données avec leur catégorie
try {
    $stmt = $pdo->prepare("
        SELECT b.*, p.nom_entreprise as producteur_nom, 
               p.est_valide_par_admin as producteur_valide,
               c.id_categorie, c.nom_categorie, c.description as categorie_description
        FROM boutique b
        JOIN producteur p ON b.id_producteur = p.id_producteur
        LEFT JOIN categorie c ON b.id_categorie = c.id_categorie
        WHERE p.est_valide_par_admin = 1
        ORDER BY b.date_creation DESC
    ");
    $stmt->execute();
    $boutiques_db = $stmt->fetchAll();
    
    // Récupérer toutes les catégories disponibles
    $stmt = $pdo->query("SELECT * FROM categorie ORDER BY nom_categorie");
    $categories_db = $stmt->fetchAll();
    
    // Récupérer les statistiques
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM boutique b JOIN producteur p ON b.id_producteur = p.id_producteur WHERE p.est_valide_par_admin = 1");
    $total_boutiques = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM produit WHERE est_valide_par_admin = 1");
    $total_produits = $stmt->fetch()['total'];
    
} catch(PDOException $e) {
    error_log("Error store: " . $e->getMessage());
    $boutiques_db = [];
    $categories_db = [];
    $total_boutiques = 0;
    $total_produits = 0;
}

// Convertir les données pour le JavaScript
$boutiques_json = [];
foreach ($boutiques_db as $b) {
    $boutiques_json[] = [
        'id' => $b['id_boutique'],
        'name' => $b['nom_boutique'],
        'categoryId' => $b['id_categorie'],
        'category' => $b['nom_categorie'] ?? 'Artisanat',
        'categoryName' => $b['nom_categorie'] ?? 'Non catégorisé',
        'badge' => 'Artisan',
        'badgeClass' => 'artisan',
        'banner' => !empty($b['image']) ? $b['image'] : 'IMAGES/default-boutique.jpg',
        'desc' => $b['description'] ?? 'Boutique artisanale marocaine',
        'rating' => 4.5,
        'reviews' => rand(10, 200),
        'products' => rand(5, 30),
        'sales' => rand(100, 5000),
        'producerId' => $b['id_producteur'],
        'producerName' => $b['producteur_nom'],
        'location' => 'Maroc',
        'since' => date('Y', strtotime($b['date_creation']))
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GreenMarket – Boutiques Artisanales Marocaines</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Lato:wght@300;400;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  /* ========== STYLES ========== */
  :root {
    --cream:      #fff9eb;
    --sage:       #9fb2ac;
    --sage-dark:  #7a9490;
    --sage-light: #c8d8d4;
    --wine:       #5d0d18;
    --wine-dark:  #3e0910;
    --wine-light: #8c2030;
    --wine-pale:  #f5e6e8;
    --text:       #2a1a1c;
    --text-muted: #6b5055;
    --border:     #e8ddd0;
    --white:      #fffdf7;
    --shadow:     rgba(93,13,24,.10);
    --shadow-md:  rgba(93,13,24,.18);
  }

  * { margin:0; padding:0; box-sizing:border-box; }

  body {
    font-family: 'Jost', sans-serif;
    background: var(--cream);
    color: var(--text);
    min-height: 100vh;
  }

  .page-header {
    background: var(--wine);
    padding: 4rem 2.5rem 3rem;
    position: relative; overflow: hidden;
  }
  .page-header::before {
    content: '';
    position: absolute; right: -80px; top: -80px;
    width: 420px; height: 420px;
    border: 55px solid rgba(255,249,235,.05);
    border-radius: 50%;
  }
  .page-header::after {
    content: '';
    position: absolute; left: 4%; bottom: -70px;
    width: 240px; height: 240px;
    border: 40px solid rgba(159,178,172,.10);
    border-radius: 50%;
  }
  .header-inner { position: relative; z-index: 1; }
  .header-eyebrow {
    font-size: .72rem; font-weight: 600; letter-spacing: .2em;
    text-transform: uppercase; color: var(--sage-light);
    margin-bottom: .9rem;
  }
  .page-header h1 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 3.6rem; font-weight: 700; line-height: 1.05;
    color: var(--cream); margin-bottom: .7rem;
  }
  .page-header h1 em { font-weight: 400; color: rgba(255,249,235,.6); font-size: 2.6rem; }
  .page-header p {
    color: rgba(255,249,235,.62); font-size: .93rem; font-weight: 300; max-width: 500px;
  }
  .header-stats {
    display: flex; gap: 2.5rem; margin-top: 2rem;
    padding-top: 1.8rem;
    border-top: 1px solid rgba(255,249,235,.14);
  }
  .h-stat-val {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2rem; font-weight: 700; color: var(--cream);
    display: block; line-height: 1;
  }
  .h-stat-label {
    font-size: .7rem; color: var(--sage-light);
    letter-spacing: .1em; text-transform: uppercase;
  }

  .search-bar-wrap {
    background: var(--white);
    padding: 1.1rem 2.5rem;
    border-bottom: 1px solid var(--border);
    display: flex; gap: 1rem; align-items: center;
    flex-wrap: wrap;
  }
  .search-input-wrapper { flex: 1; position: relative; min-width: 200px; }
  .search-input-wrapper svg {
    position: absolute; left: 1rem; top: 50%; transform: translateY(-50%);
    color: var(--sage-dark);
  }
  .search-input {
    width: 100%; padding: .68rem 1rem .68rem 2.8rem;
    border: 1.5px solid var(--border); border-radius: 3px;
    background: var(--cream);
    font-family: 'Jost', sans-serif; font-size: .88rem;
    color: var(--text); outline: none; transition: border-color .2s;
  }
  .search-input:focus { border-color: var(--wine); }
  .filter-select {
    padding: .68rem 2.1rem .68rem 1rem;
    border: 1.5px solid var(--border); border-radius: 3px;
    background: var(--cream);
    font-family: 'Jost', sans-serif; font-size: .85rem;
    color: var(--text); outline: none; cursor: pointer;
  }

  .main-layout {
    display: grid; grid-template-columns: 300px 1fr;
    max-width: 1400px; margin: 0 auto;
    padding: 2rem 2.5rem; gap: 2rem;
  }

  .sidebar { position: sticky; top: 88px; align-self: start; }
  .sidebar-section {
    background: var(--white);
    border: 1.5px solid var(--border);
    border-radius: 6px; overflow: hidden;
    box-shadow: 0 2px 18px var(--shadow);
    margin-bottom: 1.2rem;
  }
  .sidebar-header {
    background: var(--wine);
    padding: 1rem 1.3rem;
    display: flex; align-items: center; justify-content: space-between;
  }
  .sidebar-header h3 {
    font-family: 'Cormorant Garamond', serif;
    color: var(--cream); font-size: 1.12rem; font-weight: 600;
  }
  .add-btn {
    background: rgba(255,249,235,.15); color: var(--cream);
    border: 1px solid rgba(255,249,235,.28);
    border-radius: 3px; padding: .28rem .8rem;
    font-size: .72rem; font-weight: 600;
    cursor: pointer; display: flex; align-items: center; gap: .3rem;
  }
  .add-btn:hover { background: rgba(255,249,235,.28); }

  .category-list { padding: .4rem 0; }
  .category-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: .65rem 1.3rem;
    cursor: pointer; transition: background .15s;
    border-left: 3px solid transparent;
  }
  .category-item:hover { background: var(--cream); border-left-color: var(--sage); }
  .category-item.active { background: var(--wine-pale); border-left-color: var(--wine); }
  .cat-left { display: flex; align-items: center; gap: .7rem; }
  .cat-icon { font-size: 1.05rem; }
  .cat-name { font-size: .87rem; font-weight: 500; }
  .cat-count {
    background: var(--border); color: var(--text-muted);
    padding: .14rem .55rem; border-radius: 50px;
    font-size: .7rem; font-weight: 600;
  }

  .add-cat-form {
    padding: 1.2rem; border-top: 1px solid var(--border); display: none;
  }
  .add-cat-form.open { display: block; }
  .add-cat-form label {
    font-size: .72rem; font-weight: 600; text-transform: uppercase;
    color: var(--text-muted); display: block; margin-bottom: .35rem;
  }
  .add-cat-form input {
    width: 100%; padding: .55rem .8rem;
    border: 1.5px solid var(--border); border-radius: 3px;
    font-family: 'Jost', sans-serif; font-size: .85rem;
    background: var(--cream); margin-bottom: .75rem;
  }
  .add-cat-form input:focus { border-color: var(--wine); }
  .form-actions { display: flex; gap: .5rem; }
  .btn-sm-wine {
    flex: 1; background: var(--wine); color: var(--cream);
    border: none; padding: .52rem; border-radius: 3px;
    cursor: pointer;
  }
  .btn-sm-wine:hover { background: var(--wine-dark); }
  .btn-sm-cancel {
    flex: 1; background: var(--border); color: var(--text-muted);
    border: none; padding: .52rem; border-radius: 3px;
    cursor: pointer;
  }

  .stores-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
  }

  .store-card {
    background: var(--white); border: 1.5px solid var(--border);
    border-radius: 8px; overflow: hidden;
    transition: transform .25s, box-shadow .25s;
    box-shadow: 0 2px 12px var(--shadow); position: relative;
  }
  .store-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px var(--shadow-md);
  }
  .store-banner {
    height: 180px; position: relative; overflow: hidden; cursor: pointer;
  }
  .store-banner img {
    width: 100%; height: 100%; object-fit: cover; transition: transform .4s ease;
  }
  .store-card:hover .store-banner img { transform: scale(1.05); }
  .store-badge {
    position: absolute; top: 0.8rem; right: 0.8rem;
    background: var(--wine); color: white;
    font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.7rem;
    border-radius: 3px;
  }
  .store-body { padding: 1.2rem; }
  .store-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.2rem; font-weight: 700; margin-bottom: 0.3rem;
  }
  .store-category-tag {
    display: inline-block; background: var(--sage-light);
    font-size: 0.65rem; font-weight: 600; padding: 0.15rem 0.6rem;
    border-radius: 3px; margin-bottom: 0.6rem;
  }
  .store-desc {
    font-size: 0.85rem; color: var(--text-muted); line-height: 1.5;
    margin-bottom: 1rem;
  }
  .store-stats {
    display: flex; justify-content: space-between;
    border-top: 1px solid var(--border);
    padding-top: 0.8rem;
    margin-top: 0.5rem;
  }
  .store-stat { text-align: center; flex: 1; }
  .store-stat-val {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1rem; font-weight: 700; color: var(--wine);
  }
  .store-stat-label { font-size: 0.65rem; color: var(--text-muted); }
  .stars { color: #e0a82e; font-size: 0.7rem; }

  .empty-state { text-align: center; padding: 3rem; color: var(--text-muted); }
  .empty-icon { font-size: 3rem; margin-bottom: 1rem; }

  .toast {
    position: fixed; bottom: 2rem; right: 2rem;
    background: var(--wine); color: white;
    padding: .85rem 1.5rem; border-radius: 5px;
    z-index: 500; transform: translateY(80px); opacity: 0;
    transition: all .35s cubic-bezier(.34,1.4,.64,1);
  }
  .toast.show { transform: translateY(0); opacity: 1; }

  @media(max-width:900px){
    .main-layout { grid-template-columns: 1fr; }
    .sidebar { position: static; }
  }
  @media(max-width:600px){
    .page-header { padding: 2.5rem 1.2rem 2rem; }
    .page-header h1 { font-size: 2.4rem; }
    .search-bar-wrap { flex-wrap: wrap; padding: 1rem; }
    .main-layout { padding: 1.2rem 1rem; }
  }
</style>
</head>
<body data-active-page="store">

<?php include 'header.php'; ?>

<div class="page-header">
  <div class="header-inner">
    <div class="header-eyebrow">🇲🇦 Artisanat &amp; Traditions marocaines</div>
    <h1>Nos Boutiques<br><em>Artisanales</em></h1>
    <p>Découvrez des artisans marocains passionnés, perpétuant un savoir-faire unique et authentique.</p>
    <div class="header-stats">
      <div><span class="h-stat-val" id="statStores"><?php echo $total_boutiques; ?></span><span class="h-stat-label">Boutiques actives</span></div>
      <div><span class="h-stat-val" id="statProducts"><?php echo $total_produits; ?></span><span class="h-stat-label">Produits artisanaux</span></div>
      <div><span class="h-stat-val" id="statRating">4.8 ★</span><span class="h-stat-label">Note moyenne</span></div>
    </div>
  </div>
</div>

<div class="search-bar-wrap">
  <div class="search-input-wrapper">
    <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
      <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
    </svg>
    <input class="search-input" id="searchInput" type="text" placeholder="Rechercher une boutique...">
  </div>
  <select class="filter-select" id="catFilter">
    <option value="">Toutes les catégories</option>
  </select>
</div>

<div class="main-layout">
  <aside class="sidebar">
    <div class="sidebar-section">
      <div class="sidebar-header">
        <h3>Catégories artisanales</h3>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
          <button class="add-btn" id="toggleAddCat">➕ Ajouter</button>
        <?php endif; ?>
      </div>
      <div class="category-list" id="categoryList"></div>
      <div class="add-cat-form" id="addCatForm">
        <label>Nom de la catégorie</label>
        <input type="text" id="catNameInput" placeholder="Ex: Poterie, Tissage...">
        <label>Icône (emoji)</label>
        <input type="text" id="catIconInput" placeholder="📦" maxlength="2">
        <div class="form-actions">
          <button class="btn-sm-cancel" id="cancelCat">Annuler</button>
          <button class="btn-sm-wine" id="saveCat">Enregistrer</button>
        </div>
      </div>
    </div>
  </aside>
  
  <section class="stores-area">
    <div class="stores-grid" id="storesGrid"></div>
  </section>
</div>

<div class="toast" id="toast"></div>

<script>
// Données PHP converties en JavaScript
const boutiquesFromDB = <?php echo json_encode($boutiques_json); ?>;
const categoriesFromDB = <?php echo json_encode($categories_db); ?>;

let stores = boutiquesFromDB.length > 0 ? boutiquesFromDB : [];

// Initialiser les catégories depuis la base de données
let categories = [];
if (categoriesFromDB.length > 0) {
    categories = categoriesFromDB.map(c => ({
        id: c.id_categorie,
        icon: getCategoryIcon(c.nom_categorie),
        name: c.nom_categorie
    }));
}

// Fonction pour attribuer une icône selon la catégorie
function getCategoryIcon(categoryName) {
    const icons = {
        'Caftans & Vêtements traditionnels': '👘',
        'Tapis & Tissage': '🪑',
        'Poterie & Céramique': '🏺',
        'Marqueterie & Bois': '🪵',
        'Bijoux & Joaillerie': '💍',
        'Lampes & Fer forgé': '🕯️',
        'Cosmétiques naturels': '🧴',
        'Produits du terroir': '🍯'
    };
    return icons[categoryName] || '📦';
}

let currentCategory = null;

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[m]));
}

function showToast(msg) {
    const toast = document.getElementById('toast');
    toast.innerHTML = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

function renderStars(rating) {
    const full = Math.floor(rating);
    const empty = 5 - full;
    return '★'.repeat(full) + '☆'.repeat(empty);
}

function renderCategories() {
    const list = document.getElementById('categoryList');
    if (!list) return;
    
    // Calculer le nombre de boutiques par catégorie
    const categoryCounts = {};
    stores.forEach(store => {
        const catName = store.categoryName;
        if (catName) {
            categoryCounts[catName] = (categoryCounts[catName] || 0) + 1;
        }
    });
    
    list.innerHTML = `
        <div class="category-item ${currentCategory === null ? 'active' : ''}" data-name="" data-cat-id="">
            <div class="cat-left"><span class="cat-icon">🏪</span><span class="cat-name">Toutes les boutiques</span></div>
            <span class="cat-count">${stores.length}</span>
        </div>
        ${categories.map(c => `
            <div class="category-item ${currentCategory === c.name ? 'active' : ''}" data-name="${escapeHtml(c.name)}" data-cat-id="${c.id}">
                <div class="cat-left"><span class="cat-icon">${c.icon}</span><span class="cat-name">${escapeHtml(c.name)}</span></div>
                <span class="cat-count">${categoryCounts[c.name] || 0}</span>
            </div>
        `).join('')}
    `;
    
    list.querySelectorAll('.category-item').forEach(el => {
        el.addEventListener('click', () => {
            const catName = el.dataset.name;
            currentCategory = catName || null;
            renderCategories();
            renderStores();
            // Mettre à jour le select
            const catFilter = document.getElementById('catFilter');
            if (catFilter) catFilter.value = currentCategory || '';
        });
    });
}

function renderStores() {
    const grid = document.getElementById('storesGrid');
    if (!grid) return;
    
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    let filteredStores = stores.filter(s => {
        const matchSearch = !searchTerm || 
            s.name.toLowerCase().includes(searchTerm) || 
            (s.producerName || '').toLowerCase().includes(searchTerm);
        const matchCategory = !currentCategory || s.categoryName === currentCategory;
        return matchSearch && matchCategory;
    });
    
    if (filteredStores.length === 0) {
        grid.innerHTML = `<div class="empty-state"><div class="empty-icon">🏪</div><p>Aucune boutique trouvée.</p></div>`;
        return;
    }
    
    const fallbackImg = 'https://placehold.co/400x200/5D0D18/white?text=GreenMarket';
    
    grid.innerHTML = filteredStores.map(s => `
        <div class="store-card">
            <div class="store-banner" onclick="window.location.href='info-store.php?id=${s.id}'">
                <img src="${escapeHtml(s.banner || fallbackImg)}" alt="${escapeHtml(s.name)}" onerror="this.src='${fallbackImg}'">
                ${s.badge ? `<span class="store-badge">${escapeHtml(s.badge)}</span>` : ''}
            </div>
            <div class="store-body">
                <div class="store-name">${escapeHtml(s.name)}</div>
                <span class="store-category-tag">${escapeHtml(s.categoryName)}</span>
                <p class="store-desc">${escapeHtml((s.desc || '').substring(0, 100))}${(s.desc || '').length > 100 ? '…' : ''}</p>
                <div class="store-stats">
                    <div class="store-stat">
                        <div class="stars">${renderStars(s.rating || 4.5)}</div>
                        <span class="store-stat-label">${s.reviews || 0} avis</span>
                    </div>
                    <div class="store-stat">
                        <div class="store-stat-val">${s.products || 0}</div>
                        <span class="store-stat-label">Produits</span>
                    </div>
                    <div class="store-stat">
                        <div class="store-stat-val">${typeof s.sales === 'number' ? s.sales.toLocaleString() : s.sales || '0'}</div>
                        <span class="store-stat-label">Ventes</span>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// Ajouter une catégorie (admin uniquement)
function addCategory() {
    const name = document.getElementById('catNameInput').value.trim();
    const icon = document.getElementById('catIconInput').value.trim() || '📦';
    
    if (!name) {
        showToast('⚠️ Veuillez saisir un nom');
        return;
    }
    
    // Vérifier si la catégorie existe déjà
    if (categories.find(c => c.name.toLowerCase() === name.toLowerCase())) {
        showToast('⚠️ Cette catégorie existe déjà');
        return;
    }
    
    // Envoyer au serveur
    fetch('add_category.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'nom_categorie=' + encodeURIComponent(name) + '&description=' + encodeURIComponent('Catégorie ' + name)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            categories.push({ id: data.id, icon: icon, name: name });
            renderCategories();
            // Ajouter l'option au select
            const catFilter = document.getElementById('catFilter');
            if (catFilter) {
                const opt = document.createElement('option');
                opt.value = name;
                opt.textContent = name;
                catFilter.appendChild(opt);
            }
            document.getElementById('addCatForm').classList.remove('open');
            document.getElementById('catNameInput').value = '';
            document.getElementById('catIconInput').value = '';
            showToast('✅ Catégorie ajoutée avec succès');
        } else {
            showToast('❌ ' + data.message);
        }
    })
    .catch(error => {
        showToast('❌ Erreur lors de l\'ajout');
    });
}

// Événements
document.getElementById('searchInput')?.addEventListener('input', renderStores);
document.getElementById('toggleAddCat')?.addEventListener('click', () => {
    document.getElementById('addCatForm').classList.toggle('open');
});
document.getElementById('cancelCat')?.addEventListener('click', () => {
    document.getElementById('addCatForm').classList.remove('open');
    document.getElementById('catNameInput').value = '';
    document.getElementById('catIconInput').value = '';
});
document.getElementById('saveCat')?.addEventListener('click', addCategory);

// Remplir le filtre catégorie
const catFilter = document.getElementById('catFilter');
if (catFilter) {
    categories.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.name;
        opt.textContent = c.name;
        catFilter.appendChild(opt);
    });
    catFilter.addEventListener('change', (e) => {
        currentCategory = e.target.value || null;
        renderCategories();
        renderStores();
    });
}

// Initialisation
renderCategories();
renderStores();

// Mettre à jour les statistiques
document.getElementById('statStores').textContent = stores.length;
document.getElementById('statProducts').textContent = stores.reduce((sum, s) => sum + (s.products || 0), 0);
</script>
</body>
</html>