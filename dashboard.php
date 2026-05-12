<?php
require_once __DIR__ . '/functions.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'appointment_delete') {
    $stmt = db()->prepare('DELETE FROM appointments WHERE id = ? AND (user_id = ? OR guest_email = ?)');
    $stmt->execute([(int) $_POST['id'], $user['id'], $user['email']]);
    flash('Prenotazione eliminata.');
    header('Location: dashboard.php');
    exit;
}

$stmt = db()->prepare('SELECT a.*, s.name AS service_name, s.duration_minutes FROM appointments a JOIN services s ON s.id = a.service_id WHERE a.user_id = ? OR a.guest_email = ? ORDER BY a.appointment_at DESC');
$stmt->execute([$user['id'], $user['email']]);
$appointments = $stmt->fetchAll();
render_header('Area utente');
?>
<section class="dashboard-head reveal">
    <div>
        <p class="eyebrow">Area personale</p>
        <h1>Ciao, <?= e($user['name']) ?>.</h1>
        <p>Monitora le richieste inviate come utente registrato o associate alla tua email.</p>
    </div>
    <div class="hero-actions compact-actions">
        <a class="btn ghost" href="profile.php">Modifica profilo</a>
        <a class="btn primary" href="index.php#prenota">Nuova richiesta</a>
    </div>
</section>
<section class="table-card glass-panel reveal">
    <h2>I tuoi appuntamenti</h2>
    <div class="responsive-table">
        <table>
            <thead><tr><th>Data</th><th>Servizio</th><th>Durata</th><th>Stato</th><th>Note</th><th>Azioni</th></tr></thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($appointment['appointment_at'])) ?></td>
                        <td><?= e($appointment['service_name']) ?></td>
                        <td><?= (int) $appointment['duration_minutes'] ?> min</td>
                        <td><span class="badge <?= status_class($appointment['status']) ?>"><?= appointment_status_label($appointment['status']) ?></span></td>
                        <td><?= e($appointment['notes']) ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('Eliminare questa prenotazione?')">
                                <input type="hidden" name="action" value="appointment_delete">
                                <input type="hidden" name="id" value="<?= (int) $appointment['id'] ?>">
                                <button class="btn mini danger-btn" type="submit">Elimina</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$appointments): ?><tr><td colspan="6">Non hai ancora richieste.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php render_footer(); ?>
