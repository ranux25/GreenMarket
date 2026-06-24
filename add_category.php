<?php
session_start();
header('Content-Type: application/json');
include('connexion.php');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

$nom_categorie = trim($_POST['nom_categorie'] ?? '');
$description = trim($_POST['description'] ?? '');

if (empty($nom_categorie)) {
    echo json_encode(['success' => false, 'message' => 'Nom requis']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id_categorie FROM categorie WHERE nom_categorie = ?");
    $stmt->execute([$nom_categorie]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Catégorie déjà existante']);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO categorie (nom_categorie, description) VALUES (?, ?)");
    $stmt->execute([$nom_categorie, $description]);
    
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'message' => 'Catégorie ajoutée']);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>