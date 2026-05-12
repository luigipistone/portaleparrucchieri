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
        <p>Inserisci email e telefono o apri il link personale ricevuto dopo la richiesta per controllare lo stato.</p>
    </div>
    <a class="btn primary" href="index.php#prenota">Nuova richiesta</a>
</section>

<section class="booking-lookup-grid">
    <form class="profile-card glass-panel reveal liquid-form single" method="post">
        <p class="eyebrow">Ricerca</p>
        <label>Codice/link prenotazione
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
            <?php $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/'); $shareUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $basePath . '/booking_lookup.php?token=' . urlencode($appointment['booking_token']); ?>
            <article class="profile-card glass-panel reveal booking-result">
                <div>
                    <p class="eyebrow">Stato richiesta</p>
                    <h2><?= e($appointment['service_name']) ?></h2>
                    <span class="badge <?= status_class($appointment['status']) ?>"><?= appointment_status_label($appointment['status']) ?></span>
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
                        <span>Link personale</span>
                        <input readonly value="<?= e($shareUrl) ?>" onclick="this.select()">
                        <img class="qr-card" src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=<?= urlencode($shareUrl) ?>" alt="QR code per aprire il riepilogo prenotazione">
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php render_footer(); ?>
