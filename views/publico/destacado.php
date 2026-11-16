<?php
/** @var array|null $d */
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<main id="contenido" tabindex="-1" class="mx-auto max-w-3xl px-4 py-8">
    <a href="/" class="text-sm font-semibold text-emerald-800 hover:underline">&larr; Portada</a>

<?php if (!$d): ?>
    <h1 class="mt-4 text-2xl font-bold text-slate-900">Destacado no encontrado</h1>
    <p class="mt-2 text-slate-600">Puede que ya no esté publicado. Vuelve a la portada para ver las novedades.</p>
<?php else:
    $img = is_string($d['imagen'] ?? null) && str_starts_with($d['imagen'], '/uploads/') ? $d['imagen'] : null;
    $enlaces = [
        'partido' => ['/partido?id_partido=', 'Ver el partido'],
        'equipo'  => ['/club?id_equipo=', 'Ver el club'],      // ajusta al parámetro que use /club
    ];
    $enlace = $enlaces[$d['tipo'] ?? ''] ?? null;
    $parrafos = $d['cuerpo'] ? preg_split('/\R{2,}/', trim($d['cuerpo'])) : [];
?>
    <article class="mt-4">
        <h1 class="text-3xl font-bold leading-tight text-slate-900"><?= $e($d['titulo']) ?></h1>
        <?php if (!empty($d['bajada'])): ?>
        <p class="mt-2 text-lg text-slate-700"><?= $e($d['bajada']) ?></p>
        <?php endif; ?>

        <?php if ($img): ?>
        <img src="<?= $e($img) ?>" alt="" class="mt-6 w-full rounded-xl object-cover">
        <?php endif; ?>

        <div class="mt-6 space-y-4 text-slate-800">
            <?php foreach ($parrafos as $p): ?>
            <p><?= nl2br($e($p)) ?></p>
            <?php endforeach; ?>
        </div>

        <?php if ($enlace && $d['id_referencia']): ?>
        <a href="<?= $enlace[0] . (int) $d['id_referencia'] ?>"
           class="mt-8 inline-block rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
            <?= $e($enlace[1]) ?>
        </a>
        <?php endif; ?>
    </article>
<?php endif; ?>
</main>