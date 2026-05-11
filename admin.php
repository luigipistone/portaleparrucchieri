<?php
require_once __DIR__ . '/functions.php';
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
