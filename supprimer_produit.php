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

// Récupération de l'ID (GET ou POST)
$id_produit = 0;
if (isset($_GET['id'])) {
    $id_produit = (int)$_GET['id'];
} elseif (isset($_POST['id_produit'])) {
    $id_produit = (int)$_POST['id_produit'];
}

if (!$id_produit) {
    // Si c'est une requête AJAX (POST), renvoyer JSON
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'ID du produit manquant']);
        exit;
    }
    header("Location: dashboard_admin.php?msgerr=" . urlencode('ID du produit manquant'));
    exit;
}

try {
    // Démarrer la transaction
    $pdo->beginTransaction();
    
    // 1. Récupérer les infos du produit et du producteur
    $stmt = $pdo->prepare("
        SELECT p.id_produit, p.nom_produit, p.id_boutique, 
               b.id_producteur
        FROM produit p
        JOIN boutique b ON p.id_boutique = b.id_boutique
        WHERE p.id_produit = ?
    ");
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
    
    // 4. Créer une notification pour le producteur
    $id_producteur = $product['id_producteur'];
    $nom_produit = htmlspecialchars($product['nom_produit']);
    $message = "🗑️ Votre produit \"$nom_produit\" (ID: $id_produit) a été supprimé par l'administrateur.\n\n";
    $message .= "📅 Date : " . date('d/m/Y à H:i') . "\n";
    $message .= "❓ Si vous avez des questions, veuillez contacter le support.";
    
    $stmt = $pdo->prepare("
        INSERT INTO notification (id_producteur, message, date_notification, est_lu, type_notification) 
        VALUES (?, ?, NOW(), 0, 'system')
    ");
    $stmt->execute([$id_producteur, $message]);
    
    // 5. Valider la transaction
    $pdo->commit();
    
    // 6. Si c'est une requête AJAX (POST), renvoyer JSON
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Produit "' . $nom_produit . '" supprimé avec succès',
            'id' => $id_produit
        ]);
        exit;
    }
    
    // Redirection normale (GET)
    $message = urlencode('🗑️ Produit "' . $nom_produit . '" supprimé avec succès');
    header("Location: dashboard_admin.php?msgs=" . $message . "&tab=produits");
    exit;

} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log de l'erreur
    error_log("Erreur supprimer_produit (PDO): " . $e->getMessage());
    
    // Si c'est une requête AJAX (POST), renvoyer JSON
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $error_msg = "Erreur base de données";
        if (strpos($e->getMessage(), 'foreign key') !== false) {
            $error_msg = "Impossible de supprimer : le produit est référencé ailleurs";
        } elseif (strpos($e->getMessage(), 'Duplicate') !== false) {
            $error_msg = "Erreur de duplication";
        } else {
            $error_msg = $e->getMessage();
        }
        echo json_encode(['success' => false, 'message' => $error_msg]);
        exit;
    }
    
    header("Location: dashboard_admin.php?msgerr=" . urlencode($error_msg));
    exit;
    
} catch (Exception $e) {
    // Annuler la transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erreur supprimer_produit: " . $e->getMessage());
    
    // Si c'est une requête AJAX (POST), renvoyer JSON
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
    
    header("Location: dashboard_admin.php?msgerr=" . urlencode($e->getMessage()));
    exit;
}
?>