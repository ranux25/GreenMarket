<?php
ini_set('display_errors', 0);
ob_start();

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

include("connexion.php");

$id = intval($_POST['id'] ?? 0);
if (!$id) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'ID invalide']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT statut FROM producteur WHERE id_producteur = ?");
    $stmt->execute([$id]);
    $prod = $stmt->fetch();

    if (!$prod) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Producteur introuvable']);
        exit;
    }

    $new_statut = ($prod['statut'] === 'valide') ? 'refuse' : 'valide';
    $stmt = $pdo->prepare("UPDATE producteur SET statut = ? WHERE id_producteur = ?");
    $stmt->execute([$new_statut, $id]);

    $boutiques_ids = [];
    if ($new_statut === 'refuse') {
        $stmt = $pdo->prepare("SELECT id_boutique FROM boutique WHERE id_producteur = ? AND statut = 'valide'");
        $stmt->execute([$id]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $boutiques_ids = $rows;

        if (!empty($boutiques_ids)) {
            $placeholders = implode(',', array_fill(0, count($boutiques_ids), '?'));
            $stmt = $pdo->prepare("UPDATE boutique SET statut = 'refuse' WHERE id_boutique IN ($placeholders)");
            $stmt->execute($boutiques_ids);
        }
    } else {
        $stmt = $pdo->prepare("SELECT id_boutique FROM boutique WHERE id_producteur = ? AND statut = 'refuse'");
        $stmt->execute([$id]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $boutiques_ids = $rows;

        if (!empty($boutiques_ids)) {
            $placeholders = implode(',', array_fill(0, count($boutiques_ids), '?'));
            $stmt = $pdo->prepare("UPDATE boutique SET statut = 'valide' WHERE id_boutique IN ($placeholders)");
            $stmt->execute($boutiques_ids);
        }
    }

    $msg = ($new_statut === 'refuse')
        ? 'Producteur suspendu. ' . count($boutiques_ids) . ' boutique(s) désactivée(s).'
        : 'Producteur réactivé. ' . count($boutiques_ids) . ' boutique(s) réactivée(s).';

    ob_clean();
    echo json_encode([
        'success'       => true,
        'message'       => $msg,
        'new_statut'    => $new_statut,
        'boutiques_ids' => array_map('intval', $boutiques_ids)
    ]);

} catch (Throwable $e) {
    error_log("suspendre_producteur error: " . $e->getMessage());
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Erreur SQL: ' . $e->getMessage()]);
}
?>