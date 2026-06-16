<?php
// Contador del carrito (Calcula la cantidad total de artículos y no solo tipos de productos)
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += isset($item['quantity']) ? $item['quantity'] : 1;
    }
}
?>
<header class="main-header">
  <div class="nav-container">

    <div class="logo-area" onclick="window.location.href='accueil.php'">
      <img src="IMAGES/logo.png" alt="GreenMarket Logo" class="logo-img" onerror="this.src='https://placehold.co/40x40/5D0D18/ffffff?text=GM'"/>
      <span class="logo-text">Green<span class="logo-accent">Market</span></span>
    </div>

    <div class="search-bar">
      <i class="bi bi-search search-icon"></i>
      <input type="text" id="headerSearch" placeholder="Rechercher un produit, une boutique...">
    </div>

    <nav class="nav-links">
      <a href="accueil.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'accueil.php' ? 'active' : ''; ?>">Accueil</a>
      <a href="store.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'store.php' ? 'active' : ''; ?>">Boutiques</a>
      <a href="produits.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'produits.php' ? 'active' : ''; ?>">Produits</a>
      <a href="apropos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'apropos.php' ? 'active' : ''; ?>">À propos</a>
    </nav>

    <div class="user-menu">
      <a href="panier.php" class="cart-link" title="Mon panier">
        <i class="bi bi-bag"></i>
        <span class="cart-badge <?php echo $cartCount > 0 ? 'show' : ''; ?>" id="cart-count"><?php echo $cartCount; ?></span>
      </a>

      <?php if (isset($_SESSION['user_role'])): 
        $dashboardLink = '';
        if ($_SESSION['user_role'] === 'client') $dashboardLink = 'dashboard_client.php';
        elseif ($_SESSION['user_role'] === 'producteur') $dashboardLink = 'dashboard-producteur.php';
        elseif ($_SESSION['user_role'] === 'admin') $dashboardLink = 'dashboard_admin.php';
      ?>
        <div class="dropdown-wrapper" id="userDropdownWrapper">
          <button class="user-dropdown-btn" id="dropdownToggle">
            <i class="bi bi-person-circle"></i>
            <span><?php echo htmlspecialchars(explode(' ', $_SESSION['user_nom'])[0]); ?></span>
            <i class="bi bi-chevron-down arrow-icon"></i>
          </button>
          
          <div class="dropdown-menu-list" id="dropdownMenu">
            <div class="dropdown-header">
              <p class="user-fullname"><?php echo htmlspecialchars($_SESSION['user_nom']); ?></p>
              <span class="user-role-tag"><?php echo ucfirst($_SESSION['user_role']); ?></span>
            </div>
            <hr class="dropdown-divider">
            <a href="<?php echo $dashboardLink; ?>"><i class="bi bi-grid-1x2"></i> Mon Tableau de bord</a>
            <a href="profile.php"><i class="bi bi-sliders"></i> Paramètres</a>
            <hr class="dropdown-divider">
            <a href="logout.php" class="logout-item"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
          </div>
        </div>
      <?php else: ?>
        <a href="signin.php" class="login-link"><i class="bi bi-box-arrow-in-right"></i> Connexion</a>
      <?php endif; ?>
    </div>

    <div class="mobile-actions">
      <a href="panier.php" class="cart-link" title="Mon panier">
        <i class="bi bi-bag"></i>
        <span class="cart-badge <?php echo $cartCount > 0 ? 'show' : ''; ?>"><?php echo $cartCount; ?></span>
      </a>
      <button class="mobile-menu-btn" id="mobileMenuToggle" aria-label="Menu principal">
        <i class="bi bi-list icon-burger"></i>
      </button>
    </div>

  </div>

  <div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-inner">
      <a href="accueil.php" class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'accueil.php' ? 'active' : ''; ?>"><i class="bi bi-house-door"></i> Accueil</a>
      <a href="store.php" class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'store.php' ? 'active' : ''; ?>"><i class="bi bi-shop"></i> Boutiques</a>
      <a href="produits.php" class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'produits.php' ? 'active' : ''; ?>"><i class="bi bi-box-seam"></i> Produits</a>
      <a href="apropos.php" class="mobile-nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'apropos.php' ? 'active' : ''; ?>"><i class="bi bi-info-circle"></i> À propos</a>
      
      <div class="mobile-menu-divider"></div>

      <?php if (isset($_SESSION['user_role'])): ?>
        <div class="mobile-user-info">
          <p class="text-xs text-gray-400 uppercase tracking-wider">Mon Compte (<?php echo ucfirst($_SESSION['user_role']); ?>)</p>
          <p class="font-semibold text-white mt-0.5"><?php echo htmlspecialchars($_SESSION['user_nom']); ?></p>
        </div>
        <a href="<?php echo $dashboardLink; ?>" class="mobile-nav-link"><i class="bi bi-grid-1x2"></i> Tableau de bord</a>
        <a href="logout.php" class="mobile-nav-link text-red-400"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
      <?php else: ?>
        <a href="signin.php" class="mobile-login-btn"><i class="bi bi-box-arrow-in-right"></i> Se connecter</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<style>
  /* ========== MODERN REWRITTEN DESIGN SYSTEM ========== */
  .main-header {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: #5D0D18;
    box-shadow: 0 4px 25px rgba(93, 13, 24, 0.18);
    font-family: 'Lato', sans-serif;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  }

  .nav-container {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.85rem 2rem;
    gap: 1.5rem;
  }

  /* Logo text & interaction */
  .logo-area {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
  }
  .logo-img {
    height: 40px;
    width: auto;
    object-fit: contain;
    border-radius: 6px;
  }
  .logo-text {
    font-family: 'Playfair Display', serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: -0.2px;
  }
  .logo-accent {
  color: #9FB2AC !important;
  }

  /* Input bar modern aesthetics */
  .search-bar {
    flex: 1;
    max-width: 460px;
    position: relative;
  }
  .search-bar input {
    width: 100%;
    padding: 0.65rem 1rem 0.65rem 2.8rem;
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    outline: none;
    font-size: 0.88rem;
    color: #2C2C2C;
    background: rgba(255, 255, 255, 0.96);
    transition: all 0.25s ease;
  }
  .search-bar input:focus {
    background: #ffffff;
    box-shadow: 0 0 0 4px rgba(159, 178, 172, 0.3);
  }
  .search-icon {
    position: absolute;
    left: 1.1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #5D0D18;
    font-size: 0.95rem;
    opacity: 0.7;
    pointer-events: none;
  }

  /* Desktop links with modern dot indicator */
  .nav-links {
    display: flex;
    gap: 2.2rem;
    align-items: center;
  }
  .nav-links a {
    position: relative;
    color: rgba(255, 255, 255, 0.75);
    text-decoration: none;
    font-weight: 500;
    font-size: 0.95rem;
    padding: 0.4rem 0;
    transition: color 0.25s ease;
  }
  .nav-links a:hover,
  .nav-links a.active {
    color: #ffffff;
  }
  .nav-links a::after {
    content: '';
    position: absolute;
    bottom: -4px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 4px;
    background: #9FB2AC;
    border-radius: 50%;
    transition: width 0.25s ease;
  }
  .nav-links a:hover::after,
  .nav-links a.active::after {
    width: 4px;
  }

  .user-menu {
    display: flex;
    align-items: center;
    gap: 1.2rem;
  }

  /* Cart pill indicator styling */
  .cart-link {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.08);
    color: #ffffff;
    text-decoration: none;
    font-size: 1.3rem;
    transition: all 0.2s ease;
  }
  .cart-link:hover {
    background: rgba(255, 255, 255, 0.15);
  }
  .cart-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #c07a1a;
    color: #ffffff;
    font-size: 0.7rem;
    font-weight: 700;
    min-width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    border: 2px solid #5D0D18;
    opacity: 0;
    transform: scale(0.5);
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
  }
  .cart-badge.show {
    opacity: 1;
    transform: scale(1);
  }

  /* Modern Dropdown Component */
  .dropdown-wrapper {
    position: relative;
  }
  .user-dropdown-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #ffffff;
    background: rgba(255, 255, 255, 0.08);
    border: none;
    padding: 0.6rem 1.2rem;
    border-radius: 12px;
    font-weight: 500;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.2s;
  }
  .user-dropdown-btn:hover {
    background: rgba(255, 255, 255, 0.15);
  }
  .user-dropdown-btn .arrow-icon {
    font-size: 0.75rem;
    transition: transform 0.2s ease;
  }
  .dropdown-wrapper.active .arrow-icon {
    transform: rotate(180deg);
  }

  .dropdown-menu-list {
    position: absolute;
    right: 0;
    top: calc(100% + 10px);
    width: 230px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    padding: 0.6rem;
    display: none;
    flex-direction: column;
    z-index: 1010;
  }
  .dropdown-wrapper.active .dropdown-menu-list {
    display: flex;
    animation: dropdownAnim 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  }
  @keyframes dropdownAnim {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
  }

  .dropdown-header {
    padding: 0.5rem 0.8rem;
  }
  .user-fullname {
    font-weight: 700;
    color: #2C2C2C;
    font-size: 0.92rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .user-role-tag {
    display: inline-block;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    background: rgba(93, 13, 24, 0.1);
    color: #5D0D18;
    padding: 1px 6px;
    border-radius: 4px;
    margin-top: 3px;
  }
  .dropdown-divider {
    border: 0;
    border-top: 1px solid #f0f0f0;
    margin: 0.5rem 0;
  }
  .dropdown-menu-list a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0.6rem 0.8rem;
    color: #6B6B6B;
    text-decoration: none;
    font-size: 0.9rem;
    border-radius: 8px;
    transition: all 0.2s ease;
  }
  .dropdown-menu-list a:hover {
    background: #FFF9EB;
    color: #5D0D18;
  }
  .dropdown-menu-list a.logout-item:hover {
    background: #fff5f5;
    color: #c0392b;
  }

  /* Guest authentication CTA link */
  .login-link {
    display: flex;
    align-items: center;
    gap: 8px;
    background: #9FB2AC;
    color: #5D0D18;
    padding: 0.65rem 1.4rem;
    border-radius: 12px;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 700;
    transition: all 0.2s ease;
  }
  .login-link:hover {
    background: #ffffff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  }

  /* Interfaces tactiles et mobiles */
  .mobile-actions {
    display: none;
    align-items: center;
    gap: 0.75rem;
  }
  .mobile-menu-btn {
    background: rgba(255, 255, 255, 0.08);
    border: none;
    color: #ffffff;
    width: 42px;
    height: 42px;
    border-radius: 12px;
    font-size: 1.5rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .mobile-menu {
    max-height: 0;
    overflow: hidden;
    background: #4A0E17;
    transition: max-height 0.3s cubic-bezier(0.32, 0.94, 0.6, 1);
  }
  .mobile-menu.open {
    max-height: 450px;
  }
  .mobile-menu-inner {
    padding: 1rem 1.5rem 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
  }
  .mobile-nav-link {
    padding: 0.75rem 1rem;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 12px;
    border-radius: 8px;
    font-size: 0.98rem;
  }
  .mobile-nav-link i {
    color: #9FB2AC;
    font-size: 1.15rem;
    width: 20px;
  }
  .mobile-nav-link:hover,
  .mobile-nav-link.active {
    background: rgba(255,255,255,0.06);
    color: #ffffff;
  }
  .mobile-menu-divider {
    height: 1px;
    background: rgba(255,255,255,0.08);
    margin: 0.6rem 0;
  }
  .mobile-user-info {
    padding: 0.4rem 1rem;
    margin-bottom: 0.2rem;
  }
  .mobile-login-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #9FB2AC;
    color: #5D0D18;
    padding: 0.75rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 700;
    text-align: center;
    margin-top: 0.5rem;
  }

  /* MEDIA QUERIES RESPONSIVE */
  @media (max-width: 1024px) {
    .nav-container {
      padding: 0.85rem 1.5rem;
    }
    .nav-links, .user-menu {
      display: none;
    }
    .mobile-actions {
      display: flex;
    }
    .search-bar {
      order: 3;
      max-width: 100%;
      width: 100%;
      margin-top: 0.2rem;
    }
  }

  @media (max-width: 640px) {
    .logo-text { font-size: 1.3rem; }
    .logo-img { height: 35px; }
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // 1. Mobile Navigation Menu Toggle
  const mobileToggle = document.getElementById('mobileMenuToggle');
  const mobileMenu = document.getElementById('mobileMenu');
  
  if (mobileToggle && mobileMenu) {
    mobileToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      mobileMenu.classList.toggle('open');
      
      // Dynamic Burger Icon Transformation
      const icon = mobileToggle.querySelector('i');
      if(icon) {
        icon.classList.toggle('bi-list');
        icon.classList.toggle('bi-x-lg');
      }
    });
  }

  // 2. Desktop Dropdown Account Menu
  const dropdownToggle = document.getElementById('dropdownToggle');
  const dropdownWrapper = document.getElementById('userDropdownWrapper');

  if (dropdownToggle && dropdownWrapper) {
    dropdownToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      dropdownWrapper.classList.toggle('active');
    });
  }

  // 3. Click Away system (Ferme les menus ouverts si on clique en dehors)
  document.addEventListener('click', (e) => {
    if (dropdownWrapper && !dropdownWrapper.contains(e.target)) {
      dropdownWrapper.classList.remove('active');
    }
    if (mobileMenu && mobileMenu.classList.contains('open') && !mobileMenu.contains(e.target) && !mobileToggle.contains(e.target)) {
      mobileMenu.classList.remove('open');
      const icon = mobileToggle.querySelector('i');
      if(icon) {
        icon.classList.remove('bi-x-lg');
        icon.classList.add('bi-list');
      }
    }
  });

  // 4. Bar de recherche globale (Redirection sur touche Entrée)
  document.getElementById('headerSearch')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && this.value.trim()) {
      window.location.href = 'produits.php?search=' + encodeURIComponent(this.value.trim());
    }
  });
});
</script>