<?php
// Contador del carrito
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += isset($item['quantity']) ? $item['quantity'] : 1;
    }
}
$theme = $_COOKIE['theme'] ?? 'light';
$currentPage = basename($_SERVER['PHP_SELF']);

// Dashboard link según rol
$dashboardLink = '';
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'client') $dashboardLink = 'dashboard_client.php';
    elseif ($_SESSION['user_role'] === 'producteur') $dashboardLink = 'dashboard-producteur.php';
    elseif ($_SESSION['user_role'] === 'admin') $dashboardLink = 'dashboard_admin.php';
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenMarket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* ===== VARIABLES ===== */
        :root {
            --primary:        #5D0D18;
            --primary-light:  #7a1020;
            --secondary:      #9FB2AC;
            --gold:           #c07a1a;
            --bg:             #FFF9EB;
            --bg-light:       #f5f0e8;
            --bg-card:        #ffffff;
            --bg-input:       #ffffff;
            --text-dark:      #2C2C2C;
            --text-light:     #6B6B6B;
            --border-color:   #e5e7eb;
            --shadow-color:   rgba(93,13,24,0.1);

            --header-bg:         #5D0D18;
            --header-text:       #ffffff;
            --header-bg-hover:   rgba(255,255,255,0.15);
            --header-shadow:     rgba(93,13,24,0.18);
            --header-border:     rgba(255,255,255,0.05);

            --dropdown-bg:      #ffffff;
            --dropdown-text:    #2C2C2C;
            --dropdown-hover:   #FFF9EB;
            --dropdown-divider: #f0f0f0;

            --mobile-menu-bg:   #4A0E17;
            --mobile-menu-text: rgba(255,255,255,0.8);
            --mobile-menu-hover:rgba(255,255,255,0.06);

            --suggestions-bg:     #ffffff;
            --suggestions-border: #e8ddd0;
            --suggestions-shadow: rgba(0,0,0,0.15);
            --suggestions-hover:  #FFF9EB;
            --suggestions-text:   #2C2C2C;
            --suggestions-muted:  #6B6B6B;
        }

        [data-theme="dark"] {
            --primary:        #8a6048;
            --primary-light:  #a0785a;
            --secondary:      #6d4c3a;
            --gold:           #d4a85c;
            --bg:             #2c241e;
            --bg-light:       #3d3229;
            --bg-card:        #3d3229;
            --bg-input:       #4d3d32;
            --text-dark:      #f0e6d8;
            --text-light:     #b8a896;
            --border-color:   #5a4a3a;
            --shadow-color:   rgba(0,0,0,0.4);

            --header-bg:        #1a1410;
            --header-text:      #f0e6d8;
            --header-bg-hover:  rgba(240,230,216,0.12);
            --header-shadow:    rgba(0,0,0,0.4);
            --header-border:    rgba(240,230,216,0.05);

            --dropdown-bg:      #3d3229;
            --dropdown-text:    #f0e6d8;
            --dropdown-hover:   #4d3d32;
            --dropdown-divider: #5a4a3a;

            --mobile-menu-bg:    #1a1410;
            --mobile-menu-text:  rgba(240,230,216,0.8);
            --mobile-menu-hover: rgba(240,230,216,0.06);

            --suggestions-bg:     #3d3229;
            --suggestions-border: #5a4a3a;
            --suggestions-shadow: rgba(0,0,0,0.4);
            --suggestions-hover:  #4d3d32;
            --suggestions-text:   #f0e6d8;
            --suggestions-muted:  #b8a896;
        }

        /* ===== HEADER ===== */
        .hdr {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: var(--header-bg);
            box-shadow: 0 4px 25px var(--header-shadow);
            border-bottom: 1px solid var(--header-border);
            font-family: 'Lato', sans-serif;
            transition: background .3s;
        }

        .hdr-inner {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 1.25rem;
            padding: .85rem 2rem;
        }

        /* Logo */
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            flex-shrink: 0;
            text-decoration: none;
        }
        .logo img {
            height: 38px;
            border-radius: 6px;
            object-fit: contain;
        }
        .logo-text {
            font-family: 'Playfair Display', serif;
            font-size: 1.45rem;
            font-weight: 700;
            color: var(--header-text);
        }
        .logo-accent { color: var(--secondary); }

        /* Search — ocupa todo el espacio libre */
        .search-wrap {
            flex: 1;
            position: relative;
        }
        .search-wrap input {
            width: 100%;
            padding: .65rem 1rem .65rem 2.8rem;
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 12px;
            font-size: .88rem;
            color: var(--text-dark);
            background: rgba(255,255,255,0.96);
            outline: none;
            transition: box-shadow .25s;
        }
        [data-theme="dark"] .search-wrap input {
            background: var(--bg-input);
            color: var(--text-dark);
            border-color: var(--border-color);
        }
        .search-wrap input:focus {
            box-shadow: 0 0 0 4px rgba(159,178,172,.35);
        }
        .search-ico {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: .95rem;
            opacity: .7;
            pointer-events: none;
        }
        [data-theme="dark"] .search-ico { color: var(--gold); }

        /* Suggestions */
        .suggestions {
            position: absolute;
            top: calc(100% + 8px);
            left: 0; right: 0;
            background: var(--suggestions-bg);
            border: 1px solid var(--suggestions-border);
            border-radius: 12px;
            box-shadow: 0 10px 30px var(--suggestions-shadow);
            z-index: 1050;
            display: none;
            max-height: 390px;
            overflow-y: auto;
            padding: .4rem 0;
        }
        .suggestions.show { display: block; }
        .sug-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: .6rem 1rem;
            color: var(--suggestions-text);
            text-decoration: none;
            cursor: pointer;
            transition: background .15s;
        }
        .sug-item:hover { background: var(--suggestions-hover); }
        .sug-img {
            width: 36px; height: 36px;
            border-radius: 8px;
            object-fit: cover;
            background: var(--bg-light);
            flex-shrink: 0;
        }
        .sug-info { flex: 1; min-width: 0; }
        .sug-name {
            font-size: .9rem;
            font-weight: 600;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sug-detail {
            font-size: .75rem;
            color: var(--suggestions-muted);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .sug-tag {
            font-size: .6rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: .1rem .45rem;
            border-radius: 999px;
            background: var(--secondary);
            color: #fff;
        }
        .sug-price {
            font-weight: 700;
            font-size: .85rem;
            color: var(--primary);
            flex-shrink: 0;
        }
        [data-theme="dark"] .sug-price { color: var(--gold); }
        .sug-divider { border-top: 1px solid var(--suggestions-border); margin: .3rem 1rem; }
        .sug-empty { padding: 1.5rem; text-align: center; color: var(--suggestions-muted); font-size: .9rem; }

        /* Nav links */
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
            flex-shrink: 0;
        }
        .nav-links a {
            position: relative;
            color: var(--header-text);
            text-decoration: none;
            font-size: .93rem;
            font-weight: 500;
            opacity: .75;
            padding: .4rem 0;
            transition: opacity .2s;
        }
        .nav-links a:hover, .nav-links a.active { opacity: 1; }
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -4px; left: 50%;
            transform: translateX(-50%);
            width: 0; height: 4px;
            background: var(--secondary);
            border-radius: 50%;
            transition: width .25s;
        }
        .nav-links a:hover::after, .nav-links a.active::after { width: 4px; }

        /* Right actions */
        .hdr-actions {
            display: flex;
            align-items: center;
            gap: .9rem;
            flex-shrink: 0;
        }

        /* Icon button base */
        .icon-btn {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px; height: 40px;
            border-radius: 10px;
            background: rgba(255,255,255,.08);
            color: var(--header-text);
            border: none;
            cursor: pointer;
            font-size: 1.25rem;
            text-decoration: none;
            transition: background .2s;
        }
        .icon-btn:hover { background: var(--header-bg-hover); }

        /* Cart badge */
        .cart-badge {
            position: absolute;
            top: -5px; right: -5px;
            background: var(--gold);
            color: #fff;
            font-size: .68rem;
            font-weight: 700;
            min-width: 18px; height: 18px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            padding: 0 4px;
            border: 2px solid var(--header-bg);
            opacity: 0;
            transform: scale(.5);
            transition: all .3s cubic-bezier(.34,1.56,.64,1);
        }
        .cart-badge.show { opacity: 1; transform: scale(1); }

        /* Language switcher */
        .lang-wrap { position: relative; }
        .lang-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,.08);
            border: none;
            color: var(--header-text);
            padding: .55rem .85rem;
            border-radius: 10px;
            cursor: pointer;
            font-size: .82rem;
            font-weight: 500;
            transition: background .2s;
        }
        .lang-btn:hover { background: var(--header-bg-hover); }
        .lang-arrow { font-size: .72rem; transition: transform .2s; }
        .lang-wrap.open .lang-arrow { transform: rotate(180deg); }
        .lang-drop {
            position: absolute;
            right: 0; top: calc(100% + 8px);
            background: var(--dropdown-bg);
            border-radius: 12px;
            box-shadow: 0 10px 30px var(--shadow-color);
            padding: .4rem;
            min-width: 150px;
            z-index: 1020;
            display: none;
            flex-direction: column;
            gap: 2px;
            animation: fadeDown .2s ease forwards;
        }
        .lang-wrap.open .lang-drop { display: flex; }
        .lang-opt {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: .5rem .75rem;
            border-radius: 8px;
            font-size: .87rem;
            color: var(--dropdown-text);
            border: none;
            background: none;
            cursor: pointer;
            text-align: left;
            width: 100%;
            transition: background .15s;
        }
        .lang-opt:hover { background: var(--dropdown-hover); color: var(--primary); }
        .lang-opt.active { background: rgba(93,13,24,.07); color: var(--primary); font-weight: 700; }
        [data-theme="dark"] .lang-opt.active { background: rgba(240,230,216,.1); color: var(--gold); }

        /* Account dropdown — SOLO ICONO */
        .acc-wrap { position: relative; }
        .acc-btn {
            /* igual que icon-btn */
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px; height: 40px;
            border-radius: 10px;
            background: rgba(255,255,255,.08);
            color: var(--header-text);
            border: none;
            cursor: pointer;
            font-size: 1.25rem;
            transition: background .2s;
        }
        .acc-btn:hover, .acc-wrap.open .acc-btn { background: var(--header-bg-hover); }
        .acc-drop {
            position: absolute;
            right: 0; top: calc(100% + 10px);
            width: 220px;
            background: var(--dropdown-bg);
            border-radius: 12px;
            box-shadow: 0 10px 30px var(--shadow-color);
            padding: .5rem;
            display: none;
            flex-direction: column;
            z-index: 1010;
            animation: fadeDown .2s ease forwards;
        }
        .acc-wrap.open .acc-drop { display: flex; }
        .acc-head { padding: .45rem .75rem .6rem; }
        .acc-name { font-weight: 700; color: var(--dropdown-text); font-size: .9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .acc-role {
            display: inline-block;
            font-size: .65rem; font-weight: 700;
            text-transform: uppercase;
            background: rgba(93,13,24,.1);
            color: var(--primary);
            padding: 1px 6px;
            border-radius: 4px;
            margin-top: 3px;
        }
        [data-theme="dark"] .acc-role { background: rgba(240,230,216,.1); color: var(--gold); }
        .acc-divider { border: 0; border-top: 1px solid var(--dropdown-divider); margin: .4rem 0; }
        .acc-drop a {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: .55rem .75rem;
            color: var(--dropdown-text);
            text-decoration: none;
            font-size: .88rem;
            border-radius: 8px;
            transition: background .15s;
        }
        .acc-drop a:hover { background: var(--dropdown-hover); color: var(--primary); }
        [data-theme="dark"] .acc-drop a:hover { color: var(--gold); }
        .acc-drop a.logout:hover { background: #fff5f5; color: #c0392b; }
        [data-theme="dark"] .acc-drop a.logout:hover { background: #4a2d30; color: #e8b8b8; }

        /* Login link */
        .login-link {
            display: flex; align-items: center; gap: 8px;
            background: var(--secondary);
            color: var(--primary);
            padding: .6rem 1.25rem;
            border-radius: 10px;
            text-decoration: none;
            font-size: .88rem; font-weight: 700;
            transition: background .2s, box-shadow .2s;
        }
        .login-link:hover { background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,.1); }
        [data-theme="dark"] .login-link { color: #f0e6d8; }

        /* Mobile */
        .mob-actions { display: none; align-items: center; gap: .65rem; }
        .mob-btn {
            display: flex; align-items: center; justify-content: center;
            width: 40px; height: 40px;
            background: rgba(255,255,255,.08);
            border: none; border-radius: 10px;
            color: var(--header-text);
            font-size: 1.4rem;
            cursor: pointer; transition: background .2s;
        }
        .mob-btn:hover { background: var(--header-bg-hover); }

        .mob-menu {
            max-height: 0;
            overflow: hidden;
            background: var(--mobile-menu-bg);
            transition: max-height .35s cubic-bezier(.32,.94,.6,1);
        }
        .mob-menu.open { max-height: 540px; }
        .mob-inner { padding: 1rem 1.5rem 1.5rem; display: flex; flex-direction: column; gap: .35rem; }
        .mob-link {
            display: flex; align-items: center; gap: 11px;
            padding: .72rem 1rem;
            color: var(--mobile-menu-text);
            text-decoration: none;
            border-radius: 8px; font-size: .96rem;
            transition: background .15s;
        }
        .mob-link i { color: var(--secondary); font-size: 1.1rem; width: 20px; }
        .mob-link:hover, .mob-link.active { background: var(--mobile-menu-hover); color: #fff; }
        .mob-div { height: 1px; background: rgba(255,255,255,.08); margin: .55rem 0; }
        .mob-user { padding: .35rem 1rem; }
        .mob-login {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            background: var(--secondary); color: var(--primary);
            padding: .75rem; border-radius: 8px;
            text-decoration: none; font-weight: 700; margin-top: .5rem;
        }
        [data-theme="dark"] .mob-login { color: #f0e6d8; }
        .mob-lang-label { font-size: .7rem; color: rgba(255,255,255,.4); text-transform: uppercase; letter-spacing: .08em; margin-bottom: .45rem; }
        .mob-lang-opts { display: flex; gap: .45rem; flex-wrap: wrap; }
        .mob-lang-opt {
            padding: .42rem .8rem;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,.15);
            background: rgba(255,255,255,.06);
            color: rgba(255,255,255,.75);
            font-size: .83rem; cursor: pointer;
            transition: background .2s;
        }
        .mob-lang-opt:hover { background: rgba(255,255,255,.12); color: #fff; }
        .mob-lang-opt.active { background: var(--secondary); color: var(--primary); font-weight: 700; border-color: var(--secondary); }

        @keyframes fadeDown {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .nav-links, .hdr-actions { display: none; }
            .mob-actions { display: flex; }
            .hdr-inner { flex-wrap: wrap; padding: .75rem 1.25rem; }
            .search-wrap { order: 3; width: 100%; }
        }
        @media (max-width: 640px) {
            .logo-text { font-size: 1.25rem; }
            .logo img { height: 33px; }
        }
    </style>
</head>
<body>

<header class="hdr">
    <div class="hdr-inner">

        <!-- Logo -->
        <a class="logo" href="accueil.php">
            <img src="IMAGES/logo.png" alt="GreenMarket"
                 onerror="this.src='https://placehold.co/40x40/5D0D18/ffffff?text=GM'">
            <span class="logo-text">Green<span class="logo-accent">Market</span></span>
        </a>

        <!-- Barra de búsqueda -->
        <div class="search-wrap">
            <i class="bi bi-search search-ico"></i>
            <input type="text" id="headerSearch"
                   placeholder="Rechercher un produit, une boutique..."
                   autocomplete="off">
            <div class="suggestions" id="suggestions"></div>
        </div>

        <!-- Nav desktop -->
        <nav class="nav-links">
            <a href="accueil.php"  class="<?= $currentPage==='accueil.php'  ? 'active':'' ?>">Accueil</a>
            <a href="store.php"    class="<?= $currentPage==='store.php'    ? 'active':'' ?>">Boutiques</a>
            <a href="produits.php" class="<?= $currentPage==='produits.php' ? 'active':'' ?>">Produits</a>
            <a href="apropos.php"  class="<?= $currentPage==='apropos.php'  ? 'active':'' ?>">À propos</a>
        </nav>

        <!-- Actions desktop -->
        <div class="hdr-actions">

            <!-- Carrito -->
            <a href="panier.php" class="icon-btn" title="Mon panier">
                <i class="bi bi-bag"></i>
                <span class="cart-badge <?= $cartCount > 0 ? 'show' : '' ?>"
                      id="cart-count"><?= $cartCount ?></span>
            </a>

            <!-- Idioma -->
            <div class="lang-wrap" id="langWrap">
                <button class="lang-btn" id="langBtn">
                    <span id="curFlag">🇫🇷</span>
                    <span id="curCode" style="font-size:.78rem;letter-spacing:.5px">FR</span>
                    <i class="bi bi-chevron-down lang-arrow" id="langArrow"></i>
                </button>
                <div class="lang-drop" id="langDrop">
                    <button class="lang-opt active" data-lang="fr" data-flag="🇫🇷">🇫🇷 &nbsp;Français</button>
                    <button class="lang-opt" data-lang="en" data-flag="🇬🇧">🇬🇧 &nbsp;English</button>
                    <button class="lang-opt" data-lang="ar" data-flag="🇲🇦">🇲🇦 &nbsp;العربية</button>
                    <button class="lang-opt" data-lang="es" data-flag="🇪🇸">🇪🇸 &nbsp;Español</button>
                </div>
            </div>

            <!-- Cuenta -->
            <?php if (isset($_SESSION['user_role'])): ?>
            <div class="acc-wrap" id="accWrap">
                <button class="acc-btn" id="accBtn" title="Mon compte">
                    <i class="bi bi-person-circle"></i>
                </button>
                <div class="acc-drop" id="accDrop">
                    <div class="acc-head">
                        <p class="acc-name"><?= htmlspecialchars($_SESSION['user_nom']) ?></p>
                        <span class="acc-role"><?= ucfirst($_SESSION['user_role']) ?></span>
                    </div>
                    <hr class="acc-divider">
                    <a href="<?= $dashboardLink ?>"><i class="bi bi-grid-1x2"></i> Tableau de bord</a>
                    <a href="profile.php"><i class="bi bi-sliders"></i> Paramètres</a>
                    <hr class="acc-divider">
                    <a href="logout.php" class="logout"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
                </div>
            </div>
            <?php else: ?>
            <a href="signin.php" class="login-link">
                <i class="bi bi-box-arrow-in-right"></i> Connexion
            </a>
            <?php endif; ?>
        </div>

        <!-- Actions mobile -->
        <div class="mob-actions">
            <a href="panier.php" class="icon-btn">
                <i class="bi bi-bag"></i>
                <span class="cart-badge <?= $cartCount > 0 ? 'show' : '' ?>"><?= $cartCount ?></span>
            </a>
            <button class="mob-btn" id="mobToggle" aria-label="Menu">
                <i class="bi bi-list" id="mobIcon"></i>
            </button>
        </div>
    </div><!-- /hdr-inner -->

    <!-- Menú móvil -->
    <div class="mob-menu" id="mobMenu">
        <div class="mob-inner">
            <a href="accueil.php"  class="mob-link <?= $currentPage==='accueil.php'  ? 'active':'' ?>"><i class="bi bi-house-door"></i> Accueil</a>
            <a href="store.php"    class="mob-link <?= $currentPage==='store.php'    ? 'active':'' ?>"><i class="bi bi-shop"></i> Boutiques</a>
            <a href="produits.php" class="mob-link <?= $currentPage==='produits.php' ? 'active':'' ?>"><i class="bi bi-box-seam"></i> Produits</a>
            <a href="apropos.php"  class="mob-link <?= $currentPage==='apropos.php'  ? 'active':'' ?>"><i class="bi bi-info-circle"></i> À propos</a>

            <div class="mob-div"></div>

            <!-- Idioma mobile -->
            <div style="padding:.4rem 1rem">
                <p class="mob-lang-label">Langue / Language</p>
                <div class="mob-lang-opts">
                    <button class="mob-lang-opt active" data-lang="fr">🇫🇷 FR</button>
                    <button class="mob-lang-opt" data-lang="en">🇬🇧 EN</button>
                    <button class="mob-lang-opt" data-lang="ar">🇲🇦 AR</button>
                    <button class="mob-lang-opt" data-lang="es">🇪🇸 ES</button>
                </div>
            </div>

            <div class="mob-div"></div>

            <?php if (isset($_SESSION['user_role'])): ?>
            <div class="mob-user">
                <p style="font-size:.7rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.08em">
                    Mon compte (<?= ucfirst($_SESSION['user_role']) ?>)
                </p>
                <p style="font-weight:600;color:#fff;margin-top:2px"><?= htmlspecialchars($_SESSION['user_nom']) ?></p>
            </div>
            <a href="<?= $dashboardLink ?>" class="mob-link"><i class="bi bi-grid-1x2"></i> Tableau de bord</a>
            <a href="logout.php" class="mob-link" style="color:#f87171"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
            <?php else: ?>
            <a href="signin.php" class="mob-login"><i class="bi bi-box-arrow-in-right"></i> Se connecter</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Google Translate container (oculto) -->
<div id="google_translate_element" style="display:none"></div>

<script>
/* ===== Google Translate ===== */
(function(){
    const s = document.createElement('script');
    s.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
    document.head.appendChild(s);
})();
window.googleTranslateElementInit = function(){
    new google.translate.TranslateElement({
        pageLanguage:'fr',
        includedLanguages:'fr,en,ar,es',
        autoDisplay: false
    }, 'google_translate_element');
};
function applyLang(lang){
    const v = `/fr/${lang}`;
    document.cookie = `googtrans=${v}; path=/;`;
    document.cookie = `googtrans=${v}; path=/; domain=${location.hostname};`;
    localStorage.setItem('gm_lang', lang);
    document.documentElement.lang = lang;
    location.reload();
}
function syncLangUI(lang){
    const opt = document.querySelector(`#langDrop [data-lang="${lang}"]`);
    if(opt){
        document.getElementById('curFlag').textContent = opt.dataset.flag;
        document.getElementById('curCode').textContent = lang.toUpperCase();
        document.querySelectorAll('.lang-opt').forEach(o => o.classList.toggle('active', o.dataset.lang===lang));
    }
    document.querySelectorAll('.mob-lang-opt').forEach(b => b.classList.toggle('active', b.dataset.lang===lang));
}
syncLangUI(localStorage.getItem('gm_lang') || 'fr');

/* ===== Menú móvil ===== */
const mobToggle = document.getElementById('mobToggle');
const mobMenu   = document.getElementById('mobMenu');
const mobIcon   = document.getElementById('mobIcon');
mobToggle?.addEventListener('click', e => {
    e.stopPropagation();
    const open = mobMenu.classList.toggle('open');
    mobIcon.className = open ? 'bi bi-x-lg' : 'bi bi-list';
});

/* ===== Dropdown cuenta ===== */
const accWrap = document.getElementById('accWrap');
document.getElementById('accBtn')?.addEventListener('click', e => {
    e.stopPropagation();
    accWrap.classList.toggle('open');
});

/* ===== Dropdown idioma ===== */
const langWrap = document.getElementById('langWrap');
document.getElementById('langBtn')?.addEventListener('click', e => {
    e.stopPropagation();
    langWrap.classList.toggle('open');
});
document.querySelectorAll('.lang-opt').forEach(opt => {
    opt.addEventListener('click', () => {
        syncLangUI(opt.dataset.lang);
        langWrap.classList.remove('open');
        applyLang(opt.dataset.lang);
    });
});
document.querySelectorAll('.mob-lang-opt').forEach(btn => {
    btn.addEventListener('click', () => applyLang(btn.dataset.lang));
});

/* ===== Cerrar dropdowns al clic exterior ===== */
document.addEventListener('click', e => {
    if(accWrap && !accWrap.contains(e.target)) accWrap.classList.remove('open');
    if(langWrap && !langWrap.contains(e.target)) langWrap.classList.remove('open');
    if(mobMenu && !mobMenu.contains(e.target) && !mobToggle.contains(e.target)){
        mobMenu.classList.remove('open');
        mobIcon.className = 'bi bi-list';
    }
});

/* ===== Búsqueda con sugerencias ===== */
const searchInput  = document.getElementById('headerSearch');
const suggestions  = document.getElementById('suggestions');
let searchTimer = null;
let activeIdx = -1;

function renderSuggestions(data, q){
    if(!data.length){
        suggestions.innerHTML = `<div class="sug-empty"><i class="bi bi-search" style="font-size:1.4rem;display:block;margin-bottom:.5rem"></i>Aucun résultat pour "<strong>${q}</strong>"</div>`;
        suggestions.classList.add('show'); return;
    }
    let html = ''; let lastType = '';
    data.forEach((item, i) => {
        if(item.type !== lastType && i > 0) html += '<div class="sug-divider"></div>';
        lastType = item.type;
        const img = item.image || (item.type==='produit' ? 'IMAGES/default-product.jpg' : 'IMAGES/default-boutique.jpg');
        const label = item.type==='produit' ? 'Produit' : 'Boutique';
        html += `
        <a href="${item.link}" class="sug-item">
            <img src="${img}" class="sug-img" alt="${item.name}"
                 onerror="this.src='${item.type==='produit'?'IMAGES/default-product.jpg':'IMAGES/default-boutique.jpg'}'">
            <div class="sug-info">
                <div class="sug-name">${item.name}</div>
                <div class="sug-detail">
                    <span class="sug-tag">${label}</span>
                    ${item.type==='produit' ? `<span>• ${item.shop_name||''}</span>` : `<span>• ${item.producer_name||'Artisan'}</span>`}
                </div>
            </div>
            ${item.type==='produit' && item.price ? `<span class="sug-price">${item.price}</span>` : ''}
        </a>`;
    });
    suggestions.innerHTML = html;
    suggestions.classList.add('show');
}

searchInput?.addEventListener('input', function(){
    activeIdx = -1;
    clearTimeout(searchTimer);
    const q = this.value.trim();
    if(!q){ suggestions.classList.remove('show'); return; }
    suggestions.innerHTML = `<div class="sug-empty"><i class="bi bi-arrow-repeat" style="font-size:1.3rem;display:block;margin-bottom:.5rem;animation:spin 1s linear infinite"></i>Recherche...</div>`;
    suggestions.classList.add('show');
    searchTimer = setTimeout(() => {
        fetch('search_suggestions.php?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => renderSuggestions(data, q))
            .catch(() => suggestions.classList.remove('show'));
    }, 300);
});

searchInput?.addEventListener('keydown', function(e){
    const items = suggestions.querySelectorAll('.sug-item');
    if(e.key==='ArrowDown'){ e.preventDefault(); activeIdx = Math.min(activeIdx+1, items.length-1); }
    else if(e.key==='ArrowUp'){ e.preventDefault(); activeIdx = Math.max(activeIdx-1, -1); }
    else if(e.key==='Enter'){
        if(activeIdx>=0 && items[activeIdx]){ e.preventDefault(); items[activeIdx].click(); return; }
        if(this.value.trim()){ suggestions.classList.remove('show'); location.href='produits.php?search='+encodeURIComponent(this.value.trim()); }
    }
    items.forEach((el,i) => el.style.background = i===activeIdx ? 'var(--suggestions-hover)' : '');
});

document.querySelector('.search-ico')?.addEventListener('click', () => {
    const q = searchInput?.value.trim();
    if(q) location.href = 'produits.php?search=' + encodeURIComponent(q);
});
document.addEventListener('click', e => {
    if(!document.querySelector('.search-wrap')?.contains(e.target))
        suggestions.classList.remove('show');
});
suggestions?.addEventListener('click', () => setTimeout(() => suggestions.classList.remove('show'), 100));

/* ===== Spinner animation ===== */
const st = document.createElement('style');
st.textContent = '@keyframes spin{from{transform:rotate(0)}to{transform:rotate(360deg)}}';
document.head.appendChild(st);
</script>