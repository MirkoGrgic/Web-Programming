<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

require_admin();

$csvPath = __DIR__ . '/public/data/netflix_titles.csv';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_readable($csvPath)) {
        flash('error', 'CSV datoteka s filmovima nije pronađena.');
        redirect('import_films.php');
    }

    $handle = fopen($csvPath, 'rb');

    if ($handle === false) {
        flash('error', 'CSV datoteku nije moguće otvoriti.');
        redirect('import_films.php');
    }

    try {
        $header = fgetcsv($handle);

        if ($header === false) {
            throw new RuntimeException('CSV datoteka je prazna.');
        }

        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        $columnIndexes = array_flip($header);
        $requiredColumns = [
            'show_id',
            'type',
            'title',
            'country',
            'release_year',
            'duration',
            'listed_in',
            'description',
        ];

        foreach ($requiredColumns as $requiredColumn) {
            if (!array_key_exists($requiredColumn, $columnIndexes)) {
                throw new RuntimeException('CSV datoteka nema očekivane stupce.');
            }
        }

        $connection = db();
        $adoptLegacyFilm = $connection->prepare(
            'UPDATE filmovi
             SET izvorni_id = :izvorni_id,
                 zanr = :zanr,
                 zemlja = :zemlja,
                 trajanje = :trajanje,
                 prosjecna_ocjena = NULL,
                 opis = :opis
             WHERE id = :id'
        );
        $upsertFilm = $connection->prepare(
            'INSERT INTO filmovi
             (izvorni_id, naslov, zanr, godina, zemlja, trajanje, opis)
             VALUES
             (:izvorni_id, :naslov, :zanr, :godina, :zemlja, :trajanje, :opis)
             ON DUPLICATE KEY UPDATE
                 naslov = VALUES(naslov),
                 zanr = VALUES(zanr),
                 godina = VALUES(godina),
                 zemlja = VALUES(zemlja),
                 trajanje = VALUES(trajanje),
                 opis = VALUES(opis)'
        );
        $imported = 0;
        $adopted = 0;
        $skipped = 0;

        $connection->beginTransaction();
        $sourceIds = array_fill_keys(
            $connection->query(
                'SELECT izvorni_id FROM filmovi WHERE izvorni_id IS NOT NULL'
            )->fetchAll(PDO::FETCH_COLUMN),
            true
        );
        $legacyFilms = [];

        foreach ($connection->query(
            'SELECT id, naslov, godina FROM filmovi WHERE izvorni_id IS NULL ORDER BY id ASC'
        )->fetchAll() as $legacyFilm) {
            $key = $legacyFilm['naslov'] . "\0" . $legacyFilm['godina'];
            $legacyFilms[$key][] = (int) $legacyFilm['id'];
        }

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== count($header)) {
                $skipped++;
                continue;
            }

            $film = array_combine($header, $row);

            if ($film === false || ($film['type'] ?? '') !== 'Movie') {
                continue;
            }

            $year = filter_var($film['release_year'], FILTER_VALIDATE_INT);
            if ($film['show_id'] === '' || $film['title'] === '' || $film['listed_in'] === '' || $year === false) {
                $skipped++;
                continue;
            }

            $duration = null;
            if (preg_match('/^(\d+)\s+min$/', trim($film['duration']), $matches) === 1) {
                $duration = (int) $matches[1];
            }

            $parameters = [
                'izvorni_id' => $film['show_id'],
                'naslov' => $film['title'],
                'zanr' => $film['listed_in'],
                'godina' => $year,
                'zemlja' => $film['country'] !== '' ? $film['country'] : null,
                'trajanje' => $duration,
                'opis' => $film['description'] !== '' ? $film['description'] : null,
            ];

            $legacyKey = $film['title'] . "\0" . $year;
            if (
                !isset($sourceIds[$film['show_id']])
                && !empty($legacyFilms[$legacyKey])
            ) {
                $adoptParameters = $parameters;
                unset($adoptParameters['naslov'], $adoptParameters['godina']);
                $adoptParameters['id'] = array_shift($legacyFilms[$legacyKey]);
                $adoptLegacyFilm->execute($adoptParameters);
                $adopted++;
            } else {
                $upsertFilm->execute($parameters);
            }

            $sourceIds[$film['show_id']] = true;
            $imported++;
        }

        $connection->commit();
        flash(
            'success',
            'Uvezeno ili osvježeno filmova: ' . $imported
            . '. Povezano starih zapisa: ' . $adopted
            . '. Preskočeno neispravnih filmskih redaka: ' . $skipped . '.'
        );
    } catch (Throwable $exception) {
        if (isset($connection) && $connection->inTransaction()) {
            $connection->rollBack();
        }

        flash('error', 'Uvoz filmova nije uspio. Provjerite CSV datoteku i bazu podataka.');
    } finally {
        fclose($handle);
    }

    redirect('import_films.php');
}

$importedCount = (int) db()->query(
    'SELECT COUNT(*) FROM filmovi WHERE izvorni_id IS NOT NULL'
)->fetchColumn();

$pageTitle = 'Uvoz filmova';
require __DIR__ . '/includes/header.php';
?>
<section class="admin-form-card import-card">
    <h2>Uvoz filmova iz CSV datoteke</h2>
    <p>
        Izvor podataka: <code>public/data/netflix_titles.csv</code>.
        U bazu se učitavaju samo zapisi vrste <strong>Movie</strong>.
    </p>
    <p>Trenutno uvezenih CSV filmova: <strong><?= e($importedCount) ?></strong>.</p>
    <p class="section-help">
        Ponovni uvoz osvježava podatke iz CSV-a, ali zadržava prosječnu ocjenu
        koju administrator upiše u aplikaciji. Pri prvom prelasku sa stare probne
        verzije odgovarajući ručno uneseni filmovi povezuju se s CSV zapisima.
    </p>
    <form method="post" class="form-actions">
        <button type="submit">Uvezi filmove iz CSV-a</button>
        <a class="secondary-button" href="admin_films.php">Natrag na upravljanje</a>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
