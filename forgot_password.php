<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    extract($_POST);
    $err = [];

    if (!isset($ancien_mdp) || empty($ancien_mdp)) $err['ancien_mdp'] = "Veuillez entrer votre ancien mot de passe";
    if (!isset($nouveau_mdp) || empty($nouveau_mdp)) $err['nouveau_mdp'] = "Veuillez entrer un nouveau mot de passe";
    elseif (strlen($nouveau_mdp) < 6) $err['nouveau_mdp'] = "Le mot de passe doit contenir au moins 6 caractères";
    if (!isset($confirmer_mdp) || empty($confirmer_mdp)) $err['confirmer_mdp'] = "Veuillez confirmer le nouveau mot de passe";
    elseif (isset($nouveau_mdp) && $nouveau_mdp !== $confirmer_mdp) $err['confirmer_mdp'] = "Les mots de passe ne correspondent pas";

    if (empty($err)) {
        include("connexion.php");
        try {
            $role = $_SESSION['user_role'];
            if ($role == 'client') {
                $table = 'client';
                $col_id = 'id_client';
            } elseif ($role == 'producteur') {
                $table = 'producteur';
                $col_id = 'id_producteur';
            } else {
                $table = 'administrateur';
                $col_id = 'id_admin';
            }

            $req = $pdo->prepare("SELECT mot_de_passe FROM $table WHERE $col_id = ?");
            $req->execute([$_SESSION['user_id']]);
            $user = $req->fetch(PDO::FETCH_ASSOC);

            if (empty($user)) {
                $err['ancien_mdp'] = "Utilisateur introuvable";
            } elseif (!password_verify($ancien_mdp, $user['mot_de_passe'])) {
                $err['ancien_mdp'] = "Ancien mot de passe incorrect";
            } else {
                $nouveau_hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);
                $reqU = $pdo->prepare("UPDATE $table SET mot_de_passe = ? WHERE $col_id = ?");
                $r = $reqU->execute([$nouveau_hash, $_SESSION['user_id']]);
                if ($r == false) {
                    $err['general'] = "Echec de la modification du mot de passe";
                } else {
                    session_unset();
                    session_destroy();
                    header("Location: signin.php?msgs=Mot de passe modifié avec succès. Veuillez vous reconnecter.");
                    exit;
                }
            }
        }
        catch(PDOException $e) { die("Erreur modification mot de passe : " . $e->getMessage()); }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GreenMarket | Changer mot de passe</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-cream: #FFF9EB;
      --accent-sage: #9FB2AC;
      --primary-burgundy: #5D0D18;
      --primary-hover: #44070F;
      --text-dark: #2D251E;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Jost', sans-serif; }
    body {
      background-color: var(--bg-cream);
      color: var(--text-dark);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 40px 20px;
    }
    .card {
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 10px 40px rgba(93,13,24,0.1);
      padding: 40px;
      width: 100%;
      max-width: 450px;
    }
    .brand { display: flex; align-items: center; gap: 8px; color: var(--primary-burgundy); margin-bottom: 20px; }
    .brand span { font-family: 'Playfair Display', serif; font-size: 1.3rem; font-weight: 700; }
    h2 { font-family: 'Playfair Display', serif; font-size: 1.6rem; color: var(--primary-burgundy); margin-bottom: 6px; }
    .subtitle { color: #70665f; font-size: 0.9rem; margin-bottom: 24px; }
    .input-group { position: relative; margin-bottom: 6px; }
    .input-group i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #90857d; }
    .input-group input {
      width: 100%; padding: 12px 15px 12px 42px;
      background: #fcfaf5; border: 1px solid rgba(159,178,172,0.4);
      border-radius: 10px; outline: none; font-size: 0.9rem; font-family: 'Jost', sans-serif;
    }
    .input-group input:focus { border-color: var(--primary-burgundy); box-shadow: 0 0 0 2px rgba(93,13,24,0.08); }
    .err { color: red; font-size: 0.8rem; margin-bottom: 10px; margin-left: 2px; }
    .btn-submit {
      width: 100%; padding: 12px; background: var(--primary-burgundy);
      color: white; border: none; border-radius: 10px; font-size: 0.9rem;
      font-weight: 500; cursor: pointer; transition: all 0.3s; margin-top: 10px;
    }
    .btn-submit:hover { background: var(--primary-hover); transform: translateY(-2px); }
    .back-link { display: block; text-align: center; margin-top: 16px; color: var(--primary-burgundy); font-size: 0.9rem; text-decoration: none; }
    .back-link:hover { text-decoration: underline; }
  </style>
</head>
<body>
<div class="card">
  <div class="brand">
    <img src="IMAGES/logo.png" alt="Logo" style="height:35px;" onerror="this.src='https://placehold.co/35x35?text=GM'">
    <span>GreenMarket</span>
  </div>
  <h2>Changer mot de passe</h2>
  <p class="subtitle">Entrez votre ancien mot de passe puis le nouveau.</p>

  <?php if (isset($err['general'])) echo "<div class='err'>" . $err['general'] . "</div>"; ?>

  <form method="POST">
    <?php if (isset($err['ancien_mdp'])) echo "<div class='err'>" . $err['ancien_mdp'] . "</div>"; ?>
    <div class="input-group">
      <i class="bi bi-lock"></i>
      <input type="password" name="ancien_mdp" placeholder="Ancien mot de passe">
    </div>

    <?php if (isset($err['nouveau_mdp'])) echo "<div class='err'>" . $err['nouveau_mdp'] . "</div>"; ?>
    <div class="input-group">
      <i class="bi bi-lock-fill"></i>
      <input type="password" name="nouveau_mdp" placeholder="Nouveau mot de passe">
    </div>

    <?php if (isset($err['confirmer_mdp'])) echo "<div class='err'>" . $err['confirmer_mdp'] . "</div>"; ?>
    <div class="input-group">
      <i class="bi bi-shield-lock"></i>
      <input type="password" name="confirmer_mdp" placeholder="Confirmer nouveau mot de passe">
    </div>

    <button type="submit" class="btn-submit">Modifier le mot de passe</button>
  </form>

  <a href="<?php
    $role = $_SESSION['user_role'] ?? 'client';
    $pages = ['admin' => 'dashboard_admin.php', 'producteur' => 'dashboard_producteur.php', 'client' => 'dashboard_client.php'];
    echo $pages[$role] ?? 'accueil.php';
  ?>" class="back-link">← Retour au tableau de bord</a>
</div>
</body>
</html>
