<?php
session_start();
include("connexion.php");

// Detectar tema guardado (por defecto claro)
$theme = $_COOKIE['theme'] ?? 'light';

// Recuperar estadísticas reales desde la BD
try {
    $req = $pdo->query("SELECT COUNT(*) as total FROM client");
    $total_clients = $req->fetch(PDO::FETCH_ASSOC)['total'];

    $req = $pdo->query("SELECT COUNT(*) as total FROM producteur WHERE est_valide_par_admin = 1");
    $total_producteurs = $req->fetch(PDO::FETCH_ASSOC)['total'];

    $req = $pdo->query("SELECT COUNT(*) as total FROM produit WHERE est_valide_par_admin = 1");
    $total_produits = $req->fetch(PDO::FETCH_ASSOC)['total'];

    $req = $pdo->query("SELECT COUNT(*) as total FROM boutique");
    $total_boutiques = $req->fetch(PDO::FETCH_ASSOC)['total'];
} catch(PDOException $e) {
    $total_clients = 0;
    $total_producteurs = 0;
    $total_produits = 0;
    $total_boutiques = 0;
}
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>GreenMarket – À propos de notre mission</title>

    <!-- Librerías -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* ===== VARIABLES DE TEMA GLOBAL ===== */
        :root {
            --primary: #5D0D18;
            --primary-light: #7a1020;
            --secondary: #9FB2AC;
            --secondary-dark: #7a9490;
            --secondary-light: #c8d8d4;
            --gold: #c07a1a;
            --wine: #5d0d18;
            --wine-dark: #3e0910;
            --wine-light: #8c2030;
            --wine-pale: #f5e6e8;
            --bg: #FFF9EB;
            --bg-light: #f5f0e8;
            --bg-card: #ffffff;
            --bg-input: #ffffff;
            --bg-white: #fffdf7;
            --text-dark: #2C2C2C;
            --text-light: #6B6B6B;
            --text-muted: #6B6B6B;
            --text-wine: #2a1a1c;
            --text-muted-wine: #6b5055;
            --border-color: #e5e7eb;
            --card-border: #e8ddd0;
            --shadow-color: rgba(93, 13, 24, 0.08);
            --shadow-md: rgba(93, 13, 24, 0.18);
            --header-bg: #5D0D18;
            --header-text: #ffffff;
            --header-bg-hover: rgba(255, 255, 255, 0.15);
            --header-shadow: rgba(93, 13, 24, 0.18);
            --header-border: rgba(255, 255, 255, 0.05);
            --dropdown-bg: #ffffff;
            --dropdown-text: #2C2C2C;
            --dropdown-hover: #FFF9EB;
            --dropdown-divider: #f0f0f0;
            --footer-bg: #3A0A10;
            --footer-text: #d4b8a0;
            --footer-link: #c4a890;
            --footer-link-hover: #ffffff;
            --page-header-bg: #f5ede0;
            --page-header-text: #5D0D18;
            --page-header-sub: #6B6B6B;
            --page-header-border: rgba(93, 13, 24, 0.08);
            --page-header-eyebrow: #5D0D18;
            --page-header-eyebrow-bg: rgba(93, 13, 24, 0.06);
            --cream: #fff9eb;
            --modal-bg: #fffdf7;
            --modal-shadow: rgba(0,0,0,0.2);
            --modal-border: #e8ddd0;
            --modal-input-bg: #fff9eb;
        }

        /* ===== TEMA OSCURO ===== */
        [data-theme="dark"] {
            --primary: #8a6048;
            --primary-light: #a0785a;
            --secondary: #6d4c3a;
            --secondary-dark: #5a4a3a;
            --secondary-light: #8a7a6a;
            --gold: #d4a85c;
            --wine: #2c241e;
            --wine-dark: #1a1410;
            --wine-light: #4d3d32;
            --wine-pale: #3d3229;
            --bg: #2c241e;
            --bg-light: #3d3229;
            --bg-card: #3d3229;
            --bg-input: #4d3d32;
            --bg-white: #3d3229;
            --text-dark: #f0e6d8;
            --text-light: #b8a896;
            --text-muted: #b8a896;
            --text-wine: #f0e6d8;
            --text-muted-wine: #b8a896;
            --border-color: #5a4a3a;
            --card-border: #5a4a3a;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --shadow-md: rgba(0, 0, 0, 0.4);
            --header-bg: #1a1410;
            --header-text: #f0e6d8;
            --header-bg-hover: rgba(240, 230, 216, 0.12);
            --header-shadow: rgba(0, 0, 0, 0.4);
            --header-border: rgba(240, 230, 216, 0.05);
            --dropdown-bg: #3d3229;
            --dropdown-text: #f0e6d8;
            --dropdown-hover: #4d3d32;
            --dropdown-divider: #5a4a3a;
            --footer-bg: #1a1410;
            --footer-text: #b8a896;
            --footer-link: #b8a896;
            --footer-link-hover: #f0e6d8;
            --page-header-bg: #3d3229;
            --page-header-text: #f0e6d8;
            --page-header-sub: #b8a896;
            --page-header-border: rgba(240, 230, 216, 0.08);
            --page-header-eyebrow: #d4a85c;
            --page-header-eyebrow-bg: rgba(240, 230, 216, 0.06);
            --cream: #f0e6d8;
            --modal-bg: #3d3229;
            --modal-shadow: rgba(0,0,0,0.4);
            --modal-border: #5a4a3a;
            --modal-input-bg: #4d3d32;
        }

        /* ===== STYLES BASE ===== */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: var(--bg);
            color: var(--text-dark);
            font-family: 'Lato', sans-serif;
            overflow-x: hidden;
            min-height: 100vh;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        h1, h2, h3, .playfair { font-family: 'Playfair Display', serif; }

        /* ===== PAGE HEADER ===== */
        .page-header {
            background: var(--page-header-bg);
            padding: 4rem 2.5rem 3rem;
            position: relative;
            overflow: hidden;
            transition: background-color 0.3s ease;
        }
        .page-header::before {
            content: '';
            position: absolute;
            right: -80px;
            top: -80px;
            width: 420px;
            height: 420px;
            border: 55px solid var(--page-header-border);
            border-radius: 50%;
            transition: border-color 0.3s ease;
        }
        .page-header::after {
            content: '';
            position: absolute;
            left: 4%;
            bottom: -70px;
            width: 240px;
            height: 240px;
            border: 40px solid rgba(159,178,172,.10);
            border-radius: 50%;
        }
        .header-inner {
            position: relative;
            z-index: 1;
        }
        .header-eyebrow {
            display: inline-block;
            font-size: .72rem;
            font-weight: 600;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: var(--page-header-eyebrow);
            background: var(--page-header-eyebrow-bg);
            padding: 0.3rem 1rem;
            border-radius: 999px;
            margin-bottom: .9rem;
            transition: color 0.3s ease, background-color 0.3s ease;
        }
        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.6rem;
            font-weight: 700;
            line-height: 1.05;
            color: var(--page-header-text);
            margin-bottom: .7rem;
            transition: color 0.3s ease;
        }
        .page-header h1 em {
            font-style: italic;
            color: var(--gold);
            display: block;
        }
        .page-header p {
            color: var(--page-header-sub);
            font-size: .93rem;
            font-weight: 300;
            max-width: 500px;
            transition: color 0.3s ease;
        }

        /* ===== ANIMATIONS ===== */
        .reveal {
            opacity: 0;
            transform: translateY(35px);
            transition: opacity 0.7s ease, transform 0.7s ease;
        }
        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }
        .reveal-left {
            opacity: 0;
            transform: translateX(-40px);
            transition: opacity 0.7s ease, transform 0.7s ease;
        }
        .reveal-left.visible {
            opacity: 1;
            transform: translateX(0);
        }
        .reveal-right {
            opacity: 0;
            transform: translateX(40px);
            transition: opacity 0.7s ease, transform 0.7s ease;
        }
        .reveal-right.visible {
            opacity: 1;
            transform: translateX(0);
        }

        /* ===== CONTAINER ===== */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem;
        }

        /* ===== MISSION SECTION ===== */
        .mission-section {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 3rem;
            margin-bottom: 2.5rem;
            border: 1.5px solid var(--card-border);
            box-shadow: 0 4px 16px var(--shadow-color);
            text-align: center;
            transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .mission-text h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
            transition: color 0.3s ease;
        }
        .mission-text h2::after {
            content: '';
            display: block;
            width: 60px;
            height: 3px;
            background: var(--secondary);
            margin: 0.5rem auto 0;
        }
        .mission-text p {
            font-size: 1rem;
            line-height: 1.7;
            color: var(--text-muted);
            margin-bottom: 1rem;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            transition: color 0.3s ease;
        }
        .mission-quote {
            font-style: italic;
            font-size: 1.1rem;
            color: var(--primary);
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--card-border);
            transition: color 0.3s ease, border-color 0.3s ease;
        }

        /* ===== VALUES SECTION ===== */
        .values-section {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 3rem;
            margin-bottom: 2.5rem;
            border: 1.5px solid var(--card-border);
            box-shadow: 0 4px 16px var(--shadow-color);
            transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .values-section h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: var(--primary);
            text-align: center;
            margin-bottom: 2.5rem;
            transition: color 0.3s ease;
        }
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        .value-card {
            text-align: center;
            padding: 1.5rem;
        }
        .value-icon {
            width: 70px;
            height: 70px;
            background: var(--wine-pale);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2rem;
            transition: background-color 0.3s ease;
        }
        .value-card h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
            transition: color 0.3s ease;
        }
        .value-card p {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.5;
            transition: color 0.3s ease;
        }

        /* ===== IMPACT SECTION ===== */
        .impact-section {
            background: linear-gradient(135deg, var(--wine) 0%, var(--wine-dark) 100%);
            border-radius: 20px;
            padding: 3rem;
            color: var(--cream);
            margin-bottom: 2.5rem;
            box-shadow: 0 4px 16px var(--shadow-md);
            transition: background 0.3s ease, color 0.3s ease;
        }
        .impact-section h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            text-align: center;
            margin-bottom: 2rem;
            color: var(--cream);
        }
        .impact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }
        .impact-number {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            display: block;
            color: var(--gold);
        }
        .impact-label {
            font-size: 0.85rem;
            opacity: 0.8;
        }

        /* ===== TEAM SECTION ===== */
        .team-section {
            margin-bottom: 2.5rem;
        }
        .team-section h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: var(--primary);
            text-align: center;
            margin-bottom: 2.5rem;
            transition: color 0.3s ease;
        }
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 2rem;
        }
        .team-card {
            text-align: center;
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: 20px;
            border: 1.5px solid var(--card-border);
            transition: transform 0.3s ease, box-shadow 0.3s ease, background-color 0.3s ease, border-color 0.3s ease;
            box-shadow: 0 4px 16px var(--shadow-color);
        }
        .team-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 36px var(--shadow-md);
        }
        .team-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--secondary-light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 2.5rem;
            overflow: hidden;
            transition: background-color 0.3s ease;
        }
        .team-card h3 {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            color: var(--primary);
            margin-bottom: 0.25rem;
            transition: color 0.3s ease;
        }
        .team-role {
            font-size: 0.75rem;
            color: var(--secondary-dark);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
            transition: color 0.3s ease;
        }
        .team-bio {
            font-size: 0.8rem;
            color: var(--text-muted);
            line-height: 1.4;
            transition: color 0.3s ease;
        }

        /* ===== CTA SECTION ===== */
        .cta-section {
            background: var(--bg-card);
            border-radius: 20px;
            padding: 3rem;
            text-align: center;
            border: 1.5px solid var(--card-border);
            box-shadow: 0 4px 16px var(--shadow-color);
            transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .cta-section h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 1rem;
            transition: color 0.3s ease;
        }
        .cta-section p {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
            transition: color 0.3s ease;
        }
        .btn-cta {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 999px;
            font-family: 'Lato', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
        }
        .btn-cta:hover {
            background: var(--primary-light);
            transform: translateY(-2px);
        }

        /* ===== FOOTER ===== */
        footer {
            background: var(--footer-bg);
            color: var(--footer-text);
            margin-top: 2rem;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .container {
                padding: 2rem 1rem;
            }
            .values-section, .impact-section, .cta-section, .mission-section {
                padding: 2rem 1.5rem;
            }
            .page-header {
                padding: 2.5rem 1.2rem 2rem;
            }
            .page-header h1 {
                font-size: 2.4rem;
            }
            .team-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
            .values-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {
            .values-grid {
                grid-template-columns: 1fr;
            }
            .impact-grid {
                grid-template-columns: 1fr 1fr;
            }
            .page-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>

<!-- ===== HEADER ===== -->
<?php include 'header.php'; ?>

<!-- ===== PAGE HEADER ===== -->
<div class="page-header">
    <div class="header-inner">
        <div class="header-eyebrow">🇲🇦 Notre histoire &amp; mission</div>
        <h1>À propos de<br><em>GreenMarket</em></h1>
        <p>Découvrez qui nous sommes et notre engagement pour l'artisanat marocain.</p>
    </div>
</div>

<!-- ===== CONTENU PRINCIPAL ===== -->
<div class="container">

    <!-- Mission -->
    <div class="mission-section reveal">
        <div class="mission-text">
            <h2>Notre mission</h2>
            <p>GreenMarket est né d'une conviction profonde : les trésors artisanaux du Maroc méritent d'être valorisés et accessibles à tous. Notre mission est de créer un pont entre les artisans marocains, perpétuant des savoir-faire ancestraux, et les amateurs d'authenticité à travers le monde.</p>
            <p>Nous croyons fermement que chaque pièce artisanale raconte une histoire, celle de ses créateurs, de sa région et de ses traditions. C'est pourquoi nous mettons un point d'honneur à sélectionner des produits de qualité exceptionnelle, tout en garantissant une rémunération équitable aux coopératives et artisans qui nous font confiance.</p>
            <div class="mission-quote">
                🌿 "Valoriser l'artisanat marocain, un produit à la fois"
            </div>
        </div>
    </div>

    <!-- Valeurs -->
    <div class="values-section reveal-left">
        <h2>Nos valeurs</h2>
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">🤝</div>
                <h3>Commerce équitable</h3>
                <p>Nous garantissons une rémunération juste et transparente à tous nos artisans partenaires.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">🏺</div>
                <h3>Authenticité</h3>
                <p>Chaque produit est authentique, fabriqué selon les méthodes traditionnelles transmises depuis des générations.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">🌿</div>
                <h3>Durabilité</h3>
                <p>Nous privilégions les matériaux naturels et les procédés respectueux de l'environnement.</p>
            </div>
            <div class="value-card">
                <div class="value-icon">❤️</div>
                <h3>Passion</h3>
                <p>Derrière chaque produit, il y a la passion d'un artisan qui met tout son cœur dans son travail.</p>
            </div>
        </div>
    </div>

    <!-- Impact -->
    <div class="impact-section reveal">
        <h2>Notre impact en chiffres</h2>
        <div class="impact-grid">
            <div>
                <span class="impact-number"><?php echo $total_producteurs; ?>+</span>
                <span class="impact-label">Artisans partenaires</span>
            </div>
            <div>
                <span class="impact-number"><?php echo $total_boutiques; ?>+</span>
                <span class="impact-label">Boutiques</span>
            </div>
            <div>
                <span class="impact-number"><?php echo $total_produits; ?>+</span>
                <span class="impact-label">Produits artisanaux</span>
            </div>
            <div>
                <span class="impact-number"><?php echo $total_clients; ?>+</span>
                <span class="impact-label">Clients satisfaits</span>
            </div>
        </div>
    </div>

    <!-- Équipe -->
    <div class="team-section reveal-right">
        <h2>L'équipe GreenMarket</h2>
        <div class="team-grid">
            <div class="team-card">
                <div class="team-avatar">👩‍💼</div>
                <h3>Rania Ellaghmich</h3>
                <div class="team-role">Fondatrice &amp; Directrice</div>
                <p class="team-bio">Passionnée par l'artisanat marocain, elle a créé GreenMarket pour valoriser le savoir-faire de sa région natale.</p>
            </div>
            <div class="team-card">
                <div class="team-avatar">👨‍💻</div>
                <h3>Omar El Gazi</h3>
                <div class="team-role">Directeur Technique</div>
                <p class="team-bio">Expert en développement web, il assure le bon fonctionnement de la plateforme GreenMarket.</p>
            </div>
            <div class="team-card">
                <div class="team-avatar">👨‍🎨</div>
                <h3>Mohamed Bouchar</h3>
                <div class="team-role">Directeur Artistique</div>
                <p class="team-bio">Expert en artisanat traditionnel, il sélectionne chaque produit avec un soin particulier.</p>
            </div>
            <div class="team-card">
                <div class="team-avatar">👩‍💼</div>
                <h3>Rania El Morabit</h3>
                <div class="team-role">Responsable Logistique</div>
                <p class="team-bio">Elle assure que chaque commande arrive en parfait état chez nos clients.</p>
            </div>
        </div>
    </div>

    <!-- CTA -->
    <div class="cta-section reveal">
        <h2>Rejoignez l'aventure GreenMarket</h2>
        <p>Que vous soyez artisan, producteur ou amateur d'artisanat, il y a une place pour vous chez GreenMarket.</p>
        <button class="btn-cta" onclick="window.location.href='signin.php'">Devenir partenaire →</button>
    </div>

</div>

<!-- ===== FOOTER ===== -->
<?php include 'footer.php'; ?>

<!-- ===== SCRIPTS ===== -->
<script>
    // ===== SCROLL REVEAL =====
    function initReveal() {
        const elements = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) e.target.classList.add('visible');
            });
        }, { threshold: 0.1 });
        elements.forEach(el => observer.observe(el));
    }

    // ===== FORZAR INPUT DE BÚSQUEDA VACÍO =====
    // Esto asegura que el campo de búsqueda esté vacío en esta página
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('headerSearch');
        if (searchInput) {
            // Si el input tiene el email del usuario, lo limpiamos
            searchInput.value = '';
        }
        initReveal();
    });
</script>

</body>
</html>