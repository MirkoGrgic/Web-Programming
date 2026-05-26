<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function current_user(): ?array
{
    static $loaded = false;
    static $user = null;

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    if (!$loaded) {
        $statement = db()->prepare(
            'SELECT id, korisnicko_ime, email, uloga FROM korisnici WHERE id = :id'
        );
        $statement->execute(['id' => (int) $_SESSION['user_id']]);
        $user = $statement->fetch() ?: null;
        $loaded = true;

        if ($user === null) {
            unset($_SESSION['user_id']);
        }
    }

    return $user;
}

function sign_in(int $userId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

function sign_out(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $parameters = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $parameters['path'],
            $parameters['domain'],
            (bool) $parameters['secure'],
            (bool) $parameters['httponly']
        );
    }

    session_destroy();
}

function require_login(): array
{
    $user = current_user();

    if ($user === null) {
        flash('error', 'Za ovu radnju morate biti prijavljeni.');
        redirect('login.php');
    }

    return $user;
}

function require_admin(): array
{
    $user = require_login();

    if ($user['uloga'] !== 'administrator') {
        flash('error', 'Administratorsko sučelje dostupno je samo administratoru.');
        redirect('index.php');
    }

    return $user;
}
