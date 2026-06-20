<?php
session_start();
include("connexion.php");

header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

$id_client = $_SESSION['user_id'];
$id_commande = $_POST['id_commande'] ?? 0;

if (!$id_commande) {
    echo json_encode(['success' => false, 'message' => 'ID commande manquant']);
    exit();
}

try {
    // Vérifier que la commande appartient au client et est en attente
    $req = $pdo->prepare("
        SELECT statut_commande FROM commande 
        WHERE id_commande = ? AND id_client = ?
    ");
    $req->execute([$id_commande, $id_client]);
    $commande = $req->fetch();

    if (!$commande) {
        echo json_encode(['success' => false, 'message' => 'Commande non trouvée']);
        exit();
    }

    if ($commande['statut_commande'] !== 'En attente') {
        echo json_encode(['success' => false, 'message' => 'Cette commande ne peut pas être annulée']);
        exit();
    }

    // Annuler la commande
    $update = $pdo->prepare("
        UPDATE commande SET statut_commande = 'Annulée' 
        WHERE id_commande = ? AND id_client = ?
    ");
    $update->execute([$id_commande, $id_client]);

    // Restaurer les stocks
    $reqStock = $pdo->prepare("
        SELECT id_produit, quantite FROM contenir WHERE id_commande = ?
    ");
    $reqStock->execute([$id_commande]);
    $produits = $reqStock->fetchAll();

    foreach ($produits as $prod) {
        $updateStock = $pdo->prepare("
            UPDATE produit SET stock_quantite = stock_quantite + ? 
            WHERE id_produit = ?
        ");
        $updateStock->execute([$prod['quantite'], $prod['id_produit']]);
    }

    echo json_encode(['success' => true]);

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>