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
  /* ========== FOOTER STYLES ========== */
  .main-footer {
    background: linear-gradient(135deg, #5D0D18 0%, #3e0910 100%);
    color: rgba(255,255,255,0.85);
    margin-top: 3rem;
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
    color: #ECE6A6;
    font-family: 'Playfair Display', serif;
    font-size: 1.1rem;
    margin-bottom: 1.2rem;
    position: relative;
    padding-bottom: 0.6rem;
  }
  .footer-col h4::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 35px;
    height: 2px;
    background: #ECE6A6;
    border-radius: 2px;
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
    color: white;
  }
  .logo-accent {
    color: #ECE6A6;
  }
  .footer-desc {
    font-size: 0.9rem;
    line-height: 1.7;
    color: rgba(255,255,255,0.7);
    margin-bottom: 1.3rem;
  }

  .footer-socials {
    display: flex;
    gap: 0.7rem;
  }
  .footer-socials a {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 1.05rem;
    transition: all 0.25s ease;
  }
  .footer-socials a:hover {
    background: #ECE6A6;
    color: #5D0D18;
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
    color: rgba(255,255,255,0.75);
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.25s, padding-left 0.25s;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .footer-col ul li a i {
    font-size: 0.7rem;
    color: #ECE6A6;
    transition: transform 0.25s;
  }
  .footer-col ul li a:hover {
    color: #ECE6A6;
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
    color: rgba(255,255,255,0.75);
    margin-bottom: 0.3rem;
  }
  .footer-contact li i {
    color: #ECE6A6;
    margin-top: 2px;
  }

  /* Newsletter */
  .newsletter-title {
    margin-top: 0.5rem;
  }
  .newsletter-form {
    display: flex;
    border-radius: 50px;
    overflow: hidden;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.15);
  }
  .newsletter-form input {
    flex: 1;
    background: transparent;
    border: none;
    outline: none;
    padding: 0.7rem 1.1rem;
    color: white;
    font-size: 0.85rem;
  }
  .newsletter-form input::placeholder {
    color: rgba(255,255,255,0.5);
  }
  .newsletter-form button {
    background: #ECE6A6;
    color: #5D0D18;
    border: none;
    width: 44px;
    cursor: pointer;
    font-size: 1rem;
    transition: background 0.25s;
  }
  .newsletter-form button:hover {
    background: #fff;
  }

  /* Footer bottom */
  .footer-bottom {
    border-top: 1px solid rgba(255,255,255,0.1);
    padding: 1.2rem 2rem;
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
    color: rgba(255,255,255,0.6);
  }
  .footer-credit i {
    color: #ECE6A6;
  }

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