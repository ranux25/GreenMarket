<?php
session_start();
include('connexion.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté en tant que client']);
    exit();
}

$id_client = $_SESSION['user_id'];
$id_produit = intval($_POST['id_produit'] ?? 0);
$quantite = max(1, intval($_POST['quantite'] ?? 1));

if (!$id_produit) {
    echo json_encode(['success' => false, 'message' => 'Produit invalide']);
    exit();
}

try {
    $reqProd = $pdo->prepare("
        SELECT id_produit, nom_produit, stock_quantite, est_valide_par_admin, statut_publie 
        FROM produit 
        WHERE id_produit = ?
    ");
    $reqProd->execute([$id_produit]);
    $produit = $reqProd->fetch(PDO::FETCH_ASSOC);

    if (!$produit) {
        echo json_encode(['success' => false, 'message' => 'Produit non trouvé']);
        exit();
    }

    if ($produit['est_valide_par_admin'] != 1) {
        echo json_encode(['success' => false, 'message' => 'Produit non disponible (en attente de validation)']);
        exit();
    }

    if ($produit['statut_publie'] !== 'Publié') {
        echo json_encode(['success' => false, 'message' => 'Produit non disponible']);
        exit();
    }

    if ($produit['stock_quantite'] <= 0) {
        echo json_encode(['success' => false, 'message' => 'Produit en rupture de stock']);
        exit();
    }

    $reqCheck = $pdo->prepare("SELECT quantite FROM panier WHERE id_client = ? AND id_produit = ?");
    $reqCheck->execute([$id_client, $id_produit]);
    $existant = $reqCheck->fetch(PDO::FETCH_ASSOC);

    if ($existant) {
        $nouvelle_quantite = $existant['quantite'] + $quantite;
        if ($nouvelle_quantite > $produit['stock_quantite']) {
            echo json_encode(['success' => false, 'message' => 'Stock insuffisant (disponible: ' . $produit['stock_quantite'] . ')']);
            exit();
        }
        $reqUpdate = $pdo->prepare("UPDATE panier SET quantite = ? WHERE id_client = ? AND id_produit = ?");
        $reqUpdate->execute([$nouvelle_quantite, $id_client, $id_produit]);
    } else {
        if ($quantite > $produit['stock_quantite']) {
            echo json_encode(['success' => false, 'message' => 'Stock insuffisant (disponible: ' . $produit['stock_quantite'] . ')']);
            exit();
        }
        $reqInsert = $pdo->prepare("INSERT INTO panier (id_client, id_produit, quantite) VALUES (?, ?, ?)");
        $reqInsert->execute([$id_client, $id_produit, $quantite]);
    }

    $reqTotal = $pdo->prepare("SELECT SUM(quantite) as total FROM panier WHERE id_client = ?");
    $reqTotal->execute([$id_client]);
    $total = $reqTotal->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    echo json_encode([
        'success' => true,
        'total_panier' => intval($total),
        'message' => 'Produit ajouté au panier'
    ]);

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>