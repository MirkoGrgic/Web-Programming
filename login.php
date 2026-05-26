<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (current_user() !== null) {
    redirect('index.php');
}

$identifier = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '' || $password === '') {
        $error = 'Unesite korisničko ime ili email i lozinku.';
    } else {
        $statement = db()->prepare(
            'SELECT id, lozinka FROM korisnici
             WHERE korisnicko_ime = :username OR email = :email
             LIMIT 1'
        );
        $statement->execute([
            'username' => $identifier,
            'email' => $identifier,
        ]);
        $account = $statement->fetch();

        if ($account && password_verify($password, $account['lozinka'])) {
            sign_in((int) $account['id']);
            flash('success', 'Uspješno ste prijavljeni.');
            redirect('index.php');
        }

        $error = 'Neispravno korisničko ime, email ili lozinka.';
    }
}

$pageTitle = 'Prijava';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-card">
    <h2>Prijava</h2>
    <?php if ($error !== ''): ?>
        <div class="notice notice-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" class="stack-form">
        <label for="identifier">Korisničko ime ili email</label>
        <input type="text" id="identifier" name="identifier" value="<?= e($identifier) ?>" required>

        <label for="password">Lozinka</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Prijavi se</button>
    </form>
    <p>Nemate račun? <a href="register.php">Registrirajte se</a>.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
