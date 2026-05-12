<?php
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: ' . ($user['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
        exit;
    }

    flash('Credenziali non valide.', 'danger');
}

render_header('Accesso');
?>
<section class="auth-card glass-panel reveal">
    <p class="eyebrow">Bentornato</p>
    <h1>Accedi alla tua area</h1>
    <form class="liquid-form single" method="post">
        <label><span class="field-label">Email <span class="required-marker" aria-hidden="true">*</span></span><input type="email" name="email" required></label>
        <label><span class="field-label">Password <span class="required-marker" aria-hidden="true">*</span></span><input type="password" name="password" required></label>
        <button class="btn primary magnet" type="submit">Accedi</button>
    </form>
    <p>Non hai un account? <a href="register.php">Registrati</a></p>
</section>
<?php render_footer(); ?>
