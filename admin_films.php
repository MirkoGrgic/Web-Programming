<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

require_admin();

$maximumYear = (int) date('Y') + 1;
$errors = [];
$editingId = valid_id($_GET['edit'] ?? null);
$form = [
    'naslov' => '',
    'zanr' => '',
    'godina' => '',
    'zemlja' => '',
    'trajanje' => '',
    'prosjecna_ocjena' => '',
    'opis' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $postedId = valid_id($_POST['film_id'] ?? null);

    if ($action === 'delete') {
        if ($postedId === null) {
            flash('error', 'Nije moguće obrisati neispravan film.');
        } else {
            $statement = db()->prepare('DELETE FROM filmovi WHERE id = :id');
            $statement->execute(['id' => $postedId]);
            flash('success', 'Film je obrisan.');
        }
        redirect('admin_films.php');
    }

    if ($action === 'save') {
        $editingId = $postedId;
        foreach (array_keys($form) as $field) {
            $form[$field] = trim($_POST[$field] ?? '');
        }

        if ($form['naslov'] === '' || $form['zanr'] === '') {
            $errors[] = 'Naslov i žanr su obvezni podaci.';
        }

        $year = filter_var($form['godina'], FILTER_VALIDATE_INT);
        if ($year === false || $year < 1888 || $year > $maximumYear) {
            $errors[] = 'Godina filma mora biti između 1888. i ' . $maximumYear . '.';
        }

        $duration = $form['trajanje'] === ''
            ? null
            : filter_var($form['trajanje'], FILTER_VALIDATE_INT);
        if ($duration !== null && ($duration === false || $duration < 1 || $duration > 600)) {
            $errors[] = 'Trajanje filma mora biti između 1 i 600 minuta.';
        }

        $ratingValue = str_replace(',', '.', $form['prosjecna_ocjena']);
        $rating = $ratingValue === ''
            ? null
            : filter_var($ratingValue, FILTER_VALIDATE_FLOAT);
        if ($rating !== null && ($rating === false || $rating < 0 || $rating > 10)) {
            $errors[] = 'Prosječna ocjena mora biti broj između 0 i 10.';
        }

        if ($errors === []) {
            $parameters = [
                'naslov' => $form['naslov'],
                'zanr' => $form['zanr'],
                'godina' => $year,
                'zemlja' => $form['zemlja'] !== '' ? $form['zemlja'] : null,
                'trajanje' => $duration,
                'prosjecna_ocjena' => $rating,
                'opis' => $form['opis'] !== '' ? $form['opis'] : null,
            ];

            if ($editingId !== null) {
                $parameters['id'] = $editingId;
                $statement = db()->prepare(
                    'UPDATE filmovi
                     SET naslov = :naslov, zanr = :zanr, godina = :godina,
                         zemlja = :zemlja, trajanje = :trajanje,
                         prosjecna_ocjena = :prosjecna_ocjena, opis = :opis
                     WHERE id = :id'
                );
                $statement->execute($parameters);
                flash('success', 'Podaci o filmu su ažurirani.');
            } else {
                $statement = db()->prepare(
                    'INSERT INTO filmovi
                     (naslov, zanr, godina, zemlja, trajanje, prosjecna_ocjena, opis)
                     VALUES
                     (:naslov, :zanr, :godina, :zemlja, :trajanje, :prosjecna_ocjena, :opis)'
                );
                $statement->execute($parameters);
                flash('success', 'Novi film je dodan.');
            }

            redirect('admin_films.php');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $editingId !== null) {
    $statement = db()->prepare(
        'SELECT naslov, zanr, godina, zemlja, trajanje, prosjecna_ocjena, opis
         FROM filmovi WHERE id = :id'
    );
    $statement->execute(['id' => $editingId]);
    $filmToEdit = $statement->fetch();

    if (!$filmToEdit) {
        flash('error', 'Film za uređivanje nije pronađen.');
        redirect('admin_films.php');
    }

    $form = $filmToEdit;
}

$films = db()->query(
    'SELECT id, naslov, zanr, godina, zemlja, trajanje, prosjecna_ocjena
     FROM filmovi ORDER BY naslov ASC'
)->fetchAll();

$pageTitle = 'Upravljanje filmovima';
require __DIR__ . '/includes/header.php';
?>
<section class="admin-form-card">
    <h2><?= $editingId === null ? 'Dodaj film' : 'Uredi film' ?></h2>
    <p class="section-help">
        Filmovi iz početnog skupa učitavaju se kroz
        <a href="import_films.php">uvoz iz CSV datoteke</a>.
    </p>
    <?php foreach ($errors as $error): ?>
        <div class="notice notice-error" role="alert"><?= e($error) ?></div>
    <?php endforeach; ?>
    <form method="post" class="film-form">
        <input type="hidden" name="action" value="save">
        <?php if ($editingId !== null): ?>
            <input type="hidden" name="film_id" value="<?= e($editingId) ?>">
        <?php endif; ?>
        <label>
            Naslov
            <input type="text" name="naslov" value="<?= e($form['naslov']) ?>" required>
        </label>
        <label>
            Žanr
            <input type="text" name="zanr" value="<?= e($form['zanr']) ?>" required>
        </label>
        <label>
            Godina
            <input type="number" name="godina" min="1888" max="<?= $maximumYear ?>" value="<?= e($form['godina']) ?>" required>
        </label>
        <label>
            Zemlja
            <input type="text" name="zemlja" value="<?= e($form['zemlja']) ?>">
        </label>
        <label>
            Trajanje (min)
            <input type="number" name="trajanje" min="1" max="600" value="<?= e($form['trajanje']) ?>">
        </label>
        <label>
            Prosječna ocjena
            <input type="number" name="prosjecna_ocjena" min="0" max="10" step="0.1" value="<?= e($form['prosjecna_ocjena']) ?>" placeholder="Nije ocijenjeno">
        </label>
        <label class="full-row">
            Opis
            <textarea name="opis" rows="4"><?= e($form['opis']) ?></textarea>
        </label>
        <div class="form-actions full-row">
            <button type="submit"><?= $editingId === null ? 'Dodaj film' : 'Spremi izmjene' ?></button>
            <?php if ($editingId !== null): ?>
                <a class="secondary-button" href="admin_films.php">Odustani</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="catalogue admin-list">
    <h2>Filmovi u bazi</h2>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Naslov</th>
                    <th>Žanr</th>
                    <th>Godina</th>
                    <th>Zemlja</th>
                    <th>Trajanje</th>
                    <th>Ocjena</th>
                    <th>Radnje</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($films as $film): ?>
                <tr>
                    <td><?= e($film['naslov']) ?></td>
                    <td><?= e($film['zanr']) ?></td>
                    <td><?= e($film['godina']) ?></td>
                    <td><?= $film['zemlja'] !== null ? e($film['zemlja']) : 'Nije navedeno' ?></td>
                    <td><?= $film['trajanje'] !== null ? e($film['trajanje']) . ' min' : 'Nije navedeno' ?></td>
                    <td><?= e(format_rating($film['prosjecna_ocjena'])) ?></td>
                    <td class="action-cell">
                        <a class="table-action" href="admin_films.php?edit=<?= e($film['id']) ?>">Uredi</a>
                        <form method="post" class="inline-form">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="film_id" value="<?= e($film['id']) ?>">
                            <button type="submit" class="danger-button">Obriši</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
