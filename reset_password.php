<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    extract($_POST);
    $err = [];

    if (!isset($token) || empty($token)) $err['token'] = "Token invalide ou expiré";
    if (!isset($password) || empty($password)) $err['password'] = "Veuillez entrer un mot de passe";
    elseif (strlen($password) < 6) $err['password'] = "Le mot de passe doit contenir au moins 6 caractères";
    if (!isset($confpassword) || empty($confpassword)) $err['confpassword'] = "Veuillez confirmer le mot de passe";
    elseif (isset($password) && $password !== $confpassword) $err['confpassword'] = "Les mots de passe ne correspondent pas";

    if (empty($err)) {
        #NOTE : fonctionnalite reset par token necessite un systeme d'email
        #Pour l'instant on redirige vers signin avec un message
        $err['token'] = "Fonctionnalité de réinitialisation par email non encore configurée. Contactez l'administrateur.";
    }
}

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GreenMarket | Réinitialiser le mot de passe</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container d-flex flex-column justify-content-center align-items-center min-vh-100">
    <div class="d-flex gap-2 align-items-center mb-4">
      <img src="IMAGES/logo.png" alt="logo" style="width:60px;height:60px;object-fit:cover;" class="rounded-circle"
           onerror="this.src='https://placehold.co/60x60?text=GM'">
      <h1 class="h3 mb-0 fw-bold" style="color:#5d0d18;">GreenMarket</h1>
    </div>

    <div class="card p-4 shadow-sm w-100" style="max-width:450px; border-radius:12px; border:none;">
      <div class="text-center mb-4">
        <h2 class="fw-bold h4">Réinitialiser le mot de passe</h2>
      </div>

      <?php if (isset($err['token'])) echo "<div class='alert alert-danger'>" . htmlspecialchars($err['token']) . "</div>"; ?>

      <form method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <?php if (isset($err['password'])) echo "<div style='color:red;font-size:.85rem;'>" . $err['password'] . "</div>"; ?>
        <div class="mb-3">
          <label class="form-label small fw-medium">Mot de passe</label>
          <input type="password" class="form-control" name="password" placeholder="Créez un mot de passe fort">
        </div>

        <?php if (isset($err['confpassword'])) echo "<div style='color:red;font-size:.85rem;'>" . $err['confpassword'] . "</div>"; ?>
        <div class="mb-3">
          <label class="form-label small fw-medium">Confirmer le mot de passe</label>
          <input type="password" class="form-control" name="confpassword" placeholder="Confirmez votre mot de passe">
        </div>

        <input type="submit" class="btn w-100 p-2 fw-bold" style="background:#5d0d18; color:white; border-radius:6px;"
               value="Mettre à jour le mot de passe">
      </form>

      <div class="text-center mt-3">
        <a href="signin.php" style="color:#5d0d18; font-size:.9rem;">← Retour à la connexion</a>
      </div>
    </div>
  </div>
</body>
</html>
