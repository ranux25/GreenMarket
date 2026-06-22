<?php
// === ACTIVATION DEBUG ===
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Vérification ADMIN
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: dashboard_admin.php?msgerr=" . urlencode('Non autorisé'));
    exit;
}

// Inclusion de la connexion (avec chemin absolu)
require_once __DIR__ . '/connexion.php';

// Récupération de l'ID
$id_produit = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id_produit) {
    header("Location: dashboard_admin.php?msgerr=" . urlencode('ID du produit manquant'));
    exit;
}

try {
    // Démarrer la transaction
    $pdo->beginTransaction();
    
    // 1. Récupérer les infos du produit
    $stmt = $pdo->prepare("SELECT id_produit, nom_produit, id_boutique FROM produit WHERE id_produit = ?");
    $stmt->execute([$id_produit]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        throw new Exception('Produit non trouvé');
    }
    
    // 2. Supprimer les dépendances (tables sans ON DELETE CASCADE)
    $tables_sans_cascade = ['alertes_stock', 'validation_produit', 'validation_commande'];
    foreach ($tables_sans_cascade as $table) {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id_produit = ?");
        $stmt->execute([$id_produit]);
    }
    
    // 3. Supprimer le produit (les tables avec CASCADE s'occuperont du reste)
    $stmt = $pdo->prepare("DELETE FROM produit WHERE id_produit = ?");
    $stmt->execute([$id_produit]);
    
    // 4. Valider la transaction
    $pdo->commit();
    
    // 5. Redirection avec succès
    $message = urlencode('🗑️ Produit "' . htmlspecialchars($product['nom_produit']) . '" supprimé avec succès');
    header("Location: dashboard_admin.php?msgs=" . $message . "&tab=produits");
    exit;

} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log de l'erreur
    error_log("Erreur supprimer_produit (PDO): " . $e->getMessage());
    
    // Message d'erreur plus clair
    $error_msg = "Erreur base de données";
    if (strpos($e->getMessage(), 'foreign key') !== false) {
        $error_msg = "Impossible de supprimer : le produit est référencé ailleurs";
    } elseif (strpos($e->getMessage(), 'Duplicate') !== false) {
        $error_msg = "Erreur de duplication";
    } else {
        $error_msg = $e->getMessage();
    }
    
    header("Location: dashboard_admin.php?msgerr=" . urlencode($error_msg));
    exit;
    
} catch (Exception $e) {
    // Annuler la transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erreur supprimer_produit: " . $e->getMessage());
    header("Location: dashboard_admin.php?msgerr=" . urlencode($e->getMessage()));
    exit;
}
?>