<?php
try {
    $pdo = new PDO("mysql:host=localhost;port=3306;dbname=greenmarket;charset=utf8mb4", "root", "");
}
catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>