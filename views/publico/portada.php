<?php
/**
 * @var array $resultados Partidos finalizados, más reciente primero
 * @var array $proximos   Partidos programados, el más cercano primero
 * @var array|null $mini       Primera tabla de posiciones: ['titulo' => ?string, 'filas' => array, 'fase' => string]
 * @var array $destacados
*/
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

/** Escudo decorativo; si no hay imagen, disco con iniciales y color por equipo. */
$escudo = function (?string $ruta, string $nombre = '', int $idEquipo = 0, string $tam = 'h-5 w-5') use ($e): string {
    if (is_string($ruta) && str_starts_with($ruta, '/uploads/')) {
        return '<img src="' . $e($ruta) . '" alt="" loading="lazy" class="' . $tam . ' shrink-0 object-contain">';
    }
    $ini = \Model\ClubPublico::iniciales($nombre) ?: '?';
    $color = 'hsl(' . (($idEquipo * 47) % 360) . ' 50% 38%)';
    return '<span aria-hidden="true" class="flex ' . $tam . ' shrink-0 items-center justify-center rounded-full text-[9px] font-bold text-white" style="background:' . $color . '">' . $e($ini) . '</span>';
};

$dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$fechaCorta = function (string $fh) use ($dias): string {
    $t = strtotime($fh);
    return ucfirst(substr($dias[(int) date('w', $t)], 0, 3)) . ' ' . date('d/m', $t);
};
?>

<h1 class="sr-only">DatosFutbol: resultados y próximos partidos</h1>

<!-- Tira de resultados -->
<section aria-labelledby="tira-titulo" class="border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-3">
        <h2 id="tira-titulo" class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-600">
            Últimos resultados
        </h2>

        <?php if (!$resultados): ?>
            <p class="text-sm text-slate-600">Todavía no hay resultados cargados.</p>
        <?php else: ?>
        <ul class="flex gap-3 overflow-x-auto pb-2">
            <?php foreach ($resultados as $p): ?>
            <li class="shrink-0">
                <a href="/partido?id_partido=<?= (int) $p['id_partido'] ?>"
                   aria-label="<?= $e("{$p['local']} {$p['goles_local']}, {$p['visitante']} {$p['goles_visitante']}. Finalizado") ?>"
                   class="block w-52 rounded-lg border border-slate-200 bg-slate-50 p-3 hover:border-emerald-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600">
                    <p class="text-xs text-slate-600">
                        <time datetime="<?= $e(date('c', strtotime($p['fecha_hora']))) ?>"><?= $e($fechaCorta($p['fecha_hora'])) ?></time>
                        <?php if (!empty($p['jornada'])): ?>
                        &middot; Jornada <?= (int) $p['jornada'] ?>
                        <?php endif; ?>
                        &middot; Final
                    </p>
                    <p class="mt-1 flex items-center justify-between text-sm font-semibold" aria-hidden="true">
                        <span class="flex min-w-0 items-center gap-2">
                            <?= $escudo($p['escudo_local'] ?? null, $p['local'], (int) $p['id_equipo_local']) ?>
                            <span class="truncate"><?= $e($p['local']) ?></span>
                        </span>
                        <span class="ml-2 tabular-nums"><?= (int) $p['goles_local'] ?></span>
                    </p>
                    <p class="mt-1 flex items-center justify-between text-sm font-semibold" aria-hidden="true">
                        <span class="flex min-w-0 items-center gap-2">
                            <?= $escudo($p['escudo_visitante'] ?? null, $p['visitante'], (int) $p['id_equipo_visitante']) ?>
                            <span class="truncate"><?= $e($p['visitante']) ?></span>
                        </span>
                        <span class="ml-2 tabular-nums"><?= (int) $p['goles_visitante'] ?></span>
                    </p>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</section>

<div class="mx-auto grid max-w-7xl gap-8 px-4 py-8 lg:grid-cols-[minmax(0,1fr)_20rem]">

    <div class="min-w-0 space-y-8">

        <?php if ($destacados): ?>
        <section aria-labelledby="destacados-titulo">
            <h2 id="destacados-titulo" class="mb-3 text-xl font-bold text-slate-900">Destacados</h2>
            <ul class="grid gap-4 sm:grid-cols-2">
                <?php foreach ($destacados as $i => $d):
                    $img = is_string($d['imagen'] ?? null) && str_starts_with($d['imagen'], '/uploads/') ? $d['imagen'] : null;
                    $grande = $i === 0;
                ?>
                <li class="<?= $grande ? 'sm:col-span-2' : '' ?>">
                    <a href="/destacado?id=<?= (int) $d['id'] ?>"
                       class="group relative block overflow-hidden rounded-xl bg-slate-800 <?= $grande ? 'aspect-video sm:aspect-[16/7]' : 'aspect-video' ?> focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
                        <?php if ($img): ?>
                        <img src="<?= $e($img) ?>" alt="" loading="<?= $grande ? 'eager' : 'lazy' ?>"
                             class="absolute inset-0 h-full w-full object-cover object-[50%_20%] transition duration-300 group-hover:scale-105">
                        <?php endif; ?>
                        <span class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/40 to-transparent" aria-hidden="true"></span>
                        <span class="absolute inset-x-0 bottom-0 block p-4 text-white">
                            <span class="block line-clamp-2 <?= $grande ? 'text-xl' : 'text-base' ?> font-bold leading-tight"><?= $e($d['titulo']) ?></span>
                            <?php if (!empty($d['bajada'])): ?>
                            <span class="mt-1 block line-clamp-1 text-sm text-slate-100"><?= $e($d['bajada']) ?></span>
                            <?php endif; ?>
                        </span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>

        
        <!-- Próximos partidos -->
        <section aria-labelledby="proximos-titulo">
            <div class="mb-3 flex items-baseline justify-between">
                <h2 id="proximos-titulo" class="text-xl font-bold text-slate-900">Próximos partidos</h2>
                <a href="/partidos" class="text-sm font-semibold text-emerald-800 underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600">
                    Ver todos<span class="sr-only"> los partidos</span>
                </a>
            </div>

            <?php if (!$proximos): ?>
                <p class="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-600">
                    No hay partidos programados por ahora.
                </p>
            <?php else: ?>
            <ol class="divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white">
                <?php foreach ($proximos as $p): ?>
                <li>
                    <a href="/partido?id_partido=<?= (int) $p['id_partido'] ?>"
                       class="flex items-center gap-4 p-4 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-emerald-600">
                        <p class="w-24 shrink-0 text-sm text-slate-600">
                            <time datetime="<?= $e(date('c', strtotime($p['fecha_hora']))) ?>">
                                <span class="block font-semibold text-slate-800"><?= $e($fechaCorta($p['fecha_hora'])) ?></span>
                                <?= $e(date('H:i', strtotime($p['fecha_hora']))) ?> h
                            </time>
                        </p>
                        <p class="flex min-w-0 flex-1 flex-wrap items-center gap-x-2 gap-y-1 text-sm font-semibold text-slate-900">
                            <span class="flex items-center gap-2">
                                <?= $escudo($p['escudo_local'] ?? null, $p['local'], (int) $p['id_equipo_local']) ?><?= $e($p['local']) ?>
                            </span>
                            <span class="font-normal text-slate-600">vs</span>
                            <span class="flex items-center gap-2">
                                <?= $escudo($p['escudo_visitante'] ?? null, $p['visitante'], (int) $p['id_equipo_visitante']) ?><?= $e($p['visitante']) ?>
                            </span>
                        </p>
                        <?php if (!empty($p['torneo'])): ?>
                        <p class="hidden text-xs text-slate-600 sm:block"><?= $e($p['torneo']) ?></p>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ol>
            <?php endif; ?>
        </section>
    </div><!-- cierre columna izquierda -->

    <aside aria-labelledby="tabla-titulo" class="lg:sticky lg:top-4 lg:max-h-[calc(100vh-2rem)] lg:self-start lg:overflow-y-auto">
        <div class="mb-3 flex items-baseline justify-between">
            <h2 id="tabla-titulo" class="text-xl font-bold text-slate-900">Clasificación</h2>
            <a href="/clasificacion" class="text-sm font-semibold text-emerald-800 underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600">
                Ver completa
            </a>
        </div>

        <?php if (!$mini): ?>
            <p class="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-600">
                Todavía no hay posiciones.
            </p>
        <?php else: ?>
        <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
            <table class="w-full text-sm">
                <caption class="sr-only">Primeros puestos<?= $mini['titulo'] ? ', ' . $e($mini['titulo']) : '' ?></caption>
                <thead class="text-xs uppercase text-slate-600">
                    <tr class="border-b border-slate-200">
                        <th scope="col" class="px-3 py-2 text-left"><span class="sr-only">Posición</span><span aria-hidden="true">#</span></th>
                        <th scope="col" class="px-3 py-2 text-left">Equipo</th>
                        <th scope="col" class="px-3 py-2 text-right"><abbr title="Partidos jugados">PJ</abbr></th>
                        <th scope="col" class="px-3 py-2 text-right"><abbr title="Puntos">Pts</abbr></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($mini['filas'] as $pos => $f): ?>
                    <tr>
                        <td class="px-3 py-2 tabular-nums text-slate-600"><?= $pos + 1 ?></td>
                        <th scope="row" class="px-3 py-2 text-left font-semibold text-slate-900">
                            <span class="flex items-center gap-2"><?= $escudo($f['escudo'] ?? null, $f['nombre'], (int) $f['id']) ?><?= $e($f['nombre']) ?></span>
                        </th>
                        <td class="px-3 py-2 text-right tabular-nums text-slate-700"><?= (int) $f['pj'] ?></td>
                        <td class="px-3 py-2 text-right tabular-nums font-bold text-slate-900"><?= (int) $f['pts'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </aside>
</div>