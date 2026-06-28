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
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($id_produit <= 0 || !in_array($action, ['activer', 'desactiver'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides.']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    if ($action === 'activer') {
        $stmtCheck = $pdo->prepare("
            SELECT id_produit, stock_quantite, nom_produit 
            FROM produit 
            WHERE id_produit = ? AND est_valide_par_admin = 1
        ");
        $stmtCheck->execute([$id_produit]);
        $produit = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if (!$produit) {
            throw new Exception('Produit non trouvé.');
        }
        
        if ($produit['stock_quantite'] > 0) {
            throw new Exception('Ce produit est déjà en stock.');
        }
        
        $stmtCheckAlert = $pdo->prepare("
            SELECT id_alerte FROM alertes_stock 
            WHERE id_produit = ? AND id_client = ? AND est_active = 1
        ");
        $stmtCheckAlert->execute([$id_produit, $id_client]);
        
        if ($stmtCheckAlert->fetch()) {
            throw new Exception('Vous avez déjà une alerte active pour ce produit.');
        }
        
        $stmtInsert = $pdo->prepare("
            INSERT INTO alertes_stock (id_produit, id_client, date_creation, est_active) 
            VALUES (?, ?, NOW(), 1)
        ");
        $stmtInsert->execute([$id_produit, $id_client]);
        
        $pdo->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Alerte activée pour le produit "' . $produit['nom_produit'] . '".'
        ]);
        
    } else if ($action === 'desactiver') {
        $stmtUpdate = $pdo->prepare("
            UPDATE alertes_stock 
            SET est_active = 0, date_desactivation = NOW() 
            WHERE id_produit = ? AND id_client = ? AND est_active = 1
        ");
        $stmtUpdate->execute([$id_produit, $id_client]);
        
        if ($stmtUpdate->rowCount() > 0) {
            $pdo->commit();
            echo json_encode([
                'success' => true, 
                'message' => 'Alerte désactivée avec succès.'
            ]);
        } else {
            throw new Exception('Aucune alerte active trouvée pour ce produit.');
        }
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Erreur gestion_alertes_stock: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur technique. Veuillez réessayer.']);
}
?>