<?php
session_start();
require_once 'connexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$nom = trim($_POST['nom'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm'] ?? '';
$role = $_POST['role'] ?? 'client';
$nom_entreprise = trim($_POST['nom_entreprise'] ?? '');

// Stocker les données pour réaffichage en cas d'erreur
$_SESSION['form_data'] = [
    'nom' => $nom,
    'email' => $email,
    'role' => $role,
    'nom_entreprise' => $nom_entreprise
];
$_SESSION['active_form'] = 'signup';

// Validations
if (empty($nom) || empty($email) || empty($password)) {
    $_SESSION['error'] = 'Tous les champs sont requis';
    header('Location: signup.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Email invalide';
    header('Location: signup.php');
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['error'] = 'Le mot de passe doit contenir au moins 6 caractères';
    header('Location: signup.php');
    exit;
}

if ($password !== $confirm) {
    $_SESSION['error'] = 'Les mots de passe ne correspondent pas';
    header('Location: signup.php');
    exit;
}

if ($role === 'producteur' && empty($nom_entreprise)) {
    $_SESSION['error'] = 'Le nom de l\'entreprise est requis pour les producteurs';
    header('Location: signup.php');
    exit;
}

try {
    // Vérifier si email existe déjà
    $stmt = $pdo->prepare("SELECT email FROM client WHERE email = ? UNION SELECT email FROM producteur WHERE email = ? UNION SELECT email FROM administrateur WHERE email = ?");
    $stmt->execute([$email, $email, $email]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'Cet email est déjà utilisé';
        header('Location: index.php');
        exit;
    }
    
    if ($role === 'client') {
        // Insertion client
        $stmt = $pdo->prepare("INSERT INTO client (nom_client, email, mot_de_passe, date_inscription, est_actif) VALUES (?, ?, ?, NOW(), 1)");
        $stmt->execute([$nom, $email, $password]);
        
        $user_id = $pdo->lastInsertId();
        
        // Connexion automatique
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_nom'] = $nom;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = 'client';
        
        unset($_SESSION['form_data'], $_SESSION['error']);
        header('Location: dashboard_client.php');
        exit;
        
    } elseif ($role === 'producteur') {
        // Insertion producteur (en attente de validation)
        $stmt = $pdo->prepare("INSERT INTO producteur (nom_entreprise, email, mot_de_passe, est_valide_par_admin, date_inscription) VALUES (?, ?, ?, 0, NOW())");
        $stmt->execute([$nom_entreprise, $email, $password]);
        
        $user_id = $pdo->lastInsertId();
        
        // Créer automatiquement une boutique
        $stmt = $pdo->prepare("INSERT INTO boutique (id_producteur, nom_boutique, description, date_creation) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$user_id, $nom_entreprise, 'Boutique artisanale marocaine']);
        
        $_SESSION['warning'] = 'Compte créé avec succès! Un administrateur va valider votre compte. Vous pourrez vous connecter après validation.';
        unset($_SESSION['form_data']);
        header('Location: signup.php');
        exit;
    }
    
} catch(PDOException $e) {
    $_SESSION['error'] = 'Erreur lors de la création du compte. Veuillez réessayer.';
    header('Location: signup.php');
    exit;
}
?>