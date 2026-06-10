<?php
session_start();
require_once 'connexion.php';

// Vérifier que l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: signin.php');
    exit;
}

$id = $_GET['id'] ?? 0;

if (!$id) {
    $_SESSION['error'] = 'ID du producteur invalide';
    header('Location: dashboard_admin.php');
    exit;
}

try {
    // Vérifier si le producteur existe
    $stmt = $pdo->prepare("SELECT * FROM producteur WHERE id_producteur = ?");
    $stmt->execute([$id]);
    $producteur = $stmt->fetch();
    
    if (!$producteur) {
        $_SESSION['error'] = 'Producteur non trouvé';
        header('Location: dashboard_admin.php');
        exit;
    }
    
    // Suspendre le producteur (mettre est_valide_par_admin = 0)
    $stmt = $pdo->prepare("UPDATE producteur SET est_valide_par_admin = 0 WHERE id_producteur = ?");
    $stmt->execute([$id]);
    
    // Optionnel: Désactiver aussi les produits de ce producteur
    $stmt = $pdo->prepare("
        UPDATE produit p 
        JOIN boutique b ON p.id_boutique = b.id_boutique 
        SET p.est_valide_par_admin = 0, p.statut_publie = 'Suspendu'
        WHERE b.id_producteur = ?
    ");
    $stmt->execute([$id]);
    
    $_SESSION['success'] = '⛔ Producteur suspendu avec succès. Tous ses produits ont été désactivés.';
    
} catch(PDOException $e) {
    $_SESSION['error'] = 'Erreur lors de la suspension: ' . $e->getMessage();
}

header('Location: dashboard_admin.php');
exit;
?>