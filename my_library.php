<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$user = require_login();
$pendingFilm = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $filmId = valid_id($_POST['film_id'] ?? null);

    if ($filmId === null) {
        flash('error', 'Odabran je neispravan film.');
        redirect('my_library.php');
    }

    if ($action === 'remove') {
        $statement = db()->prepare(
            'DELETE FROM zeljeni_filmovi
             WHERE korisnik_id = :korisnik_id AND film_id = :film_id'
        );
        $statement->execute([
            'korisnik_id' => (int) $user['id'],
            'film_id' => $filmId,
        ]);
        flash('success', 'Film je uklonjen iz vaše videoteke.');
        redirect('my_library.php');
    }

    if ($action === 'add') {
        $statement = db()->prepare(
            'SELECT id, naslov, prosjecna_ocjena FROM filmovi WHERE id = :id'
        );
        $statement->execute(['id' => $filmId]);
        $film = $statement->fetch();

        if (!$film) {
            flash('error', 'Odabrani film više nije dostupan.');
            redirect('index.php');
        }

        $existing = db()->prepare(
            'SELECT id FROM zeljeni_filmovi
             WHERE korisnik_id = :korisnik_id AND film_id = :film_id'
        );
        $existing->execute([
            'korisnik_id' => (int) $user['id'],
            'film_id' => $filmId,
        ]);

        if ($existing->fetch()) {
            flash('info', 'Film je već spremljen u vašoj videoteci.');
            redirect('my_library.php');
        }

        if (
            $film['prosjecna_ocjena'] !== null
            && (float) $film['prosjecna_ocjena'] < 5.0
            && !isset($_POST['potvrdi_nisku_ocjenu'])
        ) {
            $pendingFilm = $film;
        } else {
            $insert = db()->prepare(
                'INSERT INTO zeljeni_filmovi (korisnik_id, film_id)
                 VALUES (:korisnik_id, :film_id)'
            );
            $insert->execute([
                'korisnik_id' => (int) $user['id'],
                'film_id' => $filmId,
            ]);
            flash('success', 'Film je dodan u vašu osobnu videoteku.');
            redirect('my_library.php');
        }
    }
}

$statement = db()->prepare(
    'SELECT f.id, f.naslov, f.zanr, f.godina, f.prosjecna_ocjena, z.dodano
     FROM zeljeni_filmovi z
     JOIN filmovi f ON f.id = z.film_id
     WHERE z.korisnik_id = :korisnik_id
     ORDER BY z.dodano DESC, f.naslov ASC'
);
$statement->execute(['korisnik_id' => (int) $user['id']]);
$savedFilms = $statement->fetchAll();

$pageTitle = 'Moja videoteka';
require __DIR__ . '/includes/header.php';
?>
<?php if ($pendingFilm !== null): ?>
    <section class="confirmation-card warning-card">
        <h2>Upozorenje na nisku ocjenu</h2>
        <p>
            Film <strong><?= e($pendingFilm['naslov']) ?></strong> ima prosječnu ocjenu
            <strong><?= e(format_rating($pendingFilm['prosjecna_ocjena'])) ?></strong>,
            što je manje od 5,0. Jeste li sigurni da ga želite dodati?
        </p>
        <form method="post" class="confirmation-actions">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="film_id" value="<?= e($pendingFilm['id']) ?>">
            <input type="hidden" name="potvrdi_nisku_ocjenu" value="1">
            <button type="submit">Da, dodaj film</button>
            <a class="secondary-button" href="index.php">Odustani</a>
        </form>
    </section>
<?php endif; ?>

<section class="catalogue library-section">
    <h2>Moja osobna videoteka</h2>
    <?php if ($savedFilms === []): ?>
        <p>Niste još dodali nijedan film. <a href="index.php">Pregledajte katalog filmova</a>.</p>
    <?php else: ?>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Naslov</th>
                        <th>Žanr</th>
                        <th>Godina</th>
                        <th>Ocjena</th>
                        <th>Ukloni</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($savedFilms as $film): ?>
                    <tr>
                        <td><?= e($film['naslov']) ?></td>
                        <td><?= e($film['zanr']) ?></td>
                        <td><?= e($film['godina']) ?></td>
                        <td><?= e(format_rating($film['prosjecna_ocjena'])) ?></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="film_id" value="<?= e($film['id']) ?>">
                                <button type="submit" class="danger-button">Ukloni</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
