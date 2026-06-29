<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 DIAGNOSTIC COMPLET</h1>";

echo "<h2>1. Session</h2>";
session_start();
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (!isset($_SESSION['user_role'])) {
    echo "❌ Session user_role non défini<br>";
} else {
    echo "✅ user_role = " . $_SESSION['user_role'] . "<br>";
}

echo "<h2>2. Inclusion connexion.php</h2>";
$chemin = __DIR__ . '/connexion.php';
echo "Chemin recherché : " . $chemin . "<br>";

if (file_exists($chemin)) {
    echo "✅ Fichier trouvé<br>";
    require_once $chemin;
    echo "✅ Fichier inclus<br>";
} else {
    echo "❌ Fichier non trouvé<br>";
    echo "Recherche dans d'autres dossiers...<br>";
    $fichiers = glob(__DIR__ . '/*/connexion.php');
    foreach ($fichiers as $f) {
        echo "Trouvé : " . $f . "<br>";
    }
}

echo "<h2>3. Test PDO</h2>";
if (isset($pdo) && $pdo instanceof PDO) {
    echo "✅ PDO défini<br>";
    
    try {
        $stmt = $pdo->query("SELECT 1");
        echo "✅ Requête test réussie<br>";
    } catch (Exception $e) {
        echo "❌ Erreur requête : " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ PDO non défini ou incorrect<br>";
}

echo "<h2>4. Paramètre GET</h2>";
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
echo "ID reçu : " . $id . "<br>";

if ($id > 0) {
    echo "✅ ID valide<br>";
    
    echo "<h2>5. Vérification du produit</h2>";
    try {
        $stmt = $pdo->prepare("SELECT * FROM produit WHERE id_produit = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product) {
            echo "✅ Produit trouvé<br>";
            echo "<pre>";
            print_r($product);
            echo "</pre>";
            
            echo "<h2>6. Vérification des références</h2>";
            $tables = [
                'alertes_stock' => 'id_produit',
                'validation_produit' => 'id_produit',
                'validation_commande' => 'id_produit',
                'contenir' => 'id_produit',
                'evaluer' => 'id_produit',
                'favoris' => 'id_produit',
                'panier' => 'id_produit',
                'notification' => 'id_produit'
            ];
            
            foreach ($tables as $table => $colonne) {
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE $colonne = ?");
                    $stmt->execute([$id]);
                    $count = $stmt->fetchColumn();
                    echo "Table $table : $count référence(s)<br>";
                } catch (Exception $e) {
                    echo "❌ Erreur table $table : " . $e->getMessage() . "<br>";
                }
            }
            
            echo "<h2>7. Test de suppression (simulé)</h2>";
            try {
                $stmt = $pdo->prepare("DELETE FROM produit WHERE id_produit = ?");
                echo "✅ Requête DELETE préparée avec succès<br>";
            } catch (Exception $e) {
                echo "❌ Erreur préparation DELETE : " . $e->getMessage() . "<br>";
            }
            
        } else {
            echo "❌ Produit non trouvé pour l'ID $id<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erreur : " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ ID invalide ou manquant<br>";
}

echo "<h2>8. Infos serveur</h2>";
echo "PHP Version : " . phpversion() . "<br>";
echo "Document Root : " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Path : " . __FILE__ . "<br>";
echo "Directory : " . __DIR__ . "<br>";
?>