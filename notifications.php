<?php
session_start();

// Proteger la página: solo usuarios logueados
if (!isset($_SESSION['user_role'])) {
    header('Location: signin.php');
    exit;
}

include('connexion.php');

// ── Marcar como leída via AJAX ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $role = $_SESSION['user_role'];
    $uid  = $_SESSION['user_id'];
    $col  = ($role === 'client') ? 'id_client' : 'id_producteur';
    if ($_POST['action'] === 'mark_read' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("UPDATE notification SET est_lu=1 WHERE id_notification=? AND $col=?");
        $stmt->execute([$_POST['id'], $uid]);
        echo json_encode(['ok' => true]);
    } elseif ($_POST['action'] === 'mark_all') {
        $stmt = $pdo->prepare("UPDATE notification SET est_lu=1 WHERE $col=?");
        $stmt->execute([$uid]);
        echo json_encode(['ok' => true]);
    } elseif ($_POST['action'] === 'delete' && isset($_POST['id'])) {
        $stmt = $pdo->prepare("DELETE FROM notification WHERE id_notification=? AND $col=?");
        $stmt->execute([$_POST['id'], $uid]);
        echo json_encode(['ok' => true]);
    }
    exit;
}

// ── Charger les notifications depuis la BD ──────────────────────────────────
$role = $_SESSION['user_role'];
$uid  = $_SESSION['user_id'];
$col  = ($role === 'client') ? 'id_client' : 'id_producteur';
try {
    $stmt = $pdo->prepare("SELECT id_notification as id, type_notification as type, message, date_notification, est_lu as is_read FROM notification WHERE $col = ? ORDER BY date_notification DESC");
    $stmt->execute([$uid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $notifications = array_map(function($n) {
        return [
            'id'      => $n['id'],
            'type'    => $n['type'],
            'title'   => ucfirst($n['type']),
            'text'    => $n['message'],
            'time'    => date('d M, H\hi', strtotime($n['date_notification'])),
            'is_read' => (int)$n['is_read'],
            'link'    => '#',
        ];
    }, $rows);
} catch(PDOException $e) {
    $notifications = [];
}

$unreadCount = count(array_filter($notifications, fn($n) => !$n['is_read']));
$theme       = $_COOKIE['theme'] ?? 'light';
$currentPage = basename($_SERVER['PHP_SELF']);

// Dashboard link
$dashboardLink = '';
if ($_SESSION['user_role'] === 'client')     $dashboardLink = 'dashboard_client.php';
elseif ($_SESSION['user_role'] === 'producteur') $dashboardLink = 'dashboard-producteur.php';
elseif ($_SESSION['user_role'] === 'admin')  $dashboardLink = 'dashboard_admin.php';
?>
<!DOCTYPE html>
<html lang="fr" data-theme="<?= $theme ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications — GreenMarket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary:      #5D0D18;
            --secondary:    #9FB2AC;
            --gold:         #c07a1a;
            --bg:           #FFF9EB;
            --bg-light:     #f5f0e8;
            --bg-card:      #ffffff;
            --text-dark:    #2C2C2C;
            --text-light:   #6B6B6B;
            --border:       #ede8df;
            --shadow:       rgba(93,13,24,0.08);
            --radius:       14px;
        }
        [data-theme="dark"] {
            --primary:    #8a6048;
            --secondary:  #6d4c3a;
            --gold:       #d4a85c;
            --bg:         #2c241e;
            --bg-light:   #3d3229;
            --bg-card:    #3d3229;
            --text-dark:  #f0e6d8;
            --text-light: #b8a896;
            --border:     #5a4a3a;
            --shadow:     rgba(0,0,0,0.3);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Lato', sans-serif;
            background: var(--bg);
            color: var(--text-dark);
            min-height: 100vh;
        }

        /* ── PAGE LAYOUT ── */
        .page-wrap {
            max-width: 780px;
            margin: 2.5rem auto;
            padding: 0 1.25rem 4rem;
        }

        /* ── BREADCRUMB ── */
        .breadcrumb {
            display: flex; align-items: center; gap: .5rem;
            font-size: .82rem; color: var(--text-light);
            margin-bottom: 1.75rem;
        }
        .breadcrumb a { color: var(--text-light); text-decoration: none; transition: color .2s; }
        .breadcrumb a:hover { color: var(--primary); }
        .breadcrumb i { font-size: .7rem; opacity: .5; }

        /* ── HEADER ── */
        .page-header {
            display: flex; align-items: flex-start; justify-content: space-between;
            gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;
        }
        .page-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.75rem; font-weight: 700;
            color: var(--text-dark);
            display: flex; align-items: center; gap: .6rem;
        }
        .page-title .unread-pill {
            font-family: 'Lato', sans-serif;
            font-size: .75rem; font-weight: 700;
            background: var(--primary); color: #fff;
            padding: .2rem .65rem; border-radius: 999px;
        }
        .header-actions { display: flex; gap: .65rem; flex-wrap: wrap; }
        .btn-outline {
            display: flex; align-items: center; gap: 6px;
            padding: .55rem 1.1rem;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            background: var(--bg-card);
            color: var(--text-dark);
            font-size: .85rem; font-weight: 600;
            cursor: pointer; transition: all .2s;
            text-decoration: none;
        }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
        .btn-primary {
            display: flex; align-items: center; gap: 6px;
            padding: .55rem 1.1rem;
            border: none; border-radius: 10px;
            background: var(--primary); color: #fff;
            font-size: .85rem; font-weight: 600;
            cursor: pointer; transition: background .2s;
        }
        .btn-primary:hover { background: #7a1020; }
        [data-theme="dark"] .btn-primary { background: var(--gold); color: #1a1410; }
        [data-theme="dark"] .btn-primary:hover { background: #b8962e; }

        /* ── FILTER TABS ── */
        .filter-bar {
            display: flex; gap: .4rem; flex-wrap: wrap;
            margin-bottom: 1.25rem;
        }
        .filter-btn {
            padding: .42rem .9rem;
            border: 1.5px solid var(--border);
            border-radius: 999px;
            background: var(--bg-card);
            color: var(--text-light);
            font-size: .82rem; font-weight: 600;
            cursor: pointer; transition: all .2s;
        }
        .filter-btn:hover { border-color: var(--primary); color: var(--primary); }
        .filter-btn.active {
            background: var(--primary); color: #fff;
            border-color: var(--primary);
        }
        [data-theme="dark"] .filter-btn.active { background: var(--gold); color: #1a1410; border-color: var(--gold); }

        /* ── NOTIFICATION CARD ── */
        .notif-list { display: flex; flex-direction: column; gap: .65rem; }

        .notif-card {
            display: flex; align-items: flex-start; gap: 1rem;
            padding: 1rem 1.25rem;
            background: var(--bg-card);
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 2px 12px var(--shadow);
            transition: box-shadow .2s, border-color .2s, opacity .3s;
            position: relative;
            cursor: pointer;
        }
        .notif-card:hover { box-shadow: 0 6px 20px var(--shadow); border-color: rgba(93,13,24,.15); }
        .notif-card.unread { border-left: 3.5px solid var(--primary); }
        [data-theme="dark"] .notif-card.unread { border-left-color: var(--gold); }
        .notif-card.removing { opacity: 0; transform: translateX(40px); transition: opacity .3s, transform .3s; }

        /* Icon */
        .notif-icon {
            width: 44px; height: 44px; flex-shrink: 0;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem;
        }
        .notif-icon.order   { background: rgba(93,13,24,.1);   color: var(--primary); }
        .notif-icon.promo   { background: rgba(192,122,26,.12); color: var(--gold); }
        .notif-icon.system  { background: rgba(159,178,172,.2); color: var(--secondary); }
        .notif-icon.message { background: rgba(59,130,246,.1);  color: #3b82f6; }
        [data-theme="dark"] .notif-icon.order   { background: rgba(212,168,92,.12); color: var(--gold); }
        [data-theme="dark"] .notif-icon.message { background: rgba(59,130,246,.15); }

        /* Body */
        .notif-body { flex: 1; min-width: 0; }
        .notif-top  { display: flex; align-items: flex-start; justify-content: space-between; gap: .5rem; }
        .notif-title {
            font-size: .95rem; font-weight: 700;
            color: var(--text-dark); line-height: 1.3;
        }
        .notif-card:not(.unread) .notif-title { font-weight: 500; }
        .notif-time { font-size: .75rem; color: var(--text-light); white-space: nowrap; flex-shrink: 0; }
        .notif-text { font-size: .85rem; color: var(--text-light); margin-top: .35rem; line-height: 1.5; }
        .notif-meta { display: flex; align-items: center; gap: .65rem; margin-top: .6rem; }
        .notif-type-tag {
            font-size: .65rem; font-weight: 700; text-transform: uppercase;
            padding: .15rem .55rem; border-radius: 999px;
        }
        .notif-type-tag.order   { background: rgba(93,13,24,.1);   color: var(--primary); }
        .notif-type-tag.promo   { background: rgba(192,122,26,.12); color: var(--gold); }
        .notif-type-tag.system  { background: rgba(159,178,172,.3); color: #4a6b65; }
        .notif-type-tag.message { background: rgba(59,130,246,.12); color: #3b82f6; }
        [data-theme="dark"] .notif-type-tag.order { background: rgba(212,168,92,.15); color: var(--gold); }

        .notif-link-btn {
            font-size: .78rem; font-weight: 600;
            color: var(--primary); text-decoration: none;
            display: flex; align-items: center; gap: 3px;
            transition: opacity .2s;
        }
        [data-theme="dark"] .notif-link-btn { color: var(--gold); }
        .notif-link-btn:hover { opacity: .7; }

        /* Unread dot */
        .unread-dot {
            width: 9px; height: 9px; border-radius: 50%;
            background: var(--primary); flex-shrink: 0; margin-top: 6px;
        }
        [data-theme="dark"] .unread-dot { background: var(--gold); }

        /* Delete button */
        .notif-delete {
            position: absolute; top: .75rem; right: .85rem;
            width: 28px; height: 28px;
            border-radius: 7px; border: none;
            background: transparent;
            color: var(--text-light);
            font-size: .85rem;
            cursor: pointer; opacity: 0;
            display: flex; align-items: center; justify-content: center;
            transition: all .2s;
        }
        .notif-card:hover .notif-delete { opacity: 1; }
        .notif-delete:hover { background: #fee2e2; color: #c0392b; }
        [data-theme="dark"] .notif-delete:hover { background: #4a2d30; color: #e8b8b8; }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center; padding: 4rem 2rem;
            background: var(--bg-card);
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
        }
        .empty-icon {
            width: 72px; height: 72px; border-radius: 50%;
            background: var(--bg-light);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem; color: var(--text-light);
            margin: 0 auto 1.25rem;
        }
        .empty-state h3 { font-size: 1.1rem; font-weight: 700; color: var(--text-dark); }
        .empty-state p  { font-size: .88rem; color: var(--text-light); margin-top: .4rem; }

        /* ── DATE SEPARATOR ── */
        .date-sep {
            display: flex; align-items: center; gap: .75rem;
            font-size: .75rem; font-weight: 700; color: var(--text-light);
            text-transform: uppercase; letter-spacing: .06em;
            margin: 1rem 0 .5rem;
        }
        .date-sep::before, .date-sep::after {
            content: ''; flex: 1; height: 1px; background: var(--border);
        }

        /* ── TOAST ── */
        .toast {
            position: fixed; bottom: 1.5rem; right: 1.5rem;
            background: var(--text-dark); color: var(--bg);
            padding: .75rem 1.2rem; border-radius: 10px;
            font-size: .85rem; font-weight: 500;
            box-shadow: 0 8px 24px rgba(0,0,0,.2);
            z-index: 9999;
            display: flex; align-items: center; gap: .6rem;
            opacity: 0; transform: translateY(12px);
            transition: all .3s cubic-bezier(.34,1.56,.64,1);
            pointer-events: none;
        }
        .toast.show { opacity: 1; transform: translateY(0); }

        /* ── RESPONSIVE ── */
        @media (max-width: 600px) {
            .page-header { flex-direction: column; }
            .page-title { font-size: 1.4rem; }
            .notif-card { padding: .85rem 1rem; }
            .notif-icon { width: 38px; height: 38px; font-size: 1rem; border-radius: 10px; }
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>

<main class="page-wrap">

    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="accueil.php">Accueil</a>
        <i class="bi bi-chevron-right"></i>
        <span>Notifications</span>
    </nav>

    <!-- Header de página -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="bi bi-bell" style="font-size:1.5rem;color:var(--primary)"></i>
            Notifications
            <?php if ($unreadCount > 0): ?>
            <span class="unread-pill" id="unreadPill"><?= $unreadCount ?> non lue<?= $unreadCount>1?'s':'' ?></span>
            <?php endif; ?>
        </h1>
        <div class="header-actions">
            <?php if ($unreadCount > 0): ?>
            <button class="btn-outline" id="markAllBtn">
                <i class="bi bi-check2-all"></i> Tout marquer lu
            </button>
            <?php endif; ?>
            <button class="btn-outline" id="deleteReadBtn">
                <i class="bi bi-trash3"></i> Supprimer lues
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filter-bar">
        <button class="filter-btn active" data-filter="all">Toutes</button>
        <button class="filter-btn" data-filter="unread">Non lues</button>
        <button class="filter-btn" data-filter="order">
            <i class="bi bi-bag-check"></i> Commandes
        </button>
        <button class="filter-btn" data-filter="promo">
            <i class="bi bi-tag"></i> Promotions
        </button>
        <button class="filter-btn" data-filter="message">
            <i class="bi bi-chat-dots"></i> Messages
        </button>
        <button class="filter-btn" data-filter="system">
            <i class="bi bi-info-circle"></i> Système
        </button>
    </div>

    <!-- Lista -->
    <div class="notif-list" id="notifList">

        <?php
        // Agrupar por fecha para separadores
        $groups = [];
        foreach ($notifications as $n) {
            // Simplificado: agrupa por "tiempo" (en producción usa date())
            $day = strstr($n['time'], 'Il y a') ? 'Aujourd\'hui' :
                   ($n['time'] === 'Hier, 14h32' ? 'Hier' : 'Plus tôt');
            $groups[$day][] = $n;
        }
        $typeIcons = [
            'order'   => 'bi-bag-check',
            'promo'   => 'bi-tag',
            'message' => 'bi-chat-dots',
            'system'  => 'bi-info-circle',
        ];
        $typeLabels = [
            'order'   => 'Commande',
            'promo'   => 'Promotion',
            'message' => 'Message',
            'system'  => 'Système',
        ];
        foreach ($groups as $day => $items):
        ?>
        <div class="date-sep" data-group="<?= htmlspecialchars($day) ?>"><?= htmlspecialchars($day) ?></div>

        <?php foreach ($items as $n): ?>
        <div class="notif-card <?= !$n['is_read'] ? 'unread' : '' ?>"
             data-id="<?= $n['id'] ?>"
             data-type="<?= htmlspecialchars($n['type']) ?>"
             data-read="<?= $n['is_read'] ? '1' : '0' ?>"
             onclick="handleCardClick(this, '<?= htmlspecialchars($n['link']) ?>')">

            <div class="notif-icon <?= $n['type'] ?>">
                <i class="bi <?= $typeIcons[$n['type']] ?? 'bi-bell' ?>"></i>
            </div>

            <div class="notif-body">
                <div class="notif-top">
                    <span class="notif-title"><?= htmlspecialchars($n['title']) ?></span>
                    <span class="notif-time"><?= htmlspecialchars($n['time']) ?></span>
                </div>
                <p class="notif-text"><?= htmlspecialchars($n['text']) ?></p>
                <div class="notif-meta">
                    <span class="notif-type-tag <?= $n['type'] ?>"><?= $typeLabels[$n['type']] ?? $n['type'] ?></span>
                    <?php if (!empty($n['link'])): ?>
                    <a href="<?= htmlspecialchars($n['link']) ?>" class="notif-link-btn" onclick="event.stopPropagation()">
                        Voir <i class="bi bi-arrow-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!$n['is_read']): ?>
            <div class="unread-dot"></div>
            <?php endif; ?>

            <button class="notif-delete" title="Supprimer"
                    onclick="event.stopPropagation(); deleteNotif(this.closest('.notif-card'))">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <?php endforeach; ?>
        <?php endforeach; ?>

    </div><!-- /notif-list -->

    <!-- Estado vacío (oculto por defecto, JS lo muestra si hace falta) -->
    <div class="empty-state" id="emptyState" style="display:none">
        <div class="empty-icon"><i class="bi bi-bell-slash"></i></div>
        <h3>Aucune notification</h3>
        <p>Vous êtes à jour ! Revenez plus tard.</p>
    </div>

</main>

<!-- Toast feedback -->
<div class="toast" id="toast">
    <i class="bi bi-check-circle-fill" style="color:#4ade80"></i>
    <span id="toastMsg"></span>
</div>

<script>
let activeFilter = 'all';

/* ── Filtrar ────────────────────────────────────────────────── */
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        activeFilter = this.dataset.filter;
        applyFilter();
    });
});

function applyFilter() {
    const cards = document.querySelectorAll('.notif-card');
    let visible = 0;

    cards.forEach(card => {
        const type   = card.dataset.type;
        const unread = card.dataset.read === '0';
        let show = false;

        if (activeFilter === 'all')         show = true;
        else if (activeFilter === 'unread') show = unread;
        else                                show = (type === activeFilter);

        card.style.display = show ? 'flex' : 'none';
        if (show) visible++;
    });

    // Mostrar/ocultar separadores de fecha
    document.querySelectorAll('.date-sep').forEach(sep => {
        let sibling = sep.nextElementSibling;
        let hasVisible = false;
        while (sibling && !sibling.classList.contains('date-sep')) {
            if (sibling.style.display !== 'none') hasVisible = true;
            sibling = sibling.nextElementSibling;
        }
        sep.style.display = hasVisible ? '' : 'none';
    });

    const emptyState = document.getElementById('emptyState');
    if (emptyState) emptyState.style.display = visible === 0 ? 'block' : 'none';
}

/* ── Marcar una como leída ──────────────────────────────────── */
function handleCardClick(card, link) {
    if (card.dataset.read === '0') markRead(card);
    if (link && link !== '#') setTimeout(() => location.href = link, 150);
}

function markRead(card) {
    if (card.dataset.read === '1') return;
    card.dataset.read = '1';
    card.classList.remove('unread');
    card.querySelector('.unread-dot')?.remove();
    updateUnreadCount(-1);
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=mark_read&id=' + card.dataset.id
    });
}

/* ── Marcar todas ───────────────────────────────────────────── */
document.getElementById('markAllBtn')?.addEventListener('click', () => {
    document.querySelectorAll('.notif-card.unread').forEach(card => {
        card.dataset.read = '1';
        card.classList.remove('unread');
        card.querySelector('.unread-dot')?.remove();
    });
    updateUnreadCount(0, true);
    document.getElementById('markAllBtn')?.remove();
    showToast('Toutes les notifications marquées comme lues');
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=mark_all'
    });
});

/* ── Eliminar notificación ──────────────────────────────────── */
function deleteNotif(card) {
    const wasUnread = card.dataset.read === '0';
    card.classList.add('removing');
    setTimeout(() => {
        if (wasUnread) updateUnreadCount(-1);
        card.remove();
        checkEmpty();
        showToast('Notification supprimée');
    }, 300);
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=delete&id=' + card.dataset.id
    });
}

/* ── Eliminar leídas ────────────────────────────────────────── */
document.getElementById('deleteReadBtn')?.addEventListener('click', () => {
    const read = document.querySelectorAll('.notif-card[data-read="1"]');
    let count = read.length;
    read.forEach(card => {
        card.classList.add('removing');
        setTimeout(() => { card.remove(); checkEmpty(); }, 300);
    });
    if (count > 0) showToast(count + ' notification(s) supprimée(s)');
});

/* ── Contador de no leídas ──────────────────────────────────── */
function updateUnreadCount(delta, reset) {
    const pill = document.getElementById('unreadPill');
    if (!pill) return;
    let current = parseInt(pill.textContent) || 0;
    const next  = reset ? 0 : Math.max(0, current + delta);
    if (next <= 0) pill.remove();
    else pill.textContent = next + ' non lue' + (next > 1 ? 's' : '');
}

function checkEmpty() {
    const remaining = document.querySelectorAll('.notif-card');
    const emptyState = document.getElementById('emptyState');
    if (emptyState) emptyState.style.display = remaining.length === 0 ? 'block' : 'none';
}

/* ── Toast ──────────────────────────────────────────────────── */
let toastTimer;
function showToast(msg) {
    const toast = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    toast.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toast.classList.remove('show'), 2800);
}
</script>