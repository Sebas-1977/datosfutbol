<?php
/**
 * @var array    $torneos   [id, nombre]
 * @var int      $idTorneo
 * @var int[]    $jornadas
 * @var int      $jornada
 * @var int|null $anterior
 * @var int|null $siguiente
 * @var array    $partidos
 */
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

/** Escudo decorativo; si no hay imagen, disco con iniciales y color por equipo. */
$escudo = function (?string $ruta, string $nombre = '', int $idEquipo = 0, string $tam = 'h-5 w-5') use ($e): string {
    if (is_string($ruta) && str_starts_with($ruta, '/uploads/')) {
        return '<img src="' . $e($ruta) . '" alt="" loading="lazy" class="' . $tam . ' shrink-0 object-contain">';
    }
    $palabras = preg_split('/\s+/', trim($nombre), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $ini = count($palabras) > 1
        ? mb_substr($palabras[0], 0, 1) . mb_substr($palabras[1], 0, 1)
        : mb_substr($palabras[0] ?? '', 0, 2);
    $ini = mb_strtoupper($ini) ?: '?';
    $color = 'hsl(' . (($idEquipo * 47) % 360) . ' 50% 38%)';
    return '<span aria-hidden="true" class="flex ' . $tam . ' shrink-0 items-center justify-center rounded-full text-[9px] font-bold text-white" style="background:' . $color . '">' . $e($ini) . '</span>';
};

$dias  = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
          'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

$fechaLarga = function (string $fh) use ($dias, $meses): string {
    $t = strtotime($fh);
    return ucfirst($dias[(int) date('w', $t)]) . ' ' . date('j', $t) . ' de ' . $meses[(int) date('n', $t) - 1];
};

$estados = ['programado' => 'Programado', 'en_juego' => 'En juego', 'finalizado' => 'Final'];

// Agrupar por día (los sin fecha van al final)
$porDia = [];
foreach ($partidos as $p) {
    $clave = $p['fecha_hora'] ? date('Y-m-d', strtotime($p['fecha_hora'])) : 'sin-fecha';
    $porDia[$clave][] = $p;
}

$url = fn(int $j) => '/partidos?torneo=' . $idTorneo . '&jornada=' . $j;
$claseNav = 'rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:border-emerald-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600';
?>

<div class="mx-auto max-w-4xl px-4 py-8">
    <h1 class="mb-6 text-2xl font-bold text-slate-900">Partidos</h1>

    <?php if (!$torneos): ?>
        <p class="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-600">
            Todavía no hay partidos cargados.
        </p>
    <?php else: ?>

    <!-- Selector -->
    <form method="get" action="/partidos" class="mb-6 flex flex-wrap items-end gap-3">
        <?php if (count($torneos) > 1): ?>
        <div>
            <label for="torneo" class="block text-sm font-semibold text-slate-700">Torneo</label>
            <select id="torneo" name="torneo"
                    class="mt-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600">
                <?php foreach ($torneos as $t): ?>
                <option value="<?= (int) $t['id'] ?>" <?= (int) $t['id'] === $idTorneo ? 'selected' : '' ?>>
                    <?= $e($t['nombre']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php else: ?>
        <input type="hidden" name="torneo" value="<?= $idTorneo ?>">
        <?php endif; ?>

        <div>
            <label for="jornada" class="block text-sm font-semibold text-slate-700">Jornada</label>
            <select id="jornada" name="jornada"
                    class="mt-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600">
                <?php foreach ($jornadas as $j): ?>
                <option value="<?= $j ?>" <?= $j === $jornada ? 'selected' : '' ?>>Jornada <?= $j ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit"
                class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
            Ver
        </button>
    </form>

    <!-- Flechas -->
    <nav aria-label="Navegación entre jornadas" class="mb-4 flex items-center justify-between gap-3">
        <?php if ($anterior !== null): ?>
            <a href="<?= $e($url($anterior)) ?>" class="<?= $claseNav ?>">
                <span aria-hidden="true">←</span> Jornada <?= $anterior ?>
            </a>
        <?php else: ?><span></span><?php endif; ?>

        <h2 class="text-lg font-bold text-slate-900">Jornada <?= $jornada ?></h2>

        <?php if ($siguiente !== null): ?>
            <a href="<?= $e($url($siguiente)) ?>" class="<?= $claseNav ?>">
                Jornada <?= $siguiente ?> <span aria-hidden="true">→</span>
            </a>
        <?php else: ?><span></span><?php endif; ?>
    </nav>

    <!-- Partidos -->
    <?php if (!$partidos): ?>
        <p class="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-600">
            No hay partidos en esta jornada.
        </p>
    <?php else: ?>
        <?php foreach ($porDia as $dia => $lista): ?>
        <section aria-labelledby="dia-<?= $e($dia) ?>" class="mb-6">
            <h3 id="dia-<?= $e($dia) ?>" class="mb-2 text-sm font-bold uppercase tracking-wide text-slate-600">
                <?php if ($dia === 'sin-fecha'): ?>
                    Fecha a confirmar
                <?php else: ?>
                    <time datetime="<?= $e($dia) ?>"><?= $e($fechaLarga($dia)) ?></time>
                <?php endif; ?>
            </h3>

            <ul class="divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white">
                <?php foreach ($lista as $p):
                    $estado   = $p['estado'];
                    $conGoles = in_array($estado, ['finalizado', 'en_juego'], true);
                    $textoEst = $estados[$estado] ?? ucfirst(str_replace('_', ' ', $estado));
                    $hora     = $p['fecha_hora'] ? date('H:i', strtotime($p['fecha_hora'])) : null;
                    $label    = $conGoles
                        ? "{$p['local']} {$p['goles_local']}, {$p['visitante']} {$p['goles_visitante']}. {$textoEst}"
                        : "{$p['local']} contra {$p['visitante']}" . ($hora ? ", {$hora} h" : '') . ". {$textoEst}";
                ?>
                <li>
                    <a href="/partido?id_partido=<?= (int) $p['id_partido'] ?>"
                       aria-label="<?= $e($label) ?>"
                       class="flex items-center gap-4 p-4 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-emerald-600">
                        <p class="w-16 shrink-0 text-sm text-slate-600" aria-hidden="true">
                            <?php if ($hora): ?>
                                <time datetime="<?= $e(date('c', strtotime($p['fecha_hora']))) ?>"><?= $e($hora) ?> h</time>
                            <?php else: ?>&ndash;<?php endif; ?>
                        </p>

                        <div class="min-w-0 max-w-xs flex-1 space-y-1 text-sm font-semibold text-slate-900" aria-hidden="true">
                            <p class="flex items-center justify-between">
                                <span class="flex min-w-0 items-center gap-2">
                                    <?= $escudo($p['escudo_local'] ?? null) ?>
                                    <span class="truncate"><?= $e($p['local']) ?></span>
                                </span>
                                <?php if ($conGoles): ?><span class="ml-2 tabular-nums"><?= (int) $p['goles_local'] ?></span><?php endif; ?>
                            </p>
                            <p class="flex items-center justify-between">
                                <span class="flex min-w-0 items-center gap-2">
                                    <?= $escudo($p['escudo_visitante'] ?? null) ?>
                                    <span class="truncate"><?= $e($p['visitante']) ?></span>
                                </span>
                                <?php if ($conGoles): ?><span class="ml-2 tabular-nums"><?= (int) $p['goles_visitante'] ?></span><?php endif; ?>
                            </p>
                        </div>

                        <p class="ml-auto w-20 shrink-0 text-right text-xs font-semibold <?= $estado === 'en_juego' ? 'text-red-700' : 'text-slate-600' ?>" aria-hidden="true">
                            <?= $e($textoEst) ?>
                        </p>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php endif; ?>
</div>