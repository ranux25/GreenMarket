<?php
session_start();
include('connexion.php');

// Vérifier que l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: signin.php');
    exit;
}

$id = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? '';

if (!$id || !in_array($action, ['valider', 'refuser'])) {
    $_SESSION['error'] = 'Action invalide';
    header('Location: dashboard_admin.php');
    exit;
}

try {
    if ($action === 'valider') {
        // Valider le producteur
        $stmt = $pdo->prepare("UPDATE producteur SET est_valide_par_admin = 1, id_admin = ? WHERE id_producteur = ?");
        $stmt->execute([$_SESSION['user_id'], $id]);
        
        // Récupérer les infos du producteur pour création automatique de boutique si besoin
        $stmt = $pdo->prepare("SELECT * FROM producteur WHERE id_producteur = ?");
        $stmt->execute([$id]);
        $producteur = $stmt->fetch();
        
        // Vérifier si une boutique existe déjà, sinon en créer une
        $stmt = $pdo->prepare("SELECT * FROM boutique WHERE id_producteur = ?");
        $stmt->execute([$id]);
        $boutique = $stmt->fetch();
        
        if (!$boutique && $producteur) {
            // Créer une boutique automatiquement
            $stmt = $pdo->prepare("INSERT INTO boutique (id_producteur, nom_boutique, description, date_creation) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$id, $producteur['nom_entreprise'], 'Boutique artisanale marocaine']);
        }
        
        $_SESSION['success'] = '✅ Producteur validé avec succès.';
    } 
    elseif ($action === 'refuser') {
        // Supprimer le producteur (et ses boutiques en cascade)
        $stmt = $pdo->prepare("DELETE FROM producteur WHERE id_producteur = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = '❌ Producteur refusé et supprimé.';
    }
    
} catch(PDOException $e) {
    $_SESSION['error'] = 'Erreur lors du traitement: ' . $e->getMessage();
}

header('Location: dashboard_admin.php');
exit;
?>