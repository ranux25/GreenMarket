<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>GreenMarket | Conditions d'utilisation</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500;600&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="legal.css">

  <style>
    body {
      font-family: 'Jost', sans-serif;
    }
    .brand-title, .legal-title {
      font-family: 'Playfair Display', serif;
      color: #5D0D18;
    }
    .legal-card {
      box-shadow: 0 15px 40px rgba(93, 13, 24, 0.05) !important;
    }
    .legal-scroll::-webkit-scrollbar {
      width: 6px;
    }
    .legal-scroll::-webkit-scrollbar-track {
      background: #fcfaf5;
      border-radius: 10px;
    }
    .legal-scroll::-webkit-scrollbar-thumb {
      background: #9FB2AC;
      border-radius: 10px;
    }
    .section-heading {
      color: #2D251E;
      font-weight: 600;
      margin-top: 20px;
    }
    .brand-logo {
      width: 45px;
      height: 45px;
      object-fit: contain;
    }
  </style>
</head>
<body>

  <div class="container d-flex flex-column justify-content-center align-items-center min-vh-100 py-5">
    
    <div class="d-flex gap-3 align-items-center mb-4">
      <img src="images/logo.png" alt="Logo GreenMarket" class="brand-logo">
      <h1 class="h3 mb-0 brand-title">GreenMarket</h1>
    </div>

    <div class="card p-4 p-md-5 legal-card w-100 shadow-sm">
      
      <div class="text-center mb-4">
        <h2 class="fw-bold h4 mb-2 legal-title">Conditions Générales d'Utilisation</h2>
        <p class="text-muted small">Dernière mise à jour : Mai 2026</p>
      </div>

      <div class="text-start overflow-auto legal-scroll pe-2" style="font-size: 0.95rem; line-height: 1.6;">
        
        <h3 class="h6 section-heading"><i class="bi bi-check-circle-fill me-2 text-theme"></i>1. Acceptation des Conditions</h3>
        <p class="text-muted">Bienvenue sur GreenMarket. En créant un compte (qu'il soit Client ou Producteur) et en utilisant notre plateforme, vous acceptez de vous conformer pleinement à ces conditions d'utilisation. Si vous n'êtes pas d'accord, veuillez ne pas poursuivre l'inscription.</p>

        <h3 class="h6 section-heading"><i class="bi bi-people-fill me-2 text-theme"></i>2. Comptes Utilisateurs (Clients & Producteurs)</h3>
        <p class="text-muted">GreenMarket propose des types de comptes distincts :
          <br>• <strong>Les Clients :</strong> pour naviguer, réserver et acheter les produits du terroir.
          <br>• <strong>Les Producteurs :</strong> pour présenter leur coopérative, lister et vendre leurs récoltes. 
          Chaque membre est responsable de la sécurité de son mot de passe et s'engage à fournir des informations professionnelles exactes.
        </p>

        <h3 class="h6 section-heading"><i class="bi bi-shop me-2 text-theme"></i>3. Règles de la Plateforme</h3>
        <p class="text-muted">GreenMarket agit uniquement comme un pont numérique reliant les producteurs agricoles locaux aux acheteurs solidaires. Les producteurs inscrits sont entièrement et légalement responsables de garantir la fraîcheur, la qualité, la transparence des prix et la conformité de tous les produits mis en ligne.</p>

        <h3 class="h6 section-heading"><i class="bi bi-shield-exclamation me-2 text-theme"></i>4. Limitation de Responsabilité</h3>
        <p class="text-muted">Nous mettons tout en œuvre pour valoriser le commerce équitable, mais nous ne sommes pas responsables des litiges directs entre acheteurs et vendeurs, des aléas de livraison ou des variations de récoltes. Les transactions sont effectuées en direct et de façon transparente entre les deux parties.</p>

        <h3 class="h6 section-heading"><i class="bi bi-arrow-repeat me-2 text-theme"></i>5. Modifications des Conditions</h3>
        <p class="text-muted">GreenMarket se réserve le droit de modifier ou de remplacer ces conditions à tout moment pour s'adapter aux évolutions de notre réseau. L'utilisation continue de la plateforme implique l'acceptation automatique des règles mises à jour.</p>
        
      </div>

      <hr class="text-muted my-4 opacity-25">

      <div class="text-center">
        <a href="signin.php" class="btn custom-submit-btn w-100 py-2 fw-medium btn-sm" style="border-radius: 12px;" onclick="if(window.opener) { window.close(); return false; }">
          <i class="bi bi-arrow-left me-1"></i> Fermer ou Retourner à l'inscription
        </a>
      </div>

    </div>

  </div>

</body>
</html>