<?php
session_start();
include("connexion.php");

header('Content-Type: application/json');

// Verificar que el usuario esté logueado y sea cliente
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté en tant que client']);
    exit();
}

$id_client = $_SESSION['user_id'];
$id_produit = $_POST['id_produit'] ?? 0;
$quantite = max(1, intval($_POST['quantite'] ?? 1));

if (!$id_produit) {
    echo json_encode(['success' => false, 'message' => 'Produit invalide']);
    exit();
}

try {
    // Vérifier que le produit existe et est disponible
    $reqProd = $pdo->prepare("SELECT stock_quantite FROM produit WHERE id_produit = ? AND est_valide_par_admin = 1");
    $reqProd->execute([$id_produit]);
    $produit = $reqProd->fetch();

    if (!$produit) {
        echo json_encode(['success' => false, 'message' => 'Produit non disponible']);
        exit();
    }

    // Vérifier si le produit est déjà dans le panier
    $reqCheck = $pdo->prepare("SELECT quantite FROM panier WHERE id_client = ? AND id_produit = ?");
    $reqCheck->execute([$id_client, $id_produit]);
    $existant = $reqCheck->fetch();

    if ($existant) {
        // Mettre à jour la quantité
        $nouvelle_quantite = $existant['quantite'] + $quantite;
        if ($nouvelle_quantite > $produit['stock_quantite']) {
            echo json_encode(['success' => false, 'message' => 'Stock insuffisant']);
            exit();
        }
        $reqUpdate = $pdo->prepare("UPDATE panier SET quantite = ? WHERE id_client = ? AND id_produit = ?");
        $reqUpdate->execute([$nouvelle_quantite, $id_client, $id_produit]);
    } else {
        // Ajouter au panier
        if ($quantite > $produit['stock_quantite']) {
            echo json_encode(['success' => false, 'message' => 'Stock insuffisant']);
            exit();
        }
        $reqInsert = $pdo->prepare("INSERT INTO panier (id_client, id_produit, quantite) VALUES (?, ?, ?)");
        $reqInsert->execute([$id_client, $id_produit, $quantite]);
    }

    // Récupérer le total du panier
    $reqTotal = $pdo->prepare("SELECT SUM(quantite) as total FROM panier WHERE id_client = ?");
    $reqTotal->execute([$id_client]);
    $total = $reqTotal->fetch()['total'] ?? 0;

    echo json_encode([
        'success' => true,
        'total_panier' => intval($total),
        'message' => 'Produit ajouté au panier'
    ]);

} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
}
?>