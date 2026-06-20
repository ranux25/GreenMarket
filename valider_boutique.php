<?php
session_start();
include('connexion.php');

// Verificar que el usuario sea admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: signin.php");
    exit();
}

$id_boutique = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if (!$id_boutique || !in_array($action, ['valider', 'refuser'])) {
    header("Location: dashboard_admin.php?msgerr=Action invalide");
    exit();
}

try {
    if ($action === 'valider') {
        // Validar la boutique
        $stmt = $pdo->prepare("UPDATE boutique SET est_valide_par_admin = 1 WHERE id_boutique = ?");
        $stmt->execute([$id_boutique]);
        
        // Récupérer les infos pour la notification
        $stmt = $pdo->prepare("SELECT b.*, p.id_producteur, p.nom_entreprise, p.email 
                               FROM boutique b 
                               JOIN producteur p ON b.id_producteur = p.id_producteur 
                               WHERE b.id_boutique = ?");
        $stmt->execute([$id_boutique]);
        $boutique = $stmt->fetch();
        
        // ================================================================
        // 🔥 AJOUTER UNE NOTIFICATION POUR LE PRODUCTEUR
        // ================================================================
        $stmt = $pdo->prepare("INSERT INTO notification (id_producteur, type_notification, message, date_notification, est_lu) 
                               VALUES (?, 'system', ?, NOW(), 0)");
        $message = "✅ Votre boutique '" . $boutique['nom_boutique'] . "' a été validée par l'administrateur. Vous pouvez maintenant gérer vos produits et recevoir des commandes !";
        $stmt->execute([$boutique['id_producteur'], $message]);
        
        // ================================================================
        // 🔥 NOTIFICATION POUR L'ADMIN (optionnel)
        // ================================================================
        // $stmt = $pdo->prepare("INSERT INTO notification (id_admin, type_notification, message, date_notification, est_lu) 
        //                        VALUES (?, 'system', ?, NOW(), 0)");
        // $message_admin = "Boutique '" . $boutique['nom_boutique'] . "' validée par " . $_SESSION['user_nom'];
        // $stmt->execute([$_SESSION['user_id'], $message_admin]);
        
        header("Location: dashboard_admin.php?msgs=Boutique validée avec succès !&tab=boutiques");
    } else {
        // Refuser la boutique
        $stmt = $pdo->prepare("SELECT image, id_producteur, nom_boutique FROM boutique WHERE id_boutique = ?");
        $stmt->execute([$id_boutique]);
        $boutique = $stmt->fetch();
        
        if ($boutique && !empty($boutique['image']) && file_exists($boutique['image'])) {
            unlink($boutique['image']);
        }
        
        // Supprimer la boutique
        $stmt = $pdo->prepare("DELETE FROM boutique WHERE id_boutique = ?");
        $stmt->execute([$id_boutique]);
        
        // ================================================================
        // 🔥 NOTIFICATION DE REFUS POUR LE PRODUCTEUR
        // ================================================================
        $stmt = $pdo->prepare("INSERT INTO notification (id_producteur, type_notification, message, date_notification, est_lu) 
                               VALUES (?, 'system', ?, NOW(), 0)");
        $message = "❌ Votre boutique '" . $boutique['nom_boutique'] . "' a été refusée par l'administrateur. Veuillez contacter le support pour plus d'informations.";
        $stmt->execute([$boutique['id_producteur'], $message]);
        
        header("Location: dashboard_admin.php?msgs=Boutique refusée et supprimée.&tab=boutiques");
    }
} catch(PDOException $e) {
    header("Location: dashboard_admin.php?msgerr=Erreur: " . $e->getMessage());
}
exit();
?>