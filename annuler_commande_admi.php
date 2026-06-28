<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

include('connexion.php');

$id_commande = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (!$id_commande) {
    echo json_encode(['success' => false, 'message' => 'ID commande manquant']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT statut_commande FROM commande WHERE id_commande = ?");
    $stmt->execute([$id_commande]);
    $commande = $stmt->fetch();

    if (!$commande) {
        echo json_encode(['success' => false, 'message' => 'Commande introuvable']);
        exit;
    }

    if (in_array($commande['statut_commande'], ['Livrée', 'Annulée'])) {
        echo json_encode(['success' => false, 'message' => 'Cette commande ne peut pas être annulée']);
        exit;
    }

    $pdo->prepare("UPDATE commande SET statut_commande = 'Annulée' WHERE id_commande = ?")
        ->execute([$id_commande]);

    $reqStock = $pdo->prepare("SELECT id_produit, quantite FROM contenir WHERE id_commande = ?");
    $reqStock->execute([$id_commande]);
    foreach ($reqStock->fetchAll() as $prod) {
        $pdo->prepare("UPDATE produit SET stock_quantite = stock_quantite + ? WHERE id_produit = ?")
            ->execute([$prod['quantite'], $prod['id_produit']]);
    }

    echo json_encode(['success' => true, 'message' => '🚫 Commande #' . $id_commande . ' annulée']);

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur base de données']);
}
?>