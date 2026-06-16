<?php
session_start();
require_once 'connexion.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$success_message = '';
$error_message = '';

// Inicializar $user como array vacío
$user = [
    'nom' => '',
    'prenom' => '',
    'email' => '',
    'telephone' => '',
    'adresse' => '',
    'role' => $user_role
];

// Obtener datos del usuario según su rol
try {
    if ($user_role === 'client') {
        $stmt = $pdo->prepare("SELECT * FROM client WHERE id_client = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data) {
            $user['nom'] = $user_data['nom_client'] ?? '';
            $user['prenom'] = $user_data['prenom_client'] ?? '';
            $user['email'] = $user_data['email_client'] ?? '';
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
            $user['email'] = $user_data['email_producteur'] ?? '';
            $user['telephone'] = $user_data['telephone_producteur'] ?? '';
            $user['adresse'] = $user_data['adresse_producteur'] ?? '';
            $user['role'] = 'producteur';
        } else {
            $error_message = "Producteur non trouvé.";
        }
        
    } elseif ($user_role === 'admin') {
        // Usar el nombre correcto de la tabla: administrateur
        $stmt = $pdo->prepare("SELECT * FROM administrateur WHERE id_admin = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data) {
            $user['nom'] = $user_data['nom_admin'] ?? '';
            $user['prenom'] = ''; // La tabla administrateur no tiene campo prenom
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

// Procesar actualización del perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
        
        try {
            if ($user_role === 'client') {
                $stmt = $pdo->prepare("UPDATE client SET nom_client = ?, prenom_client = ?, email_client = ?, telephone_client = ?, adresse_client = ? WHERE id_client = ?");
                $stmt->execute([$nom, $prenom, $email, $telephone, $adresse, $user_id]);
                
                $user['nom'] = $nom;
                $user['prenom'] = $prenom;
                $user['email'] = $email;
                $user['telephone'] = $telephone;
                $user['adresse'] = $adresse;
                
            } elseif ($user_role === 'producteur') {
                $stmt = $pdo->prepare("UPDATE producteur SET nom_entreprise = ?, nom_contact = ?, email_producteur = ?, telephone_producteur = ?, adresse_producteur = ? WHERE id_producteur = ?");
                $stmt->execute([$nom, $prenom, $email, $telephone, $adresse, $user_id]);
                
                $user['nom'] = $nom;
                $user['prenom'] = $prenom;
                $user['email'] = $email;
                $user['telephone'] = $telephone;
                $user['adresse'] = $adresse;
                
            } elseif ($user_role === 'admin') {
                // Actualizar administrateur
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
    
    // Cambiar contraseña
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
                    $stmt = $pdo->prepare("SELECT mot_de_passe_client FROM client WHERE id_client = ?");
                    $stmt->execute([$user_id]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stored_password = $result['mot_de_passe_client'] ?? null;
                } elseif ($user_role === 'producteur') {
                    $stmt = $pdo->prepare("SELECT mot_de_passe_producteur FROM producteur WHERE id_producteur = ?");
                    $stmt->execute([$user_id]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stored_password = $result['mot_de_passe_producteur'] ?? null;
                } elseif ($user_role === 'admin') {
                    $stmt = $pdo->prepare("SELECT mot_de_passe FROM administrateur WHERE id_admin = ?");
                    $stmt->execute([$user_id]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $stored_password = $result['mot_de_passe'] ?? null;
                }
                
                if ($stored_password && password_verify($current_password, $stored_password)) {
                    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    if ($user_role === 'client') {
                        $stmt = $pdo->prepare("UPDATE client SET mot_de_passe_client = ? WHERE id_client = ?");
                    } elseif ($user_role === 'producteur') {
                        $stmt = $pdo->prepare("UPDATE producteur SET mot_de_passe_producteur = ? WHERE id_producteur = ?");
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - GreenMarket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #f5f0e8 0%, #fff9eb 100%);
            font-family: 'Lato', sans-serif;
            min-height: 100vh;
        }
        
        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }
        
        .profile-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(93, 13, 24, 0.1);
            overflow: hidden;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #5D0D18 0%, #7a1322 100%);
            padding: 2rem;
            color: white;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            margin-bottom: 1rem;
            border: 3px solid #9FB2AC;
        }
        
        .profile-role-badge {
            display: inline-block;
            background: #9FB2AC;
            color: #5D0D18;
            padding: 0.25rem 1rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .tabs-nav {
            display: flex;
            border-bottom: 2px solid #eee;
            background: white;
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
            color: #6B6B6B;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .tab-btn:hover {
            color: #5D0D18;
        }
        
        .tab-btn.active {
            color: #5D0D18;
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
        
        .tab-content {
            display: none;
            padding: 2rem 1.5rem;
            animation: fadeIn 0.4s ease;
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
            color: #2C2C2C;
            font-size: 0.9rem;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
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
        
        .btn-secondary {
            background: #9FB2AC;
            color: #5D0D18;
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
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #dc3545;
        }
        
        .info-card {
            background: #f9f9f9;
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #6B6B6B;
        }
        
        .info-value {
            color: #2C2C2C;
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
    </style>
</head>
<body>

<?php include 'header.php'; ?>

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
        
        <!-- Pestaña: Mon Profil -->
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
        
        <!-- Pestaña: Paramètres -->
        <div class="tab-content" id="tab-settings">
            <h2 class="text-xl font-bold mb-4" style="color:#5D0D18;">
                <i class="bi bi-sliders2"></i> Préférences
            </h2>
            
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
        
        <!-- Pestaña: Sécurité -->
        <div class="tab-content" id="tab-security">
            <h2 class="text-xl font-bold mb-4" style="color:#5D0D18;">
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
        
        <!-- Pestaña: À propos -->
        <div class="tab-content" id="tab-info">
            <h2 class="text-xl font-bold mb-4" style="color:#5D0D18;">
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
// Gestion des onglets
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

function savePreferences() {
    const preferences = {
        notifications_promo: document.getElementById('notif_promo')?.checked || false,
        notifications_shop: document.getElementById('notif_shop')?.checked || false,
        notifications_newsletter: document.getElementById('notif_newsletter')?.checked || false,
        language: document.getElementById('languageSelect')?.value || 'fr'
    };
    
    localStorage.setItem('userPreferences', JSON.stringify(preferences));
    alert('Préférences sauvegardées !');
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

document.addEventListener('DOMContentLoaded', () => {
    loadPreferences();
});
</script>

</body>
</html>