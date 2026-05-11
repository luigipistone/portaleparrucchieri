<?php
require_once __DIR__ . '/config.php';

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        header('Location: login.php');
        exit;
    }

    return $user;
}

function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Accesso riservato agli amministratori.');
    }

    return $user;
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'][] = ['message' => $message, 'type' => $type];
}

function pull_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function appointment_status_label(string $status): string
{
    return match ($status) {
        'confirmed' => 'Confermato',
        'cancelled' => 'Annullato',
        default => 'In attesa',
    };
}

function status_class(string $status): string
{
    return match ($status) {
        'confirmed' => 'success',
        'cancelled' => 'danger',
        default => 'warning',
    };
}


function render_admin_menu(string $active): void
{
    $items = [
        'dashboard' => ['label' => 'Dashboard', 'href' => 'admin.php', 'icon' => '⌁'],
        'calendar' => ['label' => 'Calendario', 'href' => 'admin_calendar.php', 'icon' => '◷'],
        'services' => ['label' => 'Servizi', 'href' => 'admin_services.php', 'icon' => '✂'],
        'users' => ['label' => 'Utenti', 'href' => 'admin_users.php', 'icon' => '◎'],
    ];
    ?>
    <aside class="admin-menu glass-panel reveal">
        <p class="eyebrow">Menu admin</p>
        <nav>
            <?php foreach ($items as $key => $item): ?>
                <a class="admin-menu-link <?= $active === $key ? 'active' : '' ?>" href="<?= e($item['href']) ?>">
                    <span><?= e($item['icon']) ?></span>
                    <?= e($item['label']) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <?php
}

function render_header(string $title): void
{
    $user = current_user();
    $flashes = pull_flashes();
    ?>
    <!doctype html>
    <html lang="it">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> · <?= APP_NAME ?></title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="assets/css/style.css">
    </head>
    <body>
        <div class="liquid-bg" aria-hidden="true"><span></span><span></span><span></span></div>
        <header class="site-header glass-panel">
            <a class="brand" href="index.php" aria-label="Homepage Liquid Barber">
                <span class="brand-mark">✦</span>
                <span><?= APP_NAME ?></span>
            </a>
            <nav class="main-nav">
                <a href="index.php#servizi">Servizi</a>
                <a href="index.php#prenota">Prenota</a>
                <?php if ($user && $user['role'] === 'admin'): ?>
                    <a href="admin.php">Admin</a>
                <?php elseif ($user): ?>
                    <a href="dashboard.php">Area utente</a>
                <?php endif; ?>
                <?php if ($user): ?>
                    <a class="nav-pill" href="logout.php">Esci</a>
                <?php else: ?>
                    <a class="nav-pill" href="login.php">Accedi</a>
                <?php endif; ?>
            </nav>
        </header>
        <main>
            <?php foreach ($flashes as $message): ?>
                <div class="flash <?= e($message['type']) ?>"><?= e($message['message']) ?></div>
            <?php endforeach; ?>
    <?php
}

function render_footer(): void
{
    ?>
        </main>
        <script src="assets/js/app.js"></script>
    </body>
    </html>
    <?php
}
