<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

include('connexion.php');

$id_boutique = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action      = $_POST['action'] ?? '';

if (!$id_boutique || !in_array($action, ['valider', 'refuser'])) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT b.id_boutique, b.nom_boutique, b.id_producteur
        FROM boutique b
        WHERE b.id_boutique = ?
    ");
    $stmt->execute([$id_boutique]);
    $boutique = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$boutique) {
        echo json_encode(['success' => false, 'message' => 'Boutique non trouvée']);
        exit;
    }

    if ($action === 'valider') {
        $pdo->prepare("UPDATE boutique SET est_valide_par_admin = 1, statut = 'valide' WHERE id_boutique = ?")
            ->execute([$id_boutique]);
        $message   = 'Boutique "' . $boutique['nom_boutique'] . '" validée';
        $notif_msg = '✅ Votre boutique "' . $boutique['nom_boutique'] . '" a été validée par l\'administrateur.';
        $type      = 'validation_boutique';
        $new_statut = 'valide';
    } else {
        $pdo->prepare("UPDATE boutique SET est_valide_par_admin = 0, statut = 'refuse' WHERE id_boutique = ?")
            ->execute([$id_boutique]);
        $message   = 'Boutique "' . $boutique['nom_boutique'] . '" refusée';
        $notif_msg = '❌ Votre boutique "' . $boutique['nom_boutique'] . '" a été refusée par l\'administrateur.';
        $type      = 'refus_boutique';
        $new_statut = 'refuse';
    }

    $pdo->prepare("
        INSERT INTO notification (id_producteur, message, type_notification, date_notification, est_lu)
        VALUES (?, ?, ?, NOW(), 0)
    ")->execute([$boutique['id_producteur'], $notif_msg, $type]);

    echo json_encode([
        'success'    => true,
        'message'    => $message,
        'new_statut' => $new_statut
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>