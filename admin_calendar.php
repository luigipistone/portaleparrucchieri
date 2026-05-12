<?php
require_once __DIR__ . '/functions.php';
require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? 'pending';
    if (!in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
        flash('Stato appuntamento non valido.', 'danger');
    } else {
        $stmt = $pdo->prepare('UPDATE appointments SET status = ?, admin_notes = ? WHERE id = ?');
        $stmt->execute([$status, trim($_POST['admin_notes'] ?? ''), (int) $_POST['id']]);
        flash('Appuntamento aggiornato.');
    }

    header('Location: admin_calendar.php');
    exit;
}

$statusFilter = $_GET['status'] ?? 'all';
$where = '';
$params = [];
if (in_array($statusFilter, ['pending', 'confirmed', 'cancelled'], true)) {
    $where = 'WHERE a.status = ?';
    $params[] = $statusFilter;
}

$stmt = $pdo->prepare("SELECT a.*, s.name AS service_name, s.duration_minutes FROM appointments a JOIN services s ON s.id = a.service_id $where ORDER BY a.appointment_at ASC");
$stmt->execute($params);
$appointments = $stmt->fetchAll();
render_header('Calendario admin');
?>
<section class="dashboard-head reveal">
    <div>
        <p class="eyebrow">Backoffice</p>
        <h1>Calendario appuntamenti</h1>
        <p>Consulta tutte le richieste, filtra per stato e conferma o annulla gli slot.</p>
    </div>
</section>

<section class="backend-layout">
    <?php render_admin_menu('calendar'); ?>
    <div class="backend-content">
        <div class="filter-tabs glass-panel reveal delay-1">
            <a class="<?= $statusFilter === 'all' ? 'active' : '' ?>" href="admin_calendar.php" data-ajax-link>Tutti</a>
            <a class="<?= $statusFilter === 'pending' ? 'active' : '' ?>" href="admin_calendar.php?status=pending" data-ajax-link>In attesa</a>
            <a class="<?= $statusFilter === 'confirmed' ? 'active' : '' ?>" href="admin_calendar.php?status=confirmed" data-ajax-link>Confermati</a>
            <a class="<?= $statusFilter === 'cancelled' ? 'active' : '' ?>" href="admin_calendar.php?status=cancelled" data-ajax-link>Annullati</a>
        </div>

        <div class="glass-panel admin-panel reveal delay-1">
            <h2>Agenda</h2>
            <div class="calendar-list">
                <?php foreach ($appointments as $appointment): ?>
                    <article class="calendar-item <?= status_class($appointment['status']) ?>">
                        <time><?= date('d/m', strtotime($appointment['appointment_at'])) ?><span><?= date('H:i', strtotime($appointment['appointment_at'])) ?></span></time>
                        <div>
                            <?php $whatsappLink = whatsapp_link($appointment['guest_phone']); ?>
                            <h3><?= e($appointment['guest_name']) ?> · <?= e($appointment['service_name']) ?></h3>
                            <p><?= e($appointment['guest_email']) ?> <?= e($appointment['guest_phone']) ?> · <?= (int) $appointment['duration_minutes'] ?> min</p>
                            <?php if ($appointment['booking_token']): ?><p><strong>Codice prenotazione:</strong> <code><?= e($appointment['booking_token']) ?></code></p><?php endif; ?>
                            <?php if ($whatsappLink): ?><a class="btn whatsapp-btn" href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
                            <?php if ($appointment['notes']): ?><p><strong>Note cliente:</strong> <?= e($appointment['notes']) ?></p><?php endif; ?>
                            <form class="inline-admin" method="post">
                                <input type="hidden" name="id" value="<?= (int) $appointment['id'] ?>">
                                <select name="status"><option value="pending" <?= $appointment['status'] === 'pending' ? 'selected' : '' ?>>In attesa</option><option value="confirmed" <?= $appointment['status'] === 'confirmed' ? 'selected' : '' ?>>Confermato</option><option value="cancelled" <?= $appointment['status'] === 'cancelled' ? 'selected' : '' ?>>Annullato</option></select>
                                <input name="admin_notes" placeholder="Note admin" value="<?= e($appointment['admin_notes']) ?>">
                                <button class="btn mini" type="submit">Aggiorna</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (!$appointments): ?><p>Nessun appuntamento trovato per questo filtro.</p><?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php render_footer(); ?>
