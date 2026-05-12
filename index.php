<?php
require_once __DIR__ . '/functions.php';

$services = db()->query('SELECT * FROM services WHERE is_active = 1 ORDER BY price ASC')->fetchAll();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceId = (int) ($_POST['service_id'] ?? 0);
    $appointmentAt = trim($_POST['appointment_at'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $guestName = trim($_POST['guest_name'] ?? ($user['name'] ?? ''));
    $guestEmail = trim($_POST['guest_email'] ?? ($user['email'] ?? ''));
    $guestPhone = trim($_POST['guest_phone'] ?? ($user['phone'] ?? ''));

    if (!$serviceId || !$appointmentAt || !$guestName || !$guestEmail || !$guestPhone) {
        flash('Compila servizio, data, nome, email e telefono per richiedere l’appuntamento.', 'danger');
    } else {
        do {
            $bookingToken = make_booking_token();
            $stmt = db()->prepare('SELECT id FROM appointments WHERE booking_token = ?');
            $stmt->execute([$bookingToken]);
        } while ($stmt->fetch());

        $appointmentDate = str_replace('T', ' ', $appointmentAt) . ':00';
        $stmt = db()->prepare('INSERT INTO appointments (user_id, service_id, guest_name, guest_email, guest_phone, appointment_at, notes, booking_token, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, "pending")');
        $stmt->execute([$user['id'] ?? null, $serviceId, $guestName, $guestEmail, $guestPhone, $appointmentDate, $notes, $bookingToken]);

        $stmt = db()->prepare('SELECT a.*, s.name AS service_name FROM appointments a JOIN services s ON s.id = a.service_id WHERE a.id = ?');
        $stmt->execute([db()->lastInsertId()]);
        $createdAppointment = $stmt->fetch();
        if ($createdAppointment) {
            send_booking_summary_email($guestEmail, $createdAppointment);
        }

        flash('Richiesta inviata! Ti abbiamo inviato via email il codice prenotazione e il QR code.');
        header('Location: booking_lookup.php?token=' . urlencode($bookingToken));
        exit;
    }
}

render_header('Prenotazioni barbiere');
?>
<section class="hero section-grid">
    <div class="hero-copy reveal">
        <p class="eyebrow">Salone uomo · prenotazioni smart</p>
        <h1>Gestisci tagli, barbe e appuntamenti con un’esperienza liquida.</h1>
        <p>Frontend pubblico per clienti e ospiti, area utente e backoffice admin per confermare richieste, servizi e calendario.</p>
        <div class="hero-actions">
            <a class="btn primary magnet" href="#prenota">Richiedi appuntamento</a>
            <a class="btn ghost" href="login.php">Area riservata</a>
            <a class="btn ghost" href="booking_lookup.php">Trova prenotazione</a>
        </div>
    </div>
    <div class="hero-card glass-panel reveal delay-1">
        <div class="orbital-icon">💈</div>
        <h2>Next slot</h2>
        <p>Le prenotazioni entrano in stato <strong>in attesa</strong> e diventano effettive solo dopo conferma admin.</p>
        <div class="pulse-row"><span></span><span></span><span></span></div>
    </div>
</section>

<section id="servizi" class="content-section reveal">
    <div class="section-title">
        <p class="eyebrow">Menu servizi</p>
        <h2>Scegli il trattamento</h2>
    </div>
    <div class="cards-grid">
        <?php foreach ($services as $service): ?>
            <article class="service-card glass-panel">
                <div class="service-icon">✂</div>
                <h3><?= e($service['name']) ?></h3>
                <p><?= e($service['description']) ?></p>
                <div class="service-meta">
                    <span><?= (int) $service['duration_minutes'] ?> min</span>
                    <strong>€<?= number_format((float) $service['price'], 2, ',', '.') ?></strong>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section id="prenota" class="booking-shell glass-panel reveal">
    <div>
        <p class="eyebrow">Richiesta appuntamento</p>
        <h2>Dimmi quando passi.</h2>
        <p>Puoi prenotare come ospite o accedere per ritrovare tutte le tue richieste nella dashboard.</p>
    </div>
    <form class="liquid-form" method="post">
        <label>Servizio
            <select name="service_id" required>
                <option value="">Seleziona</option>
                <?php foreach ($services as $service): ?>
                    <option value="<?= (int) $service['id'] ?>"><?= e($service['name']) ?> · €<?= number_format((float) $service['price'], 2, ',', '.') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Data e ora
            <input class="liquid-datetime" type="datetime-local" name="appointment_at" step="1800" inputmode="none" onkeydown="return false" onpaste="return false" required>
        </label>
        <label>Nome
            <input type="text" name="guest_name" value="<?= e($user['name'] ?? '') ?>" required>
        </label>
        <label>Email
            <input type="email" name="guest_email" value="<?= e($user['email'] ?? '') ?>" required>
        </label>
        <label>Telefono
            <input type="tel" name="guest_phone" value="<?= e($user['phone'] ?? '') ?>" required>
        </label>
        <label class="full">Note
            <textarea name="notes" rows="4" placeholder="Preferenze, richieste particolari o stile desiderato"></textarea>
        </label>
        <button class="btn primary full magnet" type="submit">Invia richiesta</button>
    </form>
</section>
<?php render_footer(); ?>
