<?php
/**
 * @var array $torneos
 * @var int   $idTorneo
 * @var array $clubes       Clubes a mostrar (uno si hay filtro)
 * @var array $todosClubes  Para el selector
 * @var int   $idEquipo
 * @var array $plantel      [id_equipo => [posicion => [jugadores]]]
 */

use Model\ClubPublico;
use Model\PlantelPublico;

$e = fn (?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

$seudonimo = fn (array $j): string => trim((string) ($j['seudonimo'] ?? ''));

/** Con seudónimo: su primera letra. Sin seudónimo: iniciales de nombre y apellido. */
$iniciales = function (array $j) use ($seudonimo): string {
    $seudo = $seudonimo($j);
    if ($seudo !== '') {
        return mb_strtoupper(mb_substr($seudo, 0, 1));
    }
    $a = mb_substr(trim((string) $j['nombre']), 0, 1);
    $b = mb_substr(trim((string) $j['apellido']), 0, 1);
    return mb_strtoupper($a . $b);
};

$etiquetas = PlantelPublico::POSICIONES + ['' => 'Sin posición'];
$abrirTodo = $idEquipo > 0 || count($clubes) === 1;
?>
<div class="mx-auto max-w-6xl px-4 py-8">
    <h1 class="text-3xl font-black tracking-tight text-slate-900">Jugadores</h1>

    <form method="get" action="/jugadores" class="mt-4 flex flex-wrap items-end gap-4">
        <?php if (count($torneos) > 1): ?>
        <div>
            <label for="id_torneo" class="block text-sm font-semibold text-slate-700">Torneo</label>
            <select id="id_torneo" name="id_torneo"
                    class="mt-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600">
                <?php foreach ($torneos as $t): ?>
                    <option value="<?= (int) $t['id'] ?>" <?= (int) $t['id'] === $idTorneo ? 'selected' : '' ?>><?= $e($t['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php else: ?>
            <input type="hidden" name="id_torneo" value="<?= $idTorneo ?>">
        <?php endif; ?>

        <div>
            <label for="id_equipo" class="block text-sm font-semibold text-slate-700">Club</label>
            <select id="id_equipo" name="id_equipo"
                    class="mt-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600">
                <option value="0">Todos los clubes</option>
                <?php foreach ($todosClubes as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === $idEquipo ? 'selected' : '' ?>><?= $e($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit"
                class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
            Ver jugadores
        </button>
    </form>

    <?php if (!$clubes): ?>
        <p class="mt-8 rounded-lg border border-slate-200 bg-white p-6 text-slate-600">
            Todavía no hay clubes cargados en este torneo.
        </p>
    <?php else: ?>
    <div class="mt-8 border-t border-slate-300">
        <?php foreach ($clubes as $club):
            $id    = (int) $club['id'];
            $grupos = $plantel[$id] ?? [];
            $cantidad = (int) $club['cantidad'];
        ?>
        <details class="group border-b border-slate-300" <?= $abrirTodo ? 'open' : '' ?>>
            <summary class="flex cursor-pointer list-none items-center gap-4 py-4 hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-emerald-600 [&::-webkit-details-marker]:hidden">
                <?php if (!empty($club['escudo'])): ?>
                    <img src="<?= $e($club['escudo']) ?>" alt="" class="h-10 w-10 object-contain">
                <?php else: ?>
                    <span aria-hidden="true" class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-700">
                        <?= $e(ClubPublico::iniciales($club['nombre'])) ?>
                    </span>
                <?php endif; ?>
                <h2 class="flex-1 text-xl font-black uppercase text-slate-900"><?= $e($club['nombre']) ?></h2>
                <span class="text-sm text-slate-600"><?= $cantidad ?> <?= $cantidad === 1 ? 'jugador' : 'jugadores' ?></span>
                <span aria-hidden="true" class="text-slate-600 transition-transform group-open:rotate-180">▾</span>
            </summary>

            <div class="pb-6">
                <?php if (!$grupos): ?>
                    <p class="text-slate-600">Todavía no hay jugadores cargados.</p>
                <?php else: ?>
                    <?php foreach ($etiquetas as $clave => $etiquetaPos):
                        if (empty($grupos[$clave])) { continue; } ?>
                    <section class="mt-4" aria-labelledby="pos-<?= $id ?>-<?= $e($clave ?: 'sin') ?>">
                        <h3 id="pos-<?= $id ?>-<?= $e($clave ?: 'sin') ?>" class="mb-2 text-sm font-bold uppercase tracking-wide text-slate-700">
                            <?= $e($etiquetaPos) ?>
                        </h3>
                        <ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <?php foreach ($grupos[$clave] as $j):
                                $seudo = $seudonimo($j);
                                $tieneApellido = trim((string) $j['apellido']) !== '';
                                $nombreReal = trim(trim((string) $j['nombre']) . ' ' . trim((string) $j['apellido']));
                            ?>
                            <li class="flex items-center gap-3 rounded-lg bg-white p-2 shadow-sm">
                                <?php if (!empty($j['foto'])): ?>
                                    <img src="<?= $e($j['foto']) ?>" alt="" class="h-12 w-12 rounded-full object-cover">
                                <?php else: ?>
                                    <span aria-hidden="true" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-slate-200 text-sm font-bold text-slate-700">
                                        <?= $e($iniciales($j)) ?>
                                    </span>
                                <?php endif; ?>

                                <?php if ($j['numero_camiseta'] !== null): ?>
                                    <span class="w-8 shrink-0 text-center text-lg text-slate-600">
                                        <span class="sr-only">Camiseta </span><?= (int) $j['numero_camiseta'] ?>
                                    </span>
                                <?php endif; ?>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate">
                                        <?php if ($seudo !== ''): ?>
                                            <strong><?= $e($seudo) ?></strong>
                                        <?php elseif ($tieneApellido): ?>
                                            <?= $e($j['nombre']) ?> <strong><?= $e($j['apellido']) ?></strong>
                                        <?php else: ?>
                                            <strong><?= $e($j['nombre']) ?></strong>
                                        <?php endif; ?>
                                    </span>
                                    <?php if ($seudo !== '' || !empty($j['pais'])): ?>
                                        <span class="block truncate text-xs text-slate-600">
                                            <?php if ($seudo !== ''): ?>
                                                <span class="sr-only">Nombre: </span><?= $e($nombreReal) ?><?= !empty($j['pais']) ? ' · ' : '' ?>
                                            <?php endif; ?>
                                            <?php if (!empty($j['pais'])): ?>
                                                <span class="sr-only">País: </span><?= $e($j['pais']) ?>
                                            <?php endif; ?>
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </details>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>