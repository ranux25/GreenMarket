<?php
session_start();
include('connexion.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

$id_client = $_SESSION['user_id'];
$id_boutique = intval($_POST['id_boutique'] ?? 0);

if ($id_boutique <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID boutique invalide']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id_boutique FROM boutique WHERE id_boutique = ?");
    $stmt->execute([$id_boutique]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Boutique non trouvée']);
        exit();
    }

    $stmt = $pdo->prepare("SELECT id_boutique FROM favoris_boutique WHERE id_client = ? AND id_boutique = ?");
    $stmt->execute([$id_client, $id_boutique]);
    $existe = $stmt->fetch();

    if ($existe) {
        $stmt = $pdo->prepare("DELETE FROM favoris_boutique WHERE id_client = ? AND id_boutique = ?");
        $stmt->execute([$id_client, $id_boutique]);
        echo json_encode(['success' => true, 'message' => 'Boutique retirée des favoris', 'action' => 'removed']);
    } else {
        $stmt = $pdo->prepare("INSERT INTO favoris_boutique (id_client, id_boutique) VALUES (?, ?)");
        $stmt->execute([$id_client, $id_boutique]);
        echo json_encode(['success' => true, 'message' => 'Boutique ajoutée aux favoris', 'action' => 'added']);
    }

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>