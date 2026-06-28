<?php
session_start();
header('Content-Type: application/json');
include('connexion.php');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé.']);
    exit;
}

$id_client = $_SESSION['user_id'];
$nom_client = isset($_POST['nom_client']) ? trim($_POST['nom_client']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
$adresse = isset($_POST['adresse']) ? trim($_POST['adresse']) : '';

if (empty($nom_client) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Nom et email obligatoires.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id_client FROM client WHERE email = ? AND id_client != ?");
    $stmt->execute([$email, $id_client]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé.']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        UPDATE client 
        SET nom_client = ?, email = ?, telephone = ?, adresse = ? 
        WHERE id_client = ?
    ");
    $stmt->execute([$nom_client, $email, $telephone, $adresse, $id_client]);
    
    $_SESSION['user_nom'] = $nom_client;
    $_SESSION['user_email'] = $email;
    
    echo json_encode(['success' => true, 'message' => 'Profil mis à jour.']);
} catch (PDOException $e) {
    error_log("Erreur update_client_profile: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur technique.']);
}
?>