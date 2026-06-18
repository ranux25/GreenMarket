<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter en tant que client']);
    exit;
}

include("connexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    extract($_POST);
    $id_produit = $id_produit ?? 0;
    $quantite   = isset($quantite) ? intval($quantite) : 1;
    if ($quantite < 1) $quantite = 1;

    try {
        #verifier si le produit existe deja dans le panier de ce client
        $req = $pdo->prepare("SELECT quantite FROM panier WHERE id_client = ? AND id_produit = ?");
        $req->execute([$_SESSION['user_id'], $id_produit]);
        $existant = $req->fetch(PDO::FETCH_ASSOC);

        if ($existant) {
            #mettre a jour la quantite
            $nouvelle_qte = $existant['quantite'] + $quantite;
            $ri = $pdo->prepare("UPDATE panier SET quantite = ? WHERE id_client = ? AND id_produit = ?");
            $r = $ri->execute([$nouvelle_qte, $_SESSION['user_id'], $id_produit]);
        } else {
            #inserer un nouvel article
            $ri = $pdo->prepare("INSERT INTO panier (id_client, id_produit, quantite) VALUES (?, ?, ?)");
            $r = $ri->execute([$_SESSION['user_id'], $id_produit, $quantite]);
        }

        if ($r == false) {
            echo json_encode(['success' => false, 'message' => "Echec de l'ajout au panier"]);
        } else {
            #compter le total d'articles dans le panier pour mettre a jour le badge
            $reqC = $pdo->prepare("SELECT SUM(quantite) as total FROM panier WHERE id_client = ?");
            $reqC->execute([$_SESSION['user_id']]);
            $total = $reqC->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            echo json_encode(['success' => true, 'message' => 'Produit ajouté au panier', 'total_panier' => $total]);
        }
    }
    catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode invalide']);
}
?>
