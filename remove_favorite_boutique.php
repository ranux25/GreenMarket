<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

if (!isset($_POST['id_boutique'])) {
    echo json_encode(['success' => false, 'message' => 'ID boutique manquant']);
    exit;
}

include('connexion.php');

try {
    $stmt = $pdo->prepare("DELETE FROM favoris_boutique WHERE id_client = ? AND id_boutique = ?");
    $stmt->execute([$_SESSION['user_id'], intval($_POST['id_boutique'])]);
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    error_log("Erreur remove_favorite_boutique: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>