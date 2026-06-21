<?php
session_start();
include('connexion.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

$id_categorie = intval($_POST['id_categorie'] ?? 0);

if (!$id_categorie) {
    echo json_encode(['success' => false, 'message' => 'ID catégorie invalide']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM produit WHERE id_categorie = ?");
    $stmt->execute([$id_categorie]);
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($count['total'] > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'Cette catégorie contient ' . $count['total'] . ' produit(s). Supprimez-les d\'abord ou assignez-les à une autre catégorie.'
        ]);
        exit();
    }
    
    $stmt = $pdo->prepare("DELETE FROM categorie WHERE id_categorie = ?");
    $stmt->execute([$id_categorie]);
    
    echo json_encode(['success' => true, 'message' => 'Catégorie supprimée avec succès']);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>