<?php 
session_start();

// IMPORTANT: Vérifier si l'utilisateur essaie d'accéder à signin.php alors qu'il est déjà connecté
$current_page = basename($_SERVER['PHP_SELF']);

if (isset($_SESSION['user_role']) && $current_page == 'signin.php') {
    $redirects = [
        'admin'      => 'dashboard_admin.php',
        'producteur' => 'dashboard-producteur.php',
        'client'     => 'dashboard_client.php',
    ];
    $redirect = $redirects[$_SESSION['user_role']] ?? 'accueil.php';
    
    // Éviter la boucle infinie en vérifiant qu'on ne redirige pas vers la même page
    if ($redirect != $current_page) {
        header('Location: ' . $redirect);
        exit;
    }
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'connexion.php';
    
    $action = $_POST['action'] ?? '';
    
    // ========== LOGIN ==========
    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $_SESSION['error'] = 'Email et mot de passe requis';
            $_SESSION['active_form'] = 'login';
        } else {
            try {
                // Chercher dans admin d'abord
                $stmt = $pdo->prepare("SELECT id_admin as id, nom_admin as nom, email, mot_de_passe, 'admin' as role, 1 as valide FROM administrateur WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                // Chercher dans client
                if (!$user) {
                    $stmt = $pdo->prepare("SELECT id_client as id, nom_client as nom, email, mot_de_passe, 'client' as role, 1 as valide FROM client WHERE email = ? AND est_actif = 1");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                }
                
                // Chercher dans producteur
                if (!$user) {
                    $stmt = $pdo->prepare("SELECT id_producteur as id, nom_entreprise as nom, email, mot_de_passe, 'producteur' as role, est_valide_par_admin as valide FROM producteur WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                }
                
                if (!$user || $password !== $user['mot_de_passe']) {
                    $_SESSION['error'] = 'Email ou mot de passe incorrect';
                    $_SESSION['active_form'] = 'login';
                    $_SESSION['form_data'] = ['email' => $email];
                } elseif ($user['role'] === 'producteur' && $user['valide'] != 1) {
                    $_SESSION['warning'] = '⚠️ Votre compte producteur est en attente de validation par un administrateur.';
                    $_SESSION['active_form'] = 'login';
                    $_SESSION['form_data'] = ['email' => $email];
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_nom'] = $user['nom'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    
                    $redirects = [
                        'admin' => 'dashboard_admin.php',
                        'producteur' => 'dashboard-producteur.php',
                        'client' => 'dashboard_client.php'
                    ];
                    header('Location: ' . $redirects[$user['role']]);
                    exit;
                }
            } catch(PDOException $e) {
                $_SESSION['error'] = 'Erreur technique. Veuillez réessayer.';
                $_SESSION['active_form'] = 'login';
            }
        }
        header('Location: signin.php');
        exit;
    }
    
    // ========== SIGNUP ==========
    elseif ($action === 'signup') {
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        $role = $_POST['role'] ?? 'client';
        $nom_entreprise = trim($_POST['nom_entreprise'] ?? '');
        
        $_SESSION['form_data'] = ['nom' => $nom, 'email' => $email, 'role' => $role, 'nom_entreprise' => $nom_entreprise];
        $_SESSION['active_form'] = 'signup';
        
        $error = null;
        
        if (empty($email)) $error = 'L\'email est requis';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Email invalide';
        elseif (empty($password)) $error = 'Le mot de passe est requis';
        elseif (strlen($password) < 6) $error = 'Le mot de passe doit contenir au moins 6 caractères';
        elseif ($password !== $confirm) $error = 'Les mots de passe ne correspondent pas';
        elseif ($role === 'client' && empty($nom)) $error = 'Le nom est requis';
        elseif ($role === 'producteur' && empty($nom_entreprise)) $error = 'Le nom de l\'entreprise est requis';
        
        if ($error) {
            $_SESSION['error'] = $error;
            header('Location: signin.php');
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT email FROM client WHERE email = ? UNION SELECT email FROM producteur WHERE email = ? UNION SELECT email FROM administrateur WHERE email = ?");
            $stmt->execute([$email, $email, $email]);
            
            if ($stmt->fetch()) {
                $_SESSION['error'] = 'Cet email est déjà utilisé';
                header('Location: signin.php');
                exit;
            }
            
            if ($role === 'client') {
                $sql = "INSERT INTO client (nom_client, email, mot_de_passe, date_inscription, est_actif) VALUES (:nom, :email, :password, NOW(), 1)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':nom' => $nom, ':email' => $email, ':password' => $password]);
                
                $user_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_nom'] = $nom;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'client';
                
                unset($_SESSION['form_data'], $_SESSION['error']);
                header('Location: dashboard_client.php');
                exit;
                
            } elseif ($role === 'producteur') {
                $sql = "INSERT INTO producteur (id_admin, nom_entreprise, email, mot_de_passe, est_valide_par_admin, date_inscription) VALUES (NULL, :nom_entreprise, :email, :password, 0, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':nom_entreprise' => $nom_entreprise, ':email' => $email, ':password' => $password]);
                
                $user_id = $pdo->lastInsertId();
                
                $sql2 = "INSERT INTO boutique (id_producteur, nom_boutique, description, date_creation) VALUES (:id_producteur, :nom_boutique, :description, NOW())";
                $stmt2 = $pdo->prepare($sql2);
                $stmt2->execute([':id_producteur' => $user_id, ':nom_boutique' => $nom_entreprise, ':description' => 'Boutique artisanale marocaine']);
                
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_nom'] = $nom_entreprise;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'producteur';
                $_SESSION['est_valide'] = 0;
                
                unset($_SESSION['form_data'], $_SESSION['error']);
                $_SESSION['warning'] = '✅ Compte producteur créé ! Un administrateur va valider votre compte.';
                header('Location: dashboard-producteur.php');
                exit;
            }
            
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Erreur lors de l\'inscription: ' . $e->getMessage();
            header('Location: signin.php');
            exit;
        }
    }
    
    // ========== FORGOT PASSWORD ==========
    elseif ($action === 'forgot') {
        $email = trim($_POST['email'] ?? '');
        $_SESSION['form_data'] = ['email' => $email];
        $_SESSION['active_form'] = 'forgot';
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Email valide requis';
        } else {
            try {
                $tables = ['client', 'producteur', 'administrateur'];
                $found = false;
                foreach ($tables as $table) {
                    $stmt = $pdo->prepare("SELECT email FROM $table WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetch()) { $found = true; break; }
                }
                
                if ($found) {
                    $_SESSION['success'] = 'Un lien de réinitialisation a été envoyé à votre adresse email';
                    unset($_SESSION['form_data']);
                } else {
                    $_SESSION['error'] = 'Aucun compte associé à cet email';
                }
            } catch(PDOException $e) {
                $_SESSION['error'] = 'Erreur technique';
            }
        }
        header('Location: signin.php');
        exit;
    }
}

// Récupérer les messages
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
$warning = $_SESSION['warning'] ?? '';
$form_data = $_SESSION['form_data'] ?? [];
$active_form = $_SESSION['active_form'] ?? 'login';

// Effacer les messages
unset($_SESSION['error'], $_SESSION['success'], $_SESSION['warning'], $_SESSION['form_data'], $_SESSION['active_form']);
?>
<!-- Reste de votre HTML -->
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GreenMarket | Authentification</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <style>
    /* Tes styles existants - garde les mêmes */
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
      position: relative;
    }
    .back-home-btn {
      position: fixed; top: 30px; left: 30px; z-index: 100;
      background: var(--primary-burgundy); color: white; border: none;
      border-radius: 50px; padding: 10px 20px;
      display: flex; align-items: center; gap: 8px;
      text-decoration: none; font-weight: 600; font-size: 0.85rem;
      transition: all 0.3s ease;
    }
    .back-home-btn:hover { background: var(--primary-hover); transform: translateY(-2px); }
    .auth-container {
      background: #ffffff; width: 1100px; max-width: 100%; height: 650px;
      border-radius: 30px; box-shadow: 0 20px 60px rgba(93,13,24,0.1);
      position: relative; overflow: hidden; display: flex; margin: 0 auto;
    }
    /* Reste de tes styles... */
    .overlay-panel {
      position: absolute; top: 0; left: 0; width: 50%; height: 100%;
      background: linear-gradient(135deg, #8FA39D 0%, var(--accent-sage) 100%);
      z-index: 10; transition: transform 0.7s cubic-bezier(0.66,0,0.34,1);
      display: flex; flex-direction: column; justify-content: center;
      align-items: center; padding: 30px; color: var(--bg-cream); text-align: center;
    }
    .plant-illustration {
      width: 120px; height: 120px; background: rgba(255,249,235,0.15);
      border-radius: 40% 60% 60% 40% / 50% 40% 60% 50%;
      display: flex; justify-content: center; align-items: center;
      font-size: 3.5rem; margin-bottom: 20px;
      animation: float 4s ease-in-out infinite;
    }
    @keyframes float {
      0%,100% { transform: translateY(0px) rotate(0deg); }
      50% { transform: translateY(-10px) rotate(3deg); }
    }
    .panel-title { font-family: 'Playfair Display', serif; font-size: 1.8rem; margin-bottom: 12px; font-weight: 700; }
    .panel-desc { font-size: 0.85rem; max-width: 280px; opacity: 0.9; }
    .form-box {
      width: 50%; height: 100%; padding: 40px 50px;
      display: flex; flex-direction: column; justify-content: center;
      transition: opacity 0.5s ease-in-out;
      overflow-y: auto; position: relative;
    }
    .login-box  { position: absolute; left: 50%; opacity: 1; z-index: 5; pointer-events: all; }
    .signup-box { position: absolute; left: 0;   opacity: 0; z-index: 1; pointer-events: none; }
    .forgot-box { position: absolute; left: 50%; opacity: 0; z-index: 1; pointer-events: none; }

    .auth-container.right-panel-active .overlay-panel { transform: translateX(100%); }
    .auth-container.right-panel-active .login-box { opacity: 0; z-index: 1; pointer-events: none; }
    .auth-container.right-panel-active .signup-box { opacity: 1; z-index: 5; pointer-events: all; }

    .auth-container.forgot-panel-active .overlay-panel { transform: translateX(100%); }
    .auth-container.forgot-panel-active .login-box { opacity: 0; z-index: 1; pointer-events: none; }
    .auth-container.forgot-panel-active .forgot-box { opacity: 1; z-index: 5; pointer-events: all; left: 0; }

    .brand { display: flex; align-items: center; gap: 8px; color: var(--primary-burgundy); margin-bottom: 15px; }
    .brand span { font-family: 'Playfair Display', serif; font-size: 1.3rem; font-weight: 700; }
    .brand-logo { height: 35px; width: auto; object-fit: contain; }
    h2 { font-family: 'Playfair Display', serif; font-size: 1.8rem; color: var(--primary-burgundy); margin-bottom: 6px; }
    .subtitle { color: #70665f; font-size: 0.9rem; margin-bottom: 22px; }
    .input-group { position: relative; margin-bottom: 14px; }
    .input-group i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #90857d; font-size: 1rem; }
    .input-group input {
      width: 100%; padding: 12px 15px 12px 42px;
      background-color: #fcfaf5; border: 1px solid rgba(159,178,172,0.4);
      border-radius: 10px; outline: none; font-size: 0.9rem;
    }
    .input-group input:focus {
      border-color: var(--primary-burgundy);
      box-shadow: 0 0 0 2px rgba(93,13,24,0.08);
    }
    .role-container { display: flex; gap: 12px; margin-bottom: 14px; }
    .role-label-title { font-size: 0.85rem; font-weight: 500; color: #70665f; margin-bottom: 5px; display: block; }
    .role-option { flex: 1; }
    .role-option input[type="radio"] { display: none; }
    .role-card {
      display: flex; align-items: center; justify-content: center; gap: 8px;
      padding: 10px; background: #fcfaf5; border: 1px solid rgba(159,178,172,0.4);
      border-radius: 10px; cursor: pointer; font-size: 0.85rem;
    }
    .role-option input[type="radio"]:checked + .role-card {
      border-color: var(--primary-burgundy);
      background-color: rgba(93,13,24,0.03);
      color: var(--primary-burgundy);
      font-weight: 500;
    }
    .producer-fields { max-height: 0; overflow: hidden; transition: max-height 0.4s ease; opacity: 0; }
    .producer-fields.active { max-height: 200px; opacity: 1; margin-bottom: 4px; }
    .form-options { display: flex; justify-content: space-between; margin-bottom: 18px; font-size: 0.85rem; }
    .btn-submit {
      width: 100%; padding: 12px; background-color: var(--primary-burgundy);
      color: white; border: none; border-radius: 10px; font-size: 0.9rem;
      font-weight: 500; cursor: pointer; transition: all 0.3s ease;
    }
    .btn-submit:hover { background-color: var(--primary-hover); transform: translateY(-2px); }
    .terms-text { font-size: 0.75rem; color: #90857d; text-align: center; margin-top: 10px; }
    .switch-text { text-align: center; margin-top: 15px; font-size: 0.9rem; color: #70665f; }
    .switch-link { color: var(--primary-burgundy); text-decoration: none; font-weight: 600; }
    .switch-link:hover { text-decoration: underline; }
    
    .alert { padding: 10px 14px; border-radius: 10px; font-size: 0.85rem; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
    .alert-error { background: #fdf0f0; border: 1px solid #f5c6cb; color: #c0392b; }
    .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
    .alert-warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }

    @media (max-width: 800px) {
      .auth-container { flex-direction: column; height: auto; background: transparent; }
      .overlay-panel { position: relative; width: 100%; height: 160px; border-radius: 24px; margin-bottom: 15px; transform: none !important; padding: 20px; }
      .plant-illustration { width: 60px; height: 60px; font-size: 1.8rem; margin-bottom: 8px; }
      .panel-title { font-size: 1.4rem; }
      .form-box { position: relative; width: 100%; left: 0 !important; background: white; border-radius: 24px; padding: 30px 25px; }
      .auth-container .signup-box, .auth-container .forgot-box { display: none; }
      .auth-container .login-box { display: block; }
      .auth-container.right-panel-active .signup-box { display: block; }
      .auth-container.right-panel-active .login-box { display: none; }
      .auth-container.forgot-panel-active .forgot-box { display: block; }
      .auth-container.forgot-panel-active .login-box { display: none; }
    }
  </style>
</head>
<body>

<a href="accueil.php" class="back-home-btn">
  <i class="bi bi-house-door"></i> Accueil
</a>

<div class="auth-container" id="authContainer">
  <div class="overlay-panel">
    <div class="plant-illustration"><i class="bi bi-flower1"></i></div>
    <h3 class="panel-title" id="panelTitle">Bienvenue !</h3>
    <p class="panel-desc" id="panelDesc">Rejoignez notre réseau de coopératives et consommez de manière juste, authentique et locale.</p>
  </div>

  <!-- LOGIN -->
  <div class="form-box login-box" id="loginBox">
    <div class="brand">
      <img src="IMAGES/logo.png" alt="Logo" class="brand-logo" onerror="this.src='https://placehold.co/35x35?text=GM'">
      <span>GreenMarket</span>
    </div>
    <h2>Se connecter</h2>
    <p class="subtitle">Heureux de vous revoir parmi nos coopératives.</p>

    <?php if ($active_form === 'login' && $error): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($active_form === 'login' && $warning): ?>
      <div class="alert alert-warning"><?php echo htmlspecialchars($warning); ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="action" value="login">
      <div class="input-group">
        <i class="bi bi-envelope"></i>
        <input type="email" name="email" placeholder="Adresse Email" required value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
      </div>
      <div class="input-group">
        <i class="bi bi-lock"></i>
        <input type="password" name="password" placeholder="Mot de passe" required>
      </div>
      <div class="form-options">
        <label><input type="checkbox" name="remember_me"> Se souvenir de moi</label>
        <a href="#" class="forgot-link" id="toForgot">Mot de passe oublié ?</a>
      </div>
      <button type="submit" class="btn-submit">Connexion</button>
    </form>
    <p class="switch-text">Pas encore membre ? <a href="#" class="switch-link" id="toSignup">Créer un compte</a></p>
  </div>

  <!-- SIGNUP -->
  <div class="form-box signup-box" id="signupBox">
    <div class="brand">
      <img src="IMAGES/logo.png" alt="Logo" class="brand-logo" onerror="this.src='https://placehold.co/35x35?text=GM'">
      <span>GreenMarket</span>
    </div>
    <h2>Créer un compte</h2>
    <p class="subtitle">Créez votre profil pour acheter en direct ou proposer vos récoltes.</p>

    <?php if ($active_form === 'signup' && $error): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="action" value="signup">
      <div class="input-group">
        <i class="bi bi-person"></i>
        <input type="text" name="nom" placeholder="Nom complet" value="<?php echo htmlspecialchars($form_data['nom'] ?? ''); ?>">
      </div>
      <div class="input-group">
        <i class="bi bi-envelope"></i>
        <input type="email" name="email" placeholder="Adresse Email" required value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>">
      </div>

      <span class="role-label-title">Vous êtes ?</span>
      <div class="role-container">
        <label class="role-option">
          <input type="radio" name="role" value="client" <?php echo (!isset($form_data['role']) || $form_data['role'] === 'client') ? 'checked' : ''; ?>>
          <div class="role-card"><i class="bi bi-basket"></i> Client</div>
        </label>
        <label class="role-option">
          <input type="radio" name="role" value="producteur" <?php echo (isset($form_data['role']) && $form_data['role'] === 'producteur') ? 'checked' : ''; ?>>
          <div class="role-card"><i class="bi bi-shop"></i> Producteur</div>
        </label>
      </div>

      <div class="producer-fields" id="producerFields" style="<?php echo (isset($form_data['role']) && $form_data['role'] === 'producteur') ? 'max-height:200px; opacity:1; margin-bottom:4px;' : ''; ?>">
        <div class="input-group">
          <i class="bi bi-building"></i>
          <input type="text" name="nom_entreprise" placeholder="Nom de l'entreprise" value="<?php echo htmlspecialchars($form_data['nom_entreprise'] ?? ''); ?>">
        </div>
      </div>

      <div class="input-group">
        <i class="bi bi-lock"></i>
        <input type="password" name="password" placeholder="Mot de passe" required>
      </div>
      <div class="input-group">
        <i class="bi bi-shield-lock"></i>
        <input type="password" name="confirm" placeholder="Confirmer le mot de passe" required>
      </div>

      <button type="submit" class="btn-submit">S'inscrire</button>
      <p class="terms-text">En vous inscrivant, vous validez nos <a href="terms.php">CGU</a>.</p>
    </form>
    <p class="switch-text">Déjà inscrit ? <a href="#" class="switch-link" id="toLoginFromSignup">Se connecter</a></p>
  </div>

  <!-- FORGOT PASSWORD -->
  <div class="form-box forgot-box" id="forgotBox">
    <div class="brand">
      <img src="IMAGES/logo.png" alt="Logo" class="brand-logo" onerror="this.src='https://placehold.co/35x35?text=GM'">
      <span>GreenMarket</span>
    </div>
    <h2>Mot de passe oublié</h2>
    <p class="subtitle">Entrez votre adresse e-mail pour recevoir un lien de réinitialisation.</p>

    <?php if ($active_form === 'forgot' && $error): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($active_form === 'forgot' && $success): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="action" value="forgot">
      <div class="input-group">
        <i class="bi bi-envelope"></i>
        <input type="email" name="email" placeholder="Votre adresse Email" required>
      </div>
      <button type="submit" class="btn-submit">Envoyer le lien</button>
    </form>
    <p class="switch-text"><a href="#" class="switch-link" id="toLoginFromForgot">Retour à la connexion</a></p>
  </div>
</div>

<script>
  const container = document.getElementById('authContainer');
  const panelTitle = document.getElementById('panelTitle');
  const panelDesc = document.getElementById('panelDesc');

  document.getElementById('toSignup').addEventListener('click', (e) => {
    e.preventDefault();
    container.classList.remove('forgot-panel-active');
    container.classList.add('right-panel-active');
    panelTitle.textContent = "Cultivons l'avenir !";
    panelDesc.textContent = "Découvrez des produits authentiques en direct de nos petits producteurs régionaux.";
  });

  document.getElementById('toLoginFromSignup').addEventListener('click', (e) => {
    e.preventDefault();
    container.classList.remove('right-panel-active');
    panelTitle.textContent = "Bienvenue !";
    panelDesc.textContent = "Rejoignez notre réseau de coopératives et consommez de manière juste, authentique et locale.";
  });

  document.getElementById('toForgot').addEventListener('click', (e) => {
    e.preventDefault();
    container.classList.add('forgot-panel-active');
    panelTitle.textContent = "Sécurité d'abord";
    panelDesc.textContent = "Nous protégeons vos accès afin de garantir la sérénité de nos échanges locaux.";
  });

  document.getElementById('toLoginFromForgot').addEventListener('click', (e) => {
    e.preventDefault();
    container.classList.remove('forgot-panel-active');
    panelTitle.textContent = "Bienvenue !";
    panelDesc.textContent = "Rejoignez notre réseau de coopératives et consommez de manière juste, authentique et locale.";
  });

  // Role toggle
  document.querySelectorAll('input[name="role"]').forEach(radio => {
    radio.addEventListener('change', function() {
      const pf = document.getElementById('producerFields');
      if (this.value === 'producteur') {
        pf.classList.add('active');
        pf.querySelector('input').required = true;
      } else {
        pf.classList.remove('active');
        pf.querySelector('input').required = false;
      }
    });
  });
</script>
</body>
</html>