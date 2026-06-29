<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter en tant que client']);
    exit;
}

include('connexion.php');

$id_boutique = isset($_POST['id_boutique']) ? (int)$_POST['id_boutique'] : 0;

if (!$id_boutique) {
    echo json_encode(['success' => false, 'message' => 'ID de boutique manquant']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id_boutique FROM boutique WHERE id_boutique = ?");
    $stmt->execute([$id_boutique]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Boutique non trouvée']);
        exit;
    }
    
    $id_client = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM favoris_boutique WHERE id_client = ? AND id_boutique = ?");
    $stmt->execute([$id_client, $id_boutique]);
    $existe = $stmt->fetch();
    
    if ($existe) {
        $stmt = $pdo->prepare("DELETE FROM favoris_boutique WHERE id_client = ? AND id_boutique = ?");
        $stmt->execute([$id_client, $id_boutique]);
        echo json_encode(['success' => true, 'action' => 'removed']);
    } else {
        $stmt = $pdo->prepare("INSERT INTO favoris_boutique (id_client, id_boutique) VALUES (?, ?)");
        $stmt->execute([$id_client, $id_boutique]);
        echo json_encode(['success' => true, 'action' => 'added']);
    }

} catch(PDOException $e) {
    error_log("Erreur toggle_favori_boutique: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur SQL: ' . $e->getMessage()]);
} catch(Exception $e) {
    error_log("Erreur toggle_favori_boutique: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>