<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = require_login();
    $imageId = valid_id($_POST['slika_id'] ?? null);
    $rating = valid_id($_POST['ocjena'] ?? null);

    if ($imageId === null || $rating === null || $rating > 5) {
        flash('error', 'Odaberite ispravnu ocjenu od 1 do 5 zvjezdica.');
        redirect('gallery.php');
    }

    $imageExists = db()->prepare('SELECT id FROM slike WHERE id = :id');
    $imageExists->execute(['id' => $imageId]);

    if (!$imageExists->fetch()) {
        flash('error', 'Odabrana slika nije pronađena.');
        redirect('gallery.php');
    }

    $statement = db()->prepare(
        'INSERT INTO ocjene_slika (korisnik_id, slika_id, ocjena)
         VALUES (:korisnik_id, :slika_id, :ocjena)
         ON DUPLICATE KEY UPDATE ocjena = VALUES(ocjena), vrijeme_ocjene = CURRENT_TIMESTAMP'
    );
    $statement->execute([
        'korisnik_id' => (int) $user['id'],
        'slika_id' => $imageId,
        'ocjena' => $rating,
    ]);

    flash('success', 'Vaša ocjena slike je spremljena.');
    redirect('gallery.php');
}

$statement = db()->prepare(
    'SELECT s.id, s.naziv, s.putanja,
            COALESCE(statistika.prosjek, 0) AS prosjek,
            COALESCE(statistika.broj_ocjena, 0) AS broj_ocjena,
            moja.ocjena AS moja_ocjena
     FROM slike s
     LEFT JOIN (
         SELECT slika_id, AVG(ocjena) AS prosjek, COUNT(*) AS broj_ocjena
         FROM ocjene_slika
         GROUP BY slika_id
     ) statistika ON statistika.slika_id = s.id
     LEFT JOIN ocjene_slika moja
         ON moja.slika_id = s.id AND moja.korisnik_id = :korisnik_id
     ORDER BY s.id ASC'
);
$statement->execute(['korisnik_id' => $user !== null ? (int) $user['id'] : 0]);
$images = $statement->fetchAll();

$pageTitle = 'Galerija';
$additionalStyles = ['public/styles/style_slike.css'];
require __DIR__ . '/includes/header.php';
?>
<section class="gallery-intro">
    <h2>Galerija slika</h2>
    <?php if ($user === null): ?>
        <p>Za ocjenjivanje slika morate se <a href="login.php">prijaviti</a>.</p>
    <?php else: ?>
        <p>Ocijenite svaku fotografiju od 1 do 5 zvjezdica. Nova ocjena zamjenjuje vašu prethodnu ocjenu.</p>
    <?php endif; ?>
</section>

<section class="galerija rating-gallery">
    <?php foreach ($images as $image): ?>
        <figure class="galerija_slika">
            <a href="#img<?= e($image['id']) ?>">
                <img src="<?= e($image['putanja']) ?>" alt="<?= e($image['naziv']) ?>">
            </a>
            <figcaption>
                <strong><?= e($image['naziv']) ?></strong>
                <span class="rating-summary">
                    Prosjek: <?= e(format_rating($image['prosjek'])) ?> / 5
                    (<?= e($image['broj_ocjena']) ?>)
                </span>
            </figcaption>

            <?php if ($user !== null): ?>
                <form method="post" class="rating-form">
                    <input type="hidden" name="slika_id" value="<?= e($image['id']) ?>">
                    <fieldset class="rating-options">
                        <legend>Vaša ocjena</legend>
                        <?php for ($rating = 1; $rating <= 5; $rating++): ?>
                            <label>
                                <input type="radio" name="ocjena" value="<?= $rating ?>"<?= checked($image['moja_ocjena'], $rating) ?> required>
                                <span aria-hidden="true">&#9733;</span>
                                <span class="sr-only"><?= $rating ?></span>
                            </label>
                        <?php endfor; ?>
                    </fieldset>
                    <button type="submit">Spremi ocjenu</button>
                </form>
            <?php endif; ?>
        </figure>

        <div id="img<?= e($image['id']) ?>" class="lightbox">
            <a href="#" class="close" aria-label="Zatvori">&times;</a>
            <img src="<?= e($image['putanja']) ?>" alt="<?= e($image['naziv']) ?>">
        </div>
    <?php endforeach; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
