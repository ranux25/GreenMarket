<?php
require_once 'connexion.php';

echo "=== Migration des mots de passe vers password_hash ===\n\n";

$stmt = $pdo->query("SELECT id_admin, mot_de_passe FROM administrateur");
while ($row = $stmt->fetch()) {
    $current_pwd = $row['mot_de_passe'];

    if (password_get_info($current_pwd)['algo'] === 0) {
        $hashed = password_hash($current_pwd, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE administrateur SET mot_de_passe = ? WHERE id_admin = ?");
        $update->execute([$hashed, $row['id_admin']]);
        echo "✓ Admin ID {$row['id_admin']} mis à jour\n";
    } else {
        echo "→ Admin ID {$row['id_admin']} déjà hashé\n";
    }
}

$stmt = $pdo->query("SELECT id_client, mot_de_passe FROM client");
while ($row = $stmt->fetch()) {
    $current_pwd = $row['mot_de_passe'];

    if (password_get_info($current_pwd)['algo'] === 0) {
        $hashed = password_hash($current_pwd, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE client SET mot_de_passe = ? WHERE id_client = ?");
        $update->execute([$hashed, $row['id_client']]);
        echo "✓ Client ID {$row['id_client']} mis à jour\n";
    } else {
        echo "→ Client ID {$row['id_client']} déjà hashé\n";
    }
}

$stmt = $pdo->query("SELECT id_producteur, mot_de_passe FROM producteur");
while ($row = $stmt->fetch()) {
    $current_pwd = $row['mot_de_passe'];

    if (password_get_info($current_pwd)['algo'] === 0) {
        $hashed = password_hash($current_pwd, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE producteur SET mot_de_passe = ? WHERE id_producteur = ?");
        $update->execute([$hashed, $row['id_producteur']]);
        echo "✓ Producteur ID {$row['id_producteur']} mis à jour\n";
    } else {
        echo "→ Producteur ID {$row['id_producteur']} déjà hashé\n";
    }
}

echo "\n=== Migration terminée ===\n";