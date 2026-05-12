<?php
require_once __DIR__ . '/functions.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$appointments = [];
$searched = false;

if ($token) {
    $stmt = db()->prepare('SELECT a.*, s.name AS service_name, s.duration_minutes, s.price FROM appointments a JOIN services s ON s.id = a.service_id WHERE a.booking_token = ? ORDER BY a.appointment_at DESC');
    $stmt->execute([$token]);
    $appointments = $stmt->fetchAll();
    $searched = true;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searched = true;
    if (!$email || !$phone) {
        flash('Inserisci email e telefono usati nella richiesta.', 'danger');
    } else {
        $phoneDigits = preg_replace('/\D+/', '', $phone);
        $stmt = db()->prepare('SELECT a.*, s.name AS service_name, s.duration_minutes, s.price FROM appointments a JOIN services s ON s.id = a.service_id WHERE a.guest_email = ? AND REPLACE(REPLACE(REPLACE(REPLACE(a.guest_phone, " ", ""), "+", ""), "-", ""), "/", "") LIKE ? ORDER BY a.appointment_at DESC');
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
            <input name="token" value="<?= e($token) ?>" placeholder="Token opzionale">
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
                    </div>
                </div>
                <div class="profile-summary">
                    <span>Data</span><strong><?= date('d/m/Y H:i', strtotime($appointment['appointment_at'])) ?></strong>
                    <span>Nome</span><strong><?= e($appointment['guest_name']) ?></strong>
                    <span>Telefono</span><strong><?= e($appointment['guest_phone']) ?></strong>
                    <span>Durata</span><strong><?= (int) $appointment['duration_minutes'] ?> min</strong>
                    <span>Prezzo</span><strong>€<?= number_format((float) $appointment['price'], 2, ',', '.') ?></strong>
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
