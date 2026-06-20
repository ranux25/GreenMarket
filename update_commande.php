<?php
session_start();
include('connexion.php');

// Verificar que el usuario sea producteur o admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['producteur', 'admin'])) {
    header('Location: signin.php');
    exit;
}

header('Content-Type: application/json');

$id_commande = intval($_POST['id_commande'] ?? 0);
$statut = $_POST['statut'] ?? '';

$statuts_valides = ['Confirmée', 'Expédiée', 'Livrée', 'Annulée'];

if (!$id_commande || !in_array($statut, $statuts_valides)) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

try {
    // Vérifier que la commande appartient au producteur
    if ($_SESSION['user_role'] === 'producteur') {
        $stmt = $pdo->prepare("
            SELECT c.id_commande, c.statut_commande, c.id_client
            FROM commande c
            JOIN contenir co ON c.id_commande = co.id_commande
            JOIN produit p ON co.id_produit = p.id_produit
            JOIN boutique b ON p.id_boutique = b.id_boutique
            WHERE c.id_commande = ? AND b.id_producteur = ?
            GROUP BY c.id_commande
        ");
        $stmt->execute([$id_commande, $_SESSION['user_id']]);
        $commande = $stmt->fetch();
        
        if (!$commande) {
            echo json_encode(['success' => false, 'message' => 'Commande non trouvée ou non autorisée']);
            exit;
        }
    } else {
        // Admin puede modificar cualquier comanda
        $stmt = $pdo->prepare("SELECT id_client FROM commande WHERE id_commande = ?");
        $stmt->execute([$id_commande]);
        $commande = $stmt->fetch();
        
        if (!$commande) {
            echo json_encode(['success' => false, 'message' => 'Commande non trouvée']);
            exit;
        }
    }
    
    // Mettre à jour le statut
    $stmt = $pdo->prepare("UPDATE commande SET statut_commande = ? WHERE id_commande = ?");
    $stmt->execute([$statut, $id_commande]);
    
    // Envoyer une notification au client
    $message_client = "📦 Votre commande #" . str_pad($id_commande, 6, '0', STR_PAD_LEFT) . " est maintenant : {$statut}.\n";
    if ($statut === 'Livrée') {
        $message_client .= "Merci pour votre achat sur GreenMarket 🌿";
    }
    
    $stmtNotif = $pdo->prepare("
        INSERT INTO notification (id_client, type_notification, message, date_notification, est_lu) 
        VALUES (?, 'order', ?, NOW(), 0)
    ");
    $stmtNotif->execute([$commande['id_client'], $message_client]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Statut mis à jour avec succès',
        'statut' => $statut
    ]);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>