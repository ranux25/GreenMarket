<?php
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard_admin.php?msgerr=" . urlencode('Non autorisé'));
    exit;
}

include('connexion.php');

$id_produit = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (!$id_produit || !in_array($action, ['valider', 'refuser'])) {
    header("Location: dashboard_admin.php?msgerr=" . urlencode('Données invalides'));
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT p.id_produit, p.nom_produit, p.id_boutique, 
               b.id_producteur, pr.nom_entreprise, pr.email,
               p.est_valide_par_admin
        FROM produit p
        JOIN boutique b ON p.id_boutique = b.id_boutique
        JOIN producteur pr ON b.id_producteur = pr.id_producteur
        WHERE p.id_produit = ?
    ");
    $stmt->execute([$id_produit]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        throw new Exception('Produit non trouvé');
    }

    if ($action === 'valider') {
        $stmt = $pdo->prepare("UPDATE produit SET est_valide_par_admin = 1 WHERE id_produit = ?");
        $stmt->execute([$id_produit]);
        $message = '✅ Produit "' . $product['nom_produit'] . '" validé avec succès';
        $notification_message = '✅ Votre produit "' . $product['nom_produit'] . '" a été validé par l\'administrateur. Il est maintenant disponible à la vente !';
        $type = 'validation_produit';
    } else {
        $stmt = $pdo->prepare("UPDATE produit SET est_valide_par_admin = 0 WHERE id_produit = ?");
        $stmt->execute([$id_produit]);
        $message = '❌ Produit "' . $product['nom_produit'] . '" refusé';
        $notification_message = '❌ Votre produit "' . $product['nom_produit'] . '" a été refusé par l\'administrateur.';
        $type = 'refus_produit';
    }

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM notification")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('id_produit', $cols)) {
            $stmt = $pdo->prepare("
                INSERT INTO notification (id_producteur, message, type_notification, id_produit, date_notification, est_lu)
                VALUES (?, ?, ?, ?, NOW(), 0)
            ");
            $stmt->execute([$product['id_producteur'], $notification_message, $type, $id_produit]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO notification (id_producteur, message, type_notification, date_notification, est_lu)
                VALUES (?, ?, ?, NOW(), 0)
            ");
            $stmt->execute([$product['id_producteur'], $notification_message, $type]);
        }
    } catch (PDOException $e) {
        error_log("Notification insert skipped: " . $e->getMessage());
    }

    header("Location: dashboard_admin.php?msgs=" . urlencode($message) . "&tab=produits");
    exit;

} catch (PDOException $e) {
    error_log("Erreur valider_produit: " . $e->getMessage());
    header("Location: dashboard_admin.php?msgerr=" . urlencode('Erreur SQL: ' . $e->getMessage()));
    exit;
} catch (Exception $e) {
    error_log("Erreur valider_produit: " . $e->getMessage());
    header("Location: dashboard_admin.php?msgerr=" . urlencode($e->getMessage()));
    exit;
}
?>
