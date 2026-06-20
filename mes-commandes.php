<?php
session_start();
include("connexion.php");

// Verificar que el usuario esté logueado y sea cliente
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header("Location: signin.php");
    exit();
}

$id_client = $_SESSION['user_id'];
$theme = $_COOKIE['theme'] ?? 'light';

// Recuperar las commandes del cliente
try {
    $req = $pdo->prepare("
        SELECT 
            c.id_commande,
            c.date_commande,
            c.statut_commande,
            c.montant_total,
            c.facture,
            p.reference_transaction,
            p.statut_paiement,
            p.mode_paiement,
            (SELECT COUNT(*) FROM contenir WHERE id_commande = c.id_commande) as nb_produits
        FROM commande c
        LEFT JOIN paiement p ON c.id_paiement = p.id_paiement
        WHERE c.id_client = ?
        ORDER BY c.date_commande DESC
    ");
    $req->execute([$id_client]);
    $commandes = $req->fetchAll(PDO::FETCH_ASSOC);

    // Pour chaque commande, récupérer les produits
    foreach ($commandes as &$commande) {
        $reqProd = $pdo->prepare("
            SELECT 
                ct.quantite,
                ct.prix_unitaire,
                p.nom_produit,
                p.photo_url
            FROM contenir ct
            JOIN produit p ON ct.id_produit = p.id_produit
            WHERE ct.id_commande = ?
        ");
        $reqProd->execute([$commande['id_commande']]);
        $commande['produits'] = $reqProd->fetchAll(PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    $commandes = [];
    $error = "Erreur lors du chargement des commandes : " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes commandes - GreenMarket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #5D0D18;
            --primary-light: #7a1020;
            --secondary: #9FB2AC;
            --gold: #c07a1a;
            --bg: #FFF9EB;
            --bg-card: #ffffff;
            --text-dark: #2C2C2C;
            --text-light: #6B6B6B;
            --border-color: #e5e7eb;
            --shadow-color: rgba(93,13,24,0.08);
            --card-border: #e8ddd0;
            --success: #2e7d32;
            --warning: #f57c00;
            --danger: #c62828;
            --info: #0d47a1;
        }

        [data-theme="dark"] {
            --bg: #2c241e;
            --bg-card: #3d3229;
            --text-dark: #f0e6d8;
            --text-light: #b8a896;
            --border-color: #5a4a3a;
            --card-border: #5a4a3a;
            --shadow-color: rgba(0,0,0,0.3);
            --success: #66bb6a;
            --warning: #ffa726;
            --danger: #ef5350;
            --info: #42a5f5;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg);
            color: var(--text-dark);
            font-family: 'Lato', sans-serif;
            min-height: 100vh;
            transition: background 0.3s, color 0.3s;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Page header */
        .page-header {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 2.5rem 2.5rem 2rem;
            margin-bottom: 2.5rem;
            border: 1.5px solid var(--card-border);
            box-shadow: 0 4px 16px var(--shadow-color);
            transition: all 0.3s;
        }
        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .page-header h1 i {
            color: var(--gold);
        }
        .page-header p {
            color: var(--text-light);
            margin-top: 0.5rem;
            font-size: 0.95rem;
        }

        /* Status badges */
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: inline-block;
        }
        .badge-en-attente { background: #fff3e0; color: #e65100; }
        .badge-confirmee { background: #e3f2fd; color: #0d47a1; }
        .badge-expediee { background: #e8f5e9; color: #1b5e20; }
        .badge-livree { background: #c8e6c9; color: #1b5e20; }
        .badge-annulee { background: #ffebee; color: #c62828; }
        .badge-paye { background: #e8f5e9; color: #1b5e20; }
        .badge-refuse { background: #ffebee; color: #c62828; }
        .badge-rembourse { background: #f3e5f5; color: #6a1b9a; }
        .badge-en-attente-paiement { background: #fff3e0; color: #e65100; }

        [data-theme="dark"] .badge-en-attente { background: #4a2d1a; color: #ffa726; }
        [data-theme="dark"] .badge-confirmee { background: #1a2d4a; color: #64b5f6; }
        [data-theme="dark"] .badge-expediee { background: #1a3d1a; color: #66bb6a; }
        [data-theme="dark"] .badge-livree { background: #1a4a1a; color: #81c784; }
        [data-theme="dark"] .badge-annulee { background: #4a1a1a; color: #ef5350; }
        [data-theme="dark"] .badge-paye { background: #1a3d1a; color: #66bb6a; }
        [data-theme="dark"] .badge-refuse { background: #4a1a1a; color: #ef5350; }
        [data-theme="dark"] .badge-rembourse { background: #2a1a3d; color: #ab47bc; }
        [data-theme="dark"] .badge-en-attente-paiement { background: #4a2d1a; color: #ffa726; }

        /* Order card */
        .order-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1.5px solid var(--card-border);
            box-shadow: 0 4px 12px var(--shadow-color);
            transition: all 0.3s;
        }
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px var(--shadow-color);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1rem;
        }
        .order-id {
            font-size: 0.85rem;
            color: var(--text-light);
        }
        .order-id strong {
            color: var(--text-dark);
        }
        .order-date {
            font-size: 0.85rem;
            color: var(--text-light);
        }

        .order-products {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .order-product {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem;
            border-radius: 10px;
            background: var(--bg);
            transition: background 0.3s;
        }
        .order-product-img {
            width: 60px;
            height: 60px;
            border-radius: 10px;
            object-fit: cover;
            background: var(--border-color);
            flex-shrink: 0;
        }
        .order-product-info {
            flex: 1;
        }
        .order-product-name {
            font-weight: 600;
            font-size: 0.9rem;
        }
        .order-product-detail {
            font-size: 0.8rem;
            color: var(--text-light);
        }
        .order-product-price {
            font-weight: 700;
            color: var(--primary);
            font-size: 0.95rem;
            flex-shrink: 0;
        }
        [data-theme="dark"] .order-product-price {
            color: var(--gold);
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-color);
        }
        .order-total {
            font-size: 1.1rem;
            font-weight: 700;
        }
        .order-total span {
            color: var(--primary);
            font-size: 1.3rem;
        }
        [data-theme="dark"] .order-total span {
            color: var(--gold);
        }

        .order-actions {
            display: flex;
            gap: 0.75rem;
        }
        .btn-outline {
            padding: 0.4rem 1rem;
            border-radius: 8px;
            border: 2px solid var(--primary);
            background: transparent;
            color: var(--primary);
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-outline:hover {
            background: var(--primary);
            color: #fff;
        }
        [data-theme="dark"] .btn-outline {
            border-color: var(--gold);
            color: var(--gold);
        }
        [data-theme="dark"] .btn-outline:hover {
            background: var(--gold);
            color: var(--bg);
        }

        .btn-primary {
            padding: 0.4rem 1rem;
            border-radius: 8px;
            border: none;
            background: var(--primary);
            color: #fff;
            font-weight: 600;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
        }
        [data-theme="dark"] .btn-primary {
            background: var(--gold);
            color: var(--bg);
        }
        [data-theme="dark"] .btn-primary:hover {
            background: #c4903a;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--bg-card);
            border-radius: 20px;
            border: 1.5px solid var(--card-border);
        }
        .empty-state i {
            font-size: 4rem;
            color: var(--text-light);
            opacity: 0.4;
        }
        .empty-state h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            margin: 1rem 0 0.5rem;
            color: var(--text-dark);
        }
        .empty-state p {
            color: var(--text-light);
            margin-bottom: 1.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container { padding: 1rem; }
            .page-header { padding: 1.5rem; }
            .page-header h1 { font-size: 1.6rem; }
            .order-header { flex-direction: column; }
            .order-footer { flex-direction: column; align-items: stretch; }
            .order-actions { justify-content: center; }
            .order-product { flex-wrap: wrap; }
        }

        @media (max-width: 480px) {
            .order-product-img { width: 50px; height: 50px; }
            .order-product-price { font-size: 0.85rem; }
        }
    </style>
</head>
<body>

    <!-- ===== HEADER ===== -->
    <?php include 'header.php'; ?>

    <!-- ===== CONTENU PRINCIPAL ===== -->
    <div class="container">
        
        <!-- En-tête -->
        <div class="page-header">
            <h1>
                <i class="bi bi-box-seam"></i>
                Mes commandes
            </h1>
            <p>Retrouvez l'historique complet de vos achats sur GreenMarket</p>
        </div>

        <!-- Liste des commandes -->
        <?php if (empty($commandes)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h3>Aucune commande</h3>
                <p>Vous n'avez pas encore passé de commande sur GreenMarket.</p>
                <a href="produits.php" class="btn-primary" style="display:inline-block;padding:0.7rem 2rem;font-size:1rem;">
                    Découvrir les produits
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($commandes as $cmd): ?>
            <div class="order-card">
                <!-- En-tête commande -->
                <div class="order-header">
                    <div>
                        <div class="order-id">
                            <strong>Commande #<?php echo str_pad($cmd['id_commande'], 6, '0', STR_PAD_LEFT); ?></strong>
                        </div>
                        <div class="order-date">
                            <i class="bi bi-calendar3"></i>
                            <?php echo date('d/m/Y à H:i', strtotime($cmd['date_commande'])); ?>
                        </div>
                    </div>
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;align-items:center;">
                        <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $cmd['statut_commande'])); ?>">
                            <?php echo $cmd['statut_commande']; ?>
                        </span>
                        <?php if ($cmd['statut_paiement']): ?>
                        <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $cmd['statut_paiement'])); ?>">
                            <?php echo $cmd['statut_paiement']; ?>
                        </span>
                        <?php endif; ?>
                        <span style="font-size:0.75rem;color:var(--text-light);">
                            <?php echo $cmd['nb_produits']; ?> article<?php echo $cmd['nb_produits'] > 1 ? 's' : ''; ?>
                        </span>
                    </div>
                </div>

                <!-- Produits -->
                <div class="order-products">
                    <?php foreach ($cmd['produits'] as $prod): ?>
                    <div class="order-product">
                        <img src="<?php echo htmlspecialchars($prod['photo_url'] ?? 'IMAGES/default-product.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($prod['nom_produit']); ?>"
                             class="order-product-img"
                             onerror="this.src='IMAGES/default-product.jpg'">
                        <div class="order-product-info">
                            <div class="order-product-name"><?php echo htmlspecialchars($prod['nom_produit']); ?></div>
                            <div class="order-product-detail">
                                Quantité : <?php echo $prod['quantite']; ?>
                            </div>
                        </div>
                        <div class="order-product-price">
                            <?php echo number_format($prod['prix_unitaire'] * $prod['quantite'], 2, ',', ' '); ?> DH
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Footer -->
                <div class="order-footer">
                    <div class="order-total">
                        Total : <span><?php echo number_format($cmd['montant_total'], 2, ',', ' '); ?> DH</span>
                    </div>
                    <div class="order-actions">
                        <a href="facture.php?id=<?php echo $cmd['id_commande']; ?>" class="btn-outline">
                            <i class="bi bi-file-pdf"></i> Facture
                        </a>
                        <?php if ($cmd['statut_commande'] === 'En attente'): ?>
                        <button onclick="annulerCommande(<?php echo $cmd['id_commande']; ?>)" class="btn-outline" style="border-color:var(--danger);color:var(--danger);">
                            <i class="bi bi-x-circle"></i> Annuler
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <!-- ===== FOOTER ===== -->
    <?php include 'footer.php'; ?>

    <script>
        // Fonction pour annuler une commande
        function annulerCommande(id) {
            if (confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) {
                fetch('annuler_commande.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id_commande=' + id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Commande annulée avec succès !');
                        location.reload();
                    } else {
                        alert('Erreur : ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Une erreur est survenue.');
                    console.error(error);
                });
            }
        }
    </script>

</body>
</html>