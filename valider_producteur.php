<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

include('connexion.php');

$id_producteur = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (!$id_producteur || !in_array($action, ['valider', 'refuser'])) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id_producteur, nom_entreprise, email FROM producteur WHERE id_producteur = ?");
    $stmt->execute([$id_producteur]);
    $producteur = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$producteur) {
        echo json_encode(['success' => false, 'message' => 'Producteur non trouvé']);
        exit;
    }
    
    if ($action === 'valider') {
        $stmt = $pdo->prepare("UPDATE producteur SET est_valide_par_admin = 1, statut = 'valide' WHERE id_producteur = ?");
        $stmt->execute([$id_producteur]);
        $message = '✅ Producteur "' . $producteur['nom_entreprise'] . '" validé avec succès';
        
        $notification_message = '✅ Votre compte producteur "' . $producteur['nom_entreprise'] . '" a été validé par l\'administrateur. Vous pouvez maintenant créer des boutiques et gérer vos produits !';
        $type = 'validation_producteur';
        
    } else {
        $stmt = $pdo->prepare("UPDATE producteur SET est_valide_par_admin = 0, statut = 'refuse' WHERE id_producteur = ?");
        $stmt->execute([$id_producteur]);
        $message = '❌ Producteur "' . $producteur['nom_entreprise'] . '" refusé';
        
        $notification_message = '❌ Votre compte producteur "' . $producteur['nom_entreprise'] . '" a été refusé par l\'administrateur. Veuillez contacter le support pour plus d\'informations.';
        $type = 'refus_producteur';
    }

    $stmt = $pdo->prepare("
        INSERT INTO notification (id_producteur, message, type_notification, date_notification, est_lu) 
        VALUES (?, ?, ?, NOW(), 0)
    ");
    $stmt->execute([
        $id_producteur,
        $notification_message,
        $type
    ]);

    echo json_encode(['success' => true, 'message' => $message]);

} catch(PDOException $e) {
    error_log("Erreur valider_producteur: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur SQL: ' . $e->getMessage()]);
} catch(Exception $e) {
    error_log("Erreur valider_producteur: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
