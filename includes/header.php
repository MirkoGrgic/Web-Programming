<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'Netflix katalog';
$additionalStyles = $additionalStyles ?? [];
$user = current_user();
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | Netflix katalog</title>
    <link rel="stylesheet" href="public/styles/style.css">
    <?php foreach ($additionalStyles as $stylesheet): ?>
        <link rel="stylesheet" href="<?= e($stylesheet) ?>">
    <?php endforeach; ?>
</head>
<body>
<header>
    <h1>Netflix katalog</h1>
    <?php if ($user !== null): ?>
        <p class="signed-in">Prijavljen/a: <?= e($user['korisnicko_ime']) ?> (<?= e($user['uloga']) ?>)</p>
    <?php endif; ?>
</header>

<nav aria-label="Glavna navigacija">
    <ul class="nav-menu">
        <li><a href="index.php">Filmovi</a></li>
        <li><a href="grafikon.php">Grafikoni</a></li>
        <li><a href="gallery.php">Galerija</a></li>
        <?php if ($user !== null): ?>
            <li><a href="my_library.php">Moja videoteka</a></li>
            <?php if ($user['uloga'] === 'administrator'): ?>
                <li><a href="admin_films.php">Upravljanje filmovima</a></li>
                <li><a href="import_films.php">Uvoz filmova</a></li>
            <?php endif; ?>
            <li><a href="logout.php">Odjava</a></li>
        <?php else: ?>
            <li><a href="login.php">Prijava</a></li>
            <li><a href="register.php">Registracija</a></li>
        <?php endif; ?>
        <li class="dropdown">
            <details class="menu-dropdown">
                <summary>&#9776; Menu</summary>
                <ul class="submenu">
                    <li><a href="index.php">Filmovi</a></li>
                    <li><a href="grafikon.php">Grafikoni</a></li>
                    <li><a href="gallery.php">Galerija</a></li>
                    <?php if ($user !== null): ?>
                        <li><a href="my_library.php">Moja videoteka</a></li>
                        <?php if ($user['uloga'] === 'administrator'): ?>
                            <li><a href="admin_films.php">Upravljanje filmovima</a></li>
                            <li><a href="import_films.php">Uvoz filmova</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php">Odjava</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Prijava</a></li>
                        <li><a href="register.php">Registracija</a></li>
                    <?php endif; ?>
                </ul>
            </details>
        </li>
    </ul>
</nav>

<main class="page-main">
    <?php foreach (pull_flash_messages() as $message): ?>
        <div class="notice notice-<?= e($message['type']) ?>" role="alert">
            <?= e($message['message']) ?>
        </div>
    <?php endforeach; ?>
