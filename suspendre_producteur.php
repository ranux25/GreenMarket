<?php
session_start();

// SOLO ADMIN
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard_admin.php?msgerr=" . urlencode('Non autorisé'));
    exit;
}

include('connexion.php');

$id_producteur = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id_producteur) {
    header("Location: dashboard_admin.php?msgerr=" . urlencode('ID producteur manquant'));
    exit;
}

try {
    // Obtener estado actual
    $stmt = $pdo->prepare("SELECT id_producteur, nom_entreprise, est_valide_par_admin FROM producteur WHERE id_producteur = ?");
    $stmt->execute([$id_producteur]);
    $producteur = $stmt->fetch();
    
    if (!$producteur) {
        throw new Exception('Producteur non trouvé');
    }
    
    $nouveau_statut = $producteur['est_valide_par_admin'] == 1 ? 0 : 1;
    
    $stmt = $pdo->prepare("UPDATE producteur SET est_valide_par_admin = ? WHERE id_producteur = ?");
    $stmt->execute([$nouveau_statut, $id_producteur]);
    
    if ($nouveau_statut == 1) {
        $message = '✅ Producteur "' . $producteur['nom_entreprise'] . '" réactivé avec succès';
        $notification_message = '✅ Votre compte producteur "' . $producteur['nom_entreprise'] . '" a été réactivé par l\'administrateur.';
        $type = 'reactivation_producteur';
    } else {
        $message = '⛔ Producteur "' . $producteur['nom_entreprise'] . '" suspendu avec succès';
        $notification_message = '⛔ Votre compte producteur "' . $producteur['nom_entreprise'] . '" a été suspendu par l\'administrateur. Veuillez contacter le support.';
        $type = 'suspension_producteur';
    }
    
    // Notificar al productor
    $stmt = $pdo->prepare("
        INSERT INTO notification (id_producteur, message, type_notification, date_notification, est_lu) 
        VALUES (?, ?, ?, NOW(), 0)
    ");
    $stmt->execute([$id_producteur, $notification_message, $type]);

    header("Location: dashboard_admin.php?msgs=" . urlencode($message) . "&tab=producteurs");
    exit;

} catch(Exception $e) {
    header("Location: dashboard_admin.php?msgerr=" . urlencode($e->getMessage()));
    exit;
}
?>