<?php
/**
 * @var array $torneos
 * @var int   $idTorneo
 * @var array $fases
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

$columnas = [
    // clave => [abreviatura, texto completo, visible en móvil]
    'pj'  => ['PJ',  'Partidos jugados', true],
    'pg'  => ['PG',  'Ganados',          false],
    'pe'  => ['PE',  'Empatados',        false],
    'pp'  => ['PP',  'Perdidos',         false],
    'gf'  => ['GF',  'Goles a favor',    false],
    'gc'  => ['GC',  'Goles en contra',  false],
    'dg'  => ['DG',  'Diferencia de gol', true],
    'pts' => ['Pts', 'Puntos',           true],
];
?>

<div class="mx-auto max-w-4xl px-4 py-8">
    <h1 class="mb-6 text-2xl font-bold text-slate-900">Clasificación</h1>

    <?php if (!$torneos): ?>
        <p class="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-600">
            Todavía no hay torneos con partidos.
        </p>
    <?php else: ?>

    <?php if (count($torneos) > 1): ?>
    <form method="get" action="/clasificacion" class="mb-6 flex flex-wrap items-end gap-3">
        <div>
            <label for="torneo" class="block text-sm font-semibold text-slate-700">Torneo</label>
            <select id="torneo" name="torneo"
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

    <?php foreach ($fases as $i => $fase): ?>
    <section aria-labelledby="fase-<?= $i ?>" class="mb-10">
        <h2 id="fase-<?= $i ?>" class="mb-4 text-xl font-bold text-slate-900"><?= $e($fase['fase']) ?></h2>

        <?php if ($fase['tipo'] === 'eliminatoria'): ?>
            <p class="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-600">
                El cuadro de eliminatorias estará disponible pronto.
            </p>
        <?php else: ?>
            <?php foreach ($fase['tablas'] as $tabla): ?>
            <div class="mb-6 overflow-x-auto rounded-lg border border-slate-200 bg-white">
                <table class="w-full text-sm">
                    <caption class="border-b border-slate-200 px-4 py-3 text-left text-base font-bold text-slate-900">
                        <?= $e($tabla['titulo'] ?? 'Tabla de posiciones') ?>
                    </caption>
                    <thead class="text-xs uppercase text-slate-600">
                        <tr class="border-b border-slate-200">
                            <th scope="col" class="px-3 py-2 text-left"><span class="sr-only">Posición</span><span aria-hidden="true">#</span></th>
                            <th scope="col" class="px-3 py-2 text-left">Equipo</th>
                            <?php foreach ($columnas as [$abbr, $texto, $movil]): ?>
                            <th scope="col" class="px-3 py-2 text-right <?= $movil ? '' : 'hidden sm:table-cell' ?>">
                                <abbr title="<?= $e($texto) ?>"><?= $e($abbr) ?></abbr>
                            </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($tabla['filas'] as $pos => $f): ?>
                        <tr>
                            <td class="px-3 py-2 tabular-nums text-slate-600"><?= $pos + 1 ?></td>
                            <th scope="row" class="px-3 py-2 text-left font-semibold text-slate-900">
                                <span class="flex items-center gap-2"><?= $escudo($f['escudo'] ?? null) ?><?= $e($f['nombre']) ?></span>
                            </th>
                            <?php foreach ($columnas as $clave => [$abbr, $texto, $movil]): ?>
                            <td class="px-3 py-2 text-right tabular-nums <?= $clave === 'pts' ? 'font-bold text-slate-900' : 'text-slate-700' ?> <?= $movil ? '' : 'hidden sm:table-cell' ?>">
                                <?= $clave === 'dg' && $f[$clave] > 0 ? '+' : '' ?><?= (int) $f[$clave] ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
    <?php endforeach; ?>

    <p class="text-xs text-slate-600">
        Victoria 3 puntos, empate 1. Desempate: diferencia de gol, goles a favor y enfrentamiento directo.
    </p>

    <?php endif; ?>
</div>