<?php
session_start();

// SOLO ADMIN
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard_admin.php?msgerr=" . urlencode('Non autorisé'));
    exit;
}

include('connexion.php');

$id_producteur = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (!$id_producteur || !in_array($action, ['valider', 'refuser'])) {
    header("Location: dashboard_admin.php?msgerr=" . urlencode('Données invalides'));
    exit;
}

try {
    // Obtener información del producteur
    $stmt = $pdo->prepare("SELECT id_producteur, nom_entreprise, email FROM producteur WHERE id_producteur = ?");
    $stmt->execute([$id_producteur]);
    $producteur = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producteur) {
        throw new Exception('Producteur non trouvé');
    }
    
    if ($action === 'valider') {
        $stmt = $pdo->prepare("UPDATE producteur SET est_valide_par_admin = 1 WHERE id_producteur = ?");
        $stmt->execute([$id_producteur]);
        $message = '✅ Producteur "' . $producteur['nom_entreprise'] . '" validé avec succès';
        
        $notification_message = '✅ Votre compte producteur "' . $producteur['nom_entreprise'] . '" a été validé par l\'administrateur. Vous pouvez maintenant créer des boutiques et gérer vos produits !';
        $type = 'validation_producteur';
        
    } else {
        $stmt = $pdo->prepare("UPDATE producteur SET est_valide_par_admin = 0 WHERE id_producteur = ?");
        $stmt->execute([$id_producteur]);
        $message = '❌ Producteur "' . $producteur['nom_entreprise'] . '" refusé';
        
        $notification_message = '❌ Votre compte producteur "' . $producteur['nom_entreprise'] . '" a été refusé par l\'administrateur. Veuillez contacter le support pour plus d\'informations.';
        $type = 'refus_producteur';
    }

    // Insertar notificación
    $stmt = $pdo->prepare("
        INSERT INTO notification (id_producteur, message, type_notification, date_notification, est_lu) 
        VALUES (?, ?, ?, NOW(), 0)
    ");
    $stmt->execute([
        $id_producteur,
        $notification_message,
        $type
    ]);

    header("Location: dashboard_admin.php?msgs=" . urlencode($message) . "&tab=producteurs");
    exit;

} catch(PDOException $e) {
    error_log("Erreur valider_producteur: " . $e->getMessage());
    header("Location: dashboard_admin.php?msgerr=" . urlencode('Erreur SQL: ' . $e->getMessage()));
    exit;
} catch(Exception $e) {
    error_log("Erreur valider_producteur: " . $e->getMessage());
    header("Location: dashboard_admin.php?msgerr=" . urlencode($e->getMessage()));
    exit;
}
?>