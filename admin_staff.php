<?php
require_once __DIR__ . '/functions.php';
require_admin();
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'staff_save') {
        $id = (int) ($_POST['id'] ?? 0);
        $data = [trim($_POST['name'] ?? ''), trim($_POST['role_title'] ?? ''), trim($_POST['bio'] ?? ''), isset($_POST['is_active']) ? 1 : 0];

        if (!$data[0] || !$data[1]) {
            flash('Inserisci nome e ruolo dello staff.', 'danger');
        } elseif ($id) {
            $stmt = $pdo->prepare('UPDATE staff_members SET name = ?, role_title = ?, bio = ?, is_active = ? WHERE id = ?');
            $stmt->execute([...$data, $id]);
            flash('Operatore aggiornato.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO staff_members (name, role_title, bio, is_active) VALUES (?, ?, ?, ?)');
            $stmt->execute($data);
            flash('Operatore aggiunto.');
        }
    }

    if ($action === 'staff_delete') {
        try {
            $stmt = $pdo->prepare('DELETE FROM staff_members WHERE id = ?');
            $stmt->execute([(int) $_POST['id']]);
            flash('Operatore eliminato.');
        } catch (PDOException $exception) {
            flash('Impossibile eliminare un operatore collegato ad appuntamenti. Disattivalo se non vuoi più mostrarlo.', 'danger');
        }
    }

    header('Location: admin_staff.php');
    exit;
}

$staffMembers = $pdo->query('SELECT * FROM staff_members ORDER BY is_active DESC, name ASC')->fetchAll();
render_header('Staff admin');
?>
<section class="dashboard-head reveal">
    <div>
        <p class="eyebrow">Backoffice</p>
        <h1>Staff</h1>
        <p>Gestisci gli operatori selezionabili in prenotazione, come nella logica salone con agenda per professionista.</p>
    </div>
</section>

<section class="backend-layout">
    <?php render_admin_menu('staff'); ?>
    <div class="backend-content admin-grid single-column">
        <div class="glass-panel admin-panel reveal delay-1">
            <h2>Nuovo operatore</h2>
            <form class="liquid-form service-form" method="post">
                <input type="hidden" name="action" value="staff_save">
                <label><span class="field-label">Nome <span class="required-marker" aria-hidden="true">*</span></span><input name="name" required></label>
                <label><span class="field-label">Ruolo <span class="required-marker" aria-hidden="true">*</span></span><input name="role_title" value="Barber" required></label>
                <label class="check"><input type="checkbox" name="is_active" checked> Attivo</label>
                <label class="full">Bio<textarea name="bio" rows="3"></textarea></label>
                <button class="btn primary full" type="submit">Aggiungi operatore</button>
            </form>
        </div>

        <div class="glass-panel admin-panel reveal delay-1">
            <h2>Operatori</h2>
            <div class="stack-list">
                <?php foreach ($staffMembers as $member): ?>
                    <details class="mini-card">
                        <summary>
                            <?= e($member['name']) ?>
                            <span><?= $member['is_active'] ? 'Attivo' : 'Nascosto' ?> · <?= e($member['role_title']) ?></span>
                        </summary>
                        <form class="liquid-form service-form" method="post">
                            <input type="hidden" name="action" value="staff_save"><input type="hidden" name="id" value="<?= (int) $member['id'] ?>">
                            <label><span class="field-label">Nome <span class="required-marker" aria-hidden="true">*</span></span><input name="name" value="<?= e($member['name']) ?>" required></label>
                            <label><span class="field-label">Ruolo <span class="required-marker" aria-hidden="true">*</span></span><input name="role_title" value="<?= e($member['role_title']) ?>" required></label>
                            <label class="check"><input type="checkbox" name="is_active" <?= $member['is_active'] ? 'checked' : '' ?>> Attivo</label>
                            <label class="full">Bio<textarea name="bio" rows="3"><?= e($member['bio']) ?></textarea></label>
                            <button class="btn ghost full" type="submit">Salva modifiche</button>
                        </form>
                        <form method="post" onsubmit="return confirm('Eliminare questo operatore?')"><input type="hidden" name="action" value="staff_delete"><input type="hidden" name="id" value="<?= (int) $member['id'] ?>"><button class="icon-delete" type="submit" aria-label="Elimina operatore" title="Elimina operatore"><?= icon_svg('trash') ?></button></form>
                    </details>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php render_footer(); ?>
