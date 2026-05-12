<?php
require_once __DIR__ . '/functions.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$appointments = [];
$searched = false;
$pdo = db();
$services = $pdo->query('SELECT id, name FROM services WHERE is_active = 1 ORDER BY name ASC')->fetchAll();
$staffMembers = $pdo->query('SELECT id, name, role_title FROM staff_members WHERE is_active = 1 ORDER BY name ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['appointment_update', 'appointment_delete'], true)) {
    $action = $_POST['action'];
    $appointmentId = (int) ($_POST['id'] ?? 0);
    $bookingToken = trim($_POST['booking_token'] ?? '');

    if ($action === 'appointment_delete') {
        $stmt = $pdo->prepare('DELETE FROM appointments WHERE id = ? AND booking_token = ? AND status = "pending"');
        $stmt->execute([$appointmentId, $bookingToken]);
        flash($stmt->rowCount() > 0 ? 'Prenotazione eliminata.' : 'Puoi eliminare solo prenotazioni guest in attesa.', $stmt->rowCount() > 0 ? 'success' : 'danger');
        header('Location: booking_lookup.php' . ($stmt->rowCount() > 0 ? '' : '?token=' . urlencode($bookingToken)));
        exit;
    }

    $serviceId = (int) ($_POST['service_id'] ?? 0);
    $staffId = (int) ($_POST['staff_id'] ?? 0);
    $appointmentAt = trim($_POST['appointment_at'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (!$serviceId || !$staffId || !$appointmentAt) {
        flash('Seleziona servizio, data e ora per modificare la prenotazione.', 'danger');
    } else {
        $checkStmt = $pdo->prepare('SELECT id FROM appointments WHERE id = ? AND booking_token = ? AND status = "pending"');
        $checkStmt->execute([$appointmentId, $bookingToken]);

        if ($checkStmt->fetch()) {
            $appointmentDate = str_replace('T', ' ', $appointmentAt) . ':00';
            $stmt = $pdo->prepare('UPDATE appointments SET service_id = ?, staff_id = ?, appointment_at = ?, notes = ? WHERE id = ?');
            $stmt->execute([$serviceId, $staffId, $appointmentDate, $notes, $appointmentId]);
            flash('Prenotazione modificata.');
        } else {
            flash('Puoi modificare solo prenotazioni guest in attesa.', 'danger');
        }
    }

    header('Location: booking_lookup.php?token=' . urlencode($bookingToken));
    exit;
}

if ($token) {
    $stmt = $pdo->prepare('SELECT a.*, s.name AS service_name, s.duration_minutes, s.price, st.name AS staff_name, st.role_title AS staff_role FROM appointments a JOIN services s ON s.id = a.service_id LEFT JOIN staff_members st ON st.id = a.staff_id WHERE a.booking_token = ? ORDER BY a.appointment_at DESC');
    $stmt->execute([$token]);
    $appointments = $stmt->fetchAll();
    $searched = true;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searched = true;
    if (!$email || !$phone) {
        flash('Inserisci email e telefono usati nella richiesta.', 'danger');
    } else {
        $phoneDigits = preg_replace('/\D+/', '', $phone);
        $stmt = $pdo->prepare('SELECT a.*, s.name AS service_name, s.duration_minutes, s.price, st.name AS staff_name, st.role_title AS staff_role FROM appointments a JOIN services s ON s.id = a.service_id LEFT JOIN staff_members st ON st.id = a.staff_id WHERE a.guest_email = ? AND REPLACE(REPLACE(REPLACE(REPLACE(a.guest_phone, " ", ""), "+", ""), "-", ""), "/", "") LIKE ? ORDER BY a.appointment_at DESC');
        $stmt->execute([$email, '%' . $phoneDigits . '%']);
        $appointments = $stmt->fetchAll();
    }
}

render_header('Trova prenotazione');
?>
<section class="dashboard-head reveal">
    <div>
        <p class="eyebrow">Prenotazioni guest</p>
        <h1>Trova la tua richiesta</h1>
        <p>Inserisci il codice prenotazione ricevuto via email oppure usa email e telefono lasciati nella richiesta.</p>
    </div>
    <a class="btn primary" href="index.php#prenota">Nuova richiesta</a>
</section>

<section class="booking-lookup-grid">
    <form class="profile-card glass-panel reveal liquid-form single" method="post">
        <p class="eyebrow">Ricerca</p>
        <label>Codice prenotazione
            <input name="token" value="<?= e($token) ?>" placeholder="Codice a 6 cifre" inputmode="numeric" pattern="[0-9]{6,7}" maxlength="7">
        </label>
        <label>Email usata in prenotazione
            <input type="email" name="email" value="<?= e($email) ?>">
        </label>
        <label>Telefono usato in prenotazione
            <input type="tel" name="phone" value="<?= e($phone) ?>">
        </label>
        <button class="btn primary magnet" type="submit">Cerca prenotazione</button>
    </form>

    <div class="backend-content">
        <?php if ($searched && !$appointments): ?>
            <div class="profile-card glass-panel reveal"><p>Nessuna prenotazione trovata con questi dati.</p></div>
        <?php endif; ?>

        <?php foreach ($appointments as $appointment): ?>
            <?php $lookupUrl = booking_lookup_url($appointment['booking_token']); $qrUrl = qr_code_url($lookupUrl); $whatsappSummary = whatsapp_message_link($appointment['guest_phone'], 'Codice prenotazione ' . APP_NAME . ': ' . $appointment['booking_token'] . ' - QR/link: ' . $lookupUrl); ?>
            <article class="profile-card glass-panel reveal booking-result">
                <div class="booking-result-head">
                    <div>
                        <p class="eyebrow">Stato richiesta</p>
                        <h2><?= e($appointment['service_name']) ?></h2>
                    </div>
                    <div class="booking-status-actions">
                        <span class="badge <?= status_class($appointment['status']) ?>"><?= appointment_status_label($appointment['status']) ?></span>
                        <?php if ($whatsappSummary): ?><a class="btn whatsapp-btn" href="<?= e($whatsappSummary) ?>" target="_blank" rel="noopener">Inviami token e QR su WhatsApp</a><?php endif; ?>
                        <?php if ($appointment['status'] === 'pending'): ?>
                            <div class="appointment-icon-actions">
                                <details class="edit-details">
                                    <summary class="icon-action" aria-label="Modifica prenotazione" title="Modifica prenotazione"><?= icon_svg('edit') ?></summary>
                                    <form class="appointment-edit-form" method="post">
                                        <input type="hidden" name="action" value="appointment_update">
                                        <input type="hidden" name="id" value="<?= (int) $appointment['id'] ?>">
                                        <input type="hidden" name="booking_token" value="<?= e($appointment['booking_token']) ?>">
                                        <label>Servizio
                                            <select name="service_id" required>
                                                <?php foreach ($services as $service): ?>
                                                    <option value="<?= (int) $service['id'] ?>" <?= (int) $service['id'] === (int) $appointment['service_id'] ? 'selected' : '' ?>><?= e($service['name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label>Operatore
                                            <select name="staff_id" required>
                                                <?php foreach ($staffMembers as $member): ?>
                                                    <option value="<?= (int) $member['id'] ?>" <?= (int) $member['id'] === (int) $appointment['staff_id'] ? 'selected' : '' ?>><?= e($member['name']) ?> · <?= e($member['role_title']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </label>
                                        <label>Data e ora
                                            <input class="liquid-datetime" type="datetime-local" name="appointment_at" value="<?= date('Y-m-d\TH:i', strtotime($appointment['appointment_at'])) ?>" required>
                                        </label>
                                        <label>Note
                                            <textarea name="notes" rows="2"><?= e($appointment['notes']) ?></textarea>
                                        </label>
                                        <button class="btn mini primary" type="submit">Salva</button>
                                    </form>
                                </details>
                                <form method="post" onsubmit="return confirm('Eliminare questa prenotazione?')">
                                    <input type="hidden" name="action" value="appointment_delete">
                                    <input type="hidden" name="id" value="<?= (int) $appointment['id'] ?>">
                                    <input type="hidden" name="booking_token" value="<?= e($appointment['booking_token']) ?>">
                                    <button class="icon-delete" type="submit" aria-label="Elimina prenotazione" title="Elimina prenotazione"><?= icon_svg('trash') ?></button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="profile-summary">
                    <span>Data</span><strong><?= date('d/m/Y H:i', strtotime($appointment['appointment_at'])) ?></strong>
                    <span>Nome</span><strong><?= e($appointment['guest_name']) ?></strong>
                    <span>Telefono</span><strong><?= e($appointment['guest_phone']) ?></strong>
                    <span>Operatore</span><strong><?= e($appointment['staff_name'] ?? 'Da assegnare') ?></strong>
                    <span>Durata</span><strong><?= (int) $appointment['duration_minutes'] ?> min</strong>
                    <span>Prezzo</span><strong>€<?= number_format((float) $appointment['price'], 2, ',', '.') ?></strong>
                    <?php if ($appointment['notes']): ?><span>Note</span><strong><?= e($appointment['notes']) ?></strong><?php endif; ?>
                </div>
                <?php if ($appointment['booking_token']): ?>
                    <div class="booking-token-box">
                        <span>Codice prenotazione</span>
                        <strong class="booking-token-code"><?= e($appointment['booking_token']) ?></strong>
                        <img class="qr-card" src="<?= e($qrUrl) ?>" alt="QR code per aprire il riepilogo prenotazione">
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php render_footer(); ?>
