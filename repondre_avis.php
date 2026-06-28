<?php
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'producteur') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

header('Content-Type: application/json');
include('connexion.php');

$raw_id  = $_POST['id_avis']  ?? '';
$reponse = trim($_POST['reponse'] ?? '');

if (!$raw_id || !$reponse) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit;
}

$parts = explode('__', $raw_id);
if (count($parts) !== 2) {
    echo json_encode(['success' => false, 'message' => 'Identifiant invalide']);
    exit;
}

[$id_client, $id_produit] = $parts;

if (!ctype_digit($id_client) || !ctype_digit($id_produit)) {
    echo json_encode(['success' => false, 'message' => 'Identifiant invalide']);
    exit;
}

if (mb_strlen($reponse) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Réponse trop longue (max 1000 caractères)']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT p.id_produit
        FROM produit p
        JOIN boutique b ON p.id_boutique = b.id_boutique
        WHERE p.id_produit = ? AND b.id_producteur = ?
    ");
    $stmt->execute([$id_produit, $_SESSION['user_id']]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Accès non autorisé à ce produit']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT id_client FROM evaluer
        WHERE id_client = ? AND id_produit = ?
    ");
    $stmt->execute([$id_client, $id_produit]);

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Évaluation introuvable']);
        exit;
    }

    $stmt = $pdo->prepare("
        UPDATE evaluer
        SET reponse_producteur = ?
        WHERE id_client = ? AND id_produit = ?
    ");
    $stmt->execute([$reponse, $id_client, $id_produit]);

    echo json_encode(['success' => true, 'message' => 'Réponse enregistrée avec succès']);

} catch (PDOException $e) {
    error_log("Erreur repondre_avis.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur base de données']);
}
