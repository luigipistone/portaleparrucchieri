<?php
require_once __DIR__ . '/functions.php';
require_admin();
$pdo = db();

$customers = $pdo->query('SELECT u.id, u.name, u.email, u.phone, u.created_at, COUNT(a.id) AS appointments_count, MAX(a.appointment_at) AS last_appointment FROM users u LEFT JOIN appointments a ON a.user_id = u.id OR a.guest_email = u.email WHERE u.role = "customer" GROUP BY u.id, u.name, u.email, u.phone, u.created_at ORDER BY u.created_at DESC')->fetchAll();
$guestBookings = $pdo->query('SELECT guest_name, guest_email, guest_phone, COUNT(*) AS appointments_count, MAX(appointment_at) AS last_appointment FROM appointments WHERE user_id IS NULL GROUP BY guest_name, guest_email, guest_phone ORDER BY last_appointment DESC')->fetchAll();
render_header('Utenti admin');
?>
<section class="dashboard-head reveal">
    <div>
        <p class="eyebrow">Backoffice</p>
        <h1>Utenti registrati</h1>
        <p>Visualizza clienti con account e ospiti che hanno inviato richieste di appuntamento.</p>
    </div>
</section>

<section class="backend-layout">
    <?php render_admin_menu('users'); ?>
    <div class="backend-content">
        <section class="table-card glass-panel reveal delay-1">
            <div class="section-title compact-title"><div><p class="eyebrow">Account clienti</p><h2>Registrati</h2></div></div>
            <div class="responsive-table">
                <table>
                    <thead><tr><th>Nome</th><th>Email</th><th>Telefono</th><th>Richieste</th><th>Ultimo appuntamento</th><th>Registrazione</th></tr></thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?= e($customer['name']) ?></td>
                                <td><?= e($customer['email']) ?></td>
                                <td><?= e($customer['phone']) ?></td>
                                <td><?= (int) $customer['appointments_count'] ?></td>
                                <td><?= $customer['last_appointment'] ? date('d/m/Y H:i', strtotime($customer['last_appointment'])) : '—' ?></td>
                                <td><?= date('d/m/Y', strtotime($customer['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$customers): ?><tr><td colspan="6">Nessun utente registrato.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="table-card glass-panel reveal delay-1">
            <div class="section-title compact-title"><div><p class="eyebrow">Prenotazioni ospiti</p><h2>Guest</h2></div></div>
            <div class="responsive-table">
                <table>
                    <thead><tr><th>Nome</th><th>Email</th><th>Telefono</th><th>Richieste</th><th>Ultimo appuntamento</th></tr></thead>
                    <tbody>
                        <?php foreach ($guestBookings as $guest): ?>
                            <tr>
                                <td><?= e($guest['guest_name']) ?></td>
                                <td><?= e($guest['guest_email']) ?></td>
                                <td><?= e($guest['guest_phone']) ?></td>
                                <td><?= (int) $guest['appointments_count'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($guest['last_appointment'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$guestBookings): ?><tr><td colspan="5">Nessuna richiesta ospite.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>
<?php render_footer(); ?>
