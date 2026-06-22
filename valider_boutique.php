<?php
session_start();

// SOLO ADMIN
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard_admin.php?msgerr=" . urlencode('Non autorisé'));
    exit;
}

include('connexion.php');

$id_boutique = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (!$id_boutique || !in_array($action, ['valider', 'refuser'])) {
    header("Location: dashboard_admin.php?msgerr=" . urlencode('Données invalides'));
    exit;
}

try {
    // Obtener información de la boutique
    $stmt = $pdo->prepare("
        SELECT b.id_boutique, b.nom_boutique, b.id_producteur,
               pr.nom_entreprise, pr.email
        FROM boutique b
        JOIN producteur pr ON b.id_producteur = pr.id_producteur
        WHERE b.id_boutique = ?
    ");
    $stmt->execute([$id_boutique]);
    $boutique = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$boutique) {
        throw new Exception('Boutique non trouvée');
    }
    
    if ($action === 'valider') {
        $stmt = $pdo->prepare("UPDATE boutique SET est_valide_par_admin = 1 WHERE id_boutique = ?");
        $stmt->execute([$id_boutique]);
        $message = '✅ Boutique "' . $boutique['nom_boutique'] . '" validée avec succès';
        
        $notification_message = '✅ Votre boutique "' . $boutique['nom_boutique'] . '" a été validée par l\'administrateur. Vous pouvez maintenant gérer vos produits !';
        $type = 'validation_boutique';
        
    } else {
        $stmt = $pdo->prepare("UPDATE boutique SET est_valide_par_admin = 0 WHERE id_boutique = ?");
        $stmt->execute([$id_boutique]);
        $message = '❌ Boutique "' . $boutique['nom_boutique'] . '" refusée';
        
        $notification_message = '❌ Votre boutique "' . $boutique['nom_boutique'] . '" a été refusée par l\'administrateur. Veuillez contacter le support pour plus d\'informations.';
        $type = 'refus_boutique';
    }

    // Insertar notificación
    $stmt = $pdo->prepare("
        INSERT INTO notification (id_producteur, message, type_notification, date_notification, est_lu) 
        VALUES (?, ?, ?, NOW(), 0)
    ");
    $stmt->execute([
        $boutique['id_producteur'],
        $notification_message,
        $type
    ]);

    header("Location: dashboard_admin.php?msgs=" . urlencode($message) . "&tab=boutiques");
    exit;

} catch(PDOException $e) {
    error_log("Erreur valider_boutique: " . $e->getMessage());
    header("Location: dashboard_admin.php?msgerr=" . urlencode('Erreur SQL: ' . $e->getMessage()));
    exit;
} catch(Exception $e) {
    error_log("Erreur valider_boutique: " . $e->getMessage());
    header("Location: dashboard_admin.php?msgerr=" . urlencode($e->getMessage()));
    exit;
}
?>