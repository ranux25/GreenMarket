<?php
session_start();
include('connexion.php');

header('Content-Type: application/json');

// Verificar que el usuario sea admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

$id_boutique = intval($_POST['id_boutique'] ?? 0);

if (!$id_boutique) {
    echo json_encode(['success' => false, 'message' => 'ID boutique invalide']);
    exit();
}

try {
    // Récupérer l'image de la boutique
    $stmt = $pdo->prepare("SELECT image FROM boutique WHERE id_boutique = ?");
    $stmt->execute([$id_boutique]);
    $boutique = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Supprimer l'image si elle existe
    if ($boutique && !empty($boutique['image']) && file_exists($boutique['image'])) {
        unlink($boutique['image']);
    }
    
    // Supprimer la boutique (les produits seront supprimés par CASCADE)
    $stmt = $pdo->prepare("DELETE FROM boutique WHERE id_boutique = ?");
    $stmt->execute([$id_boutique]);
    
    echo json_encode(['success' => true, 'message' => 'Boutique supprimée avec succès']);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>