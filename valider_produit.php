<?php
session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: signin.php");
    exit;
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    extract($_GET);
    if (!in_array($action, ['valider', 'refuser'])) {
        header("Location: dashboard_admin.php?msgerr=Action invalide");
        exit;
    }
    include("connexion.php");
    try {
        if ($action == 'valider') {
            $req = $pdo->prepare("UPDATE produit SET est_valide_par_admin = 1, statut_publie = 'Publié' WHERE id_produit = ?");
            $r = $req->execute([$id]);
            if ($r == false) {
                header("Location: dashboard_admin.php?msgerr=Echec de validation du produit");
                exit;
            } else {
                header("Location: dashboard_admin.php?msgs=Produit validé avec succès");
                exit;
            }
        } elseif ($action == 'refuser') {
            $req = $pdo->prepare("UPDATE produit SET est_valide_par_admin = 0, statut_publie = 'Suspendu' WHERE id_produit = ?");
            $r = $req->execute([$id]);
            if ($r == false) {
                header("Location: dashboard_admin.php?msgerr=Echec du refus du produit");
                exit;
            } else {
                header("Location: dashboard_admin.php?msgs=Produit refusé avec succès");
                exit;
            }
        }
    }
    catch(PDOException $e) { die("Erreur traitement produit : " . $e->getMessage()); }
} else {
    header("Location: dashboard_admin.php?msgerr=Paramètres manquants");
    exit;
}
?>
