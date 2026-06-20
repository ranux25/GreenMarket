<?php
session_start();
include('connexion.php');

header('Content-Type: application/json');

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($search) < 1) {
    echo json_encode([]);
    exit();
}

$suggestions = [];
$searchTerm = '%' . $search . '%';

try {
    // Buscar productos
    $stmt = $pdo->prepare("
        SELECT 
            p.id_produit as id,
            p.nom_produit as name,
            'produit' as type,
            p.photo_url as image,
            p.prix_unitaire as price,
            b.nom_boutique as shop_name
        FROM produit p
        JOIN boutique b ON p.id_boutique = b.id_boutique
        WHERE p.est_valide_par_admin = 1 
        AND p.statut_publie = 'Publié'
        AND (p.nom_produit LIKE :search OR p.description LIKE :search)
        LIMIT 5
    ");
    $stmt->execute([':search' => $searchTerm]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $p) {
        $suggestions[] = [
            'id' => $p['id'],
            'name' => htmlspecialchars($p['name']),
            'type' => 'produit',
            'image' => !empty($p['image']) ? $p['image'] : 'IMAGES/default-product.jpg',
            'price' => number_format((float)$p['price'], 0, ',', ' ') . ' DH',
            'shop_name' => htmlspecialchars($p['shop_name'] ?? ''),
            'link' => 'info-produit.php?id=' . $p['id']
        ];
    }
    
    // Buscar tiendas
    $stmt = $pdo->prepare("
        SELECT 
            b.id_boutique as id,
            b.nom_boutique as name,
            'boutique' as type,
            b.image,
            b.description,
            p.nom_entreprise as producer_name
        FROM boutique b
        JOIN producteur p ON b.id_producteur = p.id_producteur
        WHERE p.est_valide_par_admin = 1
        AND (b.nom_boutique LIKE :search OR b.description LIKE :search)
        LIMIT 5
    ");
    $stmt->execute([':search' => $searchTerm]);
    $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($stores as $s) {
        $suggestions[] = [
            'id' => $s['id'],
            'name' => htmlspecialchars($s['name']),
            'type' => 'boutique',
            'image' => !empty($s['image']) ? $s['image'] : 'IMAGES/default-boutique.jpg',
            'description' => htmlspecialchars(substr($s['description'] ?? 'Boutique artisanale', 0, 60)),
            'producer_name' => htmlspecialchars($s['producer_name'] ?? 'Artisan'),
            'link' => 'info-store.php?id=' . $s['id']
        ];
    }
    
    // Ordenar: primero productos, luego tiendas
    usort($suggestions, function($a, $b) {
        $order = ['produit' => 0, 'boutique' => 1];
        return ($order[$a['type']] ?? 2) - ($order[$b['type']] ?? 2);
    });
    
    $suggestions = array_slice($suggestions, 0, 8);
    
    echo json_encode($suggestions);
    
} catch(PDOException $e) {
    error_log("Search Error: " . $e->getMessage());
    echo json_encode([]);
}
?>