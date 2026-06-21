<?php
session_start();
header('Content-Type: application/json');
require_once 'connexion.php';

// Vérifier que l'utilisateur est connecté en tant que client
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté en tant que client.']);
    exit;
}

$id_client = $_SESSION['user_id'];
$id_boutique = isset($_POST['id_boutique']) ? intval($_POST['id_boutique']) : 0;
$note = isset($_POST['note']) ? intval($_POST['note']) : 0;
$commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';

// Validations
if ($id_boutique <= 0 || $note < 1 || $note > 5) {
    echo json_encode(['success' => false, 'message' => 'Données invalides.']);
    exit;
}

if (strlen($commentaire) > 255) {
    echo json_encode(['success' => false, 'message' => 'Le commentaire est trop long (max 255 caractères).']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Vérifier que la boutique existe
    $stmtCheck = $pdo->prepare("
        SELECT b.id_boutique, b.nom_boutique, b.id_producteur
        FROM boutique b
        WHERE b.id_boutique = ? AND b.est_valide_par_admin = 1
    ");
    $stmtCheck->execute([$id_boutique]);
    $boutique = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if (!$boutique) {
        throw new Exception('Boutique non trouvée.');
    }
    
    // Vérifier si déjà évalué
    $stmtCheckEval = $pdo->prepare("
        SELECT * FROM evaluer_boutique WHERE id_client = ? AND id_boutique = ?
    ");
    $stmtCheckEval->execute([$id_client, $id_boutique]);
    $existe = $stmtCheckEval->fetch();
    
    if ($existe) {
        // Mettre à jour
        $stmtUpdate = $pdo->prepare("
            UPDATE evaluer_boutique 
            SET note = ?, commentaire = ?, date_evaluation = NOW()
            WHERE id_client = ? AND id_boutique = ?
        ");
        $stmtUpdate->execute([$note, $commentaire, $id_client, $id_boutique]);
        $message = "✅ Votre évaluation de la boutique a été mise à jour !";
    } else {
        // Insérer
        $stmtInsert = $pdo->prepare("
            INSERT INTO evaluer_boutique (id_client, id_boutique, note, commentaire, date_evaluation)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmtInsert->execute([$id_client, $id_boutique, $note, $commentaire]);
        $message = "✅ Merci pour votre évaluation de la boutique !";
    }
    
    // 🔔 Notifier le producteur
    $nom_client = $_SESSION['user_nom'] ?? 'Client';
    $stars = str_repeat('⭐', $note) . str_repeat('☆', 5 - $note);
    $msg_notification = "📝 Nouvelle évaluation pour la boutique \"{$boutique['nom_boutique']}\"\n";
    $msg_notification .= "Client : $nom_client\n";
    $msg_notification .= "Note : $stars ($note/5)\n";
    if (!empty($commentaire)) {
        $msg_notification .= "Commentaire : \"$commentaire\"\n";
    }
    $msg_notification .= "📅 " . date('d/m/Y à H:i');
    
    $stmtNotif = $pdo->prepare("
        INSERT INTO notification (id_producteur, message, date_notification, est_lu, type_notification)
        VALUES (?, ?, NOW(), 0, 'evaluation_boutique')
    ");
    $stmtNotif->execute([$boutique['id_producteur'], $msg_notification]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Erreur evaluation_boutique: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur technique.']);
}
?>