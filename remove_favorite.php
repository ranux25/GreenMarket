<?php
session_start();
header('Content-Type: application/json');
include('connexion.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé.']);
    exit;
}

$id_client = $_SESSION['user_id'];
$id_produit = isset($_POST['id_produit']) ? intval($_POST['id_produit']) : 0;

if ($id_produit <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID produit invalide.']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM favoris WHERE id_client = ? AND id_produit = ?");
    $stmt->execute([$id_client, $id_produit]);
    
    echo json_encode(['success' => true, 'message' => 'Favori supprimé.']);
} catch (PDOException $e) {
    error_log("Erreur remove_favorite: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur technique.']);
}
?>