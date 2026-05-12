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



function make_booking_token(): string
{
    return (string) random_int(100000, 999999);
}

function absolute_url(string $path): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

    return $scheme . '://' . $host . $basePath . '/' . ltrim($path, '/');
}

function booking_lookup_url(string $token): string
{
    return absolute_url('booking_lookup.php?token=' . urlencode($token));
}

function qr_code_url(string $data, int $size = 180): string
{
    return 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . urlencode($data);
}

function whatsapp_message_link(?string $phone, string $message = ''): ?string
{
    $link = whatsapp_link($phone);
    if (!$link) {
        return null;
    }

    return $message ? $link . '?text=' . rawurlencode($message) : $link;
}

function send_booking_summary_email(string $to, array $appointment): bool
{
    $token = $appointment['booking_token'] ?? '';
    $lookupUrl = booking_lookup_url($token);
    $qrUrl = qr_code_url($lookupUrl);
    $subject = 'Riepilogo richiesta appuntamento ' . APP_NAME;
    $message = implode(PHP_EOL, [
        'Ciao ' . $appointment['guest_name'] . ',',
        '',
        'Abbiamo ricevuto la tua richiesta di appuntamento.',
        '',
        'Codice prenotazione: ' . $token,
        'Servizio: ' . $appointment['service_name'],
        'Data e ora: ' . date('d/m/Y H:i', strtotime($appointment['appointment_at'])),
        'Stato: ' . appointment_status_label($appointment['status']),
        '',
        'Puoi controllare la prenotazione inserendo il codice qui:',
        $lookupUrl,
        '',
        'QR code: ' . $qrUrl,
        '',
        APP_NAME,
    ]);
    $host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    $headers = ['From: ' . APP_NAME . ' <no-reply@' . $host . '>'];

    return @mail($to, $subject, $message, implode("\r\n", $headers));
}

function whatsapp_link(?string $phone): ?string
{
    $digits = preg_replace('/\D+/', '', $phone ?? '');
    if (!$digits) {
        return null;
    }

    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);
    } elseif (str_starts_with($digits, '0')) {
        $digits = '39' . ltrim($digits, '0');
    }

    return 'https://wa.me/' . $digits;
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
                <a href="booking_lookup.php">Trova prenotazione</a>
                <?php if ($user && $user['role'] === 'admin'): ?>
                    <a href="admin.php">Admin</a>
                <?php elseif ($user): ?>
                    <a href="dashboard.php">Area utente</a>
                    <a href="profile.php">Profilo</a>
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
