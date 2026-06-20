<footer class="main-footer">
  <div class="footer-top">
    <div class="footer-container">

      <div class="footer-col footer-brand">
        <div class="footer-logo">
          <img src="IMAGES/logo.png" alt="GreenMarket Logo" class="footer-logo-img" onerror="this.src='https://placehold.co/40x40/ffffff/5D0D18?text=GM'"/>
          <span class="footer-logo-text">Green<span class="logo-accent">Market</span></span>
        </div>
        <p class="footer-desc">
          La marketplace qui connecte les producteurs locaux aux consommateurs.
          Des produits frais, sains et responsables, livrés directement de la ferme à votre table.
        </p>
        <div class="footer-socials">
          <a href="#" title="Facebook"><i class="bi bi-facebook"></i></a>
          <a href="#" title="Instagram"><i class="bi bi-instagram"></i></a>
          <a href="#" title="Twitter / X"><i class="bi bi-twitter-x"></i></a>
          <a href="#" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
        </div>
      </div>

      <div class="footer-col">
        <h4>Navigation</h4>
        <ul>
          <li><a href="accueil.php"><i class="bi bi-chevron-right"></i> Accueil</a></li>
          <li><a href="store.php"><i class="bi bi-chevron-right"></i> Boutiques</a></li>
          <li><a href="produits.php"><i class="bi bi-chevron-right"></i> Produits</a></li>
          <li><a href="apropos.php"><i class="bi bi-chevron-right"></i> À propos</a></li>
          <li><a href="panier.php"><i class="bi bi-chevron-right"></i> Mon Panier</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Informations</h4>
        <ul>
          <li><a href="#"><i class="bi bi-chevron-right"></i> Conditions générales</a></li>
          <li><a href="#"><i class="bi bi-chevron-right"></i> Politique de confidentialité</a></li>
          <li><a href="#"><i class="bi bi-chevron-right"></i> Livraison & Retours</a></li>
          <li><a href="#"><i class="bi bi-chevron-right"></i> FAQ</a></li>
          <li><a href="signin.php"><i class="bi bi-chevron-right"></i> Devenir producteur</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4>Contact</h4>
        <ul class="footer-contact">
          <li><i class="bi bi-geo-alt-fill"></i> Tetouan, Maroc</li>
          <li><i class="bi bi-envelope-fill"></i> contact@greenmarket.com</li>
          <li><i class="bi bi-telephone-fill"></i> +212 6 00 00 00 00</li>
        </ul>
        <h4 class="newsletter-title">Newsletter</h4>
        <form class="newsletter-form" onsubmit="return false;">
          <input type="email" placeholder="Votre email" required>
          <button type="submit" title="S'abonner"><i class="bi bi-send-fill"></i></button>
        </form>
      </div>

    </div>
  </div>

  <div class="footer-bottom">
    <div class="footer-bottom-container">
      <p>&copy; <?php echo date('Y'); ?> GreenMarket. Tous droits réservés.</p>
      <p class="footer-credit">Fait avec <i class="bi bi-heart-fill"></i> pour un avenir plus vert</p>
    </div>
  </div>
</footer>

<style>
  /* ========== VARIABLES DE TEMA PARA FOOTER ========== */
  :root {
    --footer-bg-start: #5D0D18;
    --footer-bg-end: #3e0910;
    --footer-text: rgba(255,255,255,0.85);
    --footer-text-light: rgba(255,255,255,0.75);
    --footer-text-muted: rgba(255,255,255,0.7);
    --footer-heading: #ECE6A6;
    --footer-border: rgba(255,255,255,0.1);
    --footer-input-bg: rgba(255,255,255,0.1);
    --footer-input-border: rgba(255,255,255,0.15);
    --footer-input-text: #ffffff;
    --footer-input-placeholder: rgba(255,255,255,0.5);
    --footer-btn-bg: #ECE6A6;
    --footer-btn-color: #5D0D18;
    --footer-social-bg: rgba(255,255,255,0.1);
    --footer-social-color: #ffffff;
    --footer-social-hover-bg: #ECE6A6;
    --footer-social-hover-color: #5D0D18;
    --footer-logo-text: #ffffff;
    --footer-logo-accent: #ECE6A6;
    --footer-heart-color: #ECE6A6;
    --footer-arrow-color: #ECE6A6;
  }

  /* ========== TEMA OSCURO BEIGE ========== */
  [data-theme="dark"] {
    --footer-bg-start: #1a1410;
    --footer-bg-end: #0d0a08;
    --footer-text: rgba(240,230,216,0.85);
    --footer-text-light: rgba(240,230,216,0.75);
    --footer-text-muted: rgba(240,230,216,0.7);
    --footer-heading: #d4a85c;
    --footer-border: rgba(240,230,216,0.1);
    --footer-input-bg: rgba(240,230,216,0.08);
    --footer-input-border: rgba(240,230,216,0.15);
    --footer-input-text: #f0e6d8;
    --footer-input-placeholder: rgba(240,230,216,0.4);
    --footer-btn-bg: #d4a85c;
    --footer-btn-color: #2c241e;
    --footer-social-bg: rgba(240,230,216,0.08);
    --footer-social-color: #f0e6d8;
    --footer-social-hover-bg: #d4a85c;
    --footer-social-hover-color: #2c241e;
    --footer-logo-text: #f0e6d8;
    --footer-logo-accent: #d4a85c;
    --footer-heart-color: #d4a85c;
    --footer-arrow-color: #d4a85c;
  }

  /* ========== FOOTER STYLES ========== */
  .main-footer {
    background: linear-gradient(135deg, var(--footer-bg-start) 0%, var(--footer-bg-end) 100%);
    color: var(--footer-text);
    margin-top: 3rem;
    transition: background 0.3s ease, color 0.3s ease;
  }

  .footer-top {
    padding: 3.5rem 2rem 2rem;
  }

  .footer-container {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1.6fr 1fr 1fr 1.2fr;
    gap: 2.5rem;
  }

  .footer-col h4 {
    color: var(--footer-heading);
    font-family: 'Playfair Display', serif;
    font-size: 1.1rem;
    margin-bottom: 1.2rem;
    position: relative;
    padding-bottom: 0.6rem;
    transition: color 0.3s ease;
  }
  .footer-col h4::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 35px;
    height: 2px;
    background: var(--footer-heading);
    border-radius: 2px;
    transition: background 0.3s ease;
  }

  /* Marca */
  .footer-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 1rem;
  }
  .footer-logo-img {
    height: 42px;
    width: auto;
    border-radius: 8px;
  }
  .footer-logo-text {
    font-family: 'Playfair Display', serif;
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--footer-logo-text);
    transition: color 0.3s ease;
  }
  .logo-accent {
    color: var(--footer-logo-accent);
    transition: color 0.3s ease;
  }
  .footer-desc {
    font-size: 0.9rem;
    line-height: 1.7;
    color: var(--footer-text-muted);
    margin-bottom: 1.3rem;
    transition: color 0.3s ease;
  }

  .footer-socials {
    display: flex;
    gap: 0.7rem;
  }
  .footer-socials a {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: var(--footer-social-bg);
    color: var(--footer-social-color);
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 1.05rem;
    transition: all 0.25s ease;
  }
  .footer-socials a:hover {
    background: var(--footer-social-hover-bg);
    color: var(--footer-social-hover-color);
    transform: translateY(-3px);
  }

  /* Listas de enlaces */
  .footer-col ul {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
  }
  .footer-col ul li a {
    color: var(--footer-text-light);
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.25s, padding-left 0.25s;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .footer-col ul li a i {
    font-size: 0.7rem;
    color: var(--footer-arrow-color);
    transition: transform 0.25s, color 0.3s ease;
  }
  .footer-col ul li a:hover {
    color: var(--footer-heading);
    padding-left: 4px;
  }
  .footer-col ul li a:hover i {
    transform: translateX(3px);
  }

  /* Contacto */
  .footer-contact {
    margin-bottom: 1.5rem;
  }
  .footer-contact li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 0.9rem;
    color: var(--footer-text-light);
    margin-bottom: 0.3rem;
    transition: color 0.3s ease;
  }
  .footer-contact li i {
    color: var(--footer-heading);
    margin-top: 2px;
    transition: color 0.3s ease;
  }

  /* Newsletter */
  .newsletter-title {
    margin-top: 0.5rem;
  }
  .newsletter-form {
    display: flex;
    border-radius: 50px;
    overflow: hidden;
    background: var(--footer-input-bg);
    border: 1px solid var(--footer-input-border);
    transition: background 0.3s ease, border-color 0.3s ease;
  }
  .newsletter-form input {
    flex: 1;
    background: transparent;
    border: none;
    outline: none;
    padding: 0.7rem 1.1rem;
    color: var(--footer-input-text);
    font-size: 0.85rem;
    transition: color 0.3s ease;
  }
  .newsletter-form input::placeholder {
    color: var(--footer-input-placeholder);
    transition: color 0.3s ease;
  }
  .newsletter-form button {
    background: var(--footer-btn-bg);
    color: var(--footer-btn-color);
    border: none;
    width: 44px;
    cursor: pointer;
    font-size: 1rem;
    transition: all 0.25s ease;
  }
  .newsletter-form button:hover {
    background: var(--footer-social-hover-bg);
    transform: scale(1.05);
  }

  /* Footer bottom */
  .footer-bottom {
    border-top: 1px solid var(--footer-border);
    padding: 1.2rem 2rem;
    transition: border-color 0.3s ease;
  }
  .footer-bottom-container {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: var(--footer-text-muted);
    transition: color 0.3s ease;
  }
  .footer-credit i {
    color: var(--footer-heart-color);
    transition: color 0.3s ease;
  }

  /* ========== RESPONSIVE ========== */
  @media (max-width: 1024px) {
    .footer-container {
      grid-template-columns: 1fr 1fr;
      gap: 2.5rem 2rem;
    }
    .footer-brand {
      grid-column: 1 / -1;
    }
  }

  @media (max-width: 640px) {
    .footer-top {
      padding: 2.5rem 1.2rem 1.5rem;
    }
    .footer-container {
      grid-template-columns: 1fr;
      gap: 2rem;
    }
    .footer-bottom-container {
      flex-direction: column;
      text-align: center;
    }
  }
</style>