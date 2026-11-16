<?php
/**
 * @var array  $torneos
 * @var int    $idTorneo
 * @var string $vista      general | jugadores | clubes
 * @var array  $jugadores  [clave => filas]
 * @var array  $clubes     [clave => filas]
 */

use Model\EstadisticaPublica;

$e = fn ($s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

$url = fn (string $v): string => '/estadisticas?' . http_build_query(['vista' => $v, 'id_torneo' => $idTorneo]);

$pestanas = ['general' => 'Vista general', 'jugadores' => 'Jugadores', 'clubes' => 'Clubes'];

$nombreJugador = function (array $f): string {
    $seudo = trim((string) ($f['seudonimo'] ?? ''));
    return $seudo !== '' ? $seudo : trim($f['nombre'] . ' ' . $f['apellido']);
};

/** Solo rutas locales, como con los escudos. */
$fotoJugador = fn (array $f): string =>
    (isset($f['foto']) && is_string($f['foto']) && str_starts_with($f['foto'], '/uploads/')) ? $f['foto'] : '';

/** Foto redonda con respaldo de iniciales (decorativa: el nombre va en texto al lado). */
$avatar = function (array $f, string $tam = 'h-10 w-10') use ($e, $fotoJugador): string {
    $seudo = trim((string) ($f['seudonimo'] ?? ''));
    $ini = $seudo !== ''
        ? mb_strtoupper(mb_substr($seudo, 0, 1))
        : (mb_strtoupper(mb_substr(trim((string) ($f['nombre'] ?? '')), 0, 1)
            . mb_substr(trim((string) ($f['apellido'] ?? '')), 0, 1)) ?: '?');
    $url = $fotoJugador($f);
    $img = $url
        ? '<img src="' . $e($url) . '" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">'
        : '';
    return '<span aria-hidden="true" class="relative flex ' . $tam . ' shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-200 text-xs font-bold text-slate-600 ring-1 ring-slate-200">'
        . $e($ini) . $img . '</span>';
};

/** Escudo del club (decorativo: el nombre va en texto al lado). Solo rutas locales. */
$escudoClub = function (array $f, string $tam = 'h-5 w-5') use ($e): string {
    $ruta = $f['escudo'] ?? '';
    if (!is_string($ruta) || !str_starts_with($ruta, '/uploads/')) {
        return '';
    }
    return '<img src="' . $e($ruta) . '" alt="" loading="lazy" class="' . $tam . ' shrink-0 object-contain">';
};

/** En la vista general se muestran solo los empatados en el primer puesto. */
$soloLideres = fn (array $filas): array => array_values(array_filter($filas, fn ($f) => $f['posicion'] === 1));
?>
<div class="mx-auto max-w-6xl px-4 py-8">
    <h1 class="text-3xl font-black tracking-tight text-slate-900">Estadísticas</h1>

    <?php if (count($torneos) > 1): ?>
    <form method="get" action="/estadisticas" class="mt-4 flex flex-wrap items-end gap-4">
        <input type="hidden" name="vista" value="<?= $e($vista) ?>">
        <div>
            <label for="id_torneo" class="block text-sm font-semibold text-slate-700">Torneo</label>
            <select id="id_torneo" name="id_torneo"
                    class="mt-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600">
                <?php foreach ($torneos as $t): ?>
                    <option value="<?= (int) $t['id'] ?>" <?= (int) $t['id'] === $idTorneo ? 'selected' : '' ?>><?= $e($t['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit"
                class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
            Ver
        </button>
    </form>
    <?php endif; ?>

    <nav aria-label="Vistas de estadísticas" class="mt-6 border-b border-slate-300">
        <ul class="flex gap-1 overflow-x-auto">
            <?php foreach ($pestanas as $clave => $texto): $activa = $clave === $vista; ?>
            <li>
                <a href="<?= $e($url($clave)) ?>" <?= $activa ? 'aria-current="page"' : '' ?>
                   class="block whitespace-nowrap border-b-2 px-3 py-3 text-sm font-bold uppercase tracking-wide focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-emerald-600
                          <?= $activa ? 'border-emerald-600 text-slate-900' : 'border-transparent text-slate-600 hover:text-slate-900' ?>">
                    <?= $texto ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <?php if (!$idTorneo): ?>
        <p class="mt-8 rounded-lg border border-slate-200 bg-white p-6 text-slate-600">Todavía no hay torneos con partidos.</p>
    <?php elseif ($vista === 'general'): ?>

        <section aria-labelledby="h-jug" class="mt-8">
            <h2 id="h-jug" class="text-2xl font-black uppercase text-slate-900">Jugadores</h2>
            <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach (EstadisticaPublica::JUGADORES as $clave => [$titulo]):
                    $lideres = $soloLideres($jugadores[$clave] ?? []); ?>
                <li class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-slate-700"><?= $e($titulo) ?></h3>
                    <?php if (!$lideres): ?>
                        <p class="mt-3 text-sm text-slate-600">Sin datos todavía.</p>
                    <?php else: ?>
                        <ol class="mt-3 space-y-2">
                            <?php foreach ($lideres as $f): ?>
                            <li class="flex items-center justify-between gap-3">
                                <?= $avatar($f) ?>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-bold text-slate-900"><?= $e($nombreJugador($f)) ?></span>
                                    <a href="/club?id_equipo=<?= (int) $f['id_equipo'] ?>" class="flex items-center gap-1.5 text-xs text-slate-600 underline-offset-2 hover:underline">
                                        <?= $escudoClub($f) ?><?= $e($f['club']) ?>
                                    </a>
                                </span>
                                <span class="text-3xl font-black tabular-nums text-slate-900"><?= (int) $f['valor'] ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <p class="mt-3 text-right text-sm">
                <a href="<?= $e($url('jugadores')) ?>" class="font-bold uppercase tracking-wide text-emerald-800 underline">Todas las estadísticas de los jugadores</a>
            </p>
        </section>

        <section aria-labelledby="h-clu" class="mt-10">
            <h2 id="h-clu" class="text-2xl font-black uppercase text-slate-900">Clubes</h2>
            <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <?php foreach (EstadisticaPublica::CLUBES as $clave => $titulo):
                    $lideres = $soloLideres($clubes[$clave] ?? []); ?>
                <li class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-slate-700"><?= $e($titulo) ?></h3>
                    <?php if (!$lideres): ?>
                        <p class="mt-3 text-sm text-slate-600">Sin datos todavía.</p>
                    <?php else: ?>
                        <ol class="mt-3 space-y-2">
                            <?php foreach ($lideres as $f): ?>
                            <li class="flex items-center justify-between gap-3">
                                <a href="/club?id_equipo=<?= (int) $f['id_equipo'] ?>" class="flex min-w-0 items-center gap-2 font-bold text-slate-900 underline-offset-2 hover:underline">
                                    <?= $escudoClub($f, 'h-6 w-6') ?><span class="truncate"><?= $e($f['club']) ?></span>
                                </a>
                                <span class="text-3xl font-black tabular-nums text-slate-900"><?= (int) $f['valor'] ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ol>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <p class="mt-3 text-right text-sm">
                <a href="<?= $e($url('clubes')) ?>" class="font-bold uppercase tracking-wide text-emerald-800 underline">Todas las estadísticas de los clubes</a>
            </p>
        </section>

    <?php elseif ($vista === 'jugadores'): ?>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <?php foreach (EstadisticaPublica::JUGADORES as $clave => [$titulo]):
                $filas = $jugadores[$clave] ?? []; ?>
            <section aria-labelledby="h-<?= $e($clave) ?>" class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                <h2 id="h-<?= $e($clave) ?>" class="border-b border-slate-200 px-4 py-3 text-lg font-black uppercase text-slate-900"><?= $e($titulo) ?></h2>
                <?php if (!$filas): ?>
                    <p class="px-4 py-4 text-sm text-slate-600">Sin datos todavía.</p>
                <?php else: ?>
                <table class="w-full text-sm">
                    <caption class="sr-only">Ranking de <?= $e(mb_strtolower($titulo)) ?></caption>
                    <thead class="text-xs uppercase text-slate-600">
                        <tr class="border-b border-slate-200">
                            <th scope="col" class="px-3 py-2 text-left"><span class="sr-only">Posición</span><span aria-hidden="true">#</span></th>
                            <th scope="col" class="px-3 py-2 text-left">Jugador</th>
                            <th scope="col" class="px-3 py-2 text-left">Club</th>
                            <th scope="col" class="px-3 py-2 text-right"><?= $e($titulo) ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($filas as $f): ?>
                        <tr>
                            <td class="px-3 py-2 tabular-nums text-slate-600"><?= (int) $f['posicion'] ?></td>
                            <th scope="row" class="px-3 py-2 text-left font-semibold text-slate-900">
                                <span class="flex items-center gap-2"><?= $avatar($f, 'h-7 w-7') ?><?= $e($nombreJugador($f)) ?></span>
                            </th>
                            <td class="px-3 py-2">
                                <a href="/club?id_equipo=<?= (int) $f['id_equipo'] ?>" class="flex items-center gap-1.5 text-slate-700 underline-offset-2 hover:underline">
                                    <?= $escudoClub($f) ?><?= $e($f['club']) ?>
                                </a>
                            </td>
                            <td class="px-3 py-2 text-right font-bold tabular-nums text-slate-900"><?= (int) $f['valor'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </section>
            <?php endforeach; ?>
        </div>

    <?php else: ?>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <?php foreach (EstadisticaPublica::CLUBES as $clave => $titulo):
                $filas = $clubes[$clave] ?? []; ?>
            <section aria-labelledby="h-<?= $e($clave) ?>" class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                <h2 id="h-<?= $e($clave) ?>" class="border-b border-slate-200 px-4 py-3 text-lg font-black uppercase text-slate-900"><?= $e($titulo) ?></h2>
                <?php if (!$filas): ?>
                    <p class="px-4 py-4 text-sm text-slate-600">Sin datos todavía.</p>
                <?php else: ?>
                <table class="w-full text-sm">
                    <caption class="sr-only">Ranking de clubes por <?= $e(mb_strtolower($titulo)) ?></caption>
                    <thead class="text-xs uppercase text-slate-600">
                        <tr class="border-b border-slate-200">
                            <th scope="col" class="px-3 py-2 text-left"><span class="sr-only">Posición</span><span aria-hidden="true">#</span></th>
                            <th scope="col" class="px-3 py-2 text-left">Club</th>
                            <th scope="col" class="px-3 py-2 text-right"><?= $e($titulo) ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($filas as $f): ?>
                        <tr>
                            <td class="px-3 py-2 tabular-nums text-slate-600"><?= (int) $f['posicion'] ?></td>
                            <th scope="row" class="px-3 py-2 text-left font-semibold text-slate-900">
                                <a href="/club?id_equipo=<?= (int) $f['id_equipo'] ?>" class="flex items-center gap-2 underline-offset-2 hover:underline">
                                    <?= $escudoClub($f, 'h-6 w-6') ?><?= $e($f['club']) ?>
                                </a>
                            </th>
                            <td class="px-3 py-2 text-right font-bold tabular-nums text-slate-900"><?= (int) $f['valor'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </section>
            <?php endforeach; ?>
        </div>

    <?php endif; ?>
</div>