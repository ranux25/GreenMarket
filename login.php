<?php
session_start();
require_once 'connexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: signin.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $_SESSION['error'] = 'Email et mot de passe requis';
    $_SESSION['active_form'] = 'login';
    header('Location: signin.php');
    exit;
}

try {
    // Chercher dans client
    $stmt = $pdo->prepare("SELECT id_client as id, nom_client as nom, email, mot_de_passe, 'client' as role, 1 as valide FROM client WHERE email = ? AND est_actif = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    // Si pas trouvé, chercher dans producteur
    if (!$user) {
        $stmt = $pdo->prepare("SELECT id_producteur as id, nom_entreprise as nom, email, mot_de_passe, 'producteur' as role, est_valide_par_admin as valide FROM producteur WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    }
    
    // Si pas trouvé, chercher dans admin
    if (!$user) {
        $stmt = $pdo->prepare("SELECT id_admin as id, nom_admin as nom, email, mot_de_passe, 'admin' as role, 1 as valide FROM administrateur WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    }
    
    if (!$user) {
        $_SESSION['error'] = 'Email ou mot de passe incorrect';
        $_SESSION['active_form'] = 'login';
        header('Location: signin.php');
        exit;
    }
    
    // Vérifier mot de passe
    if ($password !== $user['mot_de_passe']) {
        $_SESSION['error'] = 'Email ou mot de passe incorrect';
        $_SESSION['active_form'] = 'login';
        header('Location: signin.php');
        exit;
    }
    
    // Vérifier validation pour producteur
    if ($user['role'] === 'producteur' && $user['valide'] != 1) {
        $_SESSION['warning'] = '⚠️ Votre compte producteur est en attente de validation par un administrateur. Vous serez notifié par email une fois validé.';
        $_SESSION['active_form'] = 'login';
        $_SESSION['form_data'] = ['email' => $email];
        header('Location: signin.php');
        exit;
    }
    
    // Stocker en session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nom'] = $user['nom'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    
    // Rediriger selon le rôle
    $redirects = [
        'admin' => 'dashboard_admin.php',
        'producteur' => 'dashboard_producteur.php',
        'client' => 'dashboard_client.php'
    ];
    
    header('Location: ' . $redirects[$user['role']]);
    exit;
    
} catch(PDOException $e) {
    $_SESSION['error'] = 'Erreur technique. Veuillez réessayer.';
    $_SESSION['active_form'] = 'login';
    header('Location: signin.php');
    exit;
}
?>