<?php session_start();
// Si déjà connecté, rediriger
if (isset($_SESSION['user_role'])) {
    $redirects = [
        'admin'      => 'dashboard_admin.php',
        'producteur' => 'dashboard-producteur.php',
        'client'     => 'dashboard_client.php',
    ];
    header('Location: ' . ($redirects[$_SESSION['user_role']] ?? 'accueil.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GreenMarket | Authentification</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

  <style>
    :root {
      --bg-cream: #FFF9EB;
      --accent-sage: #9FB2AC;
      --primary-burgundy: #5D0D18;
      --primary-hover: #44070F;
      --text-dark: #2D251E;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Jost', sans-serif; }
    body {
      background-color: var(--bg-cream);
      color: var(--text-dark);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 40px 20px;
      overflow-x: hidden;
      position: relative;
    }
    .back-home-btn {
      position: fixed; top: 30px; left: 30px; z-index: 100;
      background: var(--primary-burgundy); color: white; border: none;
      border-radius: 50px; padding: 10px 20px; font-family: 'Jost', sans-serif;
      font-weight: 600; font-size: 0.85rem; cursor: pointer;
      display: flex; align-items: center; gap: 8px; transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(93,13,24,0.2); text-decoration: none;
    }
    .back-home-btn:hover { background: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(93,13,24,0.3); }
    .auth-container {
      background: #ffffff; width: 1100px; max-width: 100%; height: 650px;
      min-height: 600px; border-radius: 30px;
      box-shadow: 0 20px 60px rgba(93,13,24,0.1);
      position: relative; overflow: hidden; display: flex; margin: 0 auto;
    }
    .overlay-panel {
      position: absolute; top: 0; left: 0; width: 50%; height: 100%;
      background: linear-gradient(135deg, #8FA39D 0%, var(--accent-sage) 100%);
      z-index: 10; transition: transform 0.7s cubic-bezier(0.66,0,0.34,1);
      display: flex; flex-direction: column; justify-content: center;
      align-items: center; padding: 30px; color: var(--bg-cream); text-align: center;
    }
    .overlay-panel::before {
      content: ''; position: absolute; inset: 15px;
      border: 1px solid rgba(255,249,235,0.3); border-radius: 20px; pointer-events: none;
    }
    .maroccan-grid {
      position: absolute; width: 120px; height: 120px; opacity: 0.12;
      background: radial-gradient(circle, var(--bg-cream) 20%, transparent 20%),
                  radial-gradient(circle, var(--bg-cream) 20%, transparent 20%);
      background-size: 25px 25px; background-position: 0 0, 12px 12px; top: 30px;
    }
    .brand-logo { height: 30px; width: auto; object-fit: contain; }
    .plant-illustration {
      width: 120px; height: 120px; background: rgba(255,249,235,0.15);
      border-radius: 40% 60% 60% 40% / 50% 40% 60% 50%;
      display: flex; justify-content: center; align-items: center;
      font-size: 3.5rem; color: var(--bg-cream); margin-bottom: 20px;
      box-shadow: inset 0 0 20px rgba(255,255,255,0.2);
      animation: float 4s ease-in-out infinite;
    }
    @keyframes float {
      0%,100% { transform: translateY(0px) rotate(0deg); }
      50% { transform: translateY(-10px) rotate(3deg); }
    }
    .panel-title { font-family: 'Playfair Display', serif; font-size: 1.8rem; margin-bottom: 12px; font-weight: 700; }
    .panel-desc { font-size: 0.85rem; max-width: 280px; opacity: 0.9; line-height: 1.5; }
    .form-box {
      width: 50%; height: 100%; padding: 40px 50px;
      display: flex; flex-direction: column; justify-content: center;
      transition: opacity 0.5s ease-in-out, transform 0.5s ease-in-out;
      overflow-y: auto; position: relative;
    }
    .login-box  { position: absolute; left: 50%; opacity: 1; z-index: 5; pointer-events: all; }
    .signup-box { position: absolute; left: 0;   opacity: 0; z-index: 1; pointer-events: none; }
    .forgot-box { position: absolute; left: 50%; opacity: 0; z-index: 1; pointer-events: none; }

    .auth-container.right-panel-active .overlay-panel { transform: translateX(100%); }
    .auth-container.right-panel-active .login-box,
    .auth-container.right-panel-active .forgot-box  { opacity: 0; z-index: 1; pointer-events: none; }
    .auth-container.right-panel-active .signup-box  { opacity: 1; z-index: 5; pointer-events: all; }

    .auth-container.forgot-panel-active .overlay-panel { transform: translateX(100%); }
    .auth-container.forgot-panel-active .login-box,
    .auth-container.forgot-panel-active .signup-box { opacity: 0; z-index: 1; pointer-events: none; }
    .auth-container.forgot-panel-active .forgot-box { opacity: 1; z-index: 5; pointer-events: all; left: 0; }

    .brand { display: flex; align-items: center; gap: 8px; color: var(--primary-burgundy); margin-bottom: 15px; }
    .brand span { font-family: 'Playfair Display', serif; font-size: 1.3rem; font-weight: 700; }
    h2 { font-family: 'Playfair Display', serif; font-size: 1.8rem; color: var(--primary-burgundy); margin-bottom: 6px; }
    .subtitle { color: #70665f; font-size: 0.9rem; margin-bottom: 22px; }
    .input-group { position: relative; margin-bottom: 14px; }
    .input-group i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #90857d; font-size: 1rem; }
    .input-group input,
    .input-group select {
      width: 100%; padding: 12px 15px 12px 42px;
      background-color: #fcfaf5; border: 1px solid rgba(159,178,172,0.4);
      border-radius: 10px; outline: none; font-size: 0.9rem; color: var(--text-dark);
      transition: all 0.3s ease; appearance: none;
    }
    .input-group input:focus,
    .input-group select:focus {
      border-color: var(--primary-burgundy); background-color: #ffffff;
      box-shadow: 0 0 0 2px rgba(93,13,24,0.08);
    }
    .role-container { display: flex; gap: 12px; margin-bottom: 14px; }
    .role-label-title { font-size: 0.85rem; font-weight: 500; color: #70665f; margin-bottom: 5px; display: block; }
    .role-option { flex: 1; position: relative; }
    .role-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
    .role-card {
      display: flex; align-items: center; justify-content: center; gap: 8px;
      padding: 10px; background: #fcfaf5; border: 1px solid rgba(159,178,172,0.4);
      border-radius: 10px; cursor: pointer; font-size: 0.85rem; transition: all 0.3s ease;
    }
    .role-option input[type="radio"]:checked + .role-card {
      border-color: var(--primary-burgundy); background-color: rgba(93,13,24,0.03);
      color: var(--primary-burgundy); font-weight: 500;
    }
    .producer-fields { max-height: 0; overflow: hidden; transition: max-height 0.4s ease-in-out, opacity 0.3s ease; opacity: 0; }
    .producer-fields.active { max-height: 200px; opacity: 1; margin-bottom: 4px; }
    .form-options { display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; margin-bottom: 18px; }
    .remember-me { display: flex; align-items: center; gap: 6px; cursor: pointer; }
    .remember-me input { accent-color: var(--primary-burgundy); width: 14px; height: 14px; }
    .forgot-link { color: #70665f; text-decoration: none; font-size: 0.85rem; }
    .forgot-link:hover { color: var(--primary-burgundy); text-decoration: underline; }
    .btn-submit {
      width: 100%; padding: 12px; background-color: var(--primary-burgundy);
      color: #ffffff; border: none; border-radius: 10px; font-size: 0.9rem;
      font-weight: 500; cursor: pointer; transition: all 0.3s ease;
      box-shadow: 0 8px 20px rgba(93,13,24,0.15); display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-submit:hover { background-color: var(--primary-hover); transform: translateY(-2px); }
    .btn-submit:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }
    .terms-text { font-size: 0.75rem; color: #90857d; text-align: center; margin-top: 10px; line-height: 1.4; }
    .terms-text a { color: var(--primary-burgundy); text-decoration: none; font-weight: 500; }
    .switch-text { text-align: center; margin-top: 15px; font-size: 0.9rem; color: #70665f; }
    .switch-link { color: var(--primary-burgundy); text-decoration: none; font-weight: 600; margin-left: 5px; }
    .switch-link:hover { text-decoration: underline; }

    /* Alert messages */
    .alert {
      padding: 10px 14px; border-radius: 10px; font-size: 0.85rem;
      margin-bottom: 14px; display: none; align-items: center; gap: 8px;
    }
    .alert.show { display: flex; }
    .alert-error   { background: #fdf0f0; border: 1px solid #f5c6cb; color: #c0392b; }
    .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
    .alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }

    /* Spinner */
    .spinner { width: 16px; height: 16px; border: 2px solid rgba(255,255,255,0.4); border-top-color: white; border-radius: 50%; animation: spin 0.7s linear infinite; display: none; }
    @keyframes spin { to { transform: rotate(360deg); } }

    @media (max-width: 800px) {
      body { padding: 20px; }
      .back-home-btn { top: 15px; left: 15px; padding: 8px 16px; font-size: 0.75rem; }
      .auth-container { flex-direction: column; height: auto; box-shadow: none; background: transparent; margin-top: 55px; }
      .overlay-panel { position: relative; width: 100%; height: 160px; border-radius: 24px; margin-bottom: 15px; transform: none !important; padding: 20px; }
      .maroccan-grid { display: none; }
      .plant-illustration { width: 60px; height: 60px; font-size: 1.8rem; margin-bottom: 8px; }
      .panel-title { font-size: 1.4rem; margin-bottom: 4px; }
      .panel-desc { font-size: 0.8rem; max-width: 100%; }
      .form-box { position: relative; width: 100%; left: 0 !important; background: #ffffff; border-radius: 24px; padding: 30px 25px; box-shadow: 0 10px 30px rgba(93,13,24,0.05); }
      .auth-container .signup-box,
      .auth-container .forgot-box { display: none; }
      .auth-container .login-box  { display: block; }
      .auth-container.right-panel-active .signup-box { display: block; opacity: 1; pointer-events: all; }
      .auth-container.right-panel-active .login-box  { display: none; }
      .auth-container.forgot-panel-active .forgot-box { display: block; opacity: 1; pointer-events: all; }
      .auth-container.forgot-panel-active .login-box  { display: none; }
      .producer-fields-row { flex-direction: column !important; gap: 0 !important; }
    }
  </style>
</head>
<body>

  <a href="accueil.php" class="back-home-btn">
    <i class="bi bi-house-door"></i> Accueil
  </a>

  <div class="auth-container" id="authContainer">

    <div class="overlay-panel">
      <div class="maroccan-grid"></div>
      <div class="plant-illustration"><i class="bi bi-flower1"></i></div>
      <h3 class="panel-title" id="panelTitle">Bienvenue !</h3>
      <p class="panel-desc" id="panelDesc">Rejoignez notre réseau de coopératives et consommez de manière juste, authentique et locale.</p>
    </div>

    <!-- ═══════════════════ LOGIN ═══════════════════ -->
    <div class="form-box login-box" id="loginBox">
      <div class="brand">
        <img src="IMAGES/logo.png" alt="Logo GreenMarket" class="brand-logo" onerror="this.src='https://placehold.co/35x35?text=GM'">
        <span>GreenMarket</span>
      </div>
      <h2>Se connecter</h2>
      <p class="subtitle">Heureux de vous revoir parmi nos coopératives.</p>

      <div class="alert alert-error" id="loginError"><i class="bi bi-exclamation-circle"></i><span></span></div>

      <form id="loginForm" novalidate>
        <div class="input-group">
          <i class="bi bi-envelope"></i>
          <input type="email" name="email" id="loginEmail" placeholder="Adresse Email" required autocomplete="email">
        </div>
        <div class="input-group">
          <i class="bi bi-lock"></i>
          <input type="password" name="password" id="loginPassword" placeholder="Mot de passe" required autocomplete="current-password">
        </div>
        <div class="form-options">
          <label class="remember-me">
            <input type="checkbox" id="rememberMe"> Se souvenir de moi
          </label>
          <a href="#" class="forgot-link" id="toForgot">Mot de passe oublié ?</a>
        </div>
        <button type="submit" class="btn-submit" id="loginBtn">
          <span class="spinner" id="loginSpinner"></span>
          <span id="loginBtnText">Connexion</span>
        </button>
        <p class="terms-text">En vous connectant, vous acceptez nos <a href="terms.php">CGU</a> et notre <a href="privacy.php">Politique de confidentialité</a>.</p>
      </form>

      <p class="switch-text">Pas encore membre ? <a href="#" class="switch-link" id="toSignup">Créer un compte</a></p>
    </div>

    <!-- ═══════════════════ SIGNUP ═══════════════════ -->
    <div class="form-box signup-box" id="signupBox">
      <div class="brand">
        <img src="IMAGES/logo.png" alt="Logo GreenMarket" class="brand-logo" onerror="this.src='https://placehold.co/35x35?text=GM'">
        <span>GreenMarket</span>
      </div>
      <h2>Créer un compte</h2>
      <p class="subtitle">Créez votre profil pour acheter en direct ou proposer vos récoltes.</p>

      <div class="alert alert-error"   id="signupError"><i class="bi bi-exclamation-circle"></i><span></span></div>
      <div class="alert alert-warning" id="signupWarning"><i class="bi bi-hourglass-split"></i><span></span></div>

      <form id="signupForm" novalidate>
        <div class="input-group">
          <i class="bi bi-person"></i>
          <input type="text" name="nom" id="signupName" placeholder="Nom complet" required>
        </div>
        <div class="input-group">
          <i class="bi bi-envelope"></i>
          <input type="email" name="email" id="signupEmail" placeholder="Adresse Email" required autocomplete="email">
        </div>

        <span class="role-label-title">Vous êtes ?</span>
        <div class="role-container">
          <label class="role-option">
            <input type="radio" name="userRole" value="client" checked>
            <div class="role-card"><i class="bi bi-basket"></i> Client</div>
          </label>
          <label class="role-option">
            <input type="radio" name="userRole" value="producteur">
            <div class="role-card"><i class="bi bi-shop"></i> Producteur</div>
          </label>
        </div>

        <div class="producer-fields" id="producerFields">
          <div class="producer-fields-row" style="display:flex;gap:12px;">
            <div class="input-group" style="flex:1;">
              <i class="bi bi-building"></i>
              <input type="text" id="nomEntreprise" name="nom_entreprise" placeholder="Nom de l'entreprise">
            </div>
          </div>
        </div>

        <div class="input-group">
          <i class="bi bi-lock"></i>
          <input type="password" name="password" id="signupPassword" placeholder="Mot de passe" required autocomplete="new-password">
        </div>
        <div class="input-group">
          <i class="bi bi-shield-lock"></i>
          <input type="password" name="confirm" id="signupConfirmPassword" placeholder="Confirmer le mot de passe" required>
        </div>

        <button type="submit" class="btn-submit" id="signupBtn">
          <span class="spinner" id="signupSpinner"></span>
          <span id="signupBtnText">S'inscrire</span>
        </button>
        <p class="terms-text">En vous inscrivant, vous validez nos <a href="terms.php">CGU</a> et certifiez l'exactitude de vos données.</p>
      </form>

      <p class="switch-text">Déjà inscrit ? <a href="#" class="switch-link" id="toLoginFromSignup">Se connecter</a></p>
    </div>

    <!-- ═══════════════════ FORGOT ═══════════════════ -->
    <div class="form-box forgot-box" id="forgotBox">
      <div class="brand">
        <img src="IMAGES/logo.png" alt="Logo GreenMarket" class="brand-logo" onerror="this.src='https://placehold.co/35x35?text=GM'">
        <span>GreenMarket</span>
      </div>
      <h2>Mot de passe oublié</h2>
      <p class="subtitle">Entrez votre adresse e-mail pour recevoir un lien de réinitialisation.</p>

      <div class="alert alert-success" id="forgotSuccess"><i class="bi bi-check-circle"></i><span></span></div>
      <div class="alert alert-error"   id="forgotError"><i class="bi bi-exclamation-circle"></i><span></span></div>

      <form id="forgotForm" novalidate>
        <div class="input-group">
          <i class="bi bi-envelope"></i>
          <input type="email" id="forgotEmail" placeholder="Votre adresse Email" required>
        </div>
        <button type="submit" class="btn-submit">Envoyer le lien</button>
      </form>

      <p class="switch-text">Je m'en souviens ! <a href="#" class="switch-link" id="toLoginFromForgot">Retour à la connexion</a></p>
    </div>

  </div>

  <script>
    const authContainer = document.getElementById('authContainer');
    const panelTitle    = document.getElementById('panelTitle');
    const panelDesc     = document.getElementById('panelDesc');

    // ─── Panel transitions ────────────────────────────────────────────────
    document.getElementById('toSignup').addEventListener('click', e => {
      e.preventDefault();
      authContainer.classList.remove('forgot-panel-active');
      authContainer.classList.add('right-panel-active');
      setTimeout(() => {
        panelTitle.textContent = "Cultivons l'avenir !";
        panelDesc.textContent  = "Découvrez des produits authentiques en direct de nos petits producteurs régionaux.";
      }, 200);
    });

    document.getElementById('toLoginFromSignup').addEventListener('click', e => {
      e.preventDefault();
      authContainer.classList.remove('right-panel-active');
      setTimeout(() => {
        panelTitle.textContent = "Bienvenue !";
        panelDesc.textContent  = "Rejoignez notre réseau de coopératives et consommez de manière juste, authentique et locale.";
      }, 200);
    });

    document.getElementById('toForgot').addEventListener('click', e => {
      e.preventDefault();
      authContainer.classList.add('forgot-panel-active');
      setTimeout(() => {
        panelTitle.textContent = "Sécurité d'abord";
        panelDesc.textContent  = "Nous protégeons vos accès afin de garantir la sérénité de nos échanges locaux.";
      }, 200);
    });

    document.getElementById('toLoginFromForgot').addEventListener('click', e => {
      e.preventDefault();
      authContainer.classList.remove('forgot-panel-active');
      setTimeout(() => {
        panelTitle.textContent = "Bienvenue !";
        panelDesc.textContent  = "Rejoignez notre réseau de coopératives et consommez de manière juste, authentique et locale.";
      }, 200);
    });

    // ─── Role toggle (signup) ─────────────────────────────────────────────
    document.querySelectorAll('input[name="userRole"]').forEach(radio => {
      radio.addEventListener('change', () => {
        const pf = document.getElementById('producerFields');
        if (radio.value === 'producteur') {
          pf.classList.add('active');
          document.getElementById('nomEntreprise').required = true;
        } else {
          pf.classList.remove('active');
          document.getElementById('nomEntreprise').required = false;
        }
      });
    });

    // ─── Helpers ──────────────────────────────────────────────────────────
    function showAlert(el, msg) {
      el.querySelector('span').textContent = msg;
      el.classList.add('show');
    }
    function hideAlert(el) { el.classList.remove('show'); }

    function setLoading(btn, spinner, textEl, loading) {
      btn.disabled       = loading;
      spinner.style.display = loading ? 'block' : 'none';
      textEl.style.opacity  = loading ? '0.6' : '1';
    }

    async function postForm(url, data) {
      const body = new URLSearchParams(data);
      const res  = await fetch(url, { method: 'POST', body });
      return res.json();
    }

    // ─── LOGIN ────────────────────────────────────────────────────────────
    document.getElementById('loginForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      const errEl   = document.getElementById('loginError');
      const btn     = document.getElementById('loginBtn');
      const spinner = document.getElementById('loginSpinner');
      const btnText = document.getElementById('loginBtnText');

      hideAlert(errEl);
      setLoading(btn, spinner, btnText, true);

      try {
        const data = await postForm('login.php', {
          email:    document.getElementById('loginEmail').value.trim(),
          password: document.getElementById('loginPassword').value,
        });

        if (data.success) {
          btnText.textContent = 'Connexion réussie ✓';
          setTimeout(() => { window.location.href = data.redirect; }, 800);
        } else {
          showAlert(errEl, data.message);
          setLoading(btn, spinner, btnText, false);
        }
      } catch {
        showAlert(errEl, 'Erreur réseau. Veuillez réessayer.');
        setLoading(btn, spinner, btnText, false);
      }
    });

    // ─── SIGNUP ───────────────────────────────────────────────────────────
    document.getElementById('signupForm').addEventListener('submit', async function(e) {
      e.preventDefault();
      const errEl     = document.getElementById('signupError');
      const warnEl    = document.getElementById('signupWarning');
      const btn       = document.getElementById('signupBtn');
      const spinner   = document.getElementById('signupSpinner');
      const btnText   = document.getElementById('signupBtnText');

      hideAlert(errEl);
      hideAlert(warnEl);

      const password = document.getElementById('signupPassword').value;
      const confirm  = document.getElementById('signupConfirmPassword').value;
      if (password !== confirm) {
        showAlert(errEl, 'Les mots de passe ne correspondent pas.');
        return;
      }

      setLoading(btn, spinner, btnText, true);

      try {
        const data = await postForm('signup.php', {
          nom:            document.getElementById('signupName').value.trim(),
          email:          document.getElementById('signupEmail').value.trim(),
          password,
          confirm,
          role:           document.querySelector('input[name="userRole"]:checked').value,
          nom_entreprise: document.getElementById('nomEntreprise').value.trim(),
        });

        if (data.success) {
          if (data.pending) {
            showAlert(warnEl, data.message);
            setLoading(btn, spinner, btnText, false);
          } else {
            btnText.textContent = 'Compte créé ✓';
            setTimeout(() => { window.location.href = data.redirect; }, 800);
          }
        } else {
          showAlert(errEl, data.message);
          setLoading(btn, spinner, btnText, false);
        }
      } catch {
        showAlert(errEl, 'Erreur réseau. Veuillez réessayer.');
        setLoading(btn, spinner, btnText, false);
      }
    });

    // ─── FORGOT PASSWORD (placeholder) ───────────────────────────────────
    document.getElementById('forgotForm').addEventListener('submit', function(e) {
      e.preventDefault();
      const email   = document.getElementById('forgotEmail').value.trim();
      const succEl  = document.getElementById('forgotSuccess');
      const errEl   = document.getElementById('forgotError');
      hideAlert(errEl);
      if (!email) { showAlert(errEl, 'Veuillez entrer votre adresse email.'); return; }
      showAlert(succEl, `Un lien de réinitialisation a été envoyé à ${email}.`);
    });
  </script>
</body>
</html>