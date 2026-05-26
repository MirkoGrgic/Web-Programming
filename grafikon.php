<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$pageTitle = 'Grafikoni';
$additionalStyles = ['public/styles/grafikon.css'];
require __DIR__ . '/includes/header.php';
?>
<section class="grafikon-sekcija">
    <h2>Raspodjela filmova po žanru</h2>
    <div class="grafikon-wrapper">
        <div class="pie-center">
            <div class="pie-chart"></div>
        </div>

        <div class="legenda">
            <div class="stavka">
                <span class="boja drama"></span>
                Drama (30%)
            </div>
            <div class="stavka">
                <span class="boja komedija"></span>
                Komedija (25%)
            </div>
            <div class="stavka">
                <span class="boja akcija"></span>
                Akcija (15%)
            </div>
            <div class="stavka">
                <span class="boja horor"></span>
                Horor (10%)
            </div>
            <div class="stavka">
                <span class="boja ostalo"></span>
                Ostali žanrovi (20%)
            </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
