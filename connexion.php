<?php
try {
    $pdo = new PDO("mysql:host=localhost;port=3307;dbname=greenmarket","root","");
}
catch(PDOException $e) {
    echo "Erreur :",$e->getMessage();
}
?>