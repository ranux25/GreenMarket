<?php
// header.php - Header con lógica PHP para mostrar elementos según sesión
// Este archivo debe ser incluido en todas las páginas después de session_start()

// Contador del carrito (ajusta según tu lógica real de carrito en sesión/BBDD)
$cartCount = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<header class="main-header">
  <div class="header-top">
    <div class="nav-container">

      <div class="logo-area" onclick="window.location.href='accueil.php'">
        <img src="IMAGES/logo.png" alt="GreenMarket Logo" class="logo-img" onerror="this.src='https://placehold.co/40x40/ffffff/5D0D18?text=GM'"/>
        <span class="logo-text">Green<span class="logo-accent">Market</span></span>
      </div>

      <div class="search-bar">
        <i class="bi bi-search search-icon"></i>
        <input type="text" id="headerSearch" placeholder="Rechercher un produit, une boutique...">
      </div>

      <div class="nav-links">
        <a href="accueil.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'accueil.php' ? 'active' : ''; ?>">Accueil</a>
        <a href="store.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'store.php' ? 'active' : ''; ?>">Boutiques</a>
        <a href="produits.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'produits.php' ? 'active' : ''; ?>">Produits</a>
        <a href="apropos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'apropos.php' ? 'active' : ''; ?>">À propos</a>
      </div>

      <div class="user-menu">

        <!-- Icono del carrito -->
        <a href="panier.php" class="cart-link" title="Mon panier">
          <i class="bi bi-cart3"></i>
          <?php if ($cartCount > 0): ?>
            <span class="cart-badge"><?php echo $cartCount; ?></span>
          <?php endif; ?>
        </a>

        <?php if (isset($_SESSION['user_role'])): 
          $dashboardLink = '';
          if ($_SESSION['user_role'] === 'client') {
            $dashboardLink = 'dashboard_client.php';
          } elseif ($_SESSION['user_role'] === 'producteur') {
            $dashboardLink = 'dashboard-producteur.php';
          } elseif ($_SESSION['user_role'] === 'admin') {
            $dashboardLink = 'dashboard_admin.php';
          }
        ?>
          <a href="<?php echo $dashboardLink; ?>" class="user-name-link">
            <i class="bi bi-person-circle"></i>
            <span><?php echo htmlspecialchars($_SESSION['user_nom']); ?></span>
          </a>
          <a href="logout.php" class="logout-link"><i class="bi bi-box-arrow-right"></i></a>
        <?php else: ?>
          <a href="signin.php" class="login-link"><i class="bi bi-box-arrow-in-right"></i> Connexion</a>
        <?php endif; ?>
      </div>

      <div class="mobile-actions">
        <a href="panier.php" class="cart-link" title="Mon panier">
          <i class="bi bi-cart3"></i>
          <?php if ($cartCount > 0): ?>
            <span class="cart-badge"><?php echo $cartCount; ?></span>
          <?php endif; ?>
        </a>
        <button class="mobile-menu-btn" id="mobileMenuToggle">
          <i class="bi bi-list"></i>
        </button>
      </div>

    </div>
  </div>

  <div class="mobile-menu" id="mobileMenu">
    <a href="accueil.php" class="mobile-nav-link"><i class="bi bi-house-door"></i> Accueil</a>
    <a href="store.php" class="mobile-nav-link"><i class="bi bi-shop"></i> Boutiques</a>
    <a href="produits.php" class="mobile-nav-link"><i class="bi bi-box-seam"></i> Produits</a>
    <a href="apropos.php" class="mobile-nav-link"><i class="bi bi-info-circle"></i> À propos</a>
    <a href="panier.php" class="mobile-nav-link"><i class="bi bi-cart3"></i> Mon Panier <?php if ($cartCount > 0): ?><span class="mobile-cart-badge"><?php echo $cartCount; ?></span><?php endif; ?></a>

    <?php if (isset($_SESSION['user_role'])): ?>
      <?php if ($_SESSION['user_role'] === 'client'): ?>
        <a href="dashboard_client.php" class="mobile-nav-link"><i class="bi bi-person-circle"></i> Mon Compte</a>
      <?php elseif ($_SESSION['user_role'] === 'producteur'): ?>
        <a href="dashboard-producteur.php" class="mobile-nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
        <a href="dashboard_admin.php" class="mobile-nav-link"><i class="bi bi-shield-lock"></i> Administration</a>
      <?php endif; ?>
      <a href="logout.php" class="mobile-nav-link"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
    <?php else: ?>
      <a href="signin.php" class="mobile-nav-link"><i class="bi bi-box-arrow-in-right"></i> Connexion</a>
    <?php endif; ?>
  </div>
</header>

<style>
  /* ========== HEADER STYLES ========== */
  .main-header {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: linear-gradient(135deg, #5D0D18 0%, #7a1322 50%, #5D0D18 100%);
    box-shadow: 0 4px 20px rgba(93,13,24,0.35);
  }

  .header-top {
    border-bottom: 1px solid rgba(255,255,255,0.08);
  }

  .nav-container {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.2rem;
    padding: 0.7rem 2rem;
  }

  /* Logo */
  .logo-area {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    transition: transform 0.25s ease;
  }
  .logo-area:hover {
    transform: scale(1.03);
  }
  .logo-img {
    height: 44px;
    width: auto;
    object-fit: contain;
    border-radius: 8px;
  }
  .logo-text {
    font-family: 'Playfair Display', serif;
    font-size: 1.45rem;
    font-weight: 700;
    color: white;
    letter-spacing: 0.5px;
  }
  .logo-accent {
    color: #ECE6A6;
  }

  /* Búsqueda */
  .search-bar {
    flex: 1;
    max-width: 420px;
    position: relative;
  }
  .search-bar input {
    width: 100%;
    padding: 0.65rem 1rem 0.65rem 2.6rem;
    border: none;
    border-radius: 50px;
    outline: none;
    font-size: 0.9rem;
    background: rgba(255,255,255,0.95);
    transition: box-shadow 0.25s, background 0.25s;
  }
  .search-bar input:focus {
    background: #fff;
    box-shadow: 0 0 0 3px rgba(236,230,166,0.5);
  }
  .search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #5D0D18;
    opacity: 0.6;
    pointer-events: none;
  }

  /* Links de navegación */
  .nav-links {
    display: flex;
    gap: 1.8rem;
    align-items: center;
  }
  .nav-links a {
    position: relative;
    color: rgba(255,255,255,0.85);
    text-decoration: none;
    transition: color 0.3s;
    font-weight: 500;
    padding: 0.5rem 0.1rem;
  }
  .nav-links a::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: -2px;
    width: 0%;
    height: 2px;
    background: #ECE6A6;
    transition: width 0.3s ease;
  }
  .nav-links a:hover,
  .nav-links a.active {
    color: #fff;
  }
  .nav-links a:hover::after,
  .nav-links a.active::after {
    width: 100%;
  }

  /* Menú usuario */
  .user-menu {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  /* Carrito */
  .cart-link {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: rgba(255,255,255,0.12);
    color: white;
    text-decoration: none;
    font-size: 1.25rem;
    transition: all 0.25s ease;
  }
  .cart-link:hover {
    background: #ECE6A6;
    color: #5D0D18;
    transform: translateY(-2px) scale(1.05);
  }
  .cart-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    background: #ECE6A6;
    color: #5D0D18;
    font-size: 0.7rem;
    font-weight: 700;
    min-width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    box-shadow: 0 0 0 2px #5D0D18;
  }
  .cart-link:hover .cart-badge {
    box-shadow: 0 0 0 2px #ECE6A6;
    background: #5D0D18;
    color: #ECE6A6;
  }

  /* Usuario logueado */
  .user-name-link {
    display: flex;
    align-items: center;
    gap: 8px;
    color: white;
    text-decoration: none;
    font-weight: 500;
    padding: 0.5rem 1.1rem;
    border-radius: 50px;
    background: rgba(255,255,255,0.1);
    transition: all 0.3s ease;
  }
  .user-name-link:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-1px);
  }
  .user-name-link i {
    font-size: 1.15rem;
  }
  .user-name-link span {
    font-size: 0.9rem;
  }

  .logout-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: rgba(255,255,255,0.12);
    color: white;
    text-decoration: none;
    font-size: 1.1rem;
    transition: all 0.25s ease;
  }
  .logout-link:hover {
    background: #ECE6A6;
    color: #5D0D18;
  }

  .login-link {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #ECE6A6;
    color: #5D0D18;
    padding: 0.55rem 1.2rem;
    border-radius: 50px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.25s ease;
  }
  .login-link:hover {
    background: #fff;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
  }

  /* Acciones móviles */
  .mobile-actions {
    display: none;
    align-items: center;
    gap: 0.8rem;
  }
  .mobile-menu-btn {
    background: rgba(255,255,255,0.12);
    border: none;
    color: white;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    font-size: 1.4rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.25s;
  }
  .mobile-menu-btn:hover {
    background: rgba(255,255,255,0.25);
  }

  /* Menú móvil */
  .mobile-menu {
    display: none;
    flex-direction: column;
    background: #3e0910;
    padding: 0.5rem 0;
  }
  .mobile-menu.open {
    display: flex;
    animation: slideDown 0.3s ease;
  }
  @keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
  }
  .mobile-nav-link {
    padding: 0.85rem 1.5rem;
    color: white;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    font-size: 0.95rem;
    transition: background 0.2s;
  }
  .mobile-nav-link:hover {
    background: rgba(255,255,255,0.06);
  }
  .mobile-nav-link:last-child {
    border-bottom: none;
  }
  .mobile-nav-link i {
    font-size: 1.2rem;
    width: 22px;
    text-align: center;
    color: #ECE6A6;
  }
  .mobile-cart-badge {
    background: #ECE6A6;
    color: #5D0D18;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 50px;
    margin-left: auto;
  }

  @media (max-width: 1024px) {
    .nav-container {
      padding: 0.7rem 1.2rem;
    }
    .nav-links,
    .user-menu {
      display: none;
    }
    .mobile-actions {
      display: flex;
    }
    .search-bar {
      order: 1;
      max-width: 100%;
      width: 100%;
    }
  }

  @media (max-width: 640px) {
    .logo-text {
      font-size: 1.15rem;
    }
    .logo-img {
      height: 36px;
    }
  }
</style>

<script>
  // Menu mobile
  document.getElementById('mobileMenuToggle')?.addEventListener('click', function() {
    document.getElementById('mobileMenu').classList.toggle('open');
  });

  // Cerrar menu mobile al hacer clic en un enlace
  document.querySelectorAll('.mobile-nav-link').forEach(link => {
    link.addEventListener('click', function() {
      document.getElementById('mobileMenu')?.classList.remove('open');
    });
  });

  // Búsqueda con tecla Enter
  document.getElementById('headerSearch')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && this.value.trim()) {
      window.location.href = 'produits.php?search=' + encodeURIComponent(this.value.trim());
    }
  });
</script>