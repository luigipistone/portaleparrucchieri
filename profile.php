<?php
require_once __DIR__ . '/functions.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $newPasswordConfirm = $_POST['new_password_confirm'] ?? '';

    if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('Inserisci nome ed email valida.', 'danger');
    } else {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $stmt->execute([$email, $user['id']]);
        $emailAlreadyUsed = (bool) $stmt->fetch();

        if ($emailAlreadyUsed) {
            flash('Email già utilizzata da un altro account.', 'danger');
        } elseif ($newPassword || $newPasswordConfirm || $currentPassword) {
            if (!password_verify($currentPassword, $user['password_hash'])) {
                flash('La password attuale non è corretta.', 'danger');
            } elseif (strlen($newPassword) < 8) {
                flash('La nuova password deve avere almeno 8 caratteri.', 'danger');
            } elseif ($newPassword !== $newPasswordConfirm) {
                flash('La conferma della nuova password non coincide.', 'danger');
            } else {
                $stmt = db()->prepare('UPDATE users SET name = ?, email = ?, phone = ?, password_hash = ? WHERE id = ?');
                $stmt->execute([$name, $email, $phone, password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);
                flash('Profilo e password aggiornati con successo.');
                header('Location: profile.php');
                exit;
            }
        } else {
            $stmt = db()->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?');
            $stmt->execute([$name, $email, $phone, $user['id']]);
            flash('Profilo aggiornato con successo.');
            header('Location: profile.php');
            exit;
        }
    }

    $user = current_user() ?: $user;
}

render_header('Modifica profilo');
?>
<section class="dashboard-head reveal">
    <div>
        <p class="eyebrow">Area personale</p>
        <h1>Modifica profilo</h1>
        <p>Aggiorna i tuoi dati di contatto o cambia la password del tuo account.</p>
    </div>
    <a class="btn ghost" href="dashboard.php">Torna alla dashboard</a>
</section>

<section class="profile-grid">
    <form class="profile-card glass-panel reveal liquid-form single" method="post">
        <div>
            <p class="eyebrow">Dati account</p>
            <h2>Informazioni personali</h2>
        </div>
        <label>Nome
            <input type="text" name="name" value="<?= e($user['name']) ?>" required>
        </label>
        <label>Email
            <input type="email" name="email" value="<?= e($user['email']) ?>" required>
        </label>
        <label>Telefono
            <input type="tel" name="phone" value="<?= e($user['phone']) ?>">
        </label>

        <div class="password-panel">
            <p class="eyebrow">Cambio password opzionale</p>
            <p>Compila questi campi solo se vuoi impostare una nuova password.</p>
            <label>Password attuale
                <input type="password" name="current_password" autocomplete="current-password">
            </label>
            <label>Nuova password
                <input type="password" name="new_password" minlength="8" autocomplete="new-password">
            </label>
            <label>Conferma nuova password
                <input type="password" name="new_password_confirm" minlength="8" autocomplete="new-password">
            </label>
        </div>

        <button class="btn primary magnet" type="submit">Salva modifiche</button>
    </form>

    <aside class="profile-card glass-panel reveal delay-1">
        <p class="eyebrow">Riepilogo</p>
        <h2><?= e($user['name']) ?></h2>
        <div class="profile-summary">
            <span>Email</span><strong><?= e($user['email']) ?></strong>
            <span>Telefono</span><strong><?= e($user['phone'] ?: 'Non indicato') ?></strong>
            <span>Ruolo</span><strong><?= $user['role'] === 'admin' ? 'Amministratore' : 'Cliente' ?></strong>
            <span>Account creato</span><strong><?= date('d/m/Y', strtotime($user['created_at'])) ?></strong>
        </div>
    </aside>
</section>
<?php render_footer(); ?>
