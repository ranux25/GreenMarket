<?php
session_start();
header('Content-Type: application/json');
require_once 'connexion.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté en tant que client.']);
    exit;
}

$id_client = $_SESSION['user_id'];
$id_produit = isset($_POST['id_produit']) ? intval($_POST['id_produit']) : 0;
$note = isset($_POST['note']) ? intval($_POST['note']) : 0;
$commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';

if ($id_produit <= 0 || $note < 1 || $note > 5) {
    echo json_encode(['success' => false, 'message' => 'Données invalides.']);
    exit;
}

if (strlen($commentaire) > 255) {
    echo json_encode(['success' => false, 'message' => 'Le commentaire est trop long (max 255 caractères).']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $stmtCheck = $pdo->prepare("
        SELECT p.id_produit, p.nom_produit, p.id_boutique, 
               b.id_producteur
        FROM produit p
        JOIN boutique b ON p.id_boutique = b.id_boutique
        WHERE p.id_produit = ? AND p.est_valide_par_admin = 1 AND p.statut_publie = 'Publié'
    ");
    $stmtCheck->execute([$id_produit]);
    $produit = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$produit) {
        throw new Exception('Produit non trouvé.');
    }
    
    $stmtCheckEval = $pdo->prepare("
        SELECT * FROM evaluer WHERE id_client = ? AND id_produit = ?
    ");
    $stmtCheckEval->execute([$id_client, $id_produit]);
    $existe = $stmtCheckEval->fetch();
    
    if ($existe) {
        $stmtUpdate = $pdo->prepare("
            UPDATE evaluer 
            SET note = ?, commentaire = ?, date_evaluation = NOW()
            WHERE id_client = ? AND id_produit = ?
        ");
        $stmtUpdate->execute([$note, $commentaire, $id_client, $id_produit]);
        $message = "✅ Votre évaluation a été mise à jour avec succès !";
    } else {
        $stmtInsert = $pdo->prepare("
            INSERT INTO evaluer (id_client, id_produit, note, commentaire, date_evaluation)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmtInsert->execute([$id_client, $id_produit, $note, $commentaire]);
        $message = "✅ Merci pour votre évaluation !";
    }
    
    $nom_client = $_SESSION['user_nom'] ?? 'Client';
    $nom_produit = $produit['nom_produit'];
    $id_producteur = $produit['id_producteur'];
    
    $stars = str_repeat('⭐', $note) . str_repeat('☆', 5 - $note);
    
    $msg_notification = "📝 Nouvelle évaluation pour \"$nom_produit\"\n";
    $msg_notification .= "Client : $nom_client\n";
    $msg_notification .= "Note : $stars ($note/5)\n";
    if (!empty($commentaire)) {
        $msg_notification .= "Commentaire : \"$commentaire\"\n";
    }
    $msg_notification .= "📅 " . date('d/m/Y à H:i');
    
    $stmtNotif = $pdo->prepare("
        INSERT INTO notification (id_producteur, id_produit, message, date_notification, est_lu, type_notification)
        VALUES (?, ?, ?, NOW(), 0, 'evaluation')
    ");
    $stmtNotif->execute([$id_producteur, $id_produit, $msg_notification]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => $message
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Erreur evaluation_produit: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur technique. Veuillez réessayer.']);
}
?>