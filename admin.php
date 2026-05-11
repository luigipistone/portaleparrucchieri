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

$nextAppointments = $pdo->query('SELECT a.*, s.name AS service_name FROM appointments a JOIN services s ON s.id = a.service_id WHERE a.appointment_at >= NOW() ORDER BY a.appointment_at ASC LIMIT 5')->fetchAll();

$admin = require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'service_save') {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [trim($_POST['name'] ?? ''), trim($_POST['description'] ?? ''), (int) ($_POST['duration_minutes'] ?? 0), (float) ($_POST['price'] ?? 0), isset($_POST['is_active']) ? 1 : 0];
        if ($id) {
            $stmt = $pdo->prepare('UPDATE services SET name = ?, description = ?, duration_minutes = ?, price = ?, is_active = ? WHERE id = ?');
            $stmt->execute([...$data, $id]);
            flash('Servizio aggiornato.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO services (name, description, duration_minutes, price, is_active) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute($data);
            flash('Servizio aggiunto.');
        }
    }

    if ($action === 'service_delete') {
        $stmt = $pdo->prepare('DELETE FROM services WHERE id = ?');
        $stmt->execute([(int) $_POST['id']]);
        flash('Servizio eliminato.');
    }

    if ($action === 'appointment_status') {
        $stmt = $pdo->prepare('UPDATE appointments SET status = ?, admin_notes = ? WHERE id = ?');
        $stmt->execute([$_POST['status'], trim($_POST['admin_notes'] ?? ''), (int) $_POST['id']]);
        flash('Appuntamento aggiornato.');
    }

    header('Location: admin.php');
    exit;
}

$services = $pdo->query('SELECT * FROM services ORDER BY is_active DESC, name')->fetchAll();
$appointments = $pdo->query('SELECT a.*, s.name AS service_name, s.duration_minutes FROM appointments a JOIN services s ON s.id = a.service_id ORDER BY a.appointment_at ASC')->fetchAll();
$customers = $pdo->query('SELECT id, name, email, phone, created_at FROM users WHERE role = "customer" ORDER BY created_at DESC')->fetchAll();
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
            <a class="kpi-card glass-panel" href="admin_calendar.php?status=pending">
                <span class="kpi-icon">◷</span>
                <div>
                    <p>Richieste in attesa</p>
                    <strong><?= (int) $overview['pending'] ?></strong>
                </div>
                <ul>
                    <li><span>Oggi</span><b><?= (int) $overview['today'] ?></b></li>
                    <li><span>Prossimi 7 giorni</span><b><?= (int) $overview['next_7_days'] ?></b></li>
                    <li><span>Valore potenziale</span><b>€<?= number_format((float) $revenueStats['pending_value'], 2, ',', '.') ?></b></li>
                </ul>
            </a>

            <a class="kpi-card glass-panel" href="admin_calendar.php?status=confirmed">
                <span class="kpi-icon success">✓</span>
                <div>
                    <p>Appuntamenti confermati</p>
                    <strong><?= $confirmedAppointments ?></strong>
                </div>
                <ul>
                    <li><span>Conversione richieste</span><b><?= $conversionRate ?>%</b></li>
                    <li><span>Futuri confermati</span><b><?= (int) $overview['confirmed_future'] ?></b></li>
                    <li><span>Valore confermato</span><b>€<?= number_format((float) $revenueStats['confirmed_revenue'], 2, ',', '.') ?></b></li>
                </ul>
            </a>

            <a class="kpi-card glass-panel" href="admin_services.php">
                <span class="kpi-icon">✂</span>
                <div>
                    <p>Servizi configurati</p>
                    <strong><?= (int) $serviceStats['total_services'] ?></strong>
                </div>
                <ul>
                    <li><span>Attivi</span><b><?= (int) $serviceStats['active_services'] ?></b></li>
                    <li><span>Nascosti</span><b><?= (int) $serviceStats['inactive_services'] ?></b></li>
                    <li><span>Prezzo medio</span><b>€<?= number_format((float) $serviceStats['average_price'], 2, ',', '.') ?></b></li>
                </ul>
            </a>

            <a class="kpi-card glass-panel" href="admin_users.php">
                <span class="kpi-icon">◎</span>
                <div>
                    <p>Clienti</p>
                    <strong><?= (int) $userStats['registered_customers'] ?></strong>
                </div>
                <ul>
                    <li><span>Guest unici</span><b><?= (int) $userStats['guest_customers'] ?></b></li>
                    <li><span>Nuovi 30 giorni</span><b><?= (int) $userStats['new_customers_30_days'] ?></b></li>
                    <li><span>Durata media servizi</span><b><?= round((float) $serviceStats['average_duration']) ?> min</b></li>
                </ul>
            </a>
        </div>

        <section class="insights-grid reveal delay-1">
            <div class="table-card glass-panel">
                <div class="section-title compact-title">
                    <div>
                        <p class="eyebrow">Performance</p>
                        <h2>Servizi più richiesti</h2>
                    </div>
                </div>
                <div class="rank-list">
                    <?php foreach ($topServices as $index => $service): ?>
                        <div class="rank-item">
                            <span>#<?= $index + 1 ?></span>
                            <div><strong><?= e($service['name']) ?></strong><small><?= (int) $service['bookings'] ?> richieste</small></div>
                            <b>€<?= number_format((float) $service['confirmed_revenue'], 2, ',', '.') ?></b>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$topServices): ?><p>Nessun servizio disponibile.</p><?php endif; ?>
                </div>
            </div>

            <div class="table-card glass-panel">
                <div class="section-title compact-title">
                    <div>
                        <p class="eyebrow">Previsionale</p>
                        <h2>Prossimi 30 giorni</h2>
                    </div>
                </div>
                <div class="forecast-card">
                    <span>Valore appuntamenti confermati</span>
                    <strong>€<?= number_format((float) $revenueStats['next_30_days_revenue'], 2, ',', '.') ?></strong>
                    <p><?= (int) $overview['confirmed_future'] ?> appuntamenti futuri già confermati.</p>
                </div>
            </div>
        </section>

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
        <p>Gestisci conferme, calendario, servizi e clienti da un unico pannello.</p>
    </div>
</section>

<section class="admin-grid">
    <div class="glass-panel admin-panel reveal">
        <h2>Servizi</h2>
        <form class="liquid-form single compact" method="post">
            <input type="hidden" name="action" value="service_save">
            <label>Nome<input name="name" required></label>
            <label>Descrizione<textarea name="description" rows="3"></textarea></label>
            <label>Durata minuti<input type="number" name="duration_minutes" min="5" value="30" required></label>
            <label>Prezzo<input type="number" name="price" min="0" step="0.01" required></label>
            <label class="check"><input type="checkbox" name="is_active" checked> Attivo</label>
            <button class="btn primary" type="submit">Aggiungi servizio</button>
        </form>
        <div class="stack-list">
            <?php foreach ($services as $service): ?>
                <details class="mini-card">
                    <summary><?= e($service['name']) ?> <span>€<?= number_format((float) $service['price'], 2, ',', '.') ?></span></summary>
                    <form class="liquid-form single compact" method="post">
                        <input type="hidden" name="action" value="service_save"><input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                        <label>Nome<input name="name" value="<?= e($service['name']) ?>" required></label>
                        <label>Descrizione<textarea name="description" rows="3"><?= e($service['description']) ?></textarea></label>
                        <label>Durata<input type="number" name="duration_minutes" value="<?= (int) $service['duration_minutes'] ?>" required></label>
                        <label>Prezzo<input type="number" step="0.01" name="price" value="<?= e($service['price']) ?>" required></label>
                        <label class="check"><input type="checkbox" name="is_active" <?= $service['is_active'] ? 'checked' : '' ?>> Attivo</label>
                        <button class="btn ghost" type="submit">Salva</button>
                    </form>
                    <form method="post" onsubmit="return confirm('Eliminare il servizio?')"><input type="hidden" name="action" value="service_delete"><input type="hidden" name="id" value="<?= (int) $service['id'] ?>"><button class="text-danger" type="submit">Elimina</button></form>
                </details>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="glass-panel admin-panel reveal delay-1">
        <h2>Calendario appuntamenti</h2>
        <div class="calendar-list">
            <?php foreach ($appointments as $appointment): ?>
                <article class="calendar-item <?= status_class($appointment['status']) ?>">
                    <time><?= date('d/m', strtotime($appointment['appointment_at'])) ?><span><?= date('H:i', strtotime($appointment['appointment_at'])) ?></span></time>
                    <div>
                        <h3><?= e($appointment['guest_name']) ?> · <?= e($appointment['service_name']) ?></h3>
                        <p><?= e($appointment['guest_email']) ?> <?= e($appointment['guest_phone']) ?></p>
                        <form class="inline-admin" method="post">
                            <input type="hidden" name="action" value="appointment_status"><input type="hidden" name="id" value="<?= (int) $appointment['id'] ?>">
                            <select name="status"><option value="pending" <?= $appointment['status'] === 'pending' ? 'selected' : '' ?>>In attesa</option><option value="confirmed" <?= $appointment['status'] === 'confirmed' ? 'selected' : '' ?>>Confermato</option><option value="cancelled" <?= $appointment['status'] === 'cancelled' ? 'selected' : '' ?>>Annullato</option></select>
                            <input name="admin_notes" placeholder="Note admin" value="<?= e($appointment['admin_notes']) ?>">
                            <button class="btn mini" type="submit">Aggiorna</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="table-card glass-panel reveal">
    <h2>Utenti registrati</h2>
    <div class="responsive-table"><table><thead><tr><th>Nome</th><th>Email</th><th>Telefono</th><th>Registrazione</th></tr></thead><tbody>
    <?php foreach ($customers as $customer): ?><tr><td><?= e($customer['name']) ?></td><td><?= e($customer['email']) ?></td><td><?= e($customer['phone']) ?></td><td><?= date('d/m/Y', strtotime($customer['created_at'])) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
<?php render_footer(); ?>
