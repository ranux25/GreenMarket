<?php
$theme       = $_COOKIE['theme'] ?? 'light';
$currentPage = basename($_SERVER['PHP_SELF']);
$dashboardLink = '';
if (isset($_SESSION['user_role'])) {
    if ($_SESSION['user_role'] === 'client')     $dashboardLink = 'dashboard_client.php';
    elseif ($_SESSION['user_role'] === 'producteur') $dashboardLink = 'dashboard_producteur.php';
    elseif ($_SESSION['user_role'] === 'admin')  $dashboardLink = 'dashboard_admin.php';
}

// Charger le nombre d'articles dans le panier depuis la BD
$cartCount = 0;
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client') {
    if (!isset($pdo)) { include_once __DIR__ . '/connexion.php'; }
    try {
        $reqCart = $pdo->prepare("SELECT COALESCE(SUM(quantite), 0) as total FROM panier WHERE id_client = ?");
        $reqCart->execute([$_SESSION['user_id']]);
        $cartCount = (int)$reqCart->fetch(PDO::FETCH_ASSOC)['total'];
    } catch(PDOException $e) { $cartCount = 0; }
}

// Charger les notifications non lues depuis la BD (solo si está conectado)
$notifications = [];
$unreadCount   = 0;
$mesBoutiques = [];

if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    if (!isset($pdo)) { include_once __DIR__ . '/connexion.php'; }
    $role = $_SESSION['user_role'] ?? '';
    $col  = ($role === 'client') ? 'id_client' : 'id_producteur';
    
    // Notifications (solo para clientes y productores)
    if ($role === 'client' || $role === 'producteur') {
        try {
            $reqN = $pdo->prepare("
                SELECT n.id_notification as id, 
                       n.type_notification as type, 
                       n.message as text, 
                       n.date_notification, 
                       n.est_lu as is_read,
                       n.id_produit,
                       p.nom_produit
                FROM notification n
                LEFT JOIN produit p ON n.id_produit = p.id_produit
                WHERE n.$col = ? 
                ORDER BY n.date_notification DESC 
                LIMIT 10
            ");
            $reqN->execute([$_SESSION['user_id']]);
            $notifications = $reqN->fetchAll(PDO::FETCH_ASSOC);
            $unreadCount   = count(array_filter($notifications, fn($n) => !$n['is_read']));
        } catch(PDOException $e) { $notifications = []; $unreadCount = 0; }
    }
    
    // Récupérer les boutiques du producteur (solo si es producteur)
    if ($role === 'producteur') {
        try {
            $reqBoutiques = $pdo->prepare("SELECT id_boutique, nom_boutique FROM boutique WHERE id_producteur = ?");
            $reqBoutiques->execute([$_SESSION['user_id']]);
            $mesBoutiques = $reqBoutiques->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) { $mesBoutiques = []; }
    }
}

// Fonction pour obtenir l'icône selon le type de notification
function getNotifIcon($type) {
    return match($type) {
        'evaluation' => 'bi-star-fill',
        'order'      => 'bi-bag-check',
        'promo'      => 'bi-tag',
        'message'    => 'bi-chat-dots',
        'success'    => 'bi-check-circle',
        'system'     => 'bi-info-circle',
        default      => 'bi-info-circle'
    };
}

function getNotifIconClass($type) {
    return match($type) {
        'evaluation' => 'evaluation',
        'order'      => 'order',
        'promo'      => 'promo',
        'message'    => 'message',
        'success'    => 'success',
        'system'     => 'system',
        default      => 'system'
    };
}

function getNotifTitle($type) {
    return match($type) {
        'evaluation' => '⭐ Nouvelle évaluation',
        'order'      => '🛒 Nouvelle commande',
        'promo'      => '🎉 Promotion',
        'message'    => '💬 Message',
        'success'    => '✅ Succès',
        'system'     => '📢 Information',
        default      => '📢 Notification'
    };
}

// Verificar si el usuario está conectado
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenMarket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* ===== VARIABLES ===== */
        :root {
            --primary:        #5D0D18;
            --primary-light:  #7a1020;
            --secondary:      #9FB2AC;
            --gold:           #c07a1a;
            --bg:             #FFF9EB;
            --bg-light:       #f5f0e8;
            --bg-card:        #ffffff;
            --bg-input:       #ffffff;
            --text-dark:      #2C2C2C;
            --text-light:     #6B6B6B;
            --border-color:   #e5e7eb;
            --shadow-color:   rgba(93,13,24,0.1);
            --header-bg:         #5D0D18;
            --header-text:       #ffffff;
            --header-bg-hover:   rgba(255,255,255,0.15);
            --header-shadow:     rgba(93,13,24,0.18);
            --header-border:     rgba(255,255,255,0.05);
            --dropdown-bg:      #ffffff;
            --dropdown-text:    #2C2C2C;
            --dropdown-hover:   #FFF9EB;
            --dropdown-divider: #f0f0f0;
            --mobile-menu-bg:   #4A0E17;
            --mobile-menu-text: rgba(255,255,255,0.8);
            --mobile-menu-hover:rgba(255,255,255,0.06);
            --suggestions-bg:     #ffffff;
            --suggestions-border: #e8ddd0;
            --suggestions-shadow: rgba(0,0,0,0.15);
            --suggestions-hover:  #FFF9EB;
            --suggestions-text:   #2C2C2C;
            --suggestions-muted:  #6B6B6B;
        }
        [data-theme="dark"] {
            --primary:        #8a6048;
            --primary-light:  #a0785a;
            --secondary:      #6d4c3a;
            --gold:           #d4a85c;
            --bg:             #2c241e;
            --bg-light:       #3d3229;
            --bg-card:        #3d3229;
            --bg-input:       #4d3d32;
            --text-dark:      #f0e6d8;
            --text-light:     #b8a896;
            --border-color:   #5a4a3a;
            --shadow-color:   rgba(0,0,0,0.4);
            --header-bg:        #1a1410;
            --header-text:      #f0e6d8;
            --header-bg-hover:  rgba(240,230,216,0.12);
            --header-shadow:    rgba(0,0,0,0.4);
            --header-border:    rgba(240,230,216,0.05);
            --dropdown-bg:      #3d3229;
            --dropdown-text:    #f0e6d8;
            --dropdown-hover:   #4d3d32;
            --dropdown-divider: #5a4a3a;
            --mobile-menu-bg:    #1a1410;
            --mobile-menu-text:  rgba(240,230,216,0.8);
            --mobile-menu-hover: rgba(240,230,216,0.06);
            --suggestions-bg:     #3d3229;
            --suggestions-border: #5a4a3a;
            --suggestions-shadow: rgba(0,0,0,0.4);
            --suggestions-hover:  #4d3d32;
            --suggestions-text:   #f0e6d8;
            --suggestions-muted:  #b8a896;
        }

        /* ===== HEADER ===== */
        .hdr {
            position: sticky; top: 0; z-index: 1000;
            background: var(--header-bg);
            box-shadow: 0 4px 25px var(--header-shadow);
            border-bottom: 1px solid var(--header-border);
            font-family: 'Lato', sans-serif;
            transition: background .3s;
        }
        .hdr-inner {
            max-width: 1400px; margin: 0 auto;
            display: flex; align-items: center;
            gap: 1.25rem; padding: .85rem 2rem;
        }

        /* Logo */
        .logo { display: flex; align-items: center; gap: 10px; cursor: pointer; flex-shrink: 0; text-decoration: none; }
        .logo img { height: 38px; border-radius: 6px; object-fit: contain; }
        .logo-text { font-family: 'Playfair Display', serif; font-size: 1.45rem; font-weight: 700; color: var(--header-text); }
        .logo-accent { color: var(--secondary); }

        /* Search */
        .search-wrap { flex: 1; position: relative; }
        .search-wrap input {
            width: 100%; padding: .65rem 1rem .65rem 2.8rem;
            border: 1px solid rgba(255,255,255,0.15); border-radius: 12px;
            font-size: .88rem; color: var(--text-dark);
            background: rgba(255,255,255,0.96); outline: none;
            transition: box-shadow .25s;
        }
        [data-theme="dark"] .search-wrap input { background: var(--bg-input); color: var(--text-dark); border-color: var(--border-color); }
        .search-wrap input:focus { box-shadow: 0 0 0 4px rgba(159,178,172,.35); }
        .search-ico {
            position: absolute; left: 1rem; top: 50%; transform: translateY(-50%);
            color: var(--primary); font-size: .95rem; opacity: .7; pointer-events: none;
        }
        [data-theme="dark"] .search-ico { color: var(--gold); }

        /* Suggestions */
        .suggestions {
            position: absolute; top: calc(100% + 8px); left: 0; right: 0;
            background: var(--suggestions-bg); border: 1px solid var(--suggestions-border);
            border-radius: 12px; box-shadow: 0 10px 30px var(--suggestions-shadow);
            z-index: 1050; display: none; max-height: 390px; overflow-y: auto; padding: .4rem 0;
        }
        .suggestions.show { display: block; }
        .sug-item { display: flex; align-items: center; gap: 12px; padding: .6rem 1rem; color: var(--suggestions-text); text-decoration: none; cursor: pointer; transition: background .15s; }
        .sug-item:hover { background: var(--suggestions-hover); }
        .sug-img { width: 36px; height: 36px; border-radius: 8px; object-fit: cover; background: var(--bg-light); flex-shrink: 0; }
        .sug-info { flex: 1; min-width: 0; }
        .sug-name { font-size: .9rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .sug-detail { font-size: .75rem; color: var(--suggestions-muted); display: flex; align-items: center; gap: 5px; }
        .sug-tag { font-size: .6rem; font-weight: 700; text-transform: uppercase; padding: .1rem .45rem; border-radius: 999px; background: var(--secondary); color: #fff; }
        .sug-price { font-weight: 700; font-size: .85rem; color: var(--primary); flex-shrink: 0; }
        [data-theme="dark"] .sug-price { color: var(--gold); }
        .sug-divider { border-top: 1px solid var(--suggestions-border); margin: .3rem 1rem; }
        .sug-empty { padding: 1.5rem; text-align: center; color: var(--suggestions-muted); font-size: .9rem; }

        /* Nav links */
        .nav-links { display: flex; gap: 2rem; align-items: center; flex-shrink: 0; }
        .nav-links a { position: relative; color: var(--header-text); text-decoration: none; font-size: .93rem; font-weight: 500; opacity: .75; padding: .4rem 0; transition: opacity .2s; }
        .nav-links a:hover, .nav-links a.active { opacity: 1; }
        .nav-links a::after { content: ''; position: absolute; bottom: -4px; left: 50%; transform: translateX(-50%); width: 0; height: 4px; background: var(--secondary); border-radius: 50%; transition: width .25s; }
        .nav-links a:hover::after, .nav-links a.active::after { width: 4px; }

        /* Actions */
        .hdr-actions { display: flex; align-items: center; gap: .9rem; flex-shrink: 0; }
        .icon-btn { position: relative; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,.08); color: var(--header-text); border: none; cursor: pointer; font-size: 1.25rem; text-decoration: none; transition: background .2s; }
        .icon-btn:hover { background: var(--header-bg-hover); }

        /* Cart badge */
        .cart-badge { position: absolute; top: -5px; right: -5px; background: var(--gold); color: #fff; font-size: .68rem; font-weight: 700; min-width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; padding: 0 4px; border: 2px solid var(--header-bg); opacity: 0; transform: scale(.5); transition: all .3s cubic-bezier(.34,1.56,.64,1); }
        .cart-badge.show { opacity: 1; transform: scale(1); }

        /* Language */
        .lang-wrap { position: relative; }
        .lang-btn { display: flex; align-items: center; gap: 6px; background: rgba(255,255,255,.08); border: none; color: var(--header-text); padding: .55rem .85rem; border-radius: 10px; cursor: pointer; font-size: .82rem; font-weight: 500; transition: background .2s; }
        .lang-btn:hover { background: var(--header-bg-hover); }
        .lang-arrow { font-size: .72rem; transition: transform .2s; }
        .lang-wrap.open .lang-arrow { transform: rotate(180deg); }
        .lang-drop { position: absolute; right: 0; top: calc(100% + 8px); background: var(--dropdown-bg); border-radius: 12px; box-shadow: 0 10px 30px var(--shadow-color); padding: .4rem; min-width: 150px; z-index: 1020; display: none; flex-direction: column; gap: 2px; animation: fadeDown .2s ease forwards; }
        .lang-wrap.open .lang-drop { display: flex; }
        .lang-opt { display: flex; align-items: center; gap: 9px; padding: .5rem .75rem; border-radius: 8px; font-size: .87rem; color: var(--dropdown-text); border: none; background: none; cursor: pointer; text-align: left; width: 100%; transition: background .15s; }
        .lang-opt:hover { background: var(--dropdown-hover); color: var(--primary); }
        .lang-opt.active { background: rgba(93,13,24,.07); color: var(--primary); font-weight: 700; }
        [data-theme="dark"] .lang-opt.active { background: rgba(240,230,216,.1); color: var(--gold); }

        /* ===== ACCOUNT DROPDOWN ===== */
        .acc-wrap { position: relative; }
        .acc-btn { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 10px; background: rgba(255,255,255,.08); color: var(--header-text); border: none; cursor: pointer; font-size: 1.25rem; transition: background .2s; }
        .acc-btn:hover, .acc-wrap.open .acc-btn { background: var(--header-bg-hover); }

        .notif-dot {
            position: absolute; top: -4px; right: -4px;
            background: #e53e3e; color: #fff;
            font-size: .6rem; font-weight: 700;
            min-width: 16px; height: 16px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            padding: 0 3px;
            border: 2px solid var(--header-bg);
            opacity: 0; transform: scale(.5);
            transition: all .3s cubic-bezier(.34,1.56,.64,1);
        }
        .notif-dot.show { opacity: 1; transform: scale(1); }

        .acc-drop {
            position: absolute; right: 0; top: calc(100% + 10px);
            width: 380px;
            max-width: 95vw;
            background: var(--dropdown-bg);
            border-radius: 14px;
            box-shadow: 0 12px 35px var(--shadow-color);
            display: none; flex-direction: column;
            z-index: 1010;
            overflow: hidden;
            animation: fadeDown .2s ease forwards;
        }
        .acc-wrap.open .acc-drop { display: flex; }

        /* Tabs */
        .acc-tabs {
            display: flex;
            border-bottom: 1px solid var(--dropdown-divider);
            background: var(--dropdown-bg);
        }
        .acc-tab {
            flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px;
            padding: .7rem .5rem;
            font-size: .82rem; font-weight: 600;
            color: var(--text-light);
            background: none; border: none; cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: color .2s, border-color .2s;
            position: relative;
        }
        .acc-tab:hover { color: var(--primary); }
        .acc-tab.active { color: var(--primary); border-bottom-color: var(--primary); }
        [data-theme="dark"] .acc-tab.active { color: var(--gold); border-bottom-color: var(--gold); }
        .tab-badge {
            background: #e53e3e; color: #fff;
            font-size: .58rem; font-weight: 700;
            min-width: 16px; height: 16px;
            border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            padding: 0 3px;
        }

        .acc-panel { display: none; flex-direction: column; max-height: 450px; overflow-y: auto; }
        .acc-panel.active { display: flex; }

        /* Panel Compte */
        .acc-head { padding: .7rem .85rem .65rem; }
        .acc-name { font-weight: 700; color: var(--dropdown-text); font-size: .9rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .acc-role { display: inline-block; font-size: .62rem; font-weight: 700; text-transform: uppercase; background: rgba(93,13,24,.1); color: var(--primary); padding: 1px 6px; border-radius: 4px; margin-top: 3px; }
        [data-theme="dark"] .acc-role { background: rgba(240,230,216,.1); color: var(--gold); }
        .acc-divider { border: 0; border-top: 1px solid var(--dropdown-divider); margin: .3rem 0; }
        .acc-menu-body { padding: .3rem .5rem .5rem; display: flex; flex-direction: column; gap: 2px; }
        .acc-menu-body a { display: flex; align-items: center; gap: 9px; padding: .55rem .65rem; color: var(--dropdown-text); text-decoration: none; font-size: .87rem; border-radius: 8px; transition: background .15s; }
        .acc-menu-body a:hover { background: var(--dropdown-hover); color: var(--primary); }
        [data-theme="dark"] .acc-menu-body a:hover { color: var(--gold); }
        .acc-menu-body a.logout:hover { background: #fff5f5; color: #c0392b; }
        [data-theme="dark"] .acc-menu-body a.logout:hover { background: #4a2d30; color: #e8b8b8; }

        /* ===== SUB-MENU BOUTIQUES ===== */
        .boutique-submenu {
            padding: .2rem .5rem .5rem;
            border-top: 1px solid var(--dropdown-divider);
            margin-top: .2rem;
        }
        .boutique-submenu .sub-label {
            font-size: .65rem;
            font-weight: 600;
            text-transform: uppercase;
            color: var(--text-light);
            padding: .4rem .65rem .2rem;
            letter-spacing: .05em;
            transition: color 0.3s ease;
        }
        .boutique-submenu a {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: .5rem .65rem;
            color: var(--dropdown-text);
            text-decoration: none;
            font-size: .82rem;
            border-radius: 8px;
            transition: background .15s;
        }
        .boutique-submenu a:hover {
            background: var(--dropdown-hover);
            color: var(--primary);
        }
        [data-theme="dark"] .boutique-submenu a:hover {
            color: var(--gold);
        }
        .boutique-submenu a i {
            font-size: .95rem;
            opacity: .7;
        }
        .boutique-submenu .add-boutique {
            color: var(--secondary);
            font-weight: 600;
        }
        .boutique-submenu .add-boutique:hover {
            color: var(--primary);
        }
        [data-theme="dark"] .boutique-submenu .add-boutique:hover {
            color: var(--gold);
        }

        /* ===== NOTIFICATIONS PANEL STYLES ===== */
        .notif-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: .65rem .85rem .5rem;
            font-size: .75rem;
        }
        .notif-header span { font-weight: 700; color: var(--dropdown-text); font-size: .82rem; }
        .notif-mark-all {
            background: none; border: none;
            color: var(--primary); font-size: .75rem;
            cursor: pointer; font-weight: 600;
            padding: 0; transition: opacity .2s;
        }
        [data-theme="dark"] .notif-mark-all { color: var(--gold); }
        .notif-mark-all:hover { opacity: .7; }

        .notif-list {
            max-height: 350px; overflow-y: auto;
            display: flex; flex-direction: column;
        }
        .notif-list::-webkit-scrollbar { width: 4px; }
        .notif-list::-webkit-scrollbar-track { background: transparent; }
        .notif-list::-webkit-scrollbar-thumb { background: var(--secondary); border-radius: 3px; }

        .notif-item {
            display: flex; align-items: flex-start; gap: 10px;
            padding: .65rem .85rem;
            border-bottom: 1px solid var(--dropdown-divider);
            transition: background .15s;
            cursor: pointer;
            position: relative;
        }
        .notif-item:last-child { border-bottom: none; }
        .notif-item:hover { background: var(--dropdown-hover); }
        .notif-item.unread { 
            background: rgba(93,13,24,.04); 
            border-left: 3px solid var(--primary);
        }
        [data-theme="dark"] .notif-item.unread { 
            background: rgba(212,168,92,.06);
            border-left: 3px solid var(--gold);
        }

        .notif-icon {
            width: 36px; height: 36px; min-width: 36px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: .95rem;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .notif-icon.evaluation { background: rgba(192,122,26,0.15); color: var(--gold); }
        .notif-icon.order { background: rgba(93,13,24,.1); color: var(--primary); }
        .notif-icon.promo { background: rgba(192,122,26,.12); color: var(--gold); }
        .notif-icon.system { background: rgba(159,178,172,.2); color: var(--secondary); }
        .notif-icon.message { background: rgba(59,130,246,.1); color: #3b82f6; }
        .notif-icon.success { background: rgba(46,125,50,.1); color: #2e7d32; }
        [data-theme="dark"] .notif-icon.evaluation { background: rgba(212,168,92,.15); color: var(--gold); }
        [data-theme="dark"] .notif-icon.order { background: rgba(212,168,92,.15); color: var(--gold); }
        [data-theme="dark"] .notif-icon.success { background: rgba(102,187,106,.15); color: #66bb6a; }

        .notif-body { flex: 1; min-width: 0; }
        .notif-title { font-size: .82rem; font-weight: 600; color: var(--dropdown-text); line-height: 1.3; margin-bottom: 2px; }
        .notif-text { font-size: .78rem; color: var(--text-light); line-height: 1.5; white-space: pre-wrap; word-wrap: break-word; }
        .notif-text .stars { color: var(--gold); font-weight: 700; }
        .notif-produit-link {
            display: inline-block;
            margin-top: 4px;
            font-size: .7rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        [data-theme="dark"] .notif-produit-link { color: var(--gold); }
        .notif-produit-link:hover { text-decoration: underline; }
        .notif-time { font-size: .65rem; color: var(--suggestions-muted); margin-top: 4px; display: flex; align-items: center; gap: 4px; }
        .notif-unread-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--primary); flex-shrink: 0; margin-top: 8px; }
        [data-theme="dark"] .notif-unread-dot { background: var(--gold); }

        .notif-empty {
            padding: 2.5rem 1rem;
            text-align: center;
            color: var(--suggestions-muted);
            font-size: .88rem;
        }
        .notif-empty i { font-size: 2.2rem; display: block; margin-bottom: .6rem; opacity: .4; }

        .notif-footer {
            padding: .55rem .85rem;
            border-top: 1px solid var(--dropdown-divider);
        }
        .notif-footer a {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            font-size: .8rem;
            font-weight: 600;
            color: var(--primary);
            text-decoration: none;
            transition: opacity .2s;
        }
        [data-theme="dark"] .notif-footer a { color: var(--gold); }
        .notif-footer a:hover { opacity: .75; }

        /* Login link */
        .login-link { display: flex; align-items: center; gap: 8px; background: var(--secondary); color: var(--primary); padding: .6rem 1.25rem; border-radius: 10px; text-decoration: none; font-size: .88rem; font-weight: 700; transition: background .2s, box-shadow .2s; }
        .login-link:hover { background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,.1); }
        [data-theme="dark"] .login-link { color: #f0e6d8; }

        /* Mobile */
        .mob-actions { display: none; align-items: center; gap: .65rem; }
        .mob-btn { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; background: rgba(255,255,255,.08); border: none; border-radius: 10px; color: var(--header-text); font-size: 1.4rem; cursor: pointer; transition: background .2s; }
        .mob-btn:hover { background: var(--header-bg-hover); }
        .mob-menu { max-height: 0; overflow: hidden; background: var(--mobile-menu-bg); transition: max-height .35s cubic-bezier(.32,.94,.6,1); }
        .mob-menu.open { max-height: 700px; }
        .mob-inner { padding: 1rem 1.5rem 1.5rem; display: flex; flex-direction: column; gap: .35rem; }
        .mob-link { display: flex; align-items: center; gap: 11px; padding: .72rem 1rem; color: var(--mobile-menu-text); text-decoration: none; border-radius: 8px; font-size: .96rem; transition: background .15s; }
        .mob-link i { color: var(--secondary); font-size: 1.1rem; width: 20px; }
        .mob-link:hover, .mob-link.active { background: var(--mobile-menu-hover); color: #fff; }
        .mob-div { height: 1px; background: rgba(255,255,255,.08); margin: .55rem 0; }
        .mob-login { display: flex; align-items: center; justify-content: center; gap: 8px; background: var(--secondary); color: var(--primary); padding: .75rem; border-radius: 8px; text-decoration: none; font-weight: 700; margin-top: .5rem; }
        [data-theme="dark"] .mob-login { color: #f0e6d8; }
        .mob-lang-label { font-size: .7rem; color: rgba(255,255,255,.4); text-transform: uppercase; letter-spacing: .08em; margin-bottom: .45rem; }
        .mob-lang-opts { display: flex; gap: .45rem; flex-wrap: wrap; }
        .mob-lang-opt { padding: .42rem .8rem; border-radius: 8px; border: 1px solid rgba(255,255,255,.15); background: rgba(255,255,255,.06); color: rgba(255,255,255,.75); font-size: .83rem; cursor: pointer; transition: background .2s; }
        .mob-lang-opt:hover { background: rgba(255,255,255,.12); color: #fff; }
        .mob-lang-opt.active { background: var(--secondary); color: var(--primary); font-weight: 700; border-color: var(--secondary); }
        .mob-sub-label {
            font-size: .7rem;
            color: rgba(255,255,255,.4);
            text-transform: uppercase;
            letter-spacing: .08em;
            padding: .3rem 1rem .1rem;
        }
        .mob-boutique-link {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: .5rem 1rem .5rem 2.5rem;
            color: rgba(255,255,255,.6);
            text-decoration: none;
            border-radius: 8px;
            font-size: .88rem;
            transition: background .15s;
        }
        .mob-boutique-link:hover {
            background: var(--mobile-menu-hover);
            color: #fff;
        }
        .mob-boutique-link i {
            font-size: .9rem;
            opacity: .6;
        }

        .mob-notif-badge {
            margin-left: auto;
            background: #e53e3e;
            color: #fff;
            font-size: .65rem;
            font-weight: 700;
            padding: 1px 6px;
            border-radius: 999px;
        }

        @keyframes fadeDown {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes spin { from{transform:rotate(0)} to{transform:rotate(360deg)} }

        @media (max-width: 1024px) {
            .nav-links, .hdr-actions { display: none; }
            .mob-actions { display: flex; }
            .hdr-inner { flex-wrap: wrap; padding: .75rem 1.25rem; }
            .search-wrap { order: 3; width: 100%; }
        }
        @media (max-width: 640px) {
            .logo-text { font-size: 1.25rem; }
            .logo img { height: 33px; }
            .acc-drop { width: 320px; right: -60px; }
        }
    </style>
</head>
<body>

<header class="hdr">
    <div class="hdr-inner">

        <!-- Logo -->
        <a class="logo" href="accueil.php">
            <img src="IMAGES/logo.png" alt="GreenMarket"
                 onerror="this.src='https://placehold.co/40x40/5D0D18/ffffff?text=GM'">
            <span class="logo-text">Green<span class="logo-accent">Market</span></span>
        </a>

        <!-- Búsqueda -->
        <div class="search-wrap">
            <i class="bi bi-search search-ico"></i>
            <input type="text" id="headerSearch"
                   placeholder="Rechercher un produit, une boutique..."
                   autocomplete="off">
            <div class="suggestions" id="suggestions"></div>
        </div>

        <!-- Nav desktop -->
        <nav class="nav-links">
            <a href="accueil.php"  class="<?= $currentPage==='accueil.php'  ? 'active':'' ?>">Accueil</a>
            <a href="store.php"    class="<?= $currentPage==='store.php'    ? 'active':'' ?>">Boutiques</a>
            <a href="produits.php" class="<?= $currentPage==='produits.php' ? 'active':'' ?>">Produits</a>
            <a href="apropos.php"  class="<?= $currentPage==='apropos.php'  ? 'active':'' ?>">À propos</a>
        </nav>

        <!-- Actions desktop -->
        <div class="hdr-actions">

            <!-- Carrito (solo para clientes) -->
            <?php if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] === 'client'): ?>
            <a href="panier.php" class="icon-btn" title="Mon panier">
                <i class="bi bi-bag"></i>
                <span class="cart-badge <?= $cartCount > 0 ? 'show' : '' ?>"
                      id="cart-count"><?= $cartCount ?></span>
            </a>
            <?php endif; ?>

            <!-- Idioma -->
            <div class="lang-wrap" id="langWrap">
                <button class="lang-btn" id="langBtn">
                    <span id="curFlag">🇫🇷</span>
                    <span id="curCode" style="font-size:.78rem;letter-spacing:.5px">FR</span>
                    <i class="bi bi-chevron-down lang-arrow"></i>
                </button>
                <div class="lang-drop" id="langDrop">
                    <button class="lang-opt active" data-lang="fr" data-flag="🇫🇷">🇫🇷 &nbsp;Français</button>
                    <button class="lang-opt" data-lang="en" data-flag="🇬🇧">🇬🇧 &nbsp;English</button>
                    <button class="lang-opt" data-lang="ar" data-flag="🇲🇦">🇲🇦 &nbsp;العربية</button>
                    <button class="lang-opt" data-lang="es" data-flag="🇪🇸">🇪🇸 &nbsp;Español</button>
                </div>
            </div>

            <!-- Cuenta / Notificaciones (SOLO SI ESTÁ CONECTADO) -->
            <?php if ($isLoggedIn): ?>
            <div class="acc-wrap" id="accWrap">
                <button class="acc-btn" id="accBtn" title="Mon compte">
                    <i class="bi bi-person-circle"></i>
                    <?php if ($unreadCount > 0): ?>
                    <span class="notif-dot show" id="notifDot">
                        <?= $unreadCount > 9 ? '9+' : $unreadCount ?>
                    </span>
                    <?php else: ?>
                    <span class="notif-dot" id="notifDot"></span>
                    <?php endif; ?>
                </button>

                <div class="acc-drop" id="accDrop">
                    <!-- Pestañas -->
                    <div class="acc-tabs">
                        <button class="acc-tab active" data-tab="compte">
                            <i class="bi bi-person"></i> Compte
                        </button>
                        <button class="acc-tab" data-tab="notifs">
                            <i class="bi bi-bell"></i> Notifications
                            <?php if ($unreadCount > 0): ?>
                            <span class="tab-badge" id="tabBadge"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
                            <?php endif; ?>
                        </button>
                    </div>

                    <!-- Panel: Compte -->
                    <div class="acc-panel active" id="panel-compte">
                        <div class="acc-head">
                            <p class="acc-name"><?= htmlspecialchars($_SESSION['user_nom'] ?? 'Utilisateur') ?></p>
                            <span class="acc-role"><?= ucfirst($_SESSION['user_role'] ?? '') ?></span>
                        </div>
                        <hr class="acc-divider">
                        <div class="acc-menu-body">
                            <a href="<?= $dashboardLink ?>"><i class="bi bi-grid-1x2"></i> Tableau de bord</a>
                            <a href="profile.php"><i class="bi bi-sliders"></i> Paramètres</a>
                            
                            <?php if ($_SESSION['user_role'] === 'client'): ?>
                            <a href="mes-commandes.php"><i class="bi bi-box-seam"></i> Mes commandes</a>
                            <a href="favoris.php"><i class="bi bi-heart"></i> Mes favoris</a>
                            <?php endif; ?>
                            
                            <!-- Mes boutiques para producteur -->
                            <?php if ($_SESSION['user_role'] === 'producteur'): ?>
                            <div class="boutique-submenu">
                                <div class="sub-label"><i class="bi bi-shop"></i> Mes boutiques</div>
                                <?php if (!empty($mesBoutiques)): ?>
                                    <?php foreach ($mesBoutiques as $boutique): ?>
                                    <a href="gerer-boutique.php?id=<?= $boutique['id_boutique'] ?>">
                                        <i class="bi bi-shop"></i> <?= htmlspecialchars($boutique['nom_boutique']) ?>
                                    </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <a href="creer-boutique.php" class="add-boutique">
                                        <i class="bi bi-plus-circle"></i> Créer ma boutique
                                    </a>
                                <?php endif; ?>
                                <a href="creer-boutique.php" style="color:var(--secondary);font-weight:600;margin-top:2px;">
                                    <i class="bi bi-plus-circle"></i> + Nouvelle boutique
                                </a>
                            </div>
                            <hr class="acc-divider">
                            <?php endif; ?>
                            
                            <hr class="acc-divider">
                            <a href="logout.php" class="logout"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
                        </div>
                    </div>

                    <!-- ===== Panel: Notifications ===== -->
                    <div class="acc-panel" id="panel-notifs">
                        <div class="notif-header">
                            <span><i class="bi bi-bell"></i> Notifications</span>
                            <?php if ($unreadCount > 0): ?>
                            <button class="notif-mark-all" id="markAllRead">Tout marquer lu</button>
                            <?php endif; ?>
                        </div>

                        <div class="notif-list" id="notifList">
                            <?php if (empty($notifications)): ?>
                            <div class="notif-empty" id="notifEmpty">
                                <i class="bi bi-bell-slash"></i>
                                Aucune notification pour l'instant
                            </div>
                            <?php else: ?>
                            <?php foreach ($notifications as $n): 
                                $type = $n['type'] ?? 'system';
                                $isEvaluation = $type === 'evaluation';
                            ?>
                            <div class="notif-item <?= !$n['is_read'] ? 'unread' : '' ?>"
                                 data-id="<?= $n['id'] ?>"
                                 data-link="<?= !empty($n['id_produit']) ? 'info-produit.php?id=' . $n['id_produit'] : 'notifications.php' ?>">
                                <div class="notif-icon <?= getNotifIconClass($type) ?>">
                                    <i class="bi <?= getNotifIcon($type) ?>"></i>
                                </div>
                                <div class="notif-body">
                                    <div class="notif-title"><?= getNotifTitle($type) ?></div>
                                    <div class="notif-text">
                                        <?php 
                                        $message = $n['text'];
                                        if ($isEvaluation) {
                                            $message = preg_replace('/(⭐{1,5}☆{0,5})/', '<span class="stars">$1</span>', $message);
                                        }
                                        echo nl2br(htmlspecialchars($message));
                                        ?>
                                    </div>
                                    <?php if (!empty($n['nom_produit'])): ?>
                                    <a href="info-produit.php?id=<?= $n['id_produit'] ?>" class="notif-produit-link">
                                        <i class="bi bi-box-seam"></i> Voir le produit
                                    </a>
                                    <?php endif; ?>
                                    <div class="notif-time">
                                        <i class="bi bi-clock"></i>
                                        <?= isset($n['date_notification']) ? date('d/m/Y H:i', strtotime($n['date_notification'])) : '' ?>
                                    </div>
                                </div>
                                <?php if (!$n['is_read']): ?>
                                <div class="notif-unread-dot"></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <div class="notif-footer">
                            <a href="notifications.php">
                                Voir toutes les notifications <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div><!-- /acc-drop -->
            </div>
            <?php else: ?>
            <!-- Botón de conexión para usuarios no logueados -->
            <a href="signin.php" class="login-link">
                <i class="bi bi-box-arrow-in-right"></i> Connexion
            </a>
            <?php endif; ?>
        </div>

        <!-- Mobile actions -->
        <div class="mob-actions">
            <?php if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] === 'client'): ?>
            <a href="panier.php" class="icon-btn">
                <i class="bi bi-bag"></i>
                <span class="cart-badge <?= $cartCount > 0 ? 'show' : '' ?>"><?= $cartCount ?></span>
            </a>
            <?php endif; ?>
            <button class="mob-btn" id="mobToggle" aria-label="Menu">
                <i class="bi bi-list" id="mobIcon"></i>
            </button>
        </div>
    </div>

    <!-- Menú móvil -->
    <div class="mob-menu" id="mobMenu">
        <div class="mob-inner">
            <a href="accueil.php"  class="mob-link <?= $currentPage==='accueil.php'  ? 'active':'' ?>"><i class="bi bi-house-door"></i> Accueil</a>
            <a href="store.php"    class="mob-link <?= $currentPage==='store.php'    ? 'active':'' ?>"><i class="bi bi-shop"></i> Boutiques</a>
            <a href="produits.php" class="mob-link <?= $currentPage==='produits.php' ? 'active':'' ?>"><i class="bi bi-box-seam"></i> Produits</a>
            <a href="apropos.php"  class="mob-link <?= $currentPage==='apropos.php'  ? 'active':'' ?>"><i class="bi bi-info-circle"></i> À propos</a>
            <div class="mob-div"></div>
            
            <!-- Mes boutiques mobile para productor -->
            <?php if ($isLoggedIn && $_SESSION['user_role'] === 'producteur'): ?>
            <div style="padding:.2rem 1rem">
                <p class="mob-sub-label"><i class="bi bi-shop"></i> Mes boutiques</p>
                <?php if (!empty($mesBoutiques)): ?>
                    <?php foreach ($mesBoutiques as $boutique): ?>
                    <a href="gerer-boutique.php?id=<?= $boutique['id_boutique'] ?>" class="mob-boutique-link">
                        <i class="bi bi-shop"></i> <?= htmlspecialchars($boutique['nom_boutique']) ?>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <a href="creer-boutique.php" class="mob-boutique-link" style="color:var(--secondary);font-weight:600;">
                        <i class="bi bi-plus-circle"></i> Créer ma boutique
                    </a>
                <?php endif; ?>
                <a href="creer-boutique.php" class="mob-boutique-link" style="color:var(--secondary);">
                    <i class="bi bi-plus-circle"></i> + Nouvelle boutique
                </a>
            </div>
            <div class="mob-div"></div>
            <?php endif; ?>
            
            <!-- Favoris para client -->
            <?php if ($isLoggedIn && $_SESSION['user_role'] === 'client'): ?>
            <a href="favoris.php" class="mob-link">
                <i class="bi bi-heart" style="color:var(--gold);"></i> Mes favoris
            </a>
            <?php endif; ?>
            
            <div style="padding:.4rem 1rem">
                <p class="mob-lang-label">Langue / Language</p>
                <div class="mob-lang-opts">
                    <button class="mob-lang-opt active" data-lang="fr">🇫🇷 FR</button>
                    <button class="mob-lang-opt" data-lang="en">🇬🇧 EN</button>
                    <button class="mob-lang-opt" data-lang="ar">🇲🇦 AR</button>
                    <button class="mob-lang-opt" data-lang="es">🇪🇸 ES</button>
                </div>
            </div>
            <div class="mob-div"></div>
            
            <?php if ($isLoggedIn): ?>
            <div style="padding:.35rem 1rem">
                <p style="font-size:.7rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.08em">Mon compte (<?= ucfirst($_SESSION['user_role']) ?>)</p>
                <p style="font-weight:600;color:#fff;margin-top:2px"><?= htmlspecialchars($_SESSION['user_nom'] ?? 'Utilisateur') ?></p>
            </div>
            <a href="<?= $dashboardLink ?>" class="mob-link"><i class="bi bi-grid-1x2"></i> Tableau de bord</a>
            <a href="notifications.php" class="mob-link">
                <i class="bi bi-bell"></i> Notifications
                <?php if ($unreadCount > 0): ?>
                <span class="mob-notif-badge"><?= $unreadCount ?></span>
                <?php endif; ?>
            </a>
            <a href="logout.php" class="mob-link" style="color:#f87171"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
            <?php else: ?>
            <a href="signin.php" class="mob-login"><i class="bi bi-box-arrow-in-right"></i> Se connecter</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<div id="google_translate_element" style="display:none"></div>

<script>
/* ===== Google Translate ===== */
(function(){
    const s = document.createElement('script');
    s.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
    document.head.appendChild(s);
})();
window.googleTranslateElementInit = function(){
    new google.translate.TranslateElement({ pageLanguage:'fr', includedLanguages:'fr,en,ar,es', autoDisplay:false }, 'google_translate_element');
};
function applyLang(lang){
    const v = `/fr/${lang}`;
    document.cookie = `googtrans=${v}; path=/;`;
    document.cookie = `googtrans=${v}; path=/; domain=${location.hostname};`;
    localStorage.setItem('gm_lang', lang);
    document.documentElement.lang = lang;
    location.reload();
}
function syncLangUI(lang){
    const opt = document.querySelector(`#langDrop [data-lang="${lang}"]`);
    if(opt){
        document.getElementById('curFlag').textContent = opt.dataset.flag;
        document.getElementById('curCode').textContent = lang.toUpperCase();
        document.querySelectorAll('.lang-opt').forEach(o => o.classList.toggle('active', o.dataset.lang===lang));
    }
    document.querySelectorAll('.mob-lang-opt').forEach(b => b.classList.toggle('active', b.dataset.lang===lang));
}
syncLangUI(localStorage.getItem('gm_lang') || 'fr');

/* ===== Menú móvil ===== */
const mobToggle = document.getElementById('mobToggle');
const mobMenu   = document.getElementById('mobMenu');
const mobIcon   = document.getElementById('mobIcon');
mobToggle?.addEventListener('click', e => {
    e.stopPropagation();
    const open = mobMenu.classList.toggle('open');
    mobIcon.className = open ? 'bi bi-x-lg' : 'bi bi-list';
});

/* ===== Account dropdown ===== */
const accWrap = document.getElementById('accWrap');
document.getElementById('accBtn')?.addEventListener('click', e => {
    e.stopPropagation();
    accWrap.classList.toggle('open');
});

/* ===== Tabs cuenta/notificaciones ===== */
document.querySelectorAll('.acc-tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.acc-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.acc-panel').forEach(p => p.classList.remove('active'));
        tab.classList.add('active');
        document.getElementById('panel-' + tab.dataset.tab)?.classList.add('active');
    });
});

/* ===== Marcar notificación como leída ===== */
function markRead(el, link) {
    if(el.classList.contains('unread')) {
        el.classList.remove('unread');
        el.style.borderLeft = 'none';
        const dot = el.querySelector('.notif-unread-dot');
        if(dot) dot.remove();
        updateNotifCount(-1);
        const id = el.dataset.id;
        if(id) fetch('mark_notification_read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + id
        }).catch(()=>{});
    }
    if(link && link !== '#') setTimeout(() => location.href = link, 150);
}

document.querySelectorAll('.notif-item').forEach(item => {
    item.addEventListener('click', function() {
        const link = this.dataset.link || '#';
        markRead(this, link);
    });
});

/* ===== Marcar todas como leídas ===== */
document.getElementById('markAllRead')?.addEventListener('click', function() {
    document.querySelectorAll('.notif-item.unread').forEach(el => {
        el.classList.remove('unread');
        el.style.borderLeft = 'none';
        el.querySelector('.notif-unread-dot')?.remove();
    });
    updateNotifCount(0, true);
    fetch('mark_all_notifications_read.php', { method: 'POST' }).catch(()=>{});
    this.remove();
    document.getElementById('tabBadge')?.remove();
});

function updateNotifCount(delta, reset = false) {
    const dot     = document.getElementById('notifDot');
    const tabBadge= document.getElementById('tabBadge');
    let current   = parseInt(dot?.textContent) || 0;
    const next    = reset ? 0 : Math.max(0, current + delta);
    if(dot) {
        if(next <= 0){ dot.classList.remove('show'); dot.textContent = ''; }
        else { dot.textContent = next > 9 ? '9+' : next; dot.classList.add('show'); }
    }
    if(tabBadge) {
        if(next <= 0) tabBadge.remove();
        else tabBadge.textContent = next > 9 ? '9+' : next;
    }
}

/* ===== Idioma ===== */
const langWrap = document.getElementById('langWrap');
document.getElementById('langBtn')?.addEventListener('click', e => {
    e.stopPropagation(); langWrap.classList.toggle('open');
});
document.querySelectorAll('.lang-opt').forEach(opt => {
    opt.addEventListener('click', () => {
        syncLangUI(opt.dataset.lang);
        langWrap.classList.remove('open');
        applyLang(opt.dataset.lang);
    });
});
document.querySelectorAll('.mob-lang-opt').forEach(btn => {
    btn.addEventListener('click', () => applyLang(btn.dataset.lang));
});

/* ===== Cerrar al clic exterior ===== */
document.addEventListener('click', e => {
    if(accWrap  && !accWrap.contains(e.target))  accWrap.classList.remove('open');
    if(langWrap && !langWrap.contains(e.target)) langWrap.classList.remove('open');
    if(mobMenu  && !mobMenu.contains(e.target) && !mobToggle.contains(e.target)){
        mobMenu.classList.remove('open');
        mobIcon.className = 'bi bi-list';
    }
});

/* ===== Búsqueda ===== */
const searchInput = document.getElementById('headerSearch');
const suggestions = document.getElementById('suggestions');
let searchTimer = null, activeIdx = -1;

function renderSuggestions(data, q){
    if(!data.length){
        suggestions.innerHTML = `<div class="sug-empty"><i class="bi bi-search" style="font-size:1.4rem;display:block;margin-bottom:.5rem"></i>Aucun résultat pour "<strong>${q}</strong>"</div>`;
        suggestions.classList.add('show'); return;
    }
    let html = '', lastType = '';
    data.forEach((item, i) => {
        if(item.type !== lastType && i > 0) html += '<div class="sug-divider"></div>';
        lastType = item.type;
        const img   = item.image || (item.type==='produit' ? 'IMAGES/default-product.jpg' : 'IMAGES/default-boutique.jpg');
        const label = item.type==='produit' ? 'Produit' : 'Boutique';
        html += `
        <a href="${item.link}" class="sug-item">
            <img src="${img}" class="sug-img" alt="${item.name}"
                 onerror="this.src='${item.type==='produit'?'IMAGES/default-product.jpg':'IMAGES/default-boutique.jpg'}'">
            <div class="sug-info">
                <div class="sug-name">${item.name}</div>
                <div class="sug-detail">
                    <span class="sug-tag">${label}</span>
                    ${item.type==='produit' ? `<span>• ${item.shop_name||''}</span>` : `<span>• ${item.producer_name||'Artisan'}</span>`}
                </div>
            </div>
            ${item.type==='produit' && item.price ? `<span class="sug-price">${item.price}</span>` : ''}
        </a>`;
    });
    suggestions.innerHTML = html;
    suggestions.classList.add('show');
}

searchInput?.addEventListener('input', function(){
    activeIdx = -1; clearTimeout(searchTimer);
    const q = this.value.trim();
    if(!q){ suggestions.classList.remove('show'); return; }
    suggestions.innerHTML = `<div class="sug-empty"><i class="bi bi-arrow-repeat" style="font-size:1.3rem;display:block;margin-bottom:.5rem;animation:spin 1s linear infinite"></i>Recherche...</div>`;
    suggestions.classList.add('show');
    searchTimer = setTimeout(() => {
        fetch('search_suggestions.php?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => renderSuggestions(data, q))
            .catch(() => suggestions.classList.remove('show'));
    }, 300);
});

searchInput?.addEventListener('keydown', function(e){
    const items = suggestions.querySelectorAll('.sug-item');
    if(e.key==='ArrowDown'){ e.preventDefault(); activeIdx = Math.min(activeIdx+1, items.length-1); }
    else if(e.key==='ArrowUp'){ e.preventDefault(); activeIdx = Math.max(activeIdx-1, -1); }
    else if(e.key==='Enter'){
        if(activeIdx>=0 && items[activeIdx]){ e.preventDefault(); items[activeIdx].click(); return; }
        if(this.value.trim()){ suggestions.classList.remove('show'); location.href='produits.php?search='+encodeURIComponent(this.value.trim()); }
    }
    items.forEach((el,i) => el.style.background = i===activeIdx ? 'var(--suggestions-hover)' : '');
});

document.querySelector('.search-ico')?.addEventListener('click', () => {
    const q = searchInput?.value.trim();
    if(q) location.href = 'produits.php?search=' + encodeURIComponent(q);
});
document.addEventListener('click', e => {
    if(!document.querySelector('.search-wrap')?.contains(e.target))
        suggestions.classList.remove('show');
});
suggestions?.addEventListener('click', () => setTimeout(() => suggestions.classList.remove('show'), 100));
</script>