<?php
session_start();
require_once 'connexion.php';

// Activar reporte de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: signin.php');
    exit;
}

// Recibir datos del formulario
$nom = trim($_POST['nom'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm'] ?? '';
$role = $_POST['role'] ?? 'client';
$nom_entreprise = trim($_POST['nom_entreprise'] ?? '');

// Guardar datos para mostrar en caso de error
$_SESSION['form_data'] = [
    'nom' => $nom,
    'email' => $email,
    'role' => $role,
    'nom_entreprise' => $nom_entreprise
];
$_SESSION['active_form'] = 'signup';

// Validaciones
if (empty($nom) && $role === 'client') {
    $_SESSION['error'] = 'Le nom est requis';
    header('Location: signin.php');
    exit;
}

if (empty($email)) {
    $_SESSION['error'] = 'L\'email est requis';
    header('Location: signin.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Email invalide';
    header('Location: signin.php');
    exit;
}

if (empty($password)) {
    $_SESSION['error'] = 'Le mot de passe est requis';
    header('Location: signin.php');
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['error'] = 'Le mot de passe doit contenir au moins 6 caractères';
    header('Location: signin.php');
    exit;
}

if ($password !== $confirm) {
    $_SESSION['error'] = 'Les mots de passe ne correspondent pas';
    header('Location: signin.php');
    exit;
}

if ($role === 'producteur' && empty($nom_entreprise)) {
    $_SESSION['error'] = 'Le nom de l\'entreprise est requis pour les producteurs';
    header('Location: signin.php');
    exit;
}

try {
    // Vérifier si email existe déjà
    $stmt = $pdo->prepare("SELECT email FROM client WHERE email = ? 
                           UNION SELECT email FROM producteur WHERE email = ? 
                           UNION SELECT email FROM administrateur WHERE email = ?");
    $stmt->execute([$email, $email, $email]);
    
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'Cet email est déjà utilisé';
        header('Location: signin.php');
        exit;
    }
    
    if ($role === 'client') {
        // Insertion client
        $sql = "INSERT INTO client (nom_client, email, mot_de_passe, date_inscription, est_actif) 
                VALUES (:nom, :email, :password, NOW(), 1)";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':nom' => $nom,
            ':email' => $email,
            ':password' => $password
        ]);
        
        if ($result) {
            $user_id = $pdo->lastInsertId();
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_nom'] = $nom;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'client';
            
            unset($_SESSION['form_data'], $_SESSION['error']);
            header('Location: dashboard_client.php');
            exit;
        } else {
            $_SESSION['error'] = 'Erreur lors de l\'inscription du client';
            header('Location: signin.php');
            exit;
        }
        
    } elseif ($role === 'producteur') {
        // 🔥 INSERCIÓN PRODUCTEUR - id_admin se deja NULL (se asigna cuando un admin valida)
        $sql = "INSERT INTO producteur (id_admin, nom_entreprise, email, mot_de_passe, est_valide_par_admin, date_inscription) 
                VALUES (NULL, :nom_entreprise, :email, :password, 0, NOW())";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':nom_entreprise' => $nom_entreprise,
            ':email' => $email,
            ':password' => $password
        ]);
        
        if ($result) {
            $user_id = $pdo->lastInsertId();
            
            // Verificar que se insertó correctamente
            $check = $pdo->prepare("SELECT * FROM producteur WHERE id_producteur = ?");
            $check->execute([$user_id]);
            $producteur = $check->fetch();
            
            if ($producteur) {
                // 🔥 CREAR BOUTIQUE AUTOMÁTICAMENTE
                $sql2 = "INSERT INTO boutique (id_producteur, nom_boutique, description, date_creation) 
                         VALUES (:id_producteur, :nom_boutique, :description, NOW())";
                
                $stmt2 = $pdo->prepare($sql2);
                $stmt2->execute([
                    ':id_producteur' => $user_id,
                    ':nom_boutique' => $nom_entreprise,
                    ':description' => 'Boutique artisanale marocaine'
                ]);
                
                // 🔥 INICIAR SESIÓN
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_nom'] = $nom_entreprise;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'producteur';
                $_SESSION['est_valide'] = 0;
                
                unset($_SESSION['form_data'], $_SESSION['error']);
                
                $_SESSION['warning'] = '✅ Compte producteur créé avec succès ! Un administrateur va valider votre compte. Vous pourrez gérer vos produits après validation.';
                header('Location: dashboard-producteur.php');
                exit;
            } else {
                $_SESSION['error'] = 'Erreur: Producteur non trouvé après insertion';
                header('Location: signin.php');
                exit;
            }
        } else {
            $_SESSION['error'] = 'Erreur lors de l\'insertion du producteur';
            header('Location: signin.php');
            exit;
        }
    }
    
} catch(PDOException $e) {
    $_SESSION['error'] = 'Erreur base de données: ' . $e->getMessage();
    header('Location: signin.php');
    exit;
}
?>