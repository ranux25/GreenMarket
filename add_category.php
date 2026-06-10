<?php
session_start();
header('Content-Type: application/json');
require_once 'connexion.php';

// Vérifier que l'utilisateur est admin
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
    // Vérifier si la catégorie existe déjà
    $stmt = $pdo->prepare("SELECT id_categorie FROM categorie WHERE nom_categorie = ?");
    $stmt->execute([$nom_categorie]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Catégorie déjà existante']);
        exit;
    }
    
    // Insérer la nouvelle catégorie
    $stmt = $pdo->prepare("INSERT INTO categorie (nom_categorie, description) VALUES (?, ?)");
    $stmt->execute([$nom_categorie, $description]);
    
    $id = $pdo->lastInsertId();
    echo json_encode(['success' => true, 'id' => $id, 'message' => 'Catégorie ajoutée']);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>