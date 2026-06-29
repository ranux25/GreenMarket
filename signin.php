<?php
session_start();

if (isset($_SESSION['user_role'])) {
    $redirects = [
        'admin'      => 'dashboard_admin.php',
        'producteur' => 'dashboard_producteur.php',
        'client'     => 'dashboard_client.php',
    ];
    header("Location: " . ($redirects[$_SESSION['user_role']] ?? 'accueil.php'));
    exit;
}

$theme = $_COOKIE['theme'] ?? 'light';

if (isset($_GET['msgs'])) $_SESSION['success'] = $_GET['msgs'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    extract($_POST);
    $action = $action ?? '';

    if ($action == 'login') {
        $err = [];
        if (!isset($email) || empty($email)) $err['email'] = "Veuillez entrer votre email";
        if (!isset($password) || empty($password)) $err['password'] = "Veuillez entrer votre mot de passe";

        if (empty($err)) {
            $email = trim($email);
            include("connexion.php");
            try {
                $req = $pdo->prepare("SELECT id_admin as id, nom_admin as nom, email, mot_de_passe, 'admin' as role, 1 as valide FROM administrateur WHERE email = ?");
                $req->execute([$email]);
                $user = $req->fetch(PDO::FETCH_ASSOC);

                if (empty($user)) {
                    $req = $pdo->prepare("SELECT id_client as id, nom_client as nom, email, mot_de_passe, 'client' as role, 1 as valide, est_actif FROM client WHERE email = ?");
                    $req->execute([$email]);
                    $user = $req->fetch(PDO::FETCH_ASSOC);
                }

                if (empty($user)) {
                    $req = $pdo->prepare("SELECT id_producteur as id, nom_entreprise as nom, email, mot_de_passe, 'producteur' as role, est_valide_par_admin as valide, statut FROM producteur WHERE email = ?");
                    $req->execute([$email]);
                    $user = $req->fetch(PDO::FETCH_ASSOC);
                }

                if (empty($user)) {
                    $_SESSION['error'] = "Email ou mot de passe incorrect";
                    $_SESSION['active_form'] = 'login';
                    $_SESSION['form_data'] = ['email' => $email];
                } elseif (!password_verify($password, $user['mot_de_passe'])) {
                    $_SESSION['error'] = "Email ou mot de passe incorrect";
                    $_SESSION['active_form'] = 'login';
                    $_SESSION['form_data'] = ['email' => $email];
                } elseif ($user['role'] == 'client' && isset($user['est_actif']) && $user['est_actif'] == 0) {
                    $_SESSION['error'] = "⛔ Votre compte a été suspendu. Veuillez contacter le support.";
                    $_SESSION['active_form'] = 'login';
                    $_SESSION['form_data'] = ['email' => $email];
                } elseif ($user['role'] == 'producteur' && isset($user['statut']) && $user['statut'] === 'refuse') {
                    $_SESSION['error'] = "⛔ Votre compte producteur a été suspendu. Veuillez contacter le support.";
                    $_SESSION['active_form'] = 'login';
                    $_SESSION['form_data'] = ['email' => $email];
                } else {
                    $_SESSION['user_id']    = $user['id'];
                    $_SESSION['user_nom']   = $user['nom'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role']  = $user['role'];
                    $_SESSION['est_valide'] = $user['valide'] ?? 0;
                    $redirects = ['admin' => 'dashboard_admin.php', 'producteur' => 'dashboard_producteur.php', 'client' => 'dashboard_client.php'];
                    header("Location: " . $redirects[$user['role']]);
                    exit;
                }
            }
            catch(PDOException $e) { die("Erreur authentification : " . $e->getMessage()); }
        } else {
            $_SESSION['error'] = reset($err);
            $_SESSION['active_form'] = 'login';
        }
        header("Location: signin.php");
        exit;
    }

    elseif ($action == 'signup') {
        $err = [];
        $nom            = trim($nom ?? '');
        $email          = trim($email ?? '');
        $password       = $password ?? '';
        $confirm        = $confirm ?? '';
        $role           = $role ?? 'client';
        $nom_entreprise = trim($nom_entreprise ?? '');
        $boutique_description = trim($boutique_description ?? '');

        $_SESSION['form_data']   = ['nom' => $nom, 'email' => $email, 'role' => $role, 'nom_entreprise' => $nom_entreprise, 'boutique_description' => $boutique_description];
        $_SESSION['active_form'] = 'signup';

        if (empty($email)) $err['email'] = "L'email est requis";
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err['email'] = "Email invalide";

        if (empty($password)) $err['password'] = "Le mot de passe est requis";
        elseif (strlen($password) < 6) $err['password'] = "Le mot de passe doit contenir au moins 6 caractères";
        elseif ($password !== $confirm) $err['password'] = "Les mots de passe ne correspondent pas";

        if ($role == 'client' && empty($nom)) $err['nom'] = "Le nom est requis";
        if ($role == 'producteur' && empty($nom_entreprise)) $err['nom_entreprise'] = "Le nom de l'entreprise est requis";

        $boutique_image = null;
        if ($role == 'producteur' && isset($_FILES['boutique_image']) && $_FILES['boutique_image']['error'] === UPLOAD_ERR_OK) {
            $exts = ["image/jpeg", "image/png", "image/jpg", "image/gif", "image/webp"];
            if (!in_array($_FILES['boutique_image']['type'], $exts)) {
                $err['boutique_image'] = "Format image non supporté. Utilisez JPG, PNG, GIF ou WEBP";
            } elseif ($_FILES['boutique_image']['size'] > 5 * 1024 * 1024) {
                $err['boutique_image'] = "La taille de l'image ne doit pas dépasser 5MB";
            }
        }

        if (empty($err)) {
            include("connexion.php");
            try {
                $req = $pdo->prepare("SELECT email FROM client WHERE email = ? UNION SELECT email FROM producteur WHERE email = ? UNION SELECT email FROM administrateur WHERE email = ?");
                $req->execute([$email, $email, $email]);
                if ($req->fetch()) {
                    $_SESSION['error'] = "Cet email est déjà utilisé";
                    header("Location: signin.php");
                    exit;
                }

                $hash = password_hash($password, PASSWORD_DEFAULT);

                if ($role == 'client') {
                    $nom = htmlspecialchars(trim($nom));
                    $ri = $pdo->prepare("INSERT INTO client (nom_client, email, mot_de_passe, date_inscription, est_actif) VALUES (?, ?, ?, NOW(), 1)");
                    $r = $ri->execute([$nom, $email, $hash]);
                    if ($r == false) { $_SESSION['error'] = "Echec d'inscription"; header("Location: signin.php"); exit; }
                    $_SESSION['user_id']    = $pdo->lastInsertId();
                    $_SESSION['user_nom']   = $nom;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role']  = 'client';
                    unset($_SESSION['form_data'], $_SESSION['error']);
                    header("Location: dashboard_client.php");
                    exit;

                } elseif ($role == 'producteur') {
                    if (isset($_FILES['boutique_image']) && $_FILES['boutique_image']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = 'IMAGES/boutiques/';
                        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                        $file_name  = uniqid('boutique_') . '.' . pathinfo($_FILES['boutique_image']['name'], PATHINFO_EXTENSION);
                        $file_path  = $upload_dir . $file_name;
                        $ru = move_uploaded_file($_FILES['boutique_image']['tmp_name'], $file_path);
                        if ($ru == false) { $_SESSION['error'] = "Erreur lors de l'upload de l'image"; header("Location: signin.php"); exit; }
                        $boutique_image = $file_path;
                    }

                    $nom_entreprise = htmlspecialchars(trim($nom_entreprise));
                    $ri = $pdo->prepare("INSERT INTO producteur (id_admin, nom_entreprise, email, mot_de_passe, est_valide_par_admin, date_inscription) VALUES (NULL, ?, ?, ?, 0, NOW())");
                    $r = $ri->execute([$nom_entreprise, $email, $hash]);
                    if ($r == false) { $_SESSION['error'] = "Echec d'inscription"; header("Location: signin.php"); exit; }

                    $user_id = $pdo->lastInsertId();

                    $desc = !empty($boutique_description) ? $boutique_description : 'Boutique artisanale marocaine';
                    $ri2 = $pdo->prepare("INSERT INTO boutique (id_producteur, nom_boutique, description, image, date_creation) VALUES (?, ?, ?, ?, NOW())");
                    $ri2->execute([$user_id, $nom_entreprise, $desc, $boutique_image]);

                    $_SESSION['user_id']    = $user_id;
                    $_SESSION['user_nom']   = $nom_entreprise;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role']  = 'producteur';
                    $_SESSION['est_valide'] = 0;
                    unset($_SESSION['form_data'], $_SESSION['error']);
                    $_SESSION['warning'] = "✅ Compte producteur créé ! Un administrateur va valider votre compte.";
                    header("Location: dashboard_producteur.php");
                    exit;
                }
            }
            catch(PDOException $e) { die("Erreur inscription : " . $e->getMessage()); }
        } else {
            $_SESSION['error'] = reset($err);
            header("Location: signin.php");
            exit;
        }
    }

    elseif ($action == 'forgot') {
        $err = [];
        $email = trim($email ?? '');
        $_SESSION['form_data']   = ['email' => $email];
        $_SESSION['active_form'] = 'forgot';

        if (empty($email)) $err['email'] = "Veuillez entrer votre email";
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $err['email'] = "Email invalide";

        if (empty($err)) {
            include("connexion.php");
            try {
                $tables = ['client', 'producteur', 'administrateur'];
                $found  = false;
                foreach ($tables as $table) {
                    $req = $pdo->prepare("SELECT email FROM $table WHERE email = ?");
                    $req->execute([$email]);
                    if ($req->fetch()) { $found = true; break; }
                }
                if ($found) {
                    $_SESSION['success'] = "Un lien de réinitialisation a été envoyé à votre adresse email";
                    unset($_SESSION['form_data']);
                } else {
                    $_SESSION['error'] = "Aucun compte associé à cet email";
                }
            }
            catch(PDOException $e) { die("Erreur forgot password : " . $e->getMessage()); }
        } else {
            $_SESSION['error'] = reset($err);
        }
        header("Location: signin.php");
        exit;
    }
}

$error       = $_SESSION['error']   ?? '';
$success     = $_SESSION['success'] ?? '';
$warning     = $_SESSION['warning'] ?? '';
$form_data   = $_SESSION['form_data']   ?? [];
$active_form = $_SESSION['active_form'] ?? 'login';
unset($_SESSION['error'], $_SESSION['success'], $_SESSION['warning'], $_SESSION['form_data'], $_SESSION['active_form']);
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $theme; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GreenMarket | Authentification</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-body: #FFF9EB;
      --bg-card: #ffffff;
      --bg-input: #fcfaf5;
      --text-primary: #2D251E;
      --text-secondary: #70665f;
      --text-muted: #90857d;
      --border-color: rgba(159,178,172,0.4);
      --shadow-color: rgba(93,13,24,0.1);
      --primary: #5D0D18;
      --primary-hover: #44070F;
      --secondary: #9FB2AC;
      --success: #166534;
      --success-bg: #f0fdf4;
      --success-border: #bbf7d0;
      --error: #c0392b;
      --error-bg: #fdf0f0;
      --error-border: #f5c6cb;
      --warning: #92400e;
      --warning-bg: #fffbeb;
      --warning-border: #fde68a;
      --overlay-bg: linear-gradient(135deg, #8FA39D 0%, var(--secondary) 100%);
      --overlay-text: #FFF9EB;
      --icon-color: #90857d;
      --link-color: #5D0D18;
      --input-focus: rgba(93,13,24,0.08);
    }

    [data-theme="dark"] {
      --bg-body: #1a1410;
      --bg-card: #2c241e;
      --bg-input: #3d3229;
      --text-primary: #f0e6d8;
      --text-secondary: #b8a896;
      --text-muted: #8a7a6a;
      --border-color: #5a4a3a;
      --shadow-color: rgba(0,0,0,0.4);
      --primary: #8a6048;
      --primary-hover: #a0785a;
      --secondary: #6d4c3a;
      --success: #4ade80;
      --success-bg: #1a3a2a;
      --success-border: #2a5a3a;
      --error: #ef5350;
      --error-bg: #3a1a1a;
      --error-border: #5a2a2a;
      --warning: #fbbf24;
      --warning-bg: #3a2a1a;
      --warning-border: #5a4a2a;
      --overlay-bg: linear-gradient(135deg, #3d3229 0%, #2c241e 100%);
      --overlay-text: #f0e6d8;
      --icon-color: #8a7a6a;
      --link-color: #d4a85c;
      --input-focus: rgba(138,96,72,0.2);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Jost', sans-serif; }
    body {
      background-color: var(--bg-body);
      color: var(--text-primary);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 40px 20px;
      position: relative;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    .theme-toggle {
      position: fixed;
      top: 30px;
      right: 30px;
      z-index: 100;
      background: var(--bg-card);
      color: var(--text-primary);
      border: 1px solid var(--border-color);
      border-radius: 50%;
      width: 44px;
      height: 44px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
      transition: all 0.3s ease;
      box-shadow: 0 4px 12px var(--shadow-color);
    }
    .theme-toggle:hover {
      transform: scale(1.1);
      border-color: var(--primary);
    }
    [data-theme="dark"] .theme-toggle .bi-sun { display: none; }
    [data-theme="dark"] .theme-toggle .bi-moon { display: block; }
    [data-theme="light"] .theme-toggle .bi-sun { display: block; }
    [data-theme="light"] .theme-toggle .bi-moon { display: none; }

    .back-home-btn {
      position: fixed; top: 30px; left: 30px; z-index: 100;
      background: var(--primary); color: white; border: none;
      border-radius: 50px; padding: 10px 20px;
      display: flex; align-items: center; gap: 8px;
      text-decoration: none; font-weight: 600; font-size: 0.85rem;
      transition: all 0.3s ease;
    }
    .back-home-btn:hover { background: var(--primary-hover); transform: translateY(-2px); }

    .auth-container {
      background: var(--bg-card); width: 1100px; max-width: 100%; min-height: 720px;
      border-radius: 30px; box-shadow: 0 20px 60px var(--shadow-color);
      position: relative; overflow: hidden; display: flex; margin: 0 auto;
      transition: background 0.3s ease, box-shadow 0.3s ease;
    }

    .overlay-panel {
      position: absolute; top: 0; left: 0; width: 50%; height: 100%;
      background: var(--overlay-bg);
      z-index: 10; transition: transform 0.7s cubic-bezier(0.66,0,0.34,1);
      display: flex; flex-direction: column; justify-content: center;
      align-items: center; padding: 30px; color: var(--overlay-text); text-align: center;
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
      background: var(--bg-card);
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

    .brand { display: flex; align-items: center; gap: 8px; color: var(--primary); margin-bottom: 15px; }
    .brand span { font-family: 'Playfair Display', serif; font-size: 1.3rem; font-weight: 700; color: var(--text-primary); }
    .brand-logo { height: 35px; width: auto; object-fit: contain; }

    h2 { font-family: 'Playfair Display', serif; font-size: 1.8rem; color: var(--primary); margin-bottom: 6px; }
    .subtitle { color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 22px; }

    .input-group { position: relative; margin-bottom: 14px; }
    .input-group i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--icon-color); font-size: 1rem; z-index: 1; }
    .input-group input, .input-group textarea, .input-group input[type="file"] {
      width: 100%; padding: 12px 15px 12px 42px;
      background-color: var(--bg-input); border: 1px solid var(--border-color);
      border-radius: 10px; outline: none; font-size: 0.9rem;
      font-family: 'Jost', sans-serif;
      color: var(--text-primary);
      transition: border-color 0.3s, box-shadow 0.3s, background 0.3s;
    }
    .input-group input[type="file"] {
      padding: 10px 15px 10px 42px;
    }
    .input-group textarea {
      padding: 12px 15px;
      resize: vertical;
      min-height: 80px;
    }
    .input-group input:focus, .input-group textarea:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 2px var(--input-focus);
    }
    .input-group input::placeholder, .input-group textarea::placeholder {
      color: var(--text-muted);
    }

    .role-container { display: flex; gap: 12px; margin-bottom: 14px; }
    .role-label-title { font-size: 0.85rem; font-weight: 500; color: var(--text-secondary); margin-bottom: 5px; display: block; }
    .role-option { flex: 1; }
    .role-option input[type="radio"] { display: none; }
    .role-card {
      display: flex; align-items: center; justify-content: center; gap: 8px;
      padding: 10px; background: var(--bg-input); border: 1px solid var(--border-color);
      border-radius: 10px; cursor: pointer; font-size: 0.85rem;
      color: var(--text-secondary);
      transition: all 0.3s ease;
    }
    .role-option input[type="radio"]:checked + .role-card {
      border-color: var(--primary);
      background-color: var(--input-focus);
      color: var(--primary);
      font-weight: 500;
    }

    .producer-fields { max-height: 0; overflow: hidden; transition: max-height 0.4s ease, opacity 0.4s ease, margin 0.4s ease; opacity: 0; margin-bottom: 0; }
    .producer-fields.active { max-height: 500px; opacity: 1; margin-bottom: 4px; }

    .help-text {
      font-size: 0.7rem;
      color: var(--text-muted);
      margin-top: -8px;
      margin-bottom: 10px;
      margin-left: 2px;
    }
    .file-info {
      font-size: 0.7rem;
      color: var(--text-muted);
      margin-top: -8px;
      margin-bottom: 10px;
      margin-left: 2px;
    }

    .form-options { display: flex; justify-content: space-between; margin-bottom: 18px; font-size: 0.85rem; color: var(--text-secondary); }
    .form-options label { display: flex; align-items: center; gap: 6px; cursor: pointer; }
    .form-options a { color: var(--link-color); text-decoration: none; }
    .form-options a:hover { text-decoration: underline; }

    .btn-submit {
      width: 100%; padding: 12px; background-color: var(--primary);
      color: white; border: none; border-radius: 10px; font-size: 0.9rem;
      font-weight: 500; cursor: pointer; transition: all 0.3s ease;
    }
    .btn-submit:hover { background-color: var(--primary-hover); transform: translateY(-2px); }

    .terms-text { font-size: 0.75rem; color: var(--text-muted); text-align: center; margin-top: 10px; }
    .terms-text a { color: var(--link-color); text-decoration: none; }
    .terms-text a:hover { text-decoration: underline; }

    .switch-text { text-align: center; margin-top: 15px; font-size: 0.9rem; color: var(--text-secondary); }
    .switch-link { color: var(--link-color); text-decoration: none; font-weight: 600; cursor: pointer; }
    .switch-link:hover { text-decoration: underline; }

    .alert { padding: 10px 14px; border-radius: 10px; font-size: 0.85rem; margin-bottom: 14px; display: flex; align-items: center; gap: 8px; }
    .alert-error { background: var(--error-bg); border: 1px solid var(--error-border); color: var(--error); }
    .alert-success { background: var(--success-bg); border: 1px solid var(--success-border); color: var(--success); }
    .alert-warning { background: var(--warning-bg); border: 1px solid var(--warning-border); color: var(--warning); }

    @media (max-width: 800px) {
      .auth-container { flex-direction: column; height: auto; background: transparent; box-shadow: none; }
      .overlay-panel { position: relative; width: 100%; height: 160px; border-radius: 24px; margin-bottom: 15px; transform: none !important; padding: 20px; }
      .plant-illustration { width: 60px; height: 60px; font-size: 1.8rem; margin-bottom: 8px; }
      .panel-title { font-size: 1.4rem; }
      .form-box { position: relative; width: 100%; left: 0 !important; background: var(--bg-card); border-radius: 24px; padding: 30px 25px; box-shadow: 0 20px 60px var(--shadow-color); }
      .auth-container .signup-box, .auth-container .forgot-box { display: none; }
      .auth-container .login-box { display: block; }
      .auth-container.right-panel-active .signup-box { display: block; }
      .auth-container.right-panel-active .login-box { display: none; }
      .auth-container.forgot-panel-active .forgot-box { display: block; }
      .auth-container.forgot-panel-active .login-box { display: none; }
      .back-home-btn { top: 15px; left: 15px; padding: 8px 14px; font-size: 0.75rem; }
      .theme-toggle { top: 15px; right: 15px; width: 38px; height: 38px; font-size: 1rem; }
    }
  </style>
</head>
<body>

<a href="accueil.php" class="back-home-btn">
  <i class="bi bi-house-door"></i> Accueil
</a>

<button class="theme-toggle" id="themeToggle" aria-label="Changer le thème">
  <i class="bi bi-sun"></i>
  <i class="bi bi-moon"></i>
</button>

<div class="auth-container" id="authContainer">
  <div class="overlay-panel">
    <div class="plant-illustration"><i class="bi bi-flower1"></i></div>
    <h3 class="panel-title" id="panelTitle">Bienvenue !</h3>
    <p class="panel-desc" id="panelDesc">Rejoignez notre réseau de coopératives et consommez de manière juste, authentique et locale.</p>
  </div>

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

    <form method="POST" enctype="multipart/form-data">
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

      <div class="producer-fields" id="producerFields" style="<?php echo (isset($form_data['role']) && $form_data['role'] === 'producteur') ? 'max-height:400px; opacity:1; margin-bottom:4px;' : ''; ?>">
        <div class="input-group">
          <i class="bi bi-building"></i>
          <input type="text" name="nom_entreprise" placeholder="Nom de l'entreprise" value="<?php echo htmlspecialchars($form_data['nom_entreprise'] ?? ''); ?>">
        </div>
        
        <div class="input-group">
          <i class="bi bi-file-text"></i>
          <textarea name="boutique_description" placeholder="Description de votre boutique (présentation, savoir-faire...)" rows="3"><?php echo htmlspecialchars($form_data['boutique_description'] ?? ''); ?></textarea>
        </div>
        <div class="help-text">Décrivez votre activité, vos produits, votre histoire...</div>
        
        <div class="input-group">
          <i class="bi bi-image"></i>
          <input type="file" name="boutique_image" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
        </div>
        <div class="file-info">Formats acceptés : JPG, PNG, GIF, WEBP (max 5MB)</div>
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

  document.querySelectorAll('input[name="role"]').forEach(radio => {
    radio.addEventListener('change', function() {
      const pf = document.getElementById('producerFields');
      if (this.value === 'producteur') {
        pf.classList.add('active');
        pf.querySelector('input[name="nom_entreprise"]').required = true;
      } else {
        pf.classList.remove('active');
        pf.querySelector('input[name="nom_entreprise"]').required = false;
      }
    });
  });

  const themeToggle = document.getElementById('themeToggle');
  const htmlElement = document.documentElement;

  function setTheme(theme) {
    htmlElement.setAttribute('data-theme', theme);
    document.cookie = 'theme=' + theme + '; path=/; max-age=31536000';
  }

  themeToggle.addEventListener('click', function() {
    const currentTheme = htmlElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    setTheme(newTheme);
  });

  const savedTheme = '<?php echo $theme; ?>';
  if (savedTheme) {
    htmlElement.setAttribute('data-theme', savedTheme);
  }

  <?php if ($active_form === 'signup'): ?>
    container.classList.add('right-panel-active');
    panelTitle.textContent = "Cultivons l'avenir !";
    panelDesc.textContent = "Découvrez des produits authentiques en direct de nos petits producteurs régionaux.";
  <?php elseif ($active_form === 'forgot'): ?>
    container.classList.add('forgot-panel-active');
    panelTitle.textContent = "Sécurité d'abord";
    panelDesc.textContent = "Nous protégeons vos accès afin de garantir la sérénité de nos échanges locaux.";
  <?php endif; ?>
</script>
</body>
</html>