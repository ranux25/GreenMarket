<?php
session_start();
require_once 'connexion.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$success_message = '';
$error_message = '';

$user = [
    'nom' => '',
    'prenom' => '',
    'email' => '',
    'telephone' => '',
    'adresse' => '',
    'role' => $user_role
];

try {
    if ($user_role === 'client') {
        $stmt = $pdo->prepare("SELECT * FROM client WHERE id_client = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data) {
            $user['nom'] = $user_data['nom_client'] ?? '';
            $user['prenom'] = $user_data['prenom_client'] ?? '';
            $user['email'] = $user_data['email'] ?? '';
            $user['telephone'] = $user_data['telephone_client'] ?? '';
            $user['adresse'] = $user_data['adresse_client'] ?? '';
            $user['role'] = 'client';
        } else {
            $error_message = "Client non trouvé.";
        }
        
    } elseif ($user_role === 'producteur') {
        $stmt = $pdo->prepare("SELECT * FROM producteur WHERE id_producteur = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data) {
            $user['nom'] = $user_data['nom_entreprise'] ?? '';
            $user['prenom'] = $user_data['nom_contact'] ?? '';
            $user['email'] = $user_data['email'] ?? '';
            $user['telephone'] = $user_data['telephone_producteur'] ?? '';
            $user['adresse'] = $user_data['adresse_producteur'] ?? '';
            $user['role'] = 'producteur';
        } else {
            $error_message = "Producteur non trouvé.";
        }
        
    } elseif ($user_role === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM administrateur WHERE id_admin = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data) {
            $user['nom'] = $user_data['nom_admin'] ?? '';
            $user['prenom'] = '';
            $user['email'] = $user_data['email'] ?? '';
            $user['telephone'] = '';
            $user['adresse'] = '';
            $user['role'] = 'admin';
        } else {
            $error_message = "Administrateur non trouvé.";
        }
    } else {
        $error_message = "Rôle utilisateur non reconnu.";
    }
    
} catch(PDOException $e) {
    $error_message = "Erreur de base de données: " . $e->getMessage();
    error_log("Profile error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        
        try {
            if ($user_role === 'client') {
                $stmt = $pdo->prepare("UPDATE client SET nom_client = ?, prenom_client = ?, email = ?, telephone_client = ?, adresse_client = ? WHERE id_client = ?");
                $stmt->execute([$nom, $prenom, $email, $telephone, $adresse, $user_id]);
                
                $user['nom'] = $nom;
                $user['prenom'] = $prenom;
                $user['email'] = $email;
                $user['telephone'] = $telephone;
                $user['adresse'] = $adresse;
                
            } elseif ($user_role === 'producteur') {
                $stmt = $pdo->prepare("UPDATE producteur SET nom_entreprise = ?, nom_contact = ?, email = ?, telephone_producteur = ?, adresse_producteur = ? WHERE id_producteur = ?");
                $stmt->execute([$nom, $prenom, $email, $telephone, $adresse, $user_id]);
                
                $user['nom'] = $nom;
                $user['prenom'] = $prenom;
                $user['email'] = $email;
                $user['telephone'] = $telephone;
                $user['adresse'] = $adresse;
                
            } elseif ($user_role === 'admin') {
                $stmt = $pdo->prepare("UPDATE administrateur SET nom_admin = ?, email = ? WHERE id_admin = ?");
                $stmt->execute([$nom, $email, $user_id]);
                
                $user['nom'] = $nom;
                $user['email'] = $email;
            }
            
            $_SESSION['user_nom'] = $nom;
            $success_message = "Profil mis à jour avec succès !";
            
        } catch(PDOException $e) {
            $error_message = "Erreur lors de la mise à jour: " . $e->getMessage();
        }
    }
    
    elseif ($_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if ($new_password !== $confirm_password) {
            $error_message = "Les nouveaux mots de passe ne correspondent pas.";
        } elseif (strlen($new_password) < 6) {
            $error_message = "Le mot de passe doit contenir au moins 6 caractères.";
        } else {
            try {
                $stored_password = null;
                
                if ($user_role === 'client') {
                    $stmt = $pdo->prepare("SELECT mot_de_passe FROM client WHERE id_client = ?");
                    $stmt->execute([$user_id]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stored_password = $result['mot_de_passe'] ?? null;
                } elseif ($user_role === 'producteur') {
                    $stmt = $pdo->prepare("SELECT mot_de_passe FROM producteur WHERE id_producteur = ?");
                    $stmt->execute([$user_id]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stored_password = $result['mot_de_passe'] ?? null;
                } elseif ($user_role === 'admin') {
                    $stmt = $pdo->prepare("SELECT mot_de_passe FROM administrateur WHERE id_admin = ?");
                    $stmt->execute([$user_id]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stored_password = $result['mot_de_passe'] ?? null;
                }
                
                if ($stored_password && password_verify($current_password, $stored_password)) {
                    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    if ($user_role === 'client') {
                        $stmt = $pdo->prepare("UPDATE client SET mot_de_passe = ? WHERE id_client = ?");
                    } elseif ($user_role === 'producteur') {
                        $stmt = $pdo->prepare("UPDATE producteur SET mot_de_passe = ? WHERE id_producteur = ?");
                    } elseif ($user_role === 'admin') {
                        $stmt = $pdo->prepare("UPDATE administrateur SET mot_de_passe = ? WHERE id_admin = ?");
                    }
                    $stmt->execute([$new_hashed_password, $user_id]);
                    $success_message = "Mot de passe changé avec succès !";
                } else {
                    $error_message = "Mot de passe actuel incorrect.";
                }
            } catch(PDOException $e) {
                $error_message = "Erreur: " . $e->getMessage();
            }
        }
    }
}

$theme = $_COOKIE['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - GreenMarket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --bg-primary: #f5f0e8;
            --bg-secondary: #fff9eb;
            --bg-card: #ffffff;
            --text-primary: #1a1a1a;
            --text-secondary: #2C2C2C;
            --text-muted: #6B6B6B;
            --border-color: #e5e7eb;
            --shadow-color: rgba(93, 13, 24, 0.1);
            --input-bg: #ffffff;
            --card-bg: #f9f9f9;
            --header-gradient-start: #5D0D18;
            --header-gradient-end: #7a1322;
            --alert-success-bg: #d4edda;
            --alert-success-text: #155724;
            --alert-error-bg: #f8d7da;
            --alert-error-text: #721c24;
        }

        [data-theme="dark"] {
            --bg-primary: #2c241e;
            --bg-secondary: #3d3229;
            --bg-card: #3d3229;
            --text-primary: #f0e6d8;
            --text-secondary: #e8dccc;
            --text-muted: #b8a896;
            --border-color: #5a4a3a;
            --shadow-color: rgba(0, 0, 0, 0.4);
            --input-bg: #4d3d32;
            --card-bg: #4d3d32;
            --header-gradient-start: #1a1410;
            --header-gradient-end: #2c241e;
            --alert-success-bg: #2d4a35;
            --alert-success-text: #b8dcc8;
            --alert-error-bg: #4a2d30;
            --alert-error-text: #e8b8b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        body {
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            font-family: 'Lato', sans-serif;
            min-height: 100vh;
            color: var(--text-primary);
        }
        
        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        
        .profile-card {
            background: var(--bg-card);
            border-radius: 24px;
            box-shadow: 0 20px 40px var(--shadow-color);
            overflow: hidden;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--header-gradient-start) 0%, var(--header-gradient-end) 100%);
            padding: 2rem;
            color: #f0e6d8;
        }

        [data-theme="dark"] .profile-header h1 {
            color: #f0e6d8;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin-bottom: 1rem;
            border: 3px solid #9FB2AC;
            color: #f0e6d8;
        }
        
        .profile-role-badge {
            display: inline-block;
            background: #9FB2AC;
            color: #2c241e;
            padding: 0.25rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .tabs-nav {
            display: flex;
            border-bottom: 2px solid var(--border-color);
            background: var(--bg-card);
            padding: 0 1.5rem;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .tab-btn {
            padding: 1rem 1.5rem;
            background: none;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .tab-btn:hover {
            color: var(--text-secondary);
        }
        
        .tab-btn.active {
            color: #5D0D18;
        }
        
        [data-theme="dark"] .tab-btn.active {
            color: #d4c4b0;
        }
        
        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: #5D0D18;
            border-radius: 3px 3px 0 0;
        }
        
        [data-theme="dark"] .tab-btn.active::after {
            background: #d4c4b0;
        }
        
        .tab-content {
            display: none;
            padding: 2rem 1.5rem;
            animation: fadeIn 0.4s ease;
            color: var(--text-secondary);
        }
        
        .tab-content.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
            background: var(--input-bg);
            color: var(--text-secondary);
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: var(--text-muted);
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #9FB2AC;
            box-shadow: 0 0 0 3px rgba(159, 178, 172, 0.2);
        }
        
        .btn-primary {
            background: #5D0D18;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: #7a1322;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(93,13,24,0.3);
        }

        [data-theme="dark"] .btn-primary {
            background: #6d4c3a;
            color: #f0e6d8;
        }

        [data-theme="dark"] .btn-primary:hover {
            background: #8a6048;
            box-shadow: 0 4px 12px rgba(0,0,0,0.4);
        }
        
        .btn-secondary {
            background: #9FB2AC;
            color: #2c241e;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #8aa09a;
            transform: translateY(-2px);
        }
        
        .btn-theme {
            background: var(--bg-card);
            color: var(--text-secondary);
            border: 2px solid var(--border-color);
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-theme:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-color);
        }
        
        .alert-success {
            background: var(--alert-success-bg);
            color: var(--alert-success-text);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: var(--alert-error-bg);
            color: var(--alert-error-text);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #dc3545;
        }
        
        .info-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            border: 1px solid var(--border-color);
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--text-muted);
        }
        
        .info-value {
            color: var(--text-secondary);
        }

        .info-card h3 {
            color: var(--text-secondary);
        }

        .info-card p {
            color: var(--text-secondary);
        }

        .text-gray-600 {
            color: var(--text-muted) !important;
        }
        
        @media (max-width: 768px) {
            .profile-container {
                padding: 1rem;
                margin: 1rem auto;
            }
            
            .tabs-nav {
                overflow-x: auto;
                padding: 0 0.5rem;
                flex-wrap: nowrap;
            }
            
            .tab-btn {
                padding: 0.75rem 1rem;
                font-size: 0.85rem;
                white-space: nowrap;
            }
            
            .tab-content {
                padding: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .profile-header {
                padding: 1.5rem;
                text-align: center;
            }
            
            .profile-avatar {
                margin: 0 auto 1rem auto;
            }
        }

        .toggle-checkbox {
            width: 40px;
            height: 20px;
            appearance: none;
            background: #ccc;
            border-radius: 20px;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        [data-theme="dark"] .toggle-checkbox {
            background: #5a4a3a;
        }

        .toggle-checkbox:checked {
            background: #9FB2AC;
        }

        .toggle-checkbox::before {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            background: white;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: transform 0.3s ease;
        }

        .toggle-checkbox:checked::before {
            transform: translateX(20px);
        }

        .theme-toggle-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .theme-label {
            font-weight: 600;
            color: var(--text-secondary);
        }

        [data-theme="dark"] select {
            background: var(--input-bg);
            color: var(--text-secondary);
            border-color: var(--border-color);
        }

        [data-theme="dark"] select option {
            background: var(--input-bg);
            color: var(--text-secondary);
        }

        .theme-switch {
            position: relative;
            width: 60px;
            height: 30px;
            background: #ccc;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        [data-theme="dark"] .theme-switch {
            background: #5a4a3a;
        }

        .theme-switch.active {
            background: #9FB2AC;
        }

        .theme-switch .slider {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        [data-theme="dark"] .theme-switch .slider {
            transform: translateX(30px);
            background: #f0e6d8;
        }

        .theme-switch .slider i {
            font-size: 12px;
            color: #555;
        }

        [data-theme="dark"] .theme-switch .slider i {
            color: #2c241e;
        }

        [data-theme="dark"] .btn-secondary {
            background: #6d4c3a;
            color: #f0e6d8;
        }

        [data-theme="dark"] .btn-secondary:hover {
            background: #8a6048;
        }

        #toast {
            position: fixed;
            bottom: 28px;
            right: 28px;
            background: var(--primary);
            color: #fff;
            padding: 14px 22px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.95rem;
            z-index: 9999;
            transform: translateY(80px);
            opacity: 0;
            transition: 0.4s cubic-bezier(.22,1,.36,1);
            max-width: 340px;
        }
        #toast.show { transform: translateY(0); opacity: 1; }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div id="toast"></div>

<div class="profile-container">
    <div class="profile-card">
        <div class="profile-header">
            <div class="profile-avatar">
                <i class="bi bi-person-circle"></i>
            </div>
            <h1 class="text-2xl font-bold mb-2">
                <?php 
                if ($user_role === 'admin') {
                    echo htmlspecialchars($user['nom'] ?? 'Administrateur');
                } else {
                    echo htmlspecialchars(($user['nom'] ?? '') . ' ' . ($user['prenom'] ?? ''));
                }
                ?>
            </h1>
            <span class="profile-role-badge">
                <i class="bi <?php echo $user_role === 'client' ? 'bi-person' : ($user_role === 'producteur' ? 'bi-shop' : 'bi-shield-lock'); ?>"></i>
                <?php echo $user_role === 'client' ? 'Client' : ($user_role === 'producteur' ? 'Producteur' : 'Administrateur'); ?>
            </span>
        </div>
        
        <div class="tabs-nav">
            <button class="tab-btn active" data-tab="profile">
                <i class="bi bi-person"></i> Mon Profil
            </button>
            <button class="tab-btn" data-tab="settings">
                <i class="bi bi-gear"></i> Paramètres
            </button>
            <button class="tab-btn" data-tab="security">
                <i class="bi bi-shield-lock"></i> Sécurité
            </button>
            <button class="tab-btn" data-tab="info">
                <i class="bi bi-info-circle"></i> À propos
            </button>
        </div>
        
        <div class="tab-content active" id="tab-profile">
            <?php if ($success_message): ?>
                <div class="alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert-error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label><i class="bi bi-person"></i> Nom</label>
                        <input type="text" name="nom" value="<?php echo htmlspecialchars($user['nom'] ?? ''); ?>" required>
                    </div>
                    
                    <?php if ($user_role !== 'admin'): ?>
                    <div class="form-group">
                        <label><i class="bi bi-person-badge"></i> Prénom</label>
                        <input type="text" name="prenom" value="<?php echo htmlspecialchars($user['prenom'] ?? ''); ?>" <?php echo $user_role === 'producteur' ? '' : 'required'; ?>>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label><i class="bi bi-envelope"></i> Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                    </div>
                    
                    <?php if ($user_role !== 'admin'): ?>
                    <div class="form-group">
                        <label><i class="bi bi-telephone"></i> Téléphone</label>
                        <input type="tel" name="telephone" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>">
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($user_role !== 'admin'): ?>
                <div class="form-group">
                    <label><i class="bi bi-geo-alt"></i> Adresse</label>
                    <textarea name="adresse" rows="3"><?php echo htmlspecialchars($user['adresse'] ?? ''); ?></textarea>
                </div>
                <?php endif; ?>
                
                <div class="flex justify-end">
                    <button type="submit" class="btn-primary">
                        <i class="bi bi-save"></i> Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
        
        <div class="tab-content" id="tab-settings">
            <h2 class="text-xl font-bold mb-4" style="color: var(--text-secondary);">
                <i class="bi bi-sliders2"></i> Préférences
            </h2>
            
            <div class="info-card">
                <h3 class="font-bold text-lg mb-3">
                    <i class="bi bi-palette"></i> Thème
                </h3>
                <div class="theme-toggle-container">
                    <span class="theme-label">
                        <i class="bi bi-sun"></i> Mode clair
                    </span>
                    <div class="theme-switch <?php echo $theme === 'dark' ? 'active' : ''; ?>" onclick="toggleTheme()" id="themeToggle">
                        <div class="slider">
                            <i class="bi <?php echo $theme === 'dark' ? 'bi-moon-fill' : 'bi-sun-fill'; ?>"></i>
                        </div>
                    </div>
                    <span class="theme-label">
                        <i class="bi bi-moon"></i> Mode sombre beige
                    </span>
                </div>
            </div>
            
            <div class="space-y-4">
                <div class="info-card">
                    <h3 class="font-bold text-lg mb-3">
                        <i class="bi bi-bell"></i> Notifications
                    </h3>
                    <div class="space-y-3">
                        <label class="flex items-center justify-between cursor-pointer">
                            <span>Recevoir les offres promotionnelles</span>
                            <input type="checkbox" id="notif_promo" class="toggle-checkbox" checked>
                        </label>
                        <label class="flex items-center justify-between cursor-pointer">
                            <span>Recevoir les actualités des boutiques</span>
                            <input type="checkbox" id="notif_shop" class="toggle-checkbox" checked>
                        </label>
                        <label class="flex items-center justify-between cursor-pointer">
                            <span>Recevoir la newsletter hebdomadaire</span>
                            <input type="checkbox" id="notif_newsletter" class="toggle-checkbox">
                        </label>
                    </div>
                </div>
                
                <div class="info-card">
                    <h3 class="font-bold text-lg mb-3">
                        <i class="bi bi-globe"></i> Langue
                    </h3>
                    <select class="w-full p-2 border rounded-lg" id="languageSelect">
                        <option value="fr">Français</option>
                        <option value="ar">العربية</option>
                        <option value="en">English</option>
                    </select>
                </div>
                
                <div class="flex justify-end mt-4">
                    <button class="btn-secondary" onclick="savePreferences()">
                        <i class="bi bi-check-lg"></i> Sauvegarder les préférences
                    </button>
                </div>
            </div>
        </div>
        
        <div class="tab-content" id="tab-security">
            <h2 class="text-xl font-bold mb-4" style="color: var(--text-secondary);">
                <i class="bi bi-shield-lock"></i> Sécurité du compte
            </h2>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label><i class="bi bi-key"></i> Mot de passe actuel</label>
                    <input type="password" name="current_password" placeholder="Entrez votre mot de passe actuel" required>
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-lock"></i> Nouveau mot de passe</label>
                    <input type="password" name="new_password" placeholder="Minimum 6 caractères" required>
                </div>
                
                <div class="form-group">
                    <label><i class="bi bi-check-circle"></i> Confirmer le nouveau mot de passe</label>
                    <input type="password" name="confirm_password" placeholder="Confirmez votre nouveau mot de passe" required>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="btn-primary">
                        <i class="bi bi-arrow-repeat"></i> Changer le mot de passe
                    </button>
                </div>
            </form>
            
            <?php if ($user_role === 'admin'): ?>
            <div class="info-card mt-6">
                <h3 class="font-bold text-lg mb-3">
                    <i class="bi bi-shield-shaded"></i> Informations de sécurité
                </h3>
                <div class="info-row">
                    <span class="info-label">Rôle</span>
                    <span class="info-value">Administrateur</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Accès</span>
                    <span class="info-value">Complet (Dashboard Admin)</span>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="tab-content" id="tab-info">
            <h2 class="text-xl font-bold mb-4" style="color: var(--text-secondary);">
                <i class="bi bi-info-circle"></i> Informations sur GreenMarket
            </h2>
            
            <div class="space-y-4">
                <div class="info-card">
                    <h3 class="font-bold mb-2"><i class="bi bi-quote"></i> Notre mission</h3>
                    <p class="text-gray-600">GreenMarket connecte les coopératives marocaines aux consommateurs, favorisant le commerce équitable et la préservation des savoir-faire artisanaux.</p>
                </div>
                
                <div class="info-card">
                    <h3 class="font-bold mb-2"><i class="bi bi-star"></i> Version</h3>
                    <p class="text-gray-600">GreenMarket v1.0 - © 2024 Tous droits réservés</p>
                </div>
                
                <div class="info-card">
                    <h3 class="font-bold mb-2"><i class="bi bi-question-circle"></i> Support</h3>
                    <p class="text-gray-600">📧 support@greenmarket.ma</p>
                    <p class="text-gray-600">📞 +212 5XX XXX XXX</p>
                </div>
                
                <div class="flex gap-3">
                    <button class="btn-secondary" onclick="window.location.href='mailto:support@greenmarket.ma'">
                        <i class="bi bi-envelope"></i> Contacter le support
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const tabId = this.getAttribute('data-tab');
        
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        this.classList.add('active');
        document.getElementById(`tab-${tabId}`).classList.add('active');
        
        localStorage.setItem('activeProfileTab', tabId);
    });
});

const savedTab = localStorage.getItem('activeProfileTab');
if (savedTab) {
    const tabBtn = document.querySelector(`.tab-btn[data-tab="${savedTab}"]`);
    if (tabBtn) tabBtn.click();
}

function showToast(msg, bg) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.style.background = bg || 'var(--primary)';
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3000);
}

function savePreferences() {
    const preferences = {
        notifications_promo: document.getElementById('notif_promo')?.checked || false,
        notifications_shop: document.getElementById('notif_shop')?.checked || false,
        notifications_newsletter: document.getElementById('notif_newsletter')?.checked || false,
        language: document.getElementById('languageSelect')?.value || 'fr'
    };
    
    localStorage.setItem('userPreferences', JSON.stringify(preferences));
    showToast('✅ Préférences sauvegardées !', '#27ae60');
}

function loadPreferences() {
    const saved = localStorage.getItem('userPreferences');
    if (saved) {
        const prefs = JSON.parse(saved);
        if (document.getElementById('notif_promo')) document.getElementById('notif_promo').checked = prefs.notifications_promo || false;
        if (document.getElementById('notif_shop')) document.getElementById('notif_shop').checked = prefs.notifications_shop || false;
        if (document.getElementById('notif_newsletter')) document.getElementById('notif_newsletter').checked = prefs.notifications_newsletter || false;
        if (document.getElementById('languageSelect')) document.getElementById('languageSelect').value = prefs.language || 'fr';
    }
}

function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    html.setAttribute('data-theme', newTheme);
    
    document.cookie = `theme=${newTheme}; path=/; max-age=31536000`;
    
    const sliderIcon = document.querySelector('.theme-switch .slider i');
    if (sliderIcon) {
        sliderIcon.className = `bi ${newTheme === 'dark' ? 'bi-moon-fill' : 'bi-sun-fill'}`;
    }
    
    const themeSwitch = document.getElementById('themeToggle');
    if (themeSwitch) {
        if (newTheme === 'dark') {
            themeSwitch.classList.add('active');
        } else {
            themeSwitch.classList.remove('active');
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = document.cookie.split('; ').find(row => row.startsWith('theme='));
    if (savedTheme) {
        const theme = savedTheme.split('=')[1];
        document.documentElement.setAttribute('data-theme', theme);
        
        const sliderIcon = document.querySelector('.theme-switch .slider i');
        if (sliderIcon) {
            sliderIcon.className = `bi ${theme === 'dark' ? 'bi-moon-fill' : 'bi-sun-fill'}`;
        }
        
        const themeSwitch = document.getElementById('themeToggle');
        if (themeSwitch && theme === 'dark') {
            themeSwitch.classList.add('active');
        } else if (themeSwitch) {
            themeSwitch.classList.remove('active');
        }
    }
    
    loadPreferences();
});

function toggleThemeClick() {
    toggleTheme();
}
</script>

</body>
</html>