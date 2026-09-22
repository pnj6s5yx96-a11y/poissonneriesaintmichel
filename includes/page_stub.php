<?php
declare(strict_types=1);

function renderPageStub(string $title, string $heading, string $description): void
{
    $pageTitle = $title;
    $metaDescription = $description;
    $seoIndexable = false;
    require __DIR__ . '/header.php';
    ?>
    <section class="hero">
      <p class="eyebrow">Module en préparation</p>
      <h1><?= e($heading) ?></h1>
      <p><?= e($description) ?></p>
    </section>
    <?php
    require __DIR__ . '/footer.php';
}
