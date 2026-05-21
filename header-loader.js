// header-loader.js - Version corrigée pour afficher Dashboard à TOUS les utilisateurs connectés

function loadHeader() {
  fetch('header.html')
    .then(response => response.text())
    .then(data => {
      document.getElementById('header-placeholder').innerHTML = data;
      initHeaderFeatures();
    })
    .catch(error => console.error('Erreur chargement header:', error));
}

function initHeaderFeatures() {
  // Activer le lien actif
  highlightActiveNav();
  
  // Mettre à jour le compteur du panier
  updateCartCount();
  
  // Mettre à jour l'interface d'authentification
  updateAuthUI();
  
  // Menu mobile
  const toggleBtn = document.getElementById('mobileMenuToggle');
  const mobileMenu = document.getElementById('mobileMenu');
  if (toggleBtn && mobileMenu) {
    toggleBtn.style.display = 'block';
    toggleBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      mobileMenu.classList.toggle('open');
      const svg = toggleBtn.querySelector('svg');
      if (mobileMenu.classList.contains('open')) {
        if (svg) svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>';
      } else {
        if (svg) svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>';
      }
    });
    
    mobileMenu.querySelectorAll('a').forEach(link => {
      link.addEventListener('click', () => {
        mobileMenu.classList.remove('open');
        const svg = toggleBtn.querySelector('svg');
        if (svg) svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>';
      });
    });
    
    document.addEventListener('click', function(event) {
      if (!toggleBtn.contains(event.target) && !mobileMenu.contains(event.target)) {
        mobileMenu.classList.remove('open');
        const svg = toggleBtn.querySelector('svg');
        if (svg) svg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>';
      }
    });
  }
  
  // Écouter les changements de localStorage
  window.addEventListener('storage', (e) => {
    if (e.key === 'greenmarket_cart') updateCartCount();
    if (e.key === 'greenmarket_current_user') updateAuthUI();
  });
}

function highlightActiveNav() {
  const activePage = document.body.getAttribute('data-active-page');
  
  if (!activePage) return;
  
  document.querySelectorAll('.desktop-nav .nav-link, .mobile-nav-link').forEach(link => {
    const navValue = link.getAttribute('data-nav');
    if (navValue === activePage) {
      link.classList.add('active');
    } else {
      link.classList.remove('active');
    }
  });
}

function updateCartCount() {
  const cart = JSON.parse(localStorage.getItem('greenmarket_cart') || '[]');
  const total = cart.reduce((sum, item) => sum + (item.quantity || 1), 0);
  const badge = document.getElementById('cart-count');
  if (badge) badge.textContent = total;
}

function getCurrentUser() {
  const u = sessionStorage.getItem('greenmarket_current_user');
  return u ? JSON.parse(u) : null;
}

function updateAuthUI() {
  const widget = document.getElementById('authWidget');
  const dashboardLink = document.getElementById('dashboardLink');
  const mobileDashboardLink = document.getElementById('mobileDashboardLink');
  
  if (!widget) return;
  
  const user = getCurrentUser();
  
  if (user) {
    // 🔑 IMPORTANT: Afficher le Dashboard pour TOUS les utilisateurs connectés (client, artisan, admin)
    if (dashboardLink) {
      dashboardLink.style.display = 'inline-block';
      dashboardLink.href = 'dashboard.html';  // Redirige vers la page qui choisit le bon dashboard
    }
    if (mobileDashboardLink) {
      mobileDashboardLink.style.display = 'flex';
      mobileDashboardLink.href = 'dashboard.html';
    }
    
    // Afficher le nom et rôle de l'utilisateur
    const roleText = user.role === 'admin' ? 'Admin' : (user.role === 'productor' ? 'Artisan' : 'Client');
    widget.innerHTML = `<div class="user-badge">👋 ${escapeHtml(user.name)} (${roleText})<button id="logoutBtnHeader" style="background:none;border:none;color:#5d0d18;cursor:pointer;margin-left:8px;">🚪</button></div>`;
    
    document.getElementById('logoutBtnHeader')?.addEventListener('click', () => {
      sessionStorage.removeItem('greenmarket_current_user');
      window.location.href = 'accueil.html';
    });
    
  } else {
    // Utilisateur non connecté : masquer le Dashboard
    if (dashboardLink) dashboardLink.style.display = 'none';
    if (mobileDashboardLink) mobileDashboardLink.style.display = 'none';
    
    widget.innerHTML = `<a href="signin.html"><button class="btn-connexion">🔑 Connexion</button></a>`;
  }
}

function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/[&<>]/g, m => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;' }[m]));
}

function initUsersIfNeeded() {
  if (!localStorage.getItem('greenmarket_users')) {
    const defaultUsers = [
      { id: 1, name: "Jean Client", email: "client@test.com", password: "client123", role: "client" },
      { id: 2, name: "Artisan Fatima", email: "artisan@test.com", password: "artisan123", role: "productor" },
      { id: 3, name: "Admin Green", email: "admin@test.com", password: "admin123", role: "admin" }
    ];
    localStorage.setItem('greenmarket_users', JSON.stringify(defaultUsers));
  }
}

initUsersIfNeeded();
loadHeader();