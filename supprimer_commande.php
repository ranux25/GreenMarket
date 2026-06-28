<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_commande'])) {
    echo json_encode(['success' => false, 'message' => 'Requête invalide']);
    exit;
}

include('connexion.php');

try {
    $id_commande = (int)$_POST['id_commande'];
    $id_client   = (int)$_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT statut_commande FROM commande WHERE id_commande = ? AND id_client = ?");
    $stmt->execute([$id_commande, $id_client]);
    $commande = $stmt->fetch();

    if (!$commande) {
        echo json_encode(['success' => false, 'message' => 'Commande introuvable']);
        exit;
    }

    if ($commande['statut_commande'] !== 'Annulée') {
        echo json_encode(['success' => false, 'message' => 'Seules les commandes annulées peuvent être supprimées']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM contenir WHERE id_commande = ?");
    $stmt->execute([$id_commande]);

    $stmt = $pdo->prepare("DELETE FROM commande WHERE id_commande = ? AND id_client = ?");
    $stmt->execute([$id_commande, $id_client]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Erreur supprimer_commande: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
