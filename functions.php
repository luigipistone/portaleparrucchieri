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


function icon_svg(string $name, string $class = 'ui-icon'): string
{
    $icons = [
        'brand' => '<path d="M12 3l1.7 4.8L18.5 9.5l-4.8 1.8L12 16l-1.7-4.7-4.8-1.8 4.8-1.7L12 3z"/><path d="M18 15l.8 2.2L21 18l-2.2.8L18 21l-.8-2.2L15 18l2.2-.8L18 15z"/>',
        'barber' => '<path d="M8 4h8l1 4-1 12H8L7 8l1-4z"/><path d="M9 8h6M10 4v16M14 4v16"/>',
        'scissors' => '<circle cx="6" cy="7" r="2.5"/><circle cx="6" cy="17" r="2.5"/><path d="M8.2 8.2L19 19M8.2 15.8L19 5"/>',
        'dashboard' => '<path d="M4 13a8 8 0 0 1 16 0"/><path d="M12 13l4-4"/><path d="M5 17h14"/>',
        'calendar' => '<rect x="4" y="5" width="16" height="15" rx="3"/><path d="M8 3v4M16 3v4M4 10h16"/>',
        'services' => '<path d="M4 19l6-6M14 5l5 5M13 6l5 5-8 8H5v-5l8-8z"/>',
        'users' => '<path d="M16 19c0-2.2-1.8-4-4-4s-4 1.8-4 4"/><circle cx="12" cy="8" r="3"/><path d="M20 18c-.3-1.7-1.5-3-3-3.6M4 18c.3-1.7 1.5-3 3-3.6"/>',
        'staff' => '<path d="M4 20c0-3 2.7-5 6-5h4c3.3 0 6 2 6 5"/><circle cx="12" cy="7" r="3"/><path d="M8 11h8M9 4h6"/>',
        'check' => '<path d="M5 12l4 4L19 6"/>',
        'trash' => '<path d="M4 7h16"/><path d="M10 11v6M14 11v6"/><path d="M6 7l1 14h10l1-14"/><path d="M9 7V4h6v3"/>',
        'edit' => '<path d="M4 20h4l10.5-10.5a2.1 2.1 0 0 0-3-3L5 17v3z"/><path d="M13.5 7.5l3 3"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'moon' => '<path d="M20 14.5A7 7 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/>',
    ];

    $path = $icons[$name] ?? $icons['brand'];

    return '<svg class="' . e($class) . '" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">' . $path . '</svg>';
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
        'completed' => 'Usufruito',
        default => 'In attesa',
    };
}

function status_class(string $status): string
{
    return match ($status) {
        'confirmed' => 'success',
        'cancelled' => 'danger',
        'completed' => 'success',
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
        'Operatore: ' . ($appointment['staff_name'] ?? 'Da assegnare'),
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
        'dashboard' => ['label' => 'Dashboard', 'href' => 'admin.php', 'icon' => 'dashboard'],
        'calendar' => ['label' => 'Calendario', 'href' => 'admin_calendar.php', 'icon' => 'calendar'],
        'services' => ['label' => 'Servizi', 'href' => 'admin_services.php', 'icon' => 'services'],
        'users' => ['label' => 'Utenti', 'href' => 'admin_users.php', 'icon' => 'users'],
        'staff' => ['label' => 'Staff', 'href' => 'admin_staff.php', 'icon' => 'staff'],
    ];
    ?>
    <aside class="admin-menu glass-panel reveal">
        <p class="eyebrow">Menu admin</p>
        <nav>
            <?php foreach ($items as $key => $item): ?>
                <a class="admin-menu-link <?= $active === $key ? 'active' : '' ?>" href="<?= e($item['href']) ?>">
                    <span><?= icon_svg($item['icon']) ?></span>
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
                <span class="brand-mark"><?= icon_svg('brand') ?></span>
                <span><?= APP_NAME ?></span>
            </a>
            <nav class="main-nav">
                <a href="index.php#servizi">Servizi</a>
                <a href="index.php#prenota">Prenota</a>
                <a href="booking_lookup.php">Trova prenotazione</a>
                <?php if ($user && $user['role'] === 'admin'): ?>
                    <a href="admin.php">Dashboard</a>
                <?php elseif ($user): ?>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="profile.php">Profilo</a>
                <?php endif; ?>
                <button class="theme-toggle" type="button" aria-label="Cambia tema" title="Cambia tema"><span class="sun"><?= icon_svg('sun') ?></span><span class="moon"><?= icon_svg('moon') ?></span></button>
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
