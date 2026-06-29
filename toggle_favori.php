<?php
session_start();
include('connexion.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

$id_client = $_SESSION['user_id'];
$id_produit = intval($_POST['id_produit'] ?? 0);

if ($id_produit <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID produit invalide']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id_produit FROM produit WHERE id_produit = ?");
    $stmt->execute([$id_produit]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Produit non trouvé']);
        exit();
    }

    $stmt = $pdo->prepare("SELECT id_produit FROM favoris WHERE id_client = ? AND id_produit = ?");
    $stmt->execute([$id_client, $id_produit]);
    $existe = $stmt->fetch();

    if ($existe) {
        $stmt = $pdo->prepare("DELETE FROM favoris WHERE id_client = ? AND id_produit = ?");
        $stmt->execute([$id_client, $id_produit]);
        $message = 'Retiré des favoris';
        $action = 'removed';
    } else {
        $stmt = $pdo->prepare("INSERT INTO favoris (id_client, id_produit) VALUES (?, ?)");
        $stmt->execute([$id_client, $id_produit]);
        $message = 'Ajouté aux favoris';
        $action = 'added';
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'action' => $action
    ]);

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>