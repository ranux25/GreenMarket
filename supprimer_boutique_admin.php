<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

include('connexion.php');

$id_boutique = isset($_POST['id_boutique']) ? (int)$_POST['id_boutique'] : 0;

if (!$id_boutique) {
    echo json_encode(['success' => false, 'message' => 'ID de boutique manquant']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT b.id_boutique, b.nom_boutique, b.id_producteur, 
               p.nom_entreprise, p.email, p.id_producteur
        FROM boutique b 
        LEFT JOIN producteur p ON b.id_producteur = p.id_producteur 
        WHERE b.id_boutique = ?
    ");
    $stmt->execute([$id_boutique]);
    $boutique = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$boutique) {
        echo json_encode(['success' => false, 'message' => 'Boutique non trouvée']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    $tables = ['categorie_boutique', 'evaluer_boutique', 'favoris_boutique'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id_boutique = ?");
        $stmt->execute([$id_boutique]);
    }
    
    $stmt = $pdo->prepare("DELETE FROM boutique WHERE id_boutique = ?");
    $stmt->execute([$id_boutique]);
    
    $message = "❌ Votre boutique '{$boutique['nom_boutique']}' a été supprimée par l'administrateur.\n";
    $message .= "📅 Date de suppression : " . date('d/m/Y à H:i') . "\n";
    $message .= "📧 Contactez le support si vous avez des questions.";
    
    $stmt = $pdo->prepare("
        INSERT INTO notification (id_producteur, message, date_notification, est_lu, type_notification) 
        VALUES (?, ?, NOW(), 0, 'system')
    ");
    $stmt->execute([$boutique['id_producteur'], $message]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Boutique "' . $boutique['nom_boutique'] . '" supprimée avec succès',
        'notification_envoyee' => true
    ]);

} catch(PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur supprimer_boutique_admin: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur SQL: ' . $e->getMessage()]);
} catch(Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur supprimer_boutique_admin: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>