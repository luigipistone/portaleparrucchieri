<?php
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name || !$email || strlen($password) < 8) {
        flash('Inserisci nome, email e una password di almeno 8 caratteri.', 'danger');
    } else {
        try {
            $stmt = db()->prepare('INSERT INTO users (name, email, phone, password_hash, role) VALUES (?, ?, ?, ?, "customer")');
            $stmt->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
            $_SESSION['user_id'] = db()->lastInsertId();
            flash('Account creato con successo.');
            header('Location: dashboard.php');
            exit;
        } catch (PDOException $exception) {
            flash('Email già registrata.', 'danger');
        }
    }
}

render_header('Registrazione');
?>
<section class="auth-card glass-panel reveal">
    <p class="eyebrow">Nuovo cliente</p>
    <h1>Crea il tuo profilo</h1>
    <form class="liquid-form single" method="post">
        <label>Nome<input type="text" name="name" required></label>
        <label>Email<input type="email" name="email" required></label>
        <label>Telefono<input type="tel" name="phone"></label>
        <label>Password<input type="password" name="password" minlength="8" required></label>
        <button class="btn primary magnet" type="submit">Registrati</button>
    </form>
</section>
<?php render_footer(); ?>
