<?php
require_once __DIR__ . '/functions.php';
require_admin();
$pdo = db();

$stats = [
    'pending' => (int) $pdo->query('SELECT COUNT(*) FROM appointments WHERE status = "pending"')->fetchColumn(),
    'confirmed' => (int) $pdo->query('SELECT COUNT(*) FROM appointments WHERE status = "confirmed"')->fetchColumn(),
    'services' => (int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn(),
    'customers' => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE role = "customer"')->fetchColumn(),
];
$nextAppointments = $pdo->query('SELECT a.*, s.name AS service_name FROM appointments a JOIN services s ON s.id = a.service_id WHERE a.appointment_at >= NOW() ORDER BY a.appointment_at ASC LIMIT 5')->fetchAll();

render_header('Admin');
?>
<section class="dashboard-head reveal">
    <div>
        <p class="eyebrow">Backoffice</p>
        <h1>Controllo salone</h1>
        <p>Panoramica rapida del salone e accesso alle aree operative separate.</p>
    </div>
</section>

<section class="backend-layout">
    <?php render_admin_menu('dashboard'); ?>
    <div class="backend-content">
        <div class="stats-grid reveal delay-1">
            <a class="stat-card glass-panel" href="admin_calendar.php?status=pending"><span>Richieste in attesa</span><strong><?= $stats['pending'] ?></strong></a>
            <a class="stat-card glass-panel" href="admin_calendar.php?status=confirmed"><span>Appuntamenti confermati</span><strong><?= $stats['confirmed'] ?></strong></a>
            <a class="stat-card glass-panel" href="admin_services.php"><span>Servizi configurati</span><strong><?= $stats['services'] ?></strong></a>
            <a class="stat-card glass-panel" href="admin_users.php"><span>Utenti registrati</span><strong><?= $stats['customers'] ?></strong></a>
        </div>

        <section class="table-card glass-panel reveal delay-1">
            <div class="section-title compact-title">
                <div>
                    <p class="eyebrow">Prossimi slot</p>
                    <h2>Agenda immediata</h2>
                </div>
                <a class="btn ghost" href="admin_calendar.php">Apri calendario</a>
            </div>
            <div class="calendar-list">
                <?php foreach ($nextAppointments as $appointment): ?>
                    <article class="calendar-item <?= status_class($appointment['status']) ?>">
                        <time><?= date('d/m', strtotime($appointment['appointment_at'])) ?><span><?= date('H:i', strtotime($appointment['appointment_at'])) ?></span></time>
                        <div>
                            <h3><?= e($appointment['guest_name']) ?> · <?= e($appointment['service_name']) ?></h3>
                            <p><span class="badge <?= status_class($appointment['status']) ?>"><?= appointment_status_label($appointment['status']) ?></span></p>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (!$nextAppointments): ?><p>Nessun appuntamento futuro trovato.</p><?php endif; ?>
            </div>
        </section>
    </div>
</section>
<?php render_footer(); ?>
