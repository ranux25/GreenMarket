-- ============================================
-- Script de RETOUR (rollback) des mots de passe
-- Restaure les mots de passe en clair d'origine
-- A utiliser UNIQUEMENT si le hash bcrypt ne fonctionne pas
-- A executer dans phpMyAdmin (onglet SQL)
-- ============================================

-- ---- ADMINISTRATEUR ----
UPDATE administrateur SET mot_de_passe = 'admin123' WHERE email = 'admin@greenmarket.com';

-- ---- CLIENT ----
UPDATE client SET mot_de_passe = 'pass1234'  WHERE email = 'youssef.elamrani@gmail.com';
UPDATE client SET mot_de_passe = 'pass1234'  WHERE email = 'fatima.benali@gmail.com';
UPDATE client SET mot_de_passe = 'pass1234'  WHERE email = 'omar.tazi@gmail.com';
UPDATE client SET mot_de_passe = 'pass1234'  WHERE email = 'khadija.moussaoui@gmail.com';
UPDATE client SET mot_de_passe = 'pass1234'  WHERE email = 'mehdi.berrada@gmail.com';
UPDATE client SET mot_de_passe = 'pass1234'  WHERE email = 'nadia.cherkaoui@gmail.com';
UPDATE client SET mot_de_passe = 'pass1234'  WHERE email = 'amine.hajji@gmail.com';
UPDATE client SET mot_de_passe = 'pass1234'  WHERE email = 'samira.idrissi@gmail.com';
UPDATE client SET mot_de_passe = 'pass1234'  WHERE email = 'rachid.ouali@gmail.com';
UPDATE client SET mot_de_passe = 'pass1234'  WHERE email = 'laila.benkirane@gmail.com';
UPDATE client SET mot_de_passe = 'rania2004' WHERE email = 'Rania@gmail.com';
UPDATE client SET mot_de_passe = 'zineb123'  WHERE email = 'zineb@gmail.com';

-- ---- PRODUCTEUR ----
UPDATE producteur SET mot_de_passe = '123456'    WHERE email = 'caftan@gmail.com';
UPDATE producteur SET mot_de_passe = '123456'    WHERE email = 'atlas@gmail.com';
UPDATE producteur SET mot_de_passe = '123456'    WHERE email = 'safi@gmail.com';
UPDATE producteur SET mot_de_passe = '123456'    WHERE email = 'tetouan@gmail.com';
UPDATE producteur SET mot_de_passe = '123456'    WHERE email = 'amazigh@gmail.com';
UPDATE producteur SET mot_de_passe = '123456'    WHERE email = 'lumiere@gmail.com';
UPDATE producteur SET mot_de_passe = '123456'    WHERE email = 'argamane@gmail.com';
UPDATE producteur SET mot_de_passe = '123456'    WHERE email = 'saveurs@gmail.com';
UPDATE producteur SET mot_de_passe = '123456'    WHERE email = 'azilal@gmail.com';
UPDATE producteur SET mot_de_passe = '123456'    WHERE email = 'imperial@gmail.com';
UPDATE producteur SET mot_de_passe = 'ahmadi2004' WHERE email = 'ahmadi@gmail.com';
UPDATE producteur SET mot_de_passe = 'test1234'   WHERE email = 'test@gmail.com';
UPDATE producteur SET mot_de_passe = 'image1'     WHERE email = 'image@image.com';

-- ============================================
-- Verification (decommentez pour voir le resultat)
-- ============================================
-- SELECT id_admin, email, mot_de_passe FROM administrateur;
-- SELECT id_client, email, mot_de_passe FROM client;
-- SELECT id_producteur, email, mot_de_passe FROM producteur;

-- ============================================
-- NOTE IMPORTANTE :
-- Si vous executez ce script, signin.php (qui utilise password_verify())
-- ne pourra plus authentifier personne, car password_verify() exige un
-- hash bcrypt, pas un mot de passe en clair.
-- Pour revenir au fonctionnement normal, re-executez update_passwords.sql
-- ============================================
