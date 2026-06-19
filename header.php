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

      <!-- SELECTOR DE IDIOMA (desktop) -->
      <div class="lang-switcher" id="langSwitcher">
        <button class="lang-btn" id="langBtn">
          <span class="lang-flag" id="currentFlag">🇫🇷</span>
          <span class="lang-code" id="currentCode">FR</span>
          <i class="bi bi-chevron-down lang-arrow" id="langArrow"></i>
        </button>
        <div class="lang-dropdown" id="langDropdown">
          <button class="lang-option active" data-lang="fr" data-flag="🇫🇷" data-label="Français">🇫🇷 &nbsp;Français</button>
          <button class="lang-option" data-lang="en" data-flag="🇬🇧" data-label="English">🇬🇧 &nbsp;English</button>
          <button class="lang-option" data-lang="ar" data-flag="🇲🇦" data-label="العربية">🇲🇦 &nbsp;العربية</button>
          <button class="lang-option" data-lang="es" data-flag="🇪🇸" data-label="Español">🇪🇸 &nbsp;Español</button>
        </div>
      </div>

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

      <!-- SELECTOR DE IDIOMA (mobile) -->
      <div class="mobile-lang-group">
        <p class="mobile-lang-label">Langue / Language</p>
        <div class="mobile-lang-options">
          <button class="mobile-lang-btn active" data-lang="fr" data-flag="🇫🇷">🇫🇷 FR</button>
          <button class="mobile-lang-btn" data-lang="en" data-flag="🇬🇧">🇬🇧 EN</button>
          <button class="mobile-lang-btn" data-lang="ar" data-flag="🇲🇦">🇲🇦 AR</button>
          <button class="mobile-lang-btn" data-lang="es" data-flag="🇪🇸">🇪🇸 ES</button>
        </div>
      </div>

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

  /* Logo */
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

  /* Search bar */
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

  /* Nav links */
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

  /* Cart */
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

  /* ========== LANGUAGE SWITCHER ========== */
  .lang-switcher {
    position: relative;
  }
  .lang-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255, 255, 255, 0.08);
    border: none;
    color: #ffffff;
    padding: 0.55rem 0.9rem;
    border-radius: 12px;
    cursor: pointer;
    font-size: 0.88rem;
    font-weight: 500;
    transition: background 0.2s;
  }
  .lang-btn:hover {
    background: rgba(255, 255, 255, 0.15);
  }
  .lang-flag {
    font-size: 1.1rem;
  }
  .lang-code {
    letter-spacing: 0.5px;
    font-size: 0.8rem;
  }
  .lang-arrow {
    font-size: 0.75rem;
    transition: transform 0.2s ease;
  }
  .lang-switcher.open .lang-arrow {
    transform: rotate(180deg);
  }
  .lang-dropdown {
    position: absolute;
    right: 0;
    top: calc(100% + 8px);
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    padding: 0.5rem;
    min-width: 155px;
    z-index: 1020;
    display: none;
    flex-direction: column;
    gap: 2px;
    animation: dropdownAnim 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  }
  .lang-switcher.open .lang-dropdown {
    display: flex;
  }
  .lang-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0.55rem 0.8rem;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.88rem;
    color: #555;
    border: none;
    background: none;
    text-align: left;
    width: 100%;
    transition: background 0.15s;
  }
  .lang-option:hover {
    background: #FFF9EB;
    color: #5D0D18;
  }
  .lang-option.active {
    background: rgba(93, 13, 24, 0.07);
    color: #5D0D18;
    font-weight: 700;
  }

  /* Mobile lang */
  .mobile-lang-group {
    padding: 0.4rem 1rem;
  }
  .mobile-lang-label {
    font-size: 0.72rem;
    color: rgba(255, 255, 255, 0.45);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 0.5rem;
  }
  .mobile-lang-options {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
  }
  .mobile-lang-btn {
    padding: 0.45rem 0.85rem;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.15);
    background: rgba(255, 255, 255, 0.06);
    color: rgba(255, 255, 255, 0.75);
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
  }
  .mobile-lang-btn:hover {
    background: rgba(255, 255, 255, 0.12);
    color: #fff;
  }
  .mobile-lang-btn.active {
    background: #9FB2AC;
    color: #5D0D18;
    font-weight: 700;
    border-color: #9FB2AC;
  }

  /* Dropdown account menu */
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
    to   { opacity: 1; transform: translateY(0); }
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

  /* Login link */
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

  /* Mobile actions */
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

  /* Mobile menu */
  .mobile-menu {
    max-height: 0;
    overflow: hidden;
    background: #4A0E17;
    transition: max-height 0.3s cubic-bezier(0.32, 0.94, 0.6, 1);
  }
  .mobile-menu.open {
    max-height: 520px;
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

  /* MEDIA QUERIES */
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
    .logo-img  { height: 35px; }
  }
  
</style>

<script>
// 1. CARGA E INICIALIZACIÓN OFICIAL DE GOOGLE TRANSLATE
(function loadGoogleTranslate() {
  const script = document.createElement('script');
  script.src = "https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit";
  document.head.appendChild(script);
})();

window.googleTranslateElementInit = function() {
  new google.translate.TranslateElement({
    pageLanguage: 'fr', // Idioma original de tu código
    includedLanguages: 'fr,en,ar,es', 
    layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
    autoDisplay: false
  }, 'google_translate_element');
};

// 2. FUNCIÓN INMUNE: Forzar traducción mediante la Cookie oficial de Google
function forceGoogleTranslate(lang) {
  // El formato que exige la cookie de Google es: /idioma_origen/idioma_destino
  const cookieValue = `/fr/${lang}`;
  
  // Guardamos la cookie para el dominio actual y todos sus directorios
  document.cookie = `googtrans=${cookieValue}; path=/;`;
  document.cookie = `googtrans=${cookieValue}; path=/; domain=${window.location.hostname};`;
  
  // Almacenamos en el almacenamiento local
  localStorage.setItem('gm_lang', lang);
  document.documentElement.lang = lang;

  // Forzamos un ligero refresco para que Google procese la cookie nueva y traduzca todo desde el inicio
  window.location.reload();
}

document.addEventListener('DOMContentLoaded', () => {
  // Selectores de la interfaz
  const mobileToggle = document.getElementById('mobileMenuToggle');
  const mobileMenu   = document.getElementById('mobileMenu');
  const dropdownToggle  = document.getElementById('dropdownToggle');
  const dropdownWrapper = document.getElementById('userDropdownWrapper');
  const langSwitcher  = document.getElementById('langSwitcher');
  const langBtn       = document.getElementById('langBtn');
  const langDropdown  = document.getElementById('langDropdown');
  const currentFlag   = document.getElementById('currentFlag');
  const currentCode   = document.getElementById('currentCode');

  // Menú Móvil
  if (mobileToggle && mobileMenu) {
    mobileToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      mobileMenu.classList.toggle('open');
      const icon = mobileToggle.querySelector('i');
      if (icon) {
        icon.classList.toggle('bi-list');
        icon.classList.toggle('bi-x-lg');
      }
    });
  }

  // Menú Usuario Escritorio
  if (dropdownToggle && dropdownWrapper) {
    dropdownToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      dropdownWrapper.classList.toggle('active');
    });
  }

  // Cerrar menús al hacer click fuera
  document.addEventListener('click', (e) => {
    if (dropdownWrapper && !dropdownWrapper.contains(e.target)) dropdownWrapper.classList.remove('active');
    if (langSwitcher && !langSwitcher.contains(e.target)) langSwitcher.classList.remove('open');
    if (mobileMenu && mobileMenu.classList.contains('open') && !mobileMenu.contains(e.target) && mobileToggle && !mobileToggle.contains(e.target)) {
      mobileMenu.classList.remove('open');
      const icon = mobileToggle.querySelector('i');
      if (icon) { icon.className = 'bi bi-list icon-burger'; }
    }
  });

  // Búsqueda integrada
  document.getElementById('headerSearch')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && this.value.trim()) {
      window.location.href = 'produits.php?search=' + encodeURIComponent(this.value.trim());
    }
  });

  // Desplegar idiomas escritorio
  langBtn?.addEventListener('click', (e) => {
    e.stopPropagation();
    langSwitcher.classList.toggle('open');
  });

  // 3. APLICACIÓN VISUAL (Para que los botones reflejen el idioma activo al cargar)
  function updateVisualLanguage(lang) {
    const opt = langDropdown?.querySelector(`[data-lang="${lang}"]`);
    if (!opt) return;

    if (currentFlag) currentFlag.textContent = opt.dataset.flag;
    if (currentCode) currentCode.textContent = lang.toUpperCase();

    langDropdown?.querySelectorAll('.lang-option').forEach(o => o.classList.remove('active'));
    opt.classList.add('active');

    document.querySelectorAll('.mobile-lang-btn').forEach(b => {
      b.classList.toggle('active', b.dataset.lang === lang);
    });
  }

  // Cargar idioma activo al iniciar la página
  const savedLang = localStorage.getItem('gm_lang') || 'fr';
  updateVisualLanguage(savedLang);

  // Eventos de selección (Escritorio)
  langDropdown?.querySelectorAll('.lang-option').forEach(opt => {
    opt.addEventListener('click', () => {
      const selectedLang = opt.dataset.lang;
      updateVisualLanguage(selectedLang);
      langSwitcher.classList.remove('open');
      forceGoogleTranslate(selectedLang); // Cambia cookie y recarga
    });
  });

  // Eventos de selección (Móvil)
  document.querySelectorAll('.mobile-lang-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const selectedLang = btn.dataset.lang;
      updateVisualLanguage(selectedLang);
      forceGoogleTranslate(selectedLang); // Cambia cookie y recarga
    });
  });
});
</script>