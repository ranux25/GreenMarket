<?php
session_start();

// Vérifier que l'utilisateur est connecté ET est admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: signin.php');
    exit;
}

// Connexion à la base de données
require_once 'connexion.php';

// Le reste de votre code...


$id = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? '';

if (!$id || !in_array($action, ['valider', 'refuser'])) {
    header('Location: dashboard_admin.php');
    exit;
}

try {
    if ($action === 'valider') {
        $stmt = $pdo->prepare("UPDATE produit SET est_valide_par_admin = 1, statut_publie = 'Publié' WHERE id_produit = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = '✅ Produit validé avec succès.';
    } elseif ($action === 'refuser') {
        $stmt = $pdo->prepare("DELETE FROM produit WHERE id_produit = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = '❌ Produit refusé et supprimé.';
    }
} catch(PDOException $e) {
    $_SESSION['error'] = 'Erreur lors du traitement.';
}

header('Location: dashboard_admin.php');
exit;
?>