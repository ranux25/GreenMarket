<?php
// header.php - Header con lógica PHP para mostrar elementos según sesión
// Este archivo debe ser incluido en todas las páginas después de session_start()
?>
<header class="main-header">
  <div class="nav-container">
    <div class="logo-area" onclick="window.location.href='accueil.php'">
      <img src="IMAGES/logo.png" alt="GreenMarket Logo" class="logo-img" onerror="this.src='https://placehold.co/40x40/9FB2AC/5D0D18?text=GM'"/>
      <span class="logo-text">GreenMarket</span>
    </div>
    
    <div class="search-bar">
      <input type="text" id="headerSearch" placeholder="Rechercher un produit, une boutique...">
    </div>
    
    <div class="nav-links">
      <a href="accueil.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'accueil.php' ? 'active' : ''; ?>">Accueil</a>
      <a href="store.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'store.php' ? 'active' : ''; ?>">Boutiques</a>
      <a href="produits.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'produits.php' ? 'active' : ''; ?>">Produits</a>
      <a href="apropos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'apropos.php' ? 'active' : ''; ?>">À propos</a>
      
      <?php if (isset($_SESSION['user_role'])): ?>
        <?php if ($_SESSION['user_role'] === 'producteur'): ?>
          <a href="dashboard-producteur.php">📊 Dashboard</a>
        <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
          <a href="dashboard_admin.php">👑 Administration</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    
    <div class="user-menu">
      <?php if (isset($_SESSION['user_role'])): ?>
        <span class="user-name"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['user_nom']); ?></span>
        <a href="logout.php" class="logout-link"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
      <?php else: ?>
        <a href="signin.php" class="login-link"><i class="bi bi-box-arrow-in-right"></i> Connexion</a>
        <a href="signup.php" class="signup-link">S'inscrire</a>
      <?php endif; ?>
    </div>
    
    <button class="mobile-menu-btn" id="mobileMenuToggle">☰</button>
  </div>
  
  <div class="mobile-menu" id="mobileMenu">
    <a href="accueil.php" class="mobile-nav-link">🏠 Accueil</a>
    <a href="store.php" class="mobile-nav-link">🏪 Boutiques</a>
    <a href="produits.php" class="mobile-nav-link">📦 Produits</a>
    <a href="apropos.php" class="mobile-nav-link">ℹ️ À propos</a>
    
    <?php if (isset($_SESSION['user_role'])): ?>
      <?php if ($_SESSION['user_role'] === 'client'): ?>
        <a href="dashboard_client.php" class="mobile-nav-link">👤 Mon Compte</a>
      <?php elseif ($_SESSION['user_role'] === 'producteur'): ?>
        <a href="dashboard-producteur.php" class="mobile-nav-link">📊 Dashboard</a>
      <?php elseif ($_SESSION['user_role'] === 'admin'): ?>
        <a href="dashboard_admin.php" class="mobile-nav-link">👑 Administration</a>
      <?php endif; ?>
      <a href="logout.php" class="mobile-nav-link">🚪 Déconnexion</a>
    <?php else: ?>
      <a href="signin.php" class="mobile-nav-link">🔑 Connexion</a>
      <a href="signup.php" class="mobile-nav-link">📝 Inscription</a>
    <?php endif; ?>
  </div>
</header>

<style>
  /* ========== HEADER STYLES ========== */
  .main-header {
    background: #5D0D18;
    padding: 0.8rem 2rem;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  }
  
  .nav-container {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
  }
  
  .logo-area {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    transition: transform 0.2s;
  }
  .logo-area:hover {
    transform: scale(1.02);
  }
  .logo-img {
    height: 45px;
    width: auto;
    object-fit: contain;
  }
  .logo-text {
    font-family: 'Playfair Display', serif;
    font-size: 1.4rem;
    font-weight: 700;
    color: white;
  }
  
  .search-bar {
    flex: 1;
    max-width: 400px;
  }
  .search-bar input {
    width: 100%;
    padding: 0.6rem 1rem;
    border: none;
    border-radius: 50px;
    outline: none;
    font-size: 0.9rem;
    background: white;
  }
  .search-bar input:focus {
    box-shadow: 0 0 0 2px rgba(255,255,255,0.3);
  }
  
  .nav-links {
    display: flex;
    gap: 1.5rem;
    align-items: center;
  }
  .nav-links a {
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: color 0.3s;
    font-weight: 500;
    padding: 0.5rem 0;
  }
  .nav-links a:hover,
  .nav-links a.active {
    color: white;
    border-bottom: 2px solid white;
  }
  
  .user-menu {
    display: flex;
    align-items: center;
    gap: 1rem;
  }
  .user-name {
    color: white;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 5px;
  }
  .logout-link, .login-link, .signup-link {
    background: rgba(255,255,255,0.15);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    color: white;
    text-decoration: none;
    font-size: 0.85rem;
    transition: background 0.3s;
  }
  .logout-link:hover, .login-link:hover, .signup-link:hover {
    background: rgba(255,255,255,0.3);
  }
  .signup-link {
    background: #9FB2AC;
    color: #5D0D18;
  }
  .signup-link:hover {
    background: #8a9f98;
  }
  
  .mobile-menu-btn {
    display: none;
    background: none;
    border: none;
    color: white;
    font-size: 1.8rem;
    cursor: pointer;
  }
  
  .mobile-menu {
    display: none;
    flex-direction: column;
    width: 100%;
    background: #3e0910;
    margin-top: 1rem;
    border-radius: 12px;
    padding: 0.5rem 0;
  }
  .mobile-menu.open {
    display: flex;
  }
  .mobile-nav-link {
    padding: 0.75rem 1rem;
    color: white;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
  }
  .mobile-nav-link:last-child {
    border-bottom: none;
  }
  
  @media (max-width: 1024px) {
    .main-header {
      padding: 0.8rem 1rem;
    }
    .nav-links {
      display: none;
    }
    .mobile-menu-btn {
      display: block;
    }
    .search-bar {
      order: 1;
      max-width: 100%;
      width: 100%;
    }
    .nav-container {
      flex-wrap: wrap;
    }
  }
  
  @media (max-width: 640px) {
    .logo-text {
      font-size: 1.1rem;
    }
    .logo-img {
      height: 35px;
    }
    .user-name span {
      display: none;
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