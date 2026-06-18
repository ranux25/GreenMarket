<?php
session_start();
include('connexion.php');

#recuperer les boutiques validees depuis la BD
try {
    $req = $pdo->prepare("
        SELECT b.*, p.nom_entreprise as producteur_nom, 
               p.est_valide_par_admin as producteur_valide
        FROM boutique b
        JOIN producteur p ON b.id_producteur = p.id_producteur
        WHERE p.est_valide_par_admin = 1
        ORDER BY b.date_creation DESC
        LIMIT 6
    ");
    $req->execute();
    $boutiques_db = $req->fetchAll(PDO::FETCH_ASSOC);

    #recuperer les produits valides et publies
    $req2 = $pdo->prepare("
        SELECT p.*, c.nom_categorie, b.nom_boutique
        FROM produit p
        JOIN boutique b ON p.id_boutique = b.id_boutique
        LEFT JOIN categorie c ON p.id_categorie = c.id_categorie
        WHERE p.est_valide_par_admin = 1
        AND p.statut_publie = 'Publié'
        ORDER BY p.date_creation DESC
        LIMIT 8
    ");
    $req2->execute();
    $produits_db = $req2->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("Erreur accueil : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GreenMarket – Les trésors de nos coopératives</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    /* ========== STYLES (conservés) ========== */
    :root {
      --primary: #5D0D18;
      --primary-light: #7a1020;
      --secondary: #9FB2AC;
      --bg: #FFF9EB;
      --text-dark: #2C2C2C;
      --text-light: #6B6B6B;
      --gold: #c07a1a;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background-color: var(--bg);
      color: var(--text-dark);
      font-family: 'Lato', sans-serif;
      overflow-x: hidden;
    }

    h1, h2, h3, .playfair { font-family: 'Playfair Display', serif; }

    /* ========== ANIMATIONS ========== */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(40px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to   { opacity: 1; }
    }
    @keyframes slideRight {
      from { opacity: 0; transform: translateX(-40px); }
      to   { opacity: 1; transform: translateX(0); }
    }
    @keyframes scaleIn {
      from { opacity: 0; transform: scale(0.85); }
      to   { opacity: 1; transform: scale(1); }
    }
    @keyframes float {
      0%, 100% { transform: translateY(0px); }
      50%       { transform: translateY(-12px); }
    }
    @keyframes shimmer {
      0%   { background-position: -200% center; }
      100% { background-position:  200% center; }
    }
    @keyframes ticker {
      from { transform: translateX(0); }
      to   { transform: translateX(-50%); }
    }

    /* Scroll-reveal base */
    .reveal {
      opacity: 0;
      transform: translateY(35px);
      transition: opacity 0.7s ease, transform 0.7s ease;
    }
    .reveal.visible {
      opacity: 1;
      transform: translateY(0);
    }
    .reveal-left {
      opacity: 0;
      transform: translateX(-40px);
      transition: opacity 0.7s ease, transform 0.7s ease;
    }
    .reveal-left.visible {
      opacity: 1;
      transform: translateX(0);
    }
    .reveal-right {
      opacity: 0;
      transform: translateX(40px);
      transition: opacity 0.7s ease, transform 0.7s ease;
    }
    .reveal-right.visible {
      opacity: 1;
      transform: translateX(0);
    }

    /* ========== HERO ========== */
    .hero-section {
      position: relative;
      min-height: 85vh;
      display: flex;
      align-items: center;
      overflow: hidden;
    }
    .hero-bg {
      position: absolute;
      inset: 0;
    }
    .hero-bg img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transform: scale(1.05);
      transition: transform 8s ease-out;
    }
    .hero-bg img.loaded { transform: scale(1); }
    .hero-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(
        105deg,
        rgba(255,249,235,0.96) 0%,
        rgba(255,249,235,0.88) 35%,
        rgba(93,13,24,0.12) 70%,
        rgba(93,13,24,0.25) 100%
      );
    }
    .hero-content {
      position: relative;
      z-index: 2;
      padding: 0 5% 0 6%;
      max-width: 700px;
      animation: fadeUp 1s ease both;
    }
    .hero-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(93,13,24,0.08);
      border: 1px solid rgba(93,13,24,0.18);
      border-radius: 999px;
      padding: 6px 16px;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: var(--primary);
      margin-bottom: 24px;
    }
    .hero-dot {
      width: 7px;
      height: 7px;
      background: var(--primary);
      border-radius: 50%;
      animation: pulse-ring 2s infinite;
    }
    @keyframes pulse-ring {
      0%   { box-shadow: 0 0 0 0 rgba(93,13,24,0.4); }
      70%  { box-shadow: 0 0 0 18px rgba(93,13,24,0); }
      100% { box-shadow: 0 0 0 0 rgba(93,13,24,0); }
    }
    .hero-title {
      font-size: clamp(32px, 5vw, 60px);
      font-weight: 700;
      line-height: 1.1;
      color: var(--primary);
      margin-bottom: 20px;
    }
    .hero-title em {
      font-style: italic;
      color: var(--gold);
      display: block;
    }
    .hero-subtitle {
      font-size: 18px;
      color: var(--text-light);
      max-width: 460px;
      margin-bottom: 36px;
      line-height: 1.6;
    }
    .hero-cta-group {
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
    }
    .hero-decorative {
      position: absolute;
      right: 5%;
      bottom: 10%;
      z-index: 2;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }
    .hero-stat-card {
      background: rgba(255,255,255,0.92);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(93,13,24,0.1);
      border-radius: 16px;
      padding: 16px 20px;
      text-align: center;
      min-width: 110px;
    }
    .hero-stat-card .num {
      font-family: 'Playfair Display', serif;
      font-size: 28px;
      font-weight: 700;
      color: var(--primary);
      line-height: 1;
    }

    /* ========== TICKER ========== */
    .ticker-wrap {
      background: var(--primary);
      padding: 12px 0;
      overflow: hidden;
      white-space: nowrap;
    }
    .ticker-inner {
      display: inline-flex;
      gap: 0;
      animation: ticker 30s linear infinite;
    }
    .ticker-item {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      color: rgba(255,255,255,0.9);
      font-size: 13px;
      font-weight: 700;
      padding: 0 40px;
    }
    .ticker-sep { color: rgba(255,255,255,0.35); font-size: 20px; }

    /* ========== CATEGORIES ========== */
    .category-card {
      background: #fff;
      border-radius: 20px;
      padding: 28px 12px 22px;
      text-align: center;
      cursor: pointer;
      transition: transform 0.3s cubic-bezier(.34,1.56,.64,1), box-shadow 0.3s ease;
      box-shadow: 0 2px 12px rgba(93,13,24,0.07);
      border: 1.5px solid #f0e8d5;
    }
    .category-card:hover {
      transform: translateY(-8px) scale(1.03);
      box-shadow: 0 16px 36px rgba(93,13,24,0.16);
      background: #fff8f0;
    }
    .category-icon { font-size: 38px; margin-bottom: 10px; display: block; }
    .category-name { font-weight: 700; font-size: 12px; color: var(--text-dark); }

    /* ========== PRODUCT CARDS ========== */
    .product-card {
      background: #fff;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 4px 16px rgba(93,13,24,0.08);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border: 1.5px solid #f0e8d5;
    }
    .product-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 16px 40px rgba(93,13,24,0.16);
    }
    .product-img-wrap { position: relative; overflow: hidden; }
    .product-img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    .product-card:hover .product-img { transform: scale(1.07); }
    
    /* ========== BADGES ========== */
    .badge {
      display: inline-block;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 700;
      padding: 3px 12px;
    }
    .badge-secondary { background: var(--secondary); color: #fff; }
    .badge-rare { background: #c0392b; color: #fff; }
    .badge-bio { background: #6aaf6a; color: #fff; }
    .badge-fait { background: var(--gold); color: #fff; }
    .stars { color: #e0a82e; font-size: 14px; }

    /* ========== BUTTONS ========== */
    .btn-primary {
      background: var(--primary);
      color: #fff;
      border: none;
      border-radius: 999px;
      padding: 11px 24px;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s, transform 0.2s;
    }
    .btn-primary:hover { background: var(--primary-light); transform: translateY(-2px); }
    .btn-outline {
      background: transparent;
      color: var(--primary);
      border: 2px solid var(--primary);
      border-radius: 999px;
      padding: 9px 22px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.25s ease;
    }
    .btn-outline:hover { background: var(--primary); color: #fff; }
    .btn-sage {
      background: var(--secondary);
      color: #fff;
      border: none;
      border-radius: 999px;
      padding: 12px 28px;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s, transform 0.2s;
    }
    .btn-sage:hover { background: #8aa09a; transform: translateY(-2px); }

    /* ========== SECTION TITLE ========== */
    .section-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(22px, 3vw, 34px);
      font-weight: 700;
      position: relative;
      display: inline-block;
    }
    .section-title::after {
      content: '';
      display: block;
      height: 3px;
      background: linear-gradient(90deg, var(--primary), var(--secondary), transparent);
      width: 70%;
      margin-top: 8px;
    }

    /* ========== STORE CARDS ========== */
    .store-card {
      background: #fff;
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 4px 16px rgba(93,13,24,0.08);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      cursor: pointer;
      border: 1.5px solid #f0e8d5;
    }
    .store-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 16px 36px rgba(93,13,24,0.14);
    }
    .store-banner {
      height: 160px;
      width: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    .store-card:hover .store-banner { transform: scale(1.05); }
    .store-info { padding: 1.1rem 1.2rem; }
    .store-name { font-weight: 700; font-size: 1.05rem; color: var(--primary); }
    .store-category { font-size: 0.68rem; color: var(--text-light); text-transform: uppercase; }

    /* ========== WHY SECTION ========== */
    .why-feature {
      display: flex;
      align-items: flex-start;
      gap: 16px;
      padding: 18px 0;
      border-bottom: 1px solid rgba(93,13,24,0.08);
    }
    .why-check {
      width: 32px;
      height: 32px;
      background: var(--primary);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 15px;
    }

    /* ========== TOAST ========== */
    #toast {
      position: fixed;
      bottom: 28px;
      right: 28px;
      background: var(--primary);
      color: #fff;
      padding: 14px 22px;
      border-radius: 14px;
      font-weight: 700;
      z-index: 9999;
      transform: translateY(80px);
      opacity: 0;
      transition: 0.4s cubic-bezier(.22,1,.36,1);
    }
    #toast.show { transform: translateY(0); opacity: 1; }

  </style>
</head>
<body data-active-page="accueil">

<div id="toast">✓ Produit ajouté au panier !</div>

<!-- HEADER -->
<?php include 'header.php'; ?>

<!-- TICKER -->
<div class="ticker-wrap">
  <div class="ticker-inner" id="ticker">
    <span class="ticker-item">🌿 Livraison offerte dès 250 DH <span class="ticker-sep">|</span></span>
    <span class="ticker-item">🏺 Artisanat 100% authentique <span class="ticker-sep">|</span></span>
    <span class="ticker-item">🤝 Commerce équitable & local <span class="ticker-sep">|</span></span>
    <span class="ticker-item">🇲🇦 Fabriqué au Maroc <span class="ticker-sep">|</span></span>
    <span class="ticker-item">✨ Nouveaux produits chaque semaine <span class="ticker-sep">|</span></span>
    <span class="ticker-item">🌿 Livraison offerte dès 250 DH <span class="ticker-sep">|</span></span>
    <span class="ticker-item">🏺 Artisanat 100% authentique <span class="ticker-sep">|</span></span>
    <span class="ticker-item">🤝 Commerce équitable & local <span class="ticker-sep">|</span></span>
    <span class="ticker-item">🇲🇦 Fabriqué au Maroc <span class="ticker-sep">|</span></span>
    <span class="ticker-item">✨ Nouveaux produits chaque semaine <span class="ticker-sep">|</span></span>
  </div>
</div>

<!-- HERO SECTION -->
<section class="hero-section">
  <div class="hero-bg">
    <img src="IMAGES/hero2.PNG" alt="Artisanat Marocain" id="heroImg"
      onerror="this.src='https://placehold.co/1400x700/5D0D18/fff?text=GreenMarket'"/>
  </div>
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <div class="hero-eyebrow">
      <span class="hero-dot"></span>
      Commerce équitable & authentique
    </div>
    <h1 class="hero-title">
      Les trésors de nos
      <em>coopératives,</em>
      directement chez vous
    </h1>
    <p class="hero-subtitle">Produits authentiques, savoir-faire ancestral et circuits courts — chaque achat soutient une artisane marocaine.</p>
    <div class="hero-cta-group">
      <button class="btn-sage" onclick="window.location.href='store.php'">Découvrir nos coopératives</button>
      <button class="btn-outline" onclick="document.getElementById('products').scrollIntoView({behavior:'smooth'})">Voir les produits ↓</button>
    </div>
  </div>
  <div class="hero-decorative">
    <div class="hero-stat-card"><div class="num">120+</div><div style="font-size:11px;">Coopératives</div></div>
    <div class="hero-stat-card"><div class="num">4 500</div><div style="font-size:11px;">Artisans</div></div>
  </div>
</section>

<!-- CATEGORIES -->
<section class="py-16 px-4 max-w-7xl mx-auto">
  <div class="mb-10 reveal">
    <h2 class="section-title">Nos catégories de produits traditionnels</h2>
  </div>
  <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-8 gap-4 reveal">
    <div class="category-card" onclick="window.location.href='produits.php?cat=Caftans'"><span class="category-icon">👘</span><span class="category-name">Caftans</span></div>
    <div class="category-card" onclick="window.location.href='produits.php?cat=Tapis'"><span class="category-icon">🪑</span><span class="category-name">Tapis</span></div>
    <div class="category-card" onclick="window.location.href='produits.php?cat=Poterie'"><span class="category-icon">🏺</span><span class="category-name">Poterie</span></div>
    <div class="category-card" onclick="window.location.href='produits.php?cat=Bois'"><span class="category-icon">🪵</span><span class="category-name">Marqueterie</span></div>
    <div class="category-card" onclick="window.location.href='produits.php?cat=Bijoux'"><span class="category-icon">💍</span><span class="category-name">Bijoux</span></div>
    <div class="category-card" onclick="window.location.href='produits.php?cat=Lampes'"><span class="category-icon">🕯️</span><span class="category-name">Lampes</span></div>
    <div class="category-card" onclick="window.location.href='produits.php?cat=Cosmetiques'"><span class="category-icon">🧴</span><span class="category-name">Cosmétiques</span></div>
    <div class="category-card" onclick="window.location.href='produits.php?cat=Terroir'"><span class="category-icon">🍯</span><span class="category-name">Terroir</span></div>
  </div>
</section>

<!-- BOUTIQUES PARTENAIRES - DEPUIS LA BASE DE DONNÉES -->
<section class="py-14 px-4" style="background: linear-gradient(180deg,#fdf8ee,#fff9eb);">
  <div class="max-w-7xl mx-auto">
    <div class="mb-10 reveal">
      <h2 class="section-title">Nos boutiques partenaires</h2>
      <p class="mt-2 text-sm text-gray-500">Découvrez nos producteurs locaux et artisans</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 reveal" id="storesGrid">
      <?php if (empty($boutiques_db)): ?>
        <div class="text-center text-gray-500 col-span-3">Aucune boutique disponible pour le moment.</div>
      <?php else: ?>
        <?php foreach ($boutiques_db as $boutique): ?>
        <div class="store-card" onclick="window.location.href='store.php?id=<?php echo $boutique['id_boutique']; ?>'">
          <div class="store-banner-wrap">
            <img src="<?php echo !empty($boutique['image']) ? htmlspecialchars($boutique['image']) : 'https://placehold.co/400x200/5D0D18/fff?text=' . urlencode($boutique['nom_boutique']); ?>" 
                 class="store-banner" 
                 alt="<?php echo htmlspecialchars($boutique['nom_boutique']); ?>"
                 onerror="this.src='https://placehold.co/400x200/5D0D18/fff?text=<?php echo urlencode($boutique['nom_boutique']); ?>'">
          </div>
          <div class="store-info">
            <div class="store-name"><?php echo htmlspecialchars($boutique['nom_boutique']); ?></div>
            <div class="store-category"><?php echo htmlspecialchars($boutique['producteur_nom']); ?></div>
            <p class="text-sm mt-2" style="color:var(--text-light)"><?php echo htmlspecialchars(substr($boutique['description'] ?? 'Boutique artisanale', 0, 70)); ?>…</p>
            <div class="flex items-center gap-1 mt-3">
              <span class="stars">★★★★★</span>
              <span style="font-size:12px;color:var(--text-light);">4.8</span>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div class="text-center mt-10 reveal">
      <button class="btn-outline" onclick="window.location.href='store.php'">Voir toutes les boutiques →</button>
    </div>
  </div>
</section>

<!-- PRODUITS POPULAIRES -->
<section id="products" class="py-16 px-4" style="background:#fff;">
  <div class="max-w-7xl mx-auto">
    <div class="mb-10 reveal">
      <h2 class="section-title">Produits populaires</h2>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 reveal" id="productsGrid"></div>
    <div class="text-center mt-10 reveal">
      <button class="btn-outline" onclick="window.location.href='produits.php'">Voir tous les produits →</button>
    </div>
  </div>
</section>

<!-- WHY SECTION -->
<section class="py-16 px-4" style="background:linear-gradient(135deg,#fdf5e0,#f5ede0);">
  <div class="max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-14 items-center">
    <div class="reveal-left">
      <h2 class="section-title mb-8">Pourquoi acheter en coopérative ?</h2>
      <div class="why-feature"><div class="why-check">✓</div><div><p class="font-bold">Produits authentiques</p><p class="text-sm text-gray-500 mt-1">Sélectionnés avec soin, issus du savoir-faire local.</p></div></div>
      <div class="why-feature"><div class="why-check">✓</div><div><p class="font-bold">Prix équitable</p><p class="text-sm text-gray-500 mt-1">Vos achats soutiennent directement les producteurs.</p></div></div>
      <div class="why-feature"><div class="why-check">✓</div><div><p class="font-bold">Traçabilité garantie</p><p class="text-sm text-gray-500 mt-1">Connaissez l'origine exacte de chaque produit.</p></div></div>
      <div class="why-feature"><div class="why-check">✓</div><div><p class="font-bold">Savoir-faire traditionnel</p><p class="text-sm text-gray-500 mt-1">Transmission des techniques ancestrales.</p></div></div>
    </div>
    <div class="flex justify-center reveal-right">
      <img src="IMAGES\imagepage.jpg" alt="Artisanat coopératif" class="rounded-2xl shadow-2xl w-full max-w-sm" style="aspect-ratio:1/1;object-fit:cover;">
    </div>
  </div>
</section>

<?php include 'footer.php'; ?>

<script>
// Produits pour la grille (données statiques ou depuis PHP)
const productsData = <?php echo json_encode($produits_db); ?>;

// Si pas de produits en BD, utiliser les données par défaut
const defaultProducts = [
  { id: 101, name: "Caftan en Soie", price: "500 DH", image: "IMAGES/CaftanSoie.jpg", coop: "Maison du Caftan", rating: 4.8, reviews: 212, badge: "Artisanal", badgeClass: "secondary" },
  { id: 201, name: "Tapis Berbère", price: "1 200 DH", image: "IMAGES/img/tapis.jpeg", coop: "Tapis Berbère d'Atlas", rating: 5, reviews: 189, badge: "Fait main", badgeClass: "fait" },
  { id: 301, name: "Tajine Traditionnel", price: "180 DH", image: "IMAGES/img/tajin.jpeg", coop: "Poterie de Safi", rating: 4.7, reviews: 156, badge: "Artisanat", badgeClass: "secondary" },
  { id: 501, name: "Bracelet Amazigh", price: "450 DH", image: "IMAGES/img/accesorio.jpeg", coop: "Bijoux Amazigh", rating: 4.9, reviews: 203, badge: "Artisanal", badgeClass: "secondary" },
  { id: 701, name: "Huile d'Argan", price: "120 DH", image: "IMAGES/img/argan.jpg", coop: "Argamane Naturel", rating: 5, reviews: 310, badge: "Bio", badgeClass: "bio" },
  { id: 601, name: "Lanterne Laiton", price: "380 DH", image: "IMAGES/img/luz.jpeg", coop: "Lumières de Marrakech", rating: 4.7, reviews: 98, badge: "Rare", badgeClass: "rare" }
];

const products = productsData.length > 0 ? productsData.map(p => ({
  id: p.id_produit,
  name: p.nom_produit,
  price: p.prix_unitaire + " DH",
  image: p.photo_url || "IMAGES/default-product.jpg",
  coop: p.nom_boutique || "Artisan",
  rating: 4.5,
  reviews: 0,
  badge: "Artisanal",
  badgeClass: "secondary"
})) : defaultProducts;

function escapeHtml(s) {
  if (!s) return '';
  return s.replace(/[&<>]/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[m]));
}

function renderStars(rating) {
  return '★'.repeat(Math.floor(rating)) + '☆'.repeat(5 - Math.floor(rating));
}

function renderProducts() {
  const grid = document.getElementById('productsGrid');
  if (!grid) return;
  grid.innerHTML = products.map(p => {
    let badgeClass = p.badgeClass === 'secondary' ? 'badge-secondary' : p.badgeClass === 'bio' ? 'badge-bio' : p.badgeClass === 'rare' ? 'badge-rare' : 'badge-fait';
    return `
    <div class="product-card">
      <div class="product-img-wrap">
        <img src="${p.image}" class="product-img" loading="lazy"
          onerror="this.src='https://placehold.co/300x200/e8d8c4/5D0D18?text=${encodeURIComponent(p.name)}'">
      </div>
      <div class="p-4">
        <span class="badge ${badgeClass}">${p.badge}</span>
        <h3 class="font-bold mt-2">${p.name}</h3>
        <p class="text-xs text-gray-500 mt-1">👩‍🌾 ${p.coop}</p>
        <div class="stars my-2">${renderStars(p.rating)} <span style="font-size:12px;">(${p.reviews})</span></div>
        <p class="font-bold text-xl" style="color:var(--primary);">${p.price}</p>
        <button class="btn-primary w-full mt-3 py-2" onclick='addToCart(${JSON.stringify(p)})'>Ajouter au panier</button>
      </div>
    </div>`;
  }).join('');
}

let cart = [];
function updateCartCount(total) {
  const badge = document.getElementById('cart-count');
  if (badge && total !== undefined) badge.textContent = total;
}
function addToCart(product) {
  const formData = new FormData();
  formData.append('id_produit', product.id);
  formData.append('quantite', 1);

  fetch('ajouter_panier.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      const toast = document.getElementById('toast');
      if (data.success) {
        updateCartCount(data.total_panier);
        toast.textContent = `✓ ${product.name} ajouté au panier`;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2600);
      } else if (data.message.includes('connecter')) {
        toast.textContent = '⚠️ Connectez-vous pour ajouter au panier';
        toast.classList.add('show');
        setTimeout(() => { window.location.href = 'signin.php'; }, 1500);
      } else {
        toast.textContent = '❌ ' + data.message;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2600);
      }
    })
    .catch(() => {
      const toast = document.getElementById('toast');
      toast.textContent = '❌ Erreur de connexion au serveur';
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 2600);
    });
}

// Scroll reveal
function initReveal() {
  const elements = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(e => {
      if (e.isIntersecting) e.target.classList.add('visible');
    });
  }, { threshold: 0.1 });
  elements.forEach(el => observer.observe(el));
}

document.addEventListener('DOMContentLoaded', () => {
  renderProducts();
  initReveal();
  
  document.getElementById('becomePartnerBtn')?.addEventListener('click', () => {
    <?php if (isset($_SESSION['user_role'])): ?>
      <?php if ($_SESSION['user_role'] === 'producteur'): ?>
        alert("Vous êtes déjà producteur !");
      <?php else: ?>
        alert("Pour devenir partenaire, créez un compte producteur.");
        window.location.href = 'signup.php';
      <?php endif; ?>
    <?php else: ?>
      alert("Veuillez vous connecter ou créer un compte producteur.");
      window.location.href = 'signin.php';
    <?php endif; ?>
  });
});
</script>
</body>
</html>
