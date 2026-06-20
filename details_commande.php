<?php
session_start();

#verifier que l'utilisateur est connecte et est producteur
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'producteur') {
    header('Location: signin.php');
    exit;
}

#connexion a la base de donnees
include('connexion.php');

$theme = $_COOKIE['theme'] ?? 'light';
$id_commande = intval($_GET['id'] ?? 0);

if ($id_commande <= 0) {
    header('Location: dashboard_producteur.php?error=id_invalide');
    exit;
}

try {
    #recuperer les informations de la commande
    $stmt = $pdo->prepare("
        SELECT c.*, cl.nom_client, cl.email
        FROM commande c
        JOIN client cl ON c.id_client = cl.id_client
        WHERE c.id_commande = ?
    ");
    $stmt->execute([$id_commande]);
    $commande = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$commande) {
        header('Location: dashboard_producteur.php?error=commande_non_trouvee');
        exit;
    }
    
    #recuperer les produits de la commande qui appartiennent au producteur
    $stmt = $pdo->prepare("
        SELECT co.*, p.nom_produit, p.photo_url, b.nom_boutique,
               p.id_boutique, b.id_producteur
        FROM contenir co
        JOIN produit p ON co.id_produit = p.id_produit
        JOIN boutique b ON p.id_boutique = b.id_boutique
        WHERE co.id_commande = ? AND b.id_producteur = ?
    ");
    $stmt->execute([$id_commande, $_SESSION['user_id']]);
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    #verifier si le producteur a des produits dans cette commande
    if (empty($produits)) {
        header('Location: dashboard_producteur.php?error=commande_non_autorisee');
        exit;
    }
    
    #calculer le sous-total des produits du producteur
    $sous_total = 0;
    foreach ($produits as $p) {
        $sous_total += $p['prix_unitaire'] * $p['quantite'];
    }
    
    #calculer le nombre total d'articles du producteur
    $nb_articles = array_sum(array_column($produits, 'quantite'));
    
} catch(PDOException $e) {
    error_log("Error details commande: " . $e->getMessage());
    header('Location: dashboard_producteur.php?error=erreur_bd');
    exit;
}

// Fonction pour afficher le statut avec la bonne couleur
function getStatutBadge($statut) {
    $classes = [
        'En attente' => 'badge-warning',
        'Confirmée' => 'badge-info',
        'Expédiée' => 'badge-primary',
        'Livrée' => 'badge-success',
        'Annulée' => 'badge-danger'
    ];
    return $classes[$statut] ?? 'badge-warning';
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de la commande #<?php echo str_pad($id_commande, 6, '0', STR_PAD_LEFT); ?> - GreenMarket</title>
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
            --border-color: #e8ddd0;
            --shadow-color: rgba(93,13,24,0.08);
        }
        [data-theme="dark"] {
            --primary: #8a6048;
            --secondary: #6d4c3a;
            --bg: #2c241e;
            --bg-card: #3d3229;
            --text-dark: #f0e6d8;
            --text-light: #b8a896;
            --border-color: #5a4a3a;
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
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }
        .page-header {
            background: var(--primary);
            padding: 2rem 2.5rem;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 700;
        }
        .page-header p {
            color: rgba(255,255,255,0.7);
            margin-top: 0.3rem;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-light);
            text-decoration: none;
            margin-bottom: 1rem;
            transition: color 0.3s;
            font-weight: 600;
        }
        .btn-back:hover { color: var(--primary); }

        .card {
            background: var(--bg-card);
            border-radius: 16px;
            border: 1.5px solid var(--border-color);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 16px var(--shadow-color);
            transition: all 0.3s;
        }
        .card:hover {
            box-shadow: 0 8px 24px var(--shadow-color);
        }

        .badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-primary { background: #cce5ff; color: #004085; }
        [data-theme="dark"] .badge-success { background: #1a3d2a; color: #81c784; }
        [data-theme="dark"] .badge-info { background: #1a2a3d; color: #64b5f6; }
        [data-theme="dark"] .badge-warning { background: #3d2a1a; color: #ffb74d; }
        [data-theme="dark"] .badge-danger { background: #3d1a1a; color: #ef9a9a; }
        [data-theme="dark"] .badge-primary { background: #1a2a3d; color: #64b5f6; }

        .product-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        .product-item:last-child { border-bottom: none; }
        .product-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            flex-shrink: 0;
        }
        .product-item .info { flex: 1; }
        .product-item .info .name {
            font-weight: 600;
            color: var(--text-dark);
        }
        .product-item .info .detail {
            font-size: 0.8rem;
            color: var(--text-light);
        }
        .product-item .price {
            font-weight: 700;
            color: var(--primary);
            font-size: 0.95rem;
            text-align: right;
        }
        [data-theme="dark"] .product-item .price { color: var(--gold); }

        .btn {
            padding: 0.5rem 1.2rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-success { background: #27ae60; color: white; }
        .btn-success:hover { background: #219653; transform: translateY(-2px); }
        .btn-info { background: #2980b9; color: white; }
        .btn-info:hover { background: #1f6fa5; transform: translateY(-2px); }
        .btn-danger { background: #c0392b; color: white; }
        .btn-danger:hover { background: #a93226; transform: translateY(-2px); }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-light); transform: translateY(-2px); }
        .btn-wine { background: var(--primary); color: white; }
        .btn-wine:hover { background: var(--primary-light); transform: translateY(-2px); }

        .action-group { display: flex; gap: 0.75rem; flex-wrap: wrap; }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 0.4rem 0;
            font-size: 0.95rem;
        }
        .total-row .label { color: var(--text-light); }
        .grand-total {
            font-weight: 700;
            font-size: 1.15rem;
            padding-top: 0.8rem;
            margin-top: 0.5rem;
            border-top: 2px solid var(--border-color);
            color: var(--primary);
        }
        [data-theme="dark"] .grand-total { color: var(--gold); }

        .status-section {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        @media (max-width: 640px) {
            .page-header { padding: 1.5rem; }
            .page-header h1 { font-size: 1.5rem; }
            .card { padding: 1rem; }
            .product-item { flex-wrap: wrap; }
            .product-item .price { width: 100%; text-align: left; padding-left: 68px; }
            .status-section { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<div class="page-header">
    <div style="max-width:1000px;margin:0 auto;">
        <h1><i class="bi bi-box-seam"></i> Détails de la commande</h1>
        <p>#<?php echo str_pad($id_commande, 6, '0', STR_PAD_LEFT); ?></p>
    </div>
</div>

<div class="container">

    <a href="dashboard_producteur.php" class="btn-back">
        <i class="bi bi-arrow-left"></i> Retour au tableau de bord
    </a>

    <!-- Informations générales -->
    <div class="card">
        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <div>
                <h3 style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--primary);margin-bottom:0.3rem;">
                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($commande['nom_client']); ?>
                </h3>
                <p style="font-size:0.85rem;color:var(--text-light);">
                    <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($commande['email']); ?>
                </p>
            </div>
            <div style="text-align:right;">
                <p style="font-size:0.85rem;color:var(--text-light);">
                    <i class="bi bi-calendar3"></i> <?php echo date('d/m/Y H:i', strtotime($commande['date_commande'])); ?>
                </p>
                <div class="status-section" style="margin-top:0.3rem;">
                    <span class="badge <?php echo getStatutBadge($commande['statut_commande']); ?>">
                        <?php echo $commande['statut_commande']; ?>
                    </span>
                    <?php if (!empty($commande['adresse_livraison']) && $commande['adresse_livraison'] !== 'Retrait en boutique'): ?>
                    <span style="font-size:0.8rem;color:var(--text-light);">
                        <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($commande['adresse_livraison']); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Produits commandés -->
    <div class="card">
        <h3 style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--primary);margin-bottom:1rem;">
            <i class="bi bi-box-seam"></i> Vos produits commandés
            <span style="font-size:0.8rem;color:var(--text-light);font-weight:400;">
                (<?php echo $nb_articles; ?> article<?php echo $nb_articles > 1 ? 's' : ''; ?>)
            </span>
        </h3>

        <?php foreach ($produits as $p): ?>
        <div class="product-item">
            <img src="<?php echo htmlspecialchars($p['photo_url'] ?? 'IMAGES/default-product.jpg'); ?>" 
                 alt="<?php echo htmlspecialchars($p['nom_produit']); ?>"
                 onerror="this.src='IMAGES/default-product.jpg'">
            <div class="info">
                <div class="name"><?php echo htmlspecialchars($p['nom_produit']); ?></div>
                <div class="detail">
                    <i class="bi bi-shop"></i> <?php echo htmlspecialchars($p['nom_boutique']); ?>
                </div>
                <div class="detail">
                    Quantité : <?php echo $p['quantite']; ?>
                    × <?php echo number_format($p['prix_unitaire'], 2); ?> DH
                </div>
            </div>
            <div class="price">
                <?php echo number_format($p['prix_unitaire'] * $p['quantite'], 2); ?> DH
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Total -->
        <div style="border-top:2px solid var(--border-color);padding-top:1rem;margin-top:0.5rem;">
            <div class="total-row">
                <span class="label">Sous-total (vos produits)</span>
                <span><?php echo number_format($sous_total, 2); ?> DH</span>
            </div>
            <div class="total-row grand-total">
                <span>Total de vos produits</span>
                <span><?php echo number_format($sous_total, 2); ?> DH</span>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="card">
        <h3 style="font-family:'Playfair Display',serif;font-size:1.1rem;color:var(--primary);margin-bottom:1rem;">
            <i class="bi bi-gear"></i> Gérer la commande
        </h3>
        <div class="action-group">
            <?php if ($commande['statut_commande'] === 'En attente'): ?>
            <button class="btn btn-success" onclick="updateStatut(<?php echo $id_commande; ?>, 'Confirmée')">
                ✅ Confirmer
            </button>
            <?php endif; ?>

            <?php if ($commande['statut_commande'] === 'Confirmée'): ?>
            <button class="btn btn-info" onclick="updateStatut(<?php echo $id_commande; ?>, 'Expédiée')">
                📦 Expédier
            </button>
            <?php endif; ?>

            <?php if ($commande['statut_commande'] === 'Expédiée'): ?>
            <button class="btn btn-success" onclick="updateStatut(<?php echo $id_commande; ?>, 'Livrée')">
                ✅ Livrer
            </button>
            <?php endif; ?>

            <?php if (in_array($commande['statut_commande'], ['En attente', 'Confirmée', 'Expédiée'])): ?>
            <button class="btn btn-danger" onclick="updateStatut(<?php echo $id_commande; ?>, 'Annulée')">
                ❌ Annuler
            </button>
            <?php endif; ?>

            <button class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Imprimer
            </button>
        </div>
    </div>

</div>

<div class="toast" id="toast"></div>

<script>
function showToast(msg, isError = false) {
    const toast = document.getElementById('toast');
    toast.innerHTML = msg;
    if (isError) {
        toast.style.background = '#c0392b';
    } else {
        toast.style.background = '#5d0d18';
    }
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
        toast.style.background = '#5d0d18';
    }, 3000);
}

function updateStatut(id, statut) {
    if (confirm('Voulez-vous vraiment changer le statut de cette commande en "' + statut + '" ?')) {
        // Désactiver tous les boutons pendant le traitement
        document.querySelectorAll('.action-group .btn').forEach(btn => {
            btn.disabled = true;
            btn.style.opacity = '0.6';
        });
        
        fetch('update_commande.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id_commande=' + id + '&statut=' + encodeURIComponent(statut)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('✅ ' + data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('❌ ' + data.message, true);
                document.querySelectorAll('.action-group .btn').forEach(btn => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                });
            }
        })
        .catch(() => {
            showToast('❌ Erreur de connexion au serveur', true);
            document.querySelectorAll('.action-group .btn').forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '1';
            });
        });
    }
}
</script>

<style>
    .toast {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        background: #5d0d18;
        color: white;
        padding: 0.8rem 1.5rem;
        border-radius: 8px;
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.3s;
        z-index: 9999;
        box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    }
    .toast.show {
        transform: translateY(0);
        opacity: 1;
    }
</style>

</body>
</html>