-- ============================================
-- Script de mise a jour des mots de passe
-- Convertit les mots de passe en clair vers password_hash() (bcrypt)
-- compatible avec password_verify() en PHP
--
-- A executer UNE SEULE FOIS dans phpMyAdmin (onglet SQL)
-- ============================================

-- ---- ADMINISTRATEUR ----
-- admin@greenmarket.com : admin123
UPDATE administrateur SET mot_de_passe = '$2y$12$Vl5LeR0.PeHeEvoHFV.gtec8FtgGwKBle3ZAqzC5PAAW87Vx64HwS' WHERE email = 'admin@greenmarket.com';

-- ---- CLIENT ----
-- tous les clients : pass1234
UPDATE client SET mot_de_passe = '$2y$12$V//CQt8/q0ZZNu5nZlduseQQrMu6IRpSBqDo4z2.rK6AcTc0852L.' WHERE mot_de_passe = 'pass1234';

-- ---- PRODUCTEUR ----
-- la plupart des producteurs : 123456
UPDATE producteur SET mot_de_passe = '$2y$12$2Duuc.uumFnpaD4P.CIx5eyPGoISHEOUkBv1MOG0k1cu9LUb5LHbu' WHERE mot_de_passe = '123456';
-- ahmadi@gmail.com : ahmadi2004
UPDATE producteur SET mot_de_passe = '$2y$12$/CQs.NWcW4BFwUD/PtGW1ug5dW9oChCrRC0tZKxBCGxhqNxZh6596' WHERE email = 'ahmadi@gmail.com';
-- test@gmail.com : test1234
UPDATE producteur SET mot_de_passe = '$2y$12$6ciH3LhgujTNYkj5TpLhnuxPEgIyP.c35wzq1gwq5EKkfzWw58T3K' WHERE email = 'test@gmail.com';
-- image@image.com : image1
UPDATE producteur SET mot_de_passe = '$2y$12$EKH8iYt/yRqVgxfxla/HC.8bUmAWiF/BXURPcqe5tBnZhRjHamN.W' WHERE email = 'image@image.com';

-- ============================================
-- Verification (decommentez pour voir le resultat)
-- ============================================
-- SELECT id_admin, email, mot_de_passe FROM administrateur;
-- SELECT id_client, email, mot_de_passe FROM client;
-- SELECT id_producteur, email, mot_de_passe FROM producteur;
