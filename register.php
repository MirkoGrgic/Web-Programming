<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (current_user() !== null) {
    redirect('index.php');
}

$form = [
    'korisnicko_ime' => '',
    'email' => '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['korisnicko_ime'] = trim($_POST['korisnicko_ime'] ?? '');
    $form['email'] = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['password_confirmation'] ?? '';

    if (!preg_match('/^[\p{L}\d_.-]{3,50}$/u', $form['korisnicko_ime'])) {
        $errors[] = 'Korisničko ime mora imati 3 do 50 znakova i smije sadržavati slova, brojeve, točku, crticu ili donju crtu.';
    }

    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Unesite ispravnu email adresu.';
    }

    if (mb_strlen($password) < 8) {
        $errors[] = 'Lozinka mora imati najmanje 8 znakova.';
    }

    if ($password !== $passwordConfirmation) {
        $errors[] = 'Potvrda lozinke ne odgovara unesenoj lozinci.';
    }

    if ($errors === []) {
        try {
            $statement = db()->prepare(
                'INSERT INTO korisnici (korisnicko_ime, email, lozinka, uloga)
                 VALUES (:korisnicko_ime, :email, :lozinka, :uloga)'
            );
            $statement->execute([
                'korisnicko_ime' => $form['korisnicko_ime'],
                'email' => $form['email'],
                'lozinka' => password_hash($password, PASSWORD_DEFAULT),
                'uloga' => 'korisnik',
            ]);

            flash('success', 'Registracija je uspješna. Sada se možete prijaviti.');
            redirect('login.php');
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $errors[] = 'Korisničko ime ili email već postoji.';
            } else {
                throw $exception;
            }
        }
    }
}

$pageTitle = 'Registracija';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-card">
    <h2>Registracija</h2>
    <?php foreach ($errors as $error): ?>
        <div class="notice notice-error" role="alert"><?= e($error) ?></div>
    <?php endforeach; ?>
    <form method="post" class="stack-form">
        <label for="korisnicko_ime">Korisničko ime</label>
        <input type="text" id="korisnicko_ime" name="korisnicko_ime" value="<?= e($form['korisnicko_ime']) ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($form['email']) ?>" required>

        <label for="password">Lozinka</label>
        <input type="password" id="password" name="password" minlength="8" required>

        <label for="password_confirmation">Ponovite lozinku</label>
        <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" required>

        <button type="submit">Registriraj se</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
