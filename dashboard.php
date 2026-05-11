<?php
require_once __DIR__ . '/functions.php';
$user = require_login();
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
    <a class="btn primary" href="index.php#prenota">Nuova richiesta</a>
</section>
<section class="table-card glass-panel reveal">
    <h2>I tuoi appuntamenti</h2>
    <div class="responsive-table">
        <table>
            <thead><tr><th>Data</th><th>Servizio</th><th>Durata</th><th>Stato</th><th>Note</th></tr></thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i', strtotime($appointment['appointment_at'])) ?></td>
                        <td><?= e($appointment['service_name']) ?></td>
                        <td><?= (int) $appointment['duration_minutes'] ?> min</td>
                        <td><span class="badge <?= status_class($appointment['status']) ?>"><?= appointment_status_label($appointment['status']) ?></span></td>
                        <td><?= e($appointment['notes']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$appointments): ?><tr><td colspan="5">Non hai ancora richieste.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php render_footer(); ?>
