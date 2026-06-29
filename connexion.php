<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=Gr_market;charset=utf8mb4", "root", "root");
}
catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>