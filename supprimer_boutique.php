<?php
session_start();
header('Content-Type: application/json');

// Vérifier que c'est un producteur
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'producteur') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

include('connexion.php');

$id_boutique = isset($_POST['id_boutique']) ? (int)$_POST['id_boutique'] : 0;

if (!$id_boutique) {
    echo json_encode(['success' => false, 'message' => 'ID de boutique manquant']);
    exit;
}

$id_producteur = $_SESSION['user_id'];

try {
    // Vérifier que la boutique appartient au producteur
    $stmt = $pdo->prepare("
        SELECT b.id_boutique, b.nom_boutique, b.id_producteur, 
               COUNT(p.id_produit) as nb_produits
        FROM boutique b
        LEFT JOIN produit p ON b.id_boutique = p.id_boutique
        WHERE b.id_boutique = ? AND b.id_producteur = ?
        GROUP BY b.id_boutique
    ");
    $stmt->execute([$id_boutique, $id_producteur]);
    $boutique = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$boutique) {
        echo json_encode(['success' => false, 'message' => 'Boutique non trouvée']);
        exit;
    }
    
    // Démarrer la transaction
    $pdo->beginTransaction();
    
    // Supprimer les dépendances
    $tables = ['categorie_boutique', 'evaluer_boutique', 'favoris_boutique'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id_boutique = ?");
        $stmt->execute([$id_boutique]);
    }
    
    // Supprimer les produits de la boutique
    $stmt = $pdo->prepare("DELETE FROM produit WHERE id_boutique = ?");
    $stmt->execute([$id_boutique]);
    
    // Supprimer la boutique
    $stmt = $pdo->prepare("DELETE FROM boutique WHERE id_boutique = ?");
    $stmt->execute([$id_boutique]);
    
    // --- NOTIFICATION POUR LE PRODUCTEUR ---
    $message = "🗑️ Vous avez supprimé votre boutique '{$boutique['nom_boutique']}' avec {$boutique['nb_produits']} produit(s).\n";
    $message .= "📅 Suppression effectuée le " . date('d/m/Y à H:i');
    
    $stmt = $pdo->prepare("
        INSERT INTO notification (id_producteur, message, date_notification, est_lu, type_notification) 
        VALUES (?, ?, NOW(), 0, 'system')
    ");
    $stmt->execute([$id_producteur, $message]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => "La boutique '{$boutique['nom_boutique']}' a été supprimée avec succès"
    ]);

} catch(PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur supprimer_boutique: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur SQL: ' . $e->getMessage()]);
} catch(Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur supprimer_boutique: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>