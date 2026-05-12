<?php
require_once __DIR__ . '/functions.php';
require_admin();
$pdo = db();

$overview = $pdo->query('SELECT
    COUNT(*) AS total_appointments,
    SUM(status = "pending") AS pending,
    SUM(status = "confirmed") AS confirmed,
    SUM(status = "cancelled") AS cancelled,
    SUM(DATE(appointment_at) = CURDATE()) AS today,
    SUM(appointment_at >= CURDATE() AND appointment_at < DATE_ADD(CURDATE(), INTERVAL 7 DAY)) AS next_7_days,
    SUM(status = "confirmed" AND appointment_at >= CURDATE()) AS confirmed_future
    FROM appointments')->fetch();

$serviceStats = $pdo->query('SELECT
    COUNT(*) AS total_services,
    SUM(is_active = 1) AS active_services,
    SUM(is_active = 0) AS inactive_services,
    COALESCE(AVG(price), 0) AS average_price,
    COALESCE(AVG(duration_minutes), 0) AS average_duration
    FROM services')->fetch();

$userStats = $pdo->query('SELECT
    SUM(role = "customer") AS registered_customers,
    (SELECT COUNT(DISTINCT guest_email) FROM appointments WHERE user_id IS NULL) AS guest_customers,
    (SELECT COUNT(*) FROM users WHERE role = "customer" AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS new_customers_30_days
    FROM users')->fetch();

$revenueStats = $pdo->query('SELECT
    COALESCE(SUM(CASE WHEN a.status = "confirmed" THEN s.price ELSE 0 END), 0) AS confirmed_revenue,
    COALESCE(SUM(CASE WHEN a.status = "pending" THEN s.price ELSE 0 END), 0) AS pending_value,
    COALESCE(SUM(CASE WHEN a.status = "confirmed" AND a.appointment_at >= CURDATE() AND a.appointment_at < DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN s.price ELSE 0 END), 0) AS next_30_days_revenue
    FROM appointments a JOIN services s ON s.id = a.service_id')->fetch();

$totalAppointments = (int) ($overview['total_appointments'] ?? 0);
$confirmedAppointments = (int) ($overview['confirmed'] ?? 0);
$conversionRate = $totalAppointments > 0 ? round(($confirmedAppointments / $totalAppointments) * 100) : 0;

$topServices = $pdo->query('SELECT s.name, COUNT(a.id) AS bookings, COALESCE(SUM(CASE WHEN a.status = "confirmed" THEN s.price ELSE 0 END), 0) AS confirmed_revenue
    FROM services s
    LEFT JOIN appointments a ON a.service_id = s.id
    GROUP BY s.id, s.name
    ORDER BY bookings DESC, s.name ASC
    LIMIT 3')->fetchAll();

$agendaDate = date('Y-m-d');
$stmt = $pdo->prepare('SELECT COUNT(*) FROM appointments WHERE DATE(appointment_at) = ?');
$stmt->execute([$agendaDate]);
if ((int) $stmt->fetchColumn() === 0) {
    $agendaDate = $pdo->query('SELECT DATE(appointment_at) FROM appointments WHERE appointment_at >= CURDATE() ORDER BY appointment_at ASC LIMIT 1')->fetchColumn() ?: $agendaDate;
}
$stmt = $pdo->prepare('SELECT a.*, s.name AS service_name, s.duration_minutes FROM appointments a JOIN services s ON s.id = a.service_id WHERE DATE(a.appointment_at) = ? ORDER BY a.appointment_at ASC');
$stmt->execute([$agendaDate]);
$nextAppointments = $stmt->fetchAll();

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
        <div class="kpi-grid reveal delay-1">
            <a class="kpi-card glass-panel" href="admin_calendar.php?status=pending"><span class="kpi-icon">◷</span><div><p>Richieste in attesa</p><strong><?= (int) $overview['pending'] ?></strong></div><ul><li><span>Oggi</span><b><?= (int) $overview['today'] ?></b></li><li><span>Prossimi 7 giorni</span><b><?= (int) $overview['next_7_days'] ?></b></li><li><span>Valore potenziale</span><b>€<?= number_format((float) $revenueStats['pending_value'], 2, ',', '.') ?></b></li></ul></a>
            <a class="kpi-card glass-panel" href="admin_calendar.php?status=confirmed"><span class="kpi-icon success">✓</span><div><p>Appuntamenti confermati</p><strong><?= $confirmedAppointments ?></strong></div><ul><li><span>Conversione richieste</span><b><?= $conversionRate ?>%</b></li><li><span>Futuri confermati</span><b><?= (int) $overview['confirmed_future'] ?></b></li><li><span>Valore confermato</span><b>€<?= number_format((float) $revenueStats['confirmed_revenue'], 2, ',', '.') ?></b></li></ul></a>
            <a class="kpi-card glass-panel" href="admin_services.php"><span class="kpi-icon">✂</span><div><p>Servizi configurati</p><strong><?= (int) $serviceStats['total_services'] ?></strong></div><ul><li><span>Attivi</span><b><?= (int) $serviceStats['active_services'] ?></b></li><li><span>Nascosti</span><b><?= (int) $serviceStats['inactive_services'] ?></b></li><li><span>Prezzo medio</span><b>€<?= number_format((float) $serviceStats['average_price'], 2, ',', '.') ?></b></li></ul></a>
            <a class="kpi-card glass-panel" href="admin_users.php"><span class="kpi-icon">◎</span><div><p>Clienti</p><strong><?= (int) $userStats['registered_customers'] ?></strong></div><ul><li><span>Guest unici</span><b><?= (int) $userStats['guest_customers'] ?></b></li><li><span>Nuovi 30 giorni</span><b><?= (int) $userStats['new_customers_30_days'] ?></b></li><li><span>Durata media servizi</span><b><?= round((float) $serviceStats['average_duration']) ?> min</b></li></ul></a>
        </div>

        <section class="insights-grid reveal delay-1">
            <div class="table-card glass-panel">
                <div class="section-title compact-title"><div><p class="eyebrow">Performance</p><h2>Servizi più richiesti</h2></div></div>
                <div class="rank-list">
                    <?php foreach ($topServices as $index => $service): ?>
                        <div class="rank-item"><span>#<?= $index + 1 ?></span><div><strong><?= e($service['name']) ?></strong><small><?= (int) $service['bookings'] ?> richieste</small></div><b>€<?= number_format((float) $service['confirmed_revenue'], 2, ',', '.') ?></b></div>
                    <?php endforeach; ?>
                    <?php if (!$topServices): ?><p>Nessun servizio disponibile.</p><?php endif; ?>
                </div>
            </div>
            <div class="table-card glass-panel"><div class="section-title compact-title"><div><p class="eyebrow">Previsionale</p><h2>Prossimi 30 giorni</h2></div></div><div class="forecast-card"><span>Valore appuntamenti confermati</span><strong>€<?= number_format((float) $revenueStats['next_30_days_revenue'], 2, ',', '.') ?></strong><p><?= (int) $overview['confirmed_future'] ?> appuntamenti futuri già confermati.</p></div></div>
        </section>

        <section class="table-card glass-panel reveal delay-1">
            <div class="section-title compact-title">
                <div><p class="eyebrow">Agenda imminente</p><h2><?= date('d/m/Y', strtotime($agendaDate)) ?></h2></div>
                <a class="btn ghost" href="admin_calendar.php">Apri calendario</a>
            </div>
            <div class="calendar-list compact-calendar">
                <?php foreach ($nextAppointments as $appointment): ?>
                    <article class="calendar-item <?= status_class($appointment['status']) ?>">
                        <time><?= date('d/m', strtotime($appointment['appointment_at'])) ?><span><?= date('H:i', strtotime($appointment['appointment_at'])) ?></span></time>
                        <div class="appointment-row">
                            <?php $whatsappLink = whatsapp_link($appointment['guest_phone']); ?>
                            <div>
                                <h3><?= e($appointment['guest_name']) ?> · <?= e($appointment['service_name']) ?></h3>
                                <div class="appointment-meta">
                                    <span><?= e($appointment['guest_email']) ?></span><span><?= e($appointment['guest_phone']) ?></span><span><?= (int) $appointment['duration_minutes'] ?> min</span><?php if ($appointment['booking_token']): ?><code>Cod. <?= e($appointment['booking_token']) ?></code><?php endif; ?>
                                </div>
                                <?php if ($appointment['notes']): ?><p class="appointment-notes">Note: <?= e($appointment['notes']) ?></p><?php endif; ?>
                            </div>
                            <div class="appointment-actions"><?php if ($whatsappLink): ?><a class="btn whatsapp-btn" href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?></div>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (!$nextAppointments): ?><p>Nessun appuntamento futuro trovato.</p><?php endif; ?>
            </div>
        </section>
    </div>
</section>
<?php render_footer(); ?>
