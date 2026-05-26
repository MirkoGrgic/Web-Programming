<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$filters = [
    'zanr' => trim($_GET['zanr'] ?? ''),
    'godina_od' => trim($_GET['godina_od'] ?? ''),
    'godina_do' => trim($_GET['godina_do'] ?? ''),
    'zemlja' => trim($_GET['zemlja'] ?? ''),
    'sort' => $_GET['sort'] ?? 'naslov_asc',
];
$filterErrors = [];
$conditions = [];
$parameters = [];
$maximumYear = (int) date('Y') + 1;
$displayLimit = 12;
$isInitialView = $_GET === [];

if ($filters['zanr'] !== '') {
    $conditions[] = 'zanr LIKE :zanr';
    $parameters['zanr'] = '%' . $filters['zanr'] . '%';
}

if ($filters['zemlja'] !== '') {
    $conditions[] = 'zemlja LIKE :zemlja';
    $parameters['zemlja'] = '%' . $filters['zemlja'] . '%';
}

$yearFrom = null;
$yearTo = null;

if ($filters['godina_od'] !== '') {
    $yearFrom = filter_var($filters['godina_od'], FILTER_VALIDATE_INT);
    if ($yearFrom === false || $yearFrom < 1888 || $yearFrom > $maximumYear) {
        $filterErrors[] = 'Početna godina mora biti između 1888. i ' . $maximumYear . '.';
        $yearFrom = null;
    }
}

if ($filters['godina_do'] !== '') {
    $yearTo = filter_var($filters['godina_do'], FILTER_VALIDATE_INT);
    if ($yearTo === false || $yearTo < 1888 || $yearTo > $maximumYear) {
        $filterErrors[] = 'Završna godina mora biti između 1888. i ' . $maximumYear . '.';
        $yearTo = null;
    }
}

if ($yearFrom !== null && $yearTo !== null && $yearFrom > $yearTo) {
    $filterErrors[] = 'Početna godina ne može biti veća od završne godine.';
} else {
    if ($yearFrom !== null) {
        $conditions[] = 'godina >= :godina_od';
        $parameters['godina_od'] = $yearFrom;
    }

    if ($yearTo !== null) {
        $conditions[] = 'godina <= :godina_do';
        $parameters['godina_do'] = $yearTo;
    }
}

$sortOptions = [
    'naslov_asc' => 'naslov ASC',
    'godina_desc' => 'godina DESC, naslov ASC',
    'godina_asc' => 'godina ASC, naslov ASC',
    'ocjena_desc' => 'prosjecna_ocjena DESC, naslov ASC',
];

if (!array_key_exists($filters['sort'], $sortOptions)) {
    $filters['sort'] = 'naslov_asc';
}

$sql = 'SELECT id, naslov, zanr, godina, zemlja, trajanje, prosjecna_ocjena
        FROM filmovi';

if ($conditions !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

if ($isInitialView) {
    $sql .= ' ORDER BY RAND() LIMIT ' . $displayLimit;
} else {
    $sql .= ' ORDER BY ' . $sortOptions[$filters['sort']];
}

$statement = db()->prepare($sql);
$statement->execute($parameters);
$films = $statement->fetchAll();

$pageTitle = 'Filmovi';
require __DIR__ . '/includes/header.php';
?>
<section class="filters">
    <h2>Pretraživanje filmova</h2>
    <p class="section-help">Rezultati se dohvaćaju iz MySQL baze na serverskoj strani.</p>
    <?php foreach ($filterErrors as $filterError): ?>
        <div class="notice notice-error" role="alert"><?= e($filterError) ?></div>
    <?php endforeach; ?>
    <form method="get" class="filter-form">
        <label>
            Žanr
            <input type="text" name="zanr" value="<?= e($filters['zanr']) ?>" placeholder="npr. Drama">
        </label>
        <label>
            Godina od
            <input type="number" name="godina_od" min="1888" max="<?= $maximumYear ?>" value="<?= e($filters['godina_od']) ?>">
        </label>
        <label>
            Godina do
            <input type="number" name="godina_do" min="1888" max="<?= $maximumYear ?>" value="<?= e($filters['godina_do']) ?>">
        </label>
        <label>
            Zemlja
            <input type="text" name="zemlja" value="<?= e($filters['zemlja']) ?>" placeholder="npr. United States">
        </label>
        <label>
            Sortiranje
            <select name="sort">
                <option value="naslov_asc"<?= selected($filters['sort'], 'naslov_asc') ?>>Naslov A-Z</option>
                <option value="godina_desc"<?= selected($filters['sort'], 'godina_desc') ?>>Najnoviji prvo</option>
                <option value="godina_asc"<?= selected($filters['sort'], 'godina_asc') ?>>Najstariji prvo</option>
                <option value="ocjena_desc"<?= selected($filters['sort'], 'ocjena_desc') ?>>Najviša ocjena</option>
            </select>
        </label>
        <button type="submit">Filtriraj</button>
        <a class="secondary-button" href="index.php">Poništi</a>
    </form>
</section>

<section class="catalogue">
    <h2>Dostupni filmovi</h2>
    <p class="section-help">
        <?php if ($isInitialView): ?>
            Prikazan je nasumičan izbor od <?= $displayLimit ?> filmova.
        <?php else: ?>
            Prikazani su svi filmovi koji odgovaraju odabranom pretraživanju.
        <?php endif; ?>
    </p>
    <div class="table-scroll">
        <table id="dataTable">
            <thead>
                <tr>
                    <th>Naslov</th>
                    <th>Žanr</th>
                    <th>Godina</th>
                    <th>Zemlja</th>
                    <th>Trajanje</th>
                    <th>Ocjena</th>
                    <th>Videoteka</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($films === []): ?>
                <tr>
                    <td colspan="7">Nema filmova koji odgovaraju odabranim filtrima.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($films as $film): ?>
                <tr>
                    <td><?= e($film['naslov']) ?></td>
                    <td><?= e($film['zanr']) ?></td>
                    <td><?= e($film['godina']) ?></td>
                    <td><?= $film['zemlja'] !== null ? e($film['zemlja']) : 'Nije navedeno' ?></td>
                    <td><?= $film['trajanje'] !== null ? e($film['trajanje']) . ' min' : 'Nije navedeno' ?></td>
                    <td><?= e(format_rating($film['prosjecna_ocjena'])) ?></td>
                    <td>
                        <?php if (current_user() !== null): ?>
                            <form method="post" action="my_library.php" class="inline-form">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="film_id" value="<?= e($film['id']) ?>">
                                <button type="submit">Dodaj</button>
                            </form>
                        <?php else: ?>
                            <a href="login.php">Prijavite se</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
