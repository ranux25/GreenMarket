<?php
session_start();
if(!isset($_SESSION) || empty($_SESSION)){
    header("Location: signin.php");
    exit;
}
if(!in_array($_SESSION['user_role'],['client','producteur','admin'])){
    header("Location: signin.php");
    exit;
}
$role=$_SESSION['user_role'];
$id_user=$_SESSION['user_id'];
$id_commande=$_GET['id'] ?? 0;
include("connexion.php");
try{
    if($role=='client'){
        $req=$pdo->prepare("
            SELECT c.id_commande,c.date_commande,c.statut_commande,c.montant_total,
                   p.reference_transaction,p.statut_paiement,p.mode_paiement,
                   cl.nom_client,cl.email
            FROM commande c
            LEFT JOIN paiement p ON c.id_paiement=p.id_paiement
            JOIN client cl ON c.id_client=cl.id_client
            WHERE c.id_commande=? AND c.id_client=?
        ");
        $req->execute([$id_commande,$id_user]);
    }elseif($role=='producteur'){
        $req=$pdo->prepare("
            SELECT DISTINCT c.id_commande,c.date_commande,c.statut_commande,c.montant_total,
                   p.reference_transaction,p.statut_paiement,p.mode_paiement,
                   cl.nom_client,cl.email
            FROM commande c
            LEFT JOIN paiement p ON c.id_paiement=p.id_paiement
            JOIN client cl ON c.id_client=cl.id_client
            JOIN contenir ct ON c.id_commande=ct.id_commande
            JOIN produit pr ON ct.id_produit=pr.id_produit
            JOIN boutique b ON pr.id_boutique=b.id_boutique
            WHERE c.id_commande=? AND b.id_producteur=?
        ");
        $req->execute([$id_commande,$id_user]);
    }else{
        $req=$pdo->prepare("
            SELECT c.id_commande,c.date_commande,c.statut_commande,c.montant_total,
                   p.reference_transaction,p.statut_paiement,p.mode_paiement,
                   cl.nom_client,cl.email
            FROM commande c
            LEFT JOIN paiement p ON c.id_paiement=p.id_paiement
            JOIN client cl ON c.id_client=cl.id_client
            WHERE c.id_commande=?
        ");
        $req->execute([$id_commande]);
    }
    $commande=$req->fetch(PDO::FETCH_ASSOC);
    if(!$commande) die("Commande non trouvée");
    $reqProd=$pdo->prepare("
        SELECT ct.quantite,ct.prix_unitaire,p.nom_produit,p.photo_url
        FROM contenir ct
        JOIN produit p ON ct.id_produit=p.id_produit
        WHERE ct.id_commande=?
    ");
    $reqProd->execute([$id_commande]);
    $produits=$reqProd->fetchAll(PDO::FETCH_ASSOC);
}
catch(PDOException $e){die("Erreur : ".$e->getMessage());}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture #<?= str_pad($id_commande, 6, '0', STR_PAD_LEFT) ?> - GreenMarket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-gray-100 p-8">
<div class="max-w-4xl mx-auto bg-white rounded-xl shadow-lg p-8">
    <div class="flex justify-between items-center border-b pb-6 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#5D0D18]">GreenMarket</h1>
            <p class="text-sm text-gray-500">Artisanat marocain</p>
        </div>
        <div class="text-right">
            <p class="text-sm font-semibold">FACTURE</p>
            <p class="text-sm text-gray-500">#<?= str_pad($id_commande, 6, '0', STR_PAD_LEFT) ?></p>
        </div>
    </div>
    
    <div class="grid grid-cols-2 gap-4 mb-6">
        <div>
            <p class="text-sm text-gray-500">Client</p>
            <p class="font-semibold"><?= htmlspecialchars($commande['nom_client']) ?></p>
            <p class="text-sm"><?= htmlspecialchars($commande['email']) ?></p>
        </div>
        <div class="text-right">
            <p class="text-sm text-gray-500">Date</p>
            <p class="font-semibold"><?= date('d/m/Y', strtotime($commande['date_commande'])) ?></p>
            <p class="text-sm text-gray-500">Statut : <?= htmlspecialchars($commande['statut_commande']) ?></p>
        </div>
    </div>
    
    <table class="w-full mb-6">
        <thead class="bg-gray-100">
            <tr>
                <th class="text-left p-3 text-sm font-semibold">Produit</th>
                <th class="text-center p-3 text-sm font-semibold">Quantité</th>
                <th class="text-right p-3 text-sm font-semibold">Prix unitaire</th>
                <th class="text-right p-3 text-sm font-semibold">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($produits as $prod): ?>
            <tr class="border-b">
                <td class="p-3"><?= htmlspecialchars($prod['nom_produit']) ?></td>
                <td class="text-center p-3"><?= $prod['quantite'] ?></td>
                <td class="text-right p-3"><?= number_format($prod['prix_unitaire'], 2, ',', ' ') ?> DH</td>
                <td class="text-right p-3 font-semibold"><?= number_format($prod['prix_unitaire'] * $prod['quantite'], 2, ',', ' ') ?> DH</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="text-right p-3 font-bold">Total</td>
                <td class="text-right p-3 font-bold text-[#5D0D18]"><?= number_format($commande['montant_total'], 2, ',', ' ') ?> DH</td>
            </tr>
        </tfoot>
    </table>
    
    <?php if($commande['mode_paiement']): ?>
    <div class="bg-gray-50 p-4 rounded-lg mb-6">
        <p class="text-sm text-gray-500">Mode de paiement</p>
        <p class="font-semibold"><?= htmlspecialchars($commande['mode_paiement']) ?></p>
        <p class="text-sm text-gray-500">Référence : <?= htmlspecialchars($commande['reference_transaction'] ?? 'N/A') ?></p>
        <p class="text-sm text-gray-500">Statut : <?= htmlspecialchars($commande['statut_paiement']) ?></p>
    </div>
    <?php endif; ?>
    
    <div class="flex gap-4">
        <button onclick="window.print()" class="bg-[#5D0D18] text-white px-6 py-2 rounded-lg hover:bg-[#7a1020] transition">
            <i class="bi bi-printer"></i> Imprimer
        </button>
        <?php
        if($role == 'admin') $back = 'dashboard_admin.php';
        elseif($role == 'producteur') $back = 'dashboard_producteur.php';
        else $back = 'mes-commandes.php';
        ?>
        <a href="<?= $back ?>" class="bg-gray-200 px-6 py-2 rounded-lg hover:bg-gray-300 transition text-center text-gray-800">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>
</div>
</body>
</html>