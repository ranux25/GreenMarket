<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

include("connexion.php");

$id     = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$id || !in_array($action, ['suspendre', 'activer'])) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    $val = ($action === 'activer') ? 1 : 0;
    $stmt = $pdo->prepare("UPDATE client SET est_actif = ? WHERE id_client = ?");
    $stmt->execute([$val, $id]);

    $msg        = ($action === 'activer') ? 'Client réactivé avec succès.' : 'Client suspendu avec succès.';
    $new_statut = ($action === 'activer') ? 'actif' : 'suspendu';

    echo json_encode([
        'success'    => true,
        'message'    => $msg,
        'new_statut' => $new_statut
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}