<?php
session_start();
require_once 'connexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$_SESSION['form_data'] = ['email' => $email];
$_SESSION['active_form'] = 'forgot';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Email valide requis';
    header('Location: index.php');
    exit;
}

try {
    // Vérifier si email existe
    $tables = ['client', 'producteur', 'administrateur'];
    $found = false;
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SELECT email FROM $table WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $_SESSION['error'] = 'Aucun compte associé à cet email';
        header('Location: index.php');
        exit;
    }
    
    // Ici vous pouvez envoyer un vrai email
    // Pour l'instant, on affiche juste un message de succès
    $_SESSION['success'] = 'Un lien de réinitialisation a été envoyé à votre adresse email';
    unset($_SESSION['form_data']);
    header('Location: index.php');
    exit;
    
} catch(PDOException $e) {
    $_SESSION['error'] = 'Erreur technique. Veuillez réessayer.';
    header('Location: index.php');
    exit;
}
?>