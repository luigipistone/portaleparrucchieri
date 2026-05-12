<?php
require_once __DIR__ . '/functions.php';
require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'appointment_status';

    if ($action === 'appointment_delete') {
        $stmt = $pdo->prepare('DELETE FROM appointments WHERE id = ?');
        $stmt->execute([(int) $_POST['id']]);
        flash('Prenotazione eliminata.');
    } else {
        $status = $_POST['status'] ?? 'pending';
        if (!in_array($status, ['pending', 'confirmed', 'cancelled', 'completed'], true)) {
            flash('Stato appuntamento non valido.', 'danger');
        } else {
            $stmt = $pdo->prepare('UPDATE appointments SET status = ?, admin_notes = ? WHERE id = ?');
            $stmt->execute([$status, trim($_POST['admin_notes'] ?? ''), (int) $_POST['id']]);
            flash('Appuntamento aggiornato.');
        }
    }

    header('Location: admin_calendar.php');
    exit;
}

$statusFilter = $_GET['status'] ?? 'all';
$where = '';
$params = [];
if (in_array($statusFilter, ['pending', 'confirmed', 'cancelled', 'completed'], true)) {
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
            <a class="<?= $statusFilter === 'completed' ? 'active' : '' ?>" href="admin_calendar.php?status=completed" data-ajax-link>Usufruiti</a>
        </div>

        <div class="glass-panel admin-panel reveal delay-1">
            <h2>Agenda</h2>
            <div class="calendar-list compact-calendar">
                <?php foreach ($appointments as $appointment): ?>
                    <article class="calendar-item <?= status_class($appointment['status']) ?>">
                        <time><?= date('d/m', strtotime($appointment['appointment_at'])) ?><span><?= date('H:i', strtotime($appointment['appointment_at'])) ?></span></time>
                        <div class="appointment-row">
                            <?php $whatsappLink = whatsapp_link($appointment['guest_phone']); ?>
                            <div>
                                <h3><?= e($appointment['guest_name']) ?> · <?= e($appointment['service_name']) ?></h3>
                                <div class="appointment-meta">
                                    <span><?= e($appointment['guest_email']) ?></span>
                                    <span><?= e($appointment['guest_phone']) ?></span>
                                    <span><?= (int) $appointment['duration_minutes'] ?> min</span>
                                    <?php if ($appointment['booking_token']): ?><code>Cod. <?= e($appointment['booking_token']) ?></code><?php endif; ?>
                                </div>
                                <?php if ($appointment['notes']): ?><p class="appointment-notes">Note: <?= e($appointment['notes']) ?></p><?php endif; ?>
                            </div>
                            <div class="appointment-actions">
                                <?php if ($whatsappLink): ?><a class="btn whatsapp-btn" href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
                            </div>
                            <div class="appointment-forms">
                                <form class="inline-admin" method="post">
                                    <input type="hidden" name="action" value="appointment_status">
                                    <input type="hidden" name="id" value="<?= (int) $appointment['id'] ?>">
                                    <select name="status"><option value="pending" <?= $appointment['status'] === 'pending' ? 'selected' : '' ?>>In attesa</option><option value="confirmed" <?= $appointment['status'] === 'confirmed' ? 'selected' : '' ?>>Confermato</option><option value="cancelled" <?= $appointment['status'] === 'cancelled' ? 'selected' : '' ?>>Annullato</option><option value="completed" <?= $appointment['status'] === 'completed' ? 'selected' : '' ?>>Usufruito</option></select>
                                    <input name="admin_notes" placeholder="Note admin" value="<?= e($appointment['admin_notes']) ?>">
                                    <button class="btn mini" type="submit">Aggiorna</button>
                                </form>
                                <form method="post" onsubmit="return confirm('Eliminare definitivamente questa prenotazione?')">
                                    <input type="hidden" name="action" value="appointment_delete">
                                    <input type="hidden" name="id" value="<?= (int) $appointment['id'] ?>">
                                    <button class="icon-delete" type="submit" aria-label="Elimina prenotazione" title="Elimina prenotazione">🗑</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
                <?php if (!$appointments): ?><p>Nessun appuntamento trovato per questo filtro.</p><?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php render_footer(); ?>
