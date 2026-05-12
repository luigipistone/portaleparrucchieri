<?php
require_once __DIR__ . '/functions.php';
require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'service_save') {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [trim($_POST['name'] ?? ''), trim($_POST['description'] ?? ''), (int) ($_POST['duration_minutes'] ?? 0), (float) ($_POST['price'] ?? 0), isset($_POST['is_active']) ? 1 : 0];

        if (!$data[0] || $data[2] < 5 || $data[3] < 0) {
            flash('Controlla nome, durata e prezzo del servizio.', 'danger');
        } elseif ($id) {
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
        try {
            $stmt = $pdo->prepare('DELETE FROM services WHERE id = ?');
            $stmt->execute([(int) $_POST['id']]);
            flash('Servizio eliminato.');
        } catch (PDOException $exception) {
            flash('Impossibile eliminare un servizio collegato ad appuntamenti esistenti. Disattivalo se non vuoi più mostrarlo.', 'danger');
        }
    }

    header('Location: admin_services.php');
    exit;
}

$services = $pdo->query('SELECT * FROM services ORDER BY is_active DESC, name')->fetchAll();
render_header('Servizi admin');
?>
<section class="dashboard-head reveal">
    <div>
        <p class="eyebrow">Backoffice</p>
        <h1>Servizi</h1>
        <p>Aggiungi, modifica, disattiva o elimina i trattamenti selezionabili in fase di prenotazione.</p>
    </div>
</section>

<section class="backend-layout">
    <?php render_admin_menu('services'); ?>
    <div class="backend-content admin-grid single-column">
        <div class="glass-panel admin-panel reveal delay-1">
            <h2>Nuovo servizio</h2>
            <form class="liquid-form service-form" method="post">
                <input type="hidden" name="action" value="service_save">
                <label>Nome<input name="name" required></label>
                <label>Durata minuti<input type="number" name="duration_minutes" min="5" value="30" required></label>
                <label>Prezzo<input type="number" name="price" min="0" step="0.01" required></label>
                <label class="check"><input type="checkbox" name="is_active" checked> Attivo</label>
                <label class="full">Descrizione<textarea name="description" rows="3"></textarea></label>
                <button class="btn primary full" type="submit">Aggiungi servizio</button>
            </form>
        </div>

        <div class="glass-panel admin-panel reveal delay-1">
            <h2>Archivio servizi</h2>
            <div class="stack-list">
                <?php foreach ($services as $service): ?>
                    <details class="mini-card">
                        <summary>
                            <?= e($service['name']) ?>
                            <span><?= $service['is_active'] ? 'Attivo' : 'Nascosto' ?> · €<?= number_format((float) $service['price'], 2, ',', '.') ?></span>
                        </summary>
                        <form class="liquid-form service-form" method="post">
                            <input type="hidden" name="action" value="service_save"><input type="hidden" name="id" value="<?= (int) $service['id'] ?>">
                            <label>Nome<input name="name" value="<?= e($service['name']) ?>" required></label>
                            <label>Durata<input type="number" name="duration_minutes" value="<?= (int) $service['duration_minutes'] ?>" required></label>
                            <label>Prezzo<input type="number" step="0.01" name="price" value="<?= e($service['price']) ?>" required></label>
                            <label class="check"><input type="checkbox" name="is_active" <?= $service['is_active'] ? 'checked' : '' ?>> Attivo</label>
                            <label class="full">Descrizione<textarea name="description" rows="3"><?= e($service['description']) ?></textarea></label>
                            <button class="btn ghost full" type="submit">Salva modifiche</button>
                        </form>
                        <form method="post" onsubmit="return confirm('Eliminare il servizio?')"><input type="hidden" name="action" value="service_delete"><input type="hidden" name="id" value="<?= (int) $service['id'] ?>"><button class="text-danger" type="submit">Elimina</button></form>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php render_footer(); ?>
