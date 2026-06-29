<?php
session_start();
include("connexion.php");

$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
           (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded') !== false && $_SERVER['REQUEST_METHOD'] === 'POST');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    } else {
        header('Location: signin.php');
    }
    exit();
}

$id_client = $_SESSION['user_id'];
$id_commande = $_GET['id_commande'] ?? $_POST['id_commande'] ?? 0;

if (!$id_commande) {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Commande invalide']);
    } else {
        header('Location: dashboard_client.php');
    }
    exit();
}

try {
    $req = $pdo->prepare("SELECT statut_commande FROM commande WHERE id_commande = ? AND id_client = ?");
    $req->execute([$id_commande, $id_client]);
    $commande = $req->fetch();

    if (!$commande) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Commande introuvable']);
        exit();
    }

    if ($commande['statut_commande'] !== 'En attente') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Cette commande ne peut plus être annulée']);
        exit();
    }

    $pdo->prepare("UPDATE commande SET statut_commande = 'Annulée' WHERE id_commande = ? AND id_client = ?")
        ->execute([$id_commande, $id_client]);

    $reqStock = $pdo->prepare("SELECT id_produit, quantite FROM contenir WHERE id_commande = ?");
    $reqStock->execute([$id_commande]);
    foreach ($reqStock->fetchAll() as $prod) {
        $pdo->prepare("UPDATE produit SET stock_quantite = stock_quantite + ? WHERE id_produit = ?")
            ->execute([$prod['quantite'], $prod['id_produit']]);
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Commande annulée avec succès']);

} catch(PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erreur base de données']);
}
?>