<?php
session_start();
include('connexion.php');

$theme = $_COOKIE['theme'] ?? 'light';

try {
    $req = $pdo->prepare("
        SELECT b.*, p.nom_entreprise as producteur_nom, 
               p.est_valide_par_admin as producteur_valide
        FROM boutique b
        JOIN producteur p ON b.id_producteur = p.id_producteur
        WHERE p.est_valide_par_admin = 1
        AND b.statut = 'valide'
        ORDER BY b.date_creation DESC
        LIMIT 6
    ");
    $req->execute();
    $boutiques_db = $req->fetchAll(PDO::FETCH_ASSOC);

    $req2 = $pdo->prepare("
        SELECT p.*, c.nom_categorie, b.nom_boutique
        FROM produit p
        JOIN boutique b ON p.id_boutique = b.id_boutique
        LEFT JOIN categorie c ON p.id_categorie = c.id_categorie
        WHERE p.est_valide_par_admin = 1
        AND p.statut_publie = 'Publié'
        AND b.statut = 'valide'
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
<html lang="fr" data-theme="<?php echo $theme; ?>">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>GreenMarket – Les trésors de nos coopératives</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root {
      --primary: #5D0D18;
      --primary-light: #7a1020;
      --secondary: #9FB2AC;
      --secondary-dark: #8aa09a;
      --gold: #c07a1a;
      
      --bg: #FFF9EB;
      --bg-light: #f5f0e8;
      --bg-card: #ffffff;
      --bg-input: #ffffff;
      --bg-section: #fdf8ee;
      --bg-section-alt: #fff9eb;
      --bg-why: linear-gradient(135deg, #fdf5e0, #f5ede0);
      
      --text-dark: #2C2C2C;
      --text-light: #6B6B6B;
      --text-muted: #6B6B6B;
      
      --border-color: #f0e8d5;
      --shadow-color: rgba(93, 13, 24, 0.08);
      --shadow-hover: rgba(93, 13, 24, 0.16);
      
      --hero-overlay: linear-gradient(105deg, rgba(255,249,235,0.96) 0%, rgba(255,249,235,0.88) 35%, rgba(93,13,24,0.12) 70%, rgba(93,13,24,0.25) 100%);
      --hero-stat-bg: rgba(255,255,255,0.92);
      --hero-stat-border: rgba(93,13,24,0.1);
      
      --ticker-bg: var(--primary);
      --ticker-text: rgba(255,255,255,0.9);
      
      --category-bg: #fff;
      --category-border: #f0e8d5;
      --category-hover: #fff8f0;
      
      --product-bg: #fff;
      --product-border: #f0e8d5;
      
      --store-bg: #fff;
      --store-border: #f0e8d5;
      
      --why-border: rgba(93,13,24,0.08);
      --why-check-bg: var(--primary);
      
      --toast-bg: var(--primary);
      --toast-text: #fff;
      
      --badge-secondary-bg: var(--secondary);
      --badge-rare-bg: #c0392b;
      --badge-bio-bg: #6aaf6a;
      --badge-fait-bg: var(--gold);
      --badge-text: #fff;
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
      --bg-section: #3d3229;
      --bg-section-alt: #2c241e;
      --bg-why: linear-gradient(135deg, #3d3229, #2c241e);
      
      --text-dark: #f0e6d8;
      --text-light: #b8a896;
      --text-muted: #b8a896;
      
      --border-color: #5a4a3a;
      --shadow-color: rgba(0, 0, 0, 0.3);
      --shadow-hover: rgba(0, 0, 0, 0.4);
      
      --hero-overlay: linear-gradient(105deg, rgba(44,36,30,0.96) 0%, rgba(44,36,30,0.88) 35%, rgba(44,36,30,0.12) 70%, rgba(44,36,30,0.25) 100%);
      --hero-stat-bg: rgba(61,50,41,0.92);
      --hero-stat-border: rgba(240,230,216,0.1);
      
      --ticker-bg: #1a1410;
      --ticker-text: #f0e6d8;
      
      --category-bg: #3d3229;
      --category-border: #5a4a3a;
      --category-hover: #4d3d32;
      
      --product-bg: #3d3229;
      --product-border: #5a4a3a;
      
      --store-bg: #3d3229;
      --store-border: #5a4a3a;
      
      --why-border: #5a4a3a;
      --why-check-bg: var(--gold);
      
      --toast-bg: var(--primary);
      --toast-text: #f0e6d8;
      
      --badge-secondary-bg: #6d4c3a;
      --badge-rare-bg: #8a2a20;
      --badge-bio-bg: #4a7a4a;
      --badge-fait-bg: #b8943a;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      background-color: var(--bg);
      color: var(--text-dark);
      font-family: 'Lato', sans-serif;
      overflow-x: hidden;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    h1, h2, h3, .playfair { font-family: 'Playfair Display', serif; }

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
      background: var(--hero-overlay);
      transition: background 0.3s ease;
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
      transition: background 0.3s ease, border-color 0.3s ease, color 0.3s ease;
    }
    [data-theme="dark"] .hero-eyebrow {
      background: rgba(240,230,216,0.08);
      border-color: rgba(240,230,216,0.18);
      color: var(--text-dark);
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
    [data-theme="dark"] .hero-dot {
      background: var(--gold);
    }
    .hero-title {
      font-size: clamp(32px, 5vw, 60px);
      font-weight: 700;
      line-height: 1.1;
      color: var(--primary);
      margin-bottom: 20px;
      transition: color 0.3s ease;
    }
    [data-theme="dark"] .hero-title {
      color: var(--text-dark);
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
      transition: color 0.3s ease;
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
      background: var(--hero-stat-bg);
      backdrop-filter: blur(10px);
      border: 1px solid var(--hero-stat-border);
      border-radius: 16px;
      padding: 16px 20px;
      text-align: center;
      min-width: 110px;
      transition: background 0.3s ease, border-color 0.3s ease;
    }
    .hero-stat-card .num {
      font-family: 'Playfair Display', serif;
      font-size: 28px;
      font-weight: 700;
      color: var(--primary);
      line-height: 1;
      transition: color 0.3s ease;
    }
    [data-theme="dark"] .hero-stat-card .num {
      color: var(--gold);
    }
    .hero-stat-card div {
      color: var(--text-light);
      transition: color 0.3s ease;
    }

    .ticker-wrap {
      background: var(--ticker-bg);
      padding: 12px 0;
      overflow: hidden;
      white-space: nowrap;
      transition: background 0.3s ease;
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
      color: var(--ticker-text);
      font-size: 13px;
      font-weight: 700;
      padding: 0 40px;
      transition: color 0.3s ease;
    }
    .ticker-sep { color: rgba(255,255,255,0.35); font-size: 20px; }

    .category-card {
      background: var(--category-bg);
      border-radius: 20px;
      padding: 28px 12px 22px;
      text-align: center;
      cursor: pointer;
      transition: transform 0.3s cubic-bezier(.34,1.56,.64,1), box-shadow 0.3s ease, background 0.3s ease, border-color 0.3s ease;
      box-shadow: 0 2px 12px var(--shadow-color);
      border: 1.5px solid var(--category-border);
    }
    .category-card:hover {
      transform: translateY(-8px) scale(1.03);
      box-shadow: 0 16px 36px var(--shadow-hover);
      background: var(--category-hover);
    }
    .category-icon { font-size: 38px; margin-bottom: 10px; display: block; }
    .category-name { 
      font-weight: 700; 
      font-size: 12px; 
      color: var(--text-dark);
      transition: color 0.3s ease;
    }

    .product-card {
      background: var(--product-bg);
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 4px 16px var(--shadow-color);
      transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease, border-color 0.3s ease;
      border: 1.5px solid var(--product-border);
    }
    .product-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 16px 40px var(--shadow-hover);
    }
    .product-img-wrap { position: relative; overflow: hidden; }
    .product-img {
      width: 100%;
      height: 200px;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    .product-card:hover .product-img { transform: scale(1.07); }
    
    .badge {
      display: inline-block;
      border-radius: 999px;
      font-size: 11px;
      font-weight: 700;
      padding: 3px 12px;
      color: var(--badge-text);
    }
    .badge-secondary { background: var(--badge-secondary-bg); }
    .badge-rare { background: var(--badge-rare-bg); }
    .badge-bio { background: var(--badge-bio-bg); }
    .badge-fait { background: var(--badge-fait-bg); }
    .stars { color: #e0a82e; font-size: 14px; }

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
    [data-theme="dark"] .btn-outline {
      color: var(--text-dark);
      border-color: var(--text-dark);
    }
    [data-theme="dark"] .btn-outline:hover {
      background: var(--primary);
      color: #fff;
      border-color: var(--primary);
    }
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
    .btn-sage:hover { background: var(--secondary-dark); transform: translateY(-2px); }

    .section-title {
      font-family: 'Playfair Display', serif;
      font-size: clamp(22px, 3vw, 34px);
      font-weight: 700;
      position: relative;
      display: inline-block;
      color: var(--text-dark);
      transition: color 0.3s ease;
    }
    .section-title::after {
      content: '';
      display: block;
      height: 3px;
      background: linear-gradient(90deg, var(--primary), var(--secondary), transparent);
      width: 70%;
      margin-top: 8px;
      transition: background 0.3s ease;
    }

    .store-card {
      background: var(--store-bg);
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 4px 16px var(--shadow-color);
      transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease, border-color 0.3s ease;
      cursor: pointer;
      border: 1.5px solid var(--store-border);
    }
    .store-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 16px 36px var(--shadow-hover);
    }
    .store-banner {
      height: 160px;
      width: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    .store-card:hover .store-banner { transform: scale(1.05); }
    .store-info { padding: 1.1rem 1.2rem; }
    .store-name { 
      font-weight: 700; 
      font-size: 1.05rem; 
      color: var(--primary);
      transition: color 0.3s ease;
    }
    [data-theme="dark"] .store-name {
      color: var(--gold);
    }
    .store-category { 
      font-size: 0.68rem; 
      color: var(--text-light); 
      text-transform: uppercase;
      transition: color 0.3s ease;
    }
    .store-info p {
      color: var(--text-light);
      transition: color 0.3s ease;
    }

    .why-section {
      background: var(--bg-why);
      transition: background 0.3s ease;
    }
    .why-feature {
      display: flex;
      align-items: flex-start;
      gap: 16px;
      padding: 18px 0;
      border-bottom: 1px solid var(--why-border);
      transition: border-color 0.3s ease;
    }
    .why-check {
      width: 32px;
      height: 32px;
      background: var(--why-check-bg);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 15px;
      flex-shrink: 0;
      transition: background 0.3s ease;
    }
    .why-feature p {
      color: var(--text-dark);
      transition: color 0.3s ease;
    }
    .why-feature .text-sm {
      color: var(--text-light) !important;
      transition: color 0.3s ease;
    }

    #toast {
      position: fixed;
      bottom: 28px;
      right: 28px;
      background: var(--toast-bg);
      color: var(--toast-text);
      padding: 14px 22px;
      border-radius: 14px;
      font-weight: 700;
      z-index: 9999;
      transform: translateY(80px);
      opacity: 0;
      transition: 0.4s cubic-bezier(.22,1,.36,1);
    }
    #toast.show { transform: translateY(0); opacity: 1; }

    .section-categories {
      background: var(--bg);
      transition: background 0.3s ease;
    }
    .section-stores {
      background: var(--bg-section);
      transition: background 0.3s ease;
    }
    .section-products {
      background: var(--bg-card);
      transition: background 0.3s ease;
    }

    @media (max-width: 768px) {
      .hero-decorative {
        display: none;
      }
      .hero-content {
        padding: 0 5%;
      }
    }
  </style>
</head>
<body data-active-page="accueil">

<div id="toast">✓ Produit ajouté au panier !</div>

<?php include 'header.php'; ?>

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

<section class="py-16 px-4 max-w-7xl mx-auto section-categories">
  <div class="mb-10 reveal">
    <h2 class="section-title">Nos categories de produits traditionnels</h2>
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

<section class="py-14 px-4 section-stores">
  <div class="max-w-7xl mx-auto">
    <div class="mb-10 reveal">
      <h2 class="section-title">Nos boutiques partenaires</h2>
      <p class="mt-2 text-sm" style="color: var(--text-light);">Découvrez nos producteurs locaux et artisans</p>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 reveal" id="storesGrid">
      <?php if (empty($boutiques_db)): ?>
        <div class="text-center col-span-3" style="color: var(--text-light);">Aucune boutique disponible pour le moment.</div>
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
            <p class="text-sm mt-2"><?php echo htmlspecialchars(substr($boutique['description'] ?? 'Boutique artisanale', 0, 70)); ?>…</p>
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

<section id="products" class="py-16 px-4 section-products">
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

<section class="py-16 px-4 why-section">
  <div class="max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-14 items-center">
    <div class="reveal-left">
      <h2 class="section-title mb-8">Pourquoi acheter en coopérative ?</h2>
      <div class="why-feature"><div class="why-check">✓</div><div><p class="font-bold">Produits authentiques</p><p class="text-sm">Sélectionnés avec soin, issus du savoir-faire local.</p></div></div>
      <div class="why-feature"><div class="why-check">✓</div><div><p class="font-bold">Prix équitable</p><p class="text-sm">Vos achats soutiennent directement les producteurs.</p></div></div>
      <div class="why-feature"><div class="why-check">✓</div><div><p class="font-bold">Traçabilité garantie</p><p class="text-sm">Connaissez l'origine exacte de chaque produit.</p></div></div>
      <div class="why-feature"><div class="why-check">✓</div><div><p class="font-bold">Savoir-faire traditionnel</p><p class="text-sm">Transmission des techniques ancestrales.</p></div></div>
    </div>
    <div class="flex justify-center reveal-right">
      <img src="IMAGES/imagepage.jpg" alt="Artisanat coopératif" class="rounded-2xl shadow-2xl w-full max-w-sm" style="aspect-ratio:1/1;object-fit:cover;">
    </div>
  </div>
</section>

<?php include 'footer.php'; ?>

<script>
const productsData = <?php echo json_encode($produits_db); ?>;

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
        <h3 class="font-bold mt-2" style="color: var(--text-dark);">${p.name}</h3>
        <p class="text-xs mt-1" style="color: var(--text-light);">👩‍🌾 ${p.coop}</p>
        <div class="stars my-2">${renderStars(p.rating)} <span style="font-size:12px;color:var(--text-light);">(${p.reviews})</span></div>
        <p class="font-bold text-xl" style="color:var(--primary);">${p.price}</p>
        <button class="btn-primary w-full mt-3 py-2" onclick='addToCart(${JSON.stringify(p)}, this)'>Ajouter au panier</button>
      </div>
    </div>`;
  }).join('');
}

let cart = [];
function updateCartCount(total) {
  const badges = document.querySelectorAll('.cart-badge');
  badges.forEach(badge => {
    if (total !== undefined) {
      badge.textContent = total;
      if (total > 0) badge.classList.add('show');
    }
  });
}

function addToCart(product, btn) {
  if (btn && btn.disabled) return;
  if (btn) {
    btn.disabled = true;
    btn.textContent = '⏳ ...';
    btn.style.opacity = '0.7';
  }

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
        toast.style.background = 'var(--toast-bg)';
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2600);
        if (btn) { btn.textContent = '✓ Ajouté'; btn.style.opacity = '1'; }
        setTimeout(() => { if (btn) { btn.textContent = 'Ajouter au panier'; btn.disabled = false; } }, 2000);
      } else if (data.message.includes('connecter')) {
        toast.textContent = '⚠️ Connectez-vous pour ajouter au panier';
        toast.style.background = '#e67e22';
        toast.classList.add('show');
        setTimeout(() => { window.location.href = 'signin.php'; }, 1500);
      } else {
        toast.textContent = '❌ ' + data.message;
        toast.style.background = '#c0392b';
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2600);
        if (btn) { btn.textContent = 'Ajouter au panier'; btn.disabled = false; btn.style.opacity = '1'; }
      }
    })
    .catch(() => {
      const toast = document.getElementById('toast');
      toast.textContent = '❌ Erreur de connexion au serveur';
      toast.style.background = '#c0392b';
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 2600);
      if (btn) { btn.textContent = 'Ajouter au panier'; btn.disabled = false; btn.style.opacity = '1'; }
    });
}

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
    const toast = document.getElementById('toast');
    <?php if (isset($_SESSION['user_role'])): ?>
      <?php if ($_SESSION['user_role'] === 'producteur'): ?>
        toast.textContent = '✓ Vous êtes déjà producteur !';
        toast.style.background = 'var(--toast-bg)';
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 2600);
      <?php else: ?>
        toast.textContent = '⚠️ Pour devenir partenaire, créez un compte producteur.';
        toast.style.background = '#e67e22';
        toast.classList.add('show');
        setTimeout(() => { window.location.href = 'signup.php'; }, 2000);
      <?php endif; ?>
    <?php else: ?>
      toast.textContent = '⚠️ Veuillez vous connecter ou créer un compte producteur.';
      toast.style.background = '#e67e22';
      toast.classList.add('show');
      setTimeout(() => { window.location.href = 'signin.php'; }, 2000);
    <?php endif; ?>
  });
});
</script>
</body>
</html>