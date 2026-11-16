<?php
/**
 * @var array|null $club
 * @var array|null $torneo
 * @var array      $partidos
 * @var array      $stats
 * @var array      $tarjetas
 * @var array      $plantel   [posicion => [jugadores]]
 * @var array      $tablas
 */

use Model\ClubPublico;
use Model\PlantelPublico;

$e = fn (?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<div class="mx-auto max-w-6xl px-4 py-8">
<?php if (empty($club)): ?>
    <h1 class="text-3xl font-black tracking-tight text-slate-900">Club no encontrado</h1>
    <p class="mt-4 text-slate-600">
        El club que buscás no existe. <a href="/clubes" class="font-semibold text-emerald-800 underline">Ver todos los clubes</a>.
    </p>
<?php else:
    $nombre   = (string) $club['nombre'];
    $etiquetas = PlantelPublico::POSICIONES + ['' => 'Sin posición'];

    $estados = [
        'en_juego'   => 'En juego',
        'suspendido' => 'Suspendido',
        'cancelado'  => 'Cancelado',
    ];

    $fecha = function (?string $fh): string {
        $ts = $fh ? strtotime($fh) : false;
        return $ts ? date('d/m/Y H:i', $ts) : 'Fecha a confirmar';
    };

    // Perfil: solo los datos que existan. Los valores ya salen escapados.
    $perfil = [];
    if (!empty($club['nombre_completo'])) { $perfil['Nombre completo'] = $e($club['nombre_completo']); }
    if (!empty($club['fundacion']))       { $perfil['Fundación'] = (int) $club['fundacion']; }

    $colores = array_filter(
        [$club['color_primario'] ?? null, $club['color_secundario'] ?? null],
        fn ($c) => is_string($c) && preg_match('/^#[0-9A-Fa-f]{6}$/', $c)
    );
    if ($colores) {
        $html = '';
        foreach ($colores as $c) {
            $html .= '<span class="mr-3 inline-flex items-center gap-1">'
                . '<span aria-hidden="true" class="inline-block h-4 w-4 rounded border border-slate-400" style="background-color:' . $e($c) . '"></span>'
                . '<span class="text-xs">' . $e(strtoupper($c)) . '</span></span>';
        }
        $perfil['Colores'] = $html;
    }

    if (!empty($club['cancha'])) {
        $texto = $e($club['cancha']);
        if (!empty($club['cancha_capacidad'])) {
            $texto .= ' <span class="font-normal text-slate-600">(capacidad ' . number_format((int) $club['cancha_capacidad'], 0, ',', '.') . ')</span>';
        }
        $perfil['Cancha local'] = $texto;
    }
    if (!empty($club['direccion'])) { $perfil['Dirección'] = $e($club['direccion']); }
    if (!empty($club['ciudad']))    { $perfil['Ciudad'] = $e($club['ciudad']); }
    if (!empty($club['telefono']))  { $perfil['Teléfono'] = $e($club['telefono']); }
    if (!empty($club['email'])) {
        $perfil['Email'] = '<a class="text-emerald-800 underline" href="mailto:' . $e($club['email']) . '">' . $e($club['email']) . '</a>';
    }
    if (!empty($club['sitio_web']) && in_array(parse_url($club['sitio_web'], PHP_URL_SCHEME), ['http', 'https'], true)) {
        $perfil['Sitio web'] = '<a class="text-emerald-800 underline" rel="noopener noreferrer" href="' . $e($club['sitio_web']) . '">' . $e($club['sitio_web']) . '</a>';
    }
?>
    <header class="flex items-center gap-5">
        <?php if (!empty($club['escudo'])): ?>
            <img src="<?= $e($club['escudo']) ?>" alt="" width="80" height="80" class="h-20 w-20 shrink-0 object-contain">
        <?php else: ?>
            <span aria-hidden="true" class="flex h-20 w-20 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xl font-bold text-slate-700">
                <?= $e(ClubPublico::iniciales($nombre)) ?>
            </span>
        <?php endif; ?>
        <div class="min-w-0">
            <h1 class="text-3xl font-black uppercase tracking-tight text-slate-900"><?= $e($nombre) ?></h1>
            <?php if (!empty($torneo['nombre'])): ?>
                <p class="mt-1 text-sm font-semibold uppercase tracking-wide text-slate-600"><?= $e($torneo['nombre']) ?></p>
            <?php endif; ?>
        </div>
    </header>

    <nav aria-label="Secciones del club" class="mt-6 border-b border-slate-300">
        <ul class="flex gap-1 overflow-x-auto">
            <?php foreach (['clasificacion' => 'Clasificación', 'partidos' => 'Partidos', 'plantel' => 'Plantel', 'estadisticas' => 'Estadísticas', 'perfil' => 'Perfil'] as $ancla => $texto): ?>
            <li>
                <a href="#<?= $ancla ?>"
                   class="block whitespace-nowrap px-3 py-3 text-sm font-bold uppercase tracking-wide text-slate-600 hover:text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-emerald-600">
                    <?= $texto ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </nav>

        <section id="clasificacion" aria-labelledby="h-clasif" class="mt-10 scroll-mt-4">
        <h2 id="h-clasif" class="text-2xl font-black uppercase text-slate-900">Clasificación</h2>
        <?php if (!$tablas): ?>
            <p class="mt-4 text-slate-600">Todavía no hay clasificación para este club.</p>
        <?php else: ?>
            <?php foreach ($tablas as $tabla): ?>
            <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
                <table class="w-full text-sm">
                    <caption class="border-b border-slate-200 px-4 py-3 text-left font-bold text-slate-900">
                        <?= $e($tabla['fase']) ?><?= $tabla['titulo'] ? ' · ' . $e($tabla['titulo']) : '' ?>
                    </caption>
                    <thead class="text-xs uppercase text-slate-600">
                        <tr class="border-b border-slate-200">
                            <th scope="col" class="px-3 py-2 text-left"><span class="sr-only">Posición</span><span aria-hidden="true">#</span></th>
                            <th scope="col" class="px-3 py-2 text-left">Equipo</th>
                            <th scope="col" class="px-3 py-2 text-right"><abbr title="Partidos jugados">PJ</abbr></th>
                            <th scope="col" class="hidden px-3 py-2 text-right sm:table-cell"><abbr title="Ganados">PG</abbr></th>
                            <th scope="col" class="hidden px-3 py-2 text-right sm:table-cell"><abbr title="Empatados">PE</abbr></th>
                            <th scope="col" class="hidden px-3 py-2 text-right sm:table-cell"><abbr title="Perdidos">PP</abbr></th>
                            <th scope="col" class="hidden px-3 py-2 text-right sm:table-cell"><abbr title="Goles a favor">GF</abbr></th>
                            <th scope="col" class="hidden px-3 py-2 text-right sm:table-cell"><abbr title="Goles en contra">GC</abbr></th>
                            <th scope="col" class="px-3 py-2 text-right"><abbr title="Diferencia de gol">DG</abbr></th>
                            <th scope="col" class="px-3 py-2 text-right"><abbr title="Puntos">Pts</abbr></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tabla['filas'] as $pos => $f):
                            $esEste = (int) $f['id'] === (int) $club['id'];
                        ?>
                        <tr class="border-b border-slate-100 <?= $esEste ? 'bg-emerald-700 text-white' : '' ?>">
                            <td class="px-3 py-2 tabular-nums"><?= $pos + 1 ?></td>
                            <th scope="row" class="px-3 py-2 text-left font-semibold">
                                <?php if ($esEste): ?>
                                    <?= $e($f['nombre']) ?><span class="sr-only"> (este club)</span>
                                <?php else: ?>
                                    <a href="/club?id_equipo=<?= (int) $f['id'] ?>" class="underline-offset-2 hover:underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600"><?= $e($f['nombre']) ?></a>
                                <?php endif; ?>
                            </th>
                            <td class="px-3 py-2 text-right tabular-nums"><?= (int) $f['pj'] ?></td>
                            <td class="hidden px-3 py-2 text-right tabular-nums sm:table-cell"><?= (int) $f['pg'] ?></td>
                            <td class="hidden px-3 py-2 text-right tabular-nums sm:table-cell"><?= (int) $f['pe'] ?></td>
                            <td class="hidden px-3 py-2 text-right tabular-nums sm:table-cell"><?= (int) $f['pp'] ?></td>
                            <td class="hidden px-3 py-2 text-right tabular-nums sm:table-cell"><?= (int) $f['gf'] ?></td>
                            <td class="hidden px-3 py-2 text-right tabular-nums sm:table-cell"><?= (int) $f['gc'] ?></td>
                            <td class="px-3 py-2 text-right tabular-nums"><?= $f['dg'] > 0 ? '+' : '' ?><?= (int) $f['dg'] ?></td>
                            <td class="px-3 py-2 text-right font-bold tabular-nums"><?= (int) $f['pts'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
            <p class="mt-2 text-xs text-slate-600">
                <a href="/clasificacion" class="font-semibold text-emerald-800 underline">Ver clasificación completa</a>
            </p>
        <?php endif; ?>
    </section>
    
    <section id="partidos" aria-labelledby="h-partidos" class="mt-10 scroll-mt-4">
        <h2 id="h-partidos" class="text-2xl font-black uppercase text-slate-900">Partidos</h2>
        <?php if (!$partidos): ?>
            <p class="mt-4 text-slate-600">Todavía no hay partidos programados.</p>
        <?php else: ?>
        <ul class="mt-4 space-y-3">
            <?php foreach ($partidos as $p):
                $esLocal = (int) $p['id_equipo_local'] === (int) $club['id'];
                $jugado  = in_array($p['estado'], ['finalizado', 'en_juego'], true);
            ?>
            <li>
            <a href="/partido?id_partido=<?= (int) $p['id'] ?>"
            class="block rounded-lg bg-white p-3 shadow-sm ring-1 ring-slate-200 transition hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600">
                <p class="text-xs font-semibold uppercase text-slate-600">
                    <?php if ($p['fecha_hora']): ?>
                        <time datetime="<?= $e(date('c', strtotime($p['fecha_hora']))) ?>"><?= $e($fecha($p['fecha_hora'])) ?></time>
                    <?php else: ?>
                        Fecha a confirmar
                    <?php endif; ?>
                    <?php if ($p['jornada']): ?> &middot; Jornada <?= (int) $p['jornada'] ?><?php endif; ?>
                    <?php if (isset($estados[$p['estado']])): ?> &middot; <?= $e($estados[$p['estado']]) ?><?php endif; ?>
                </p>
                <div class="mt-2 grid grid-cols-[1fr_auto_1fr] items-center gap-3">
                    <span class="text-right <?= $esLocal ? 'font-black' : '' ?>"><?= $e($p['local']) ?></span>
                    <span class="rounded bg-slate-100 px-3 py-1 text-lg font-black tabular-nums text-slate-900">
                        <?php if ($jugado): ?>
                            <span class="sr-only">Resultado: </span><?= (int) $p['goles_local'] ?> - <?= (int) $p['goles_visitante'] ?>
                        <?php else: ?>
                            vs
                        <?php endif; ?>
                    </span>
                    <span class="<?= !$esLocal ? 'font-black' : '' ?>"><?= $e($p['visitante']) ?></span>
                </div>
                <span class="sr-only">Ver crónica del partido</span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>

    <section id="plantel" aria-labelledby="h-plantel" class="mt-12 scroll-mt-4">
        <h2 id="h-plantel" class="text-2xl font-black uppercase text-slate-900">Plantel</h2>
        <?php if (!$plantel): ?>
            <p class="mt-4 text-slate-600">Todavía no hay jugadores cargados.</p>
        <?php else: ?>
            <?php foreach ($etiquetas as $clave => $etiquetaPos):
                if (empty($plantel[$clave])) { continue; } ?>
            <h3 class="mb-2 mt-6 text-sm font-bold uppercase tracking-wide text-slate-700"><?= $e($etiquetaPos) ?></h3>
            <ul class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($plantel[$clave] as $j):
                    $ini = mb_strtoupper(mb_substr(trim((string) $j['nombre']), 0, 1) . mb_substr(trim((string) $j['apellido']), 0, 1));
                    $tieneApellido = trim((string) $j['apellido']) !== '';
                ?>
                <li class="flex items-center gap-3 rounded-lg bg-white p-2 shadow-sm">
                    <?php if (!empty($j['foto'])): ?>
                        <img src="<?= $e($j['foto']) ?>" alt="" class="h-12 w-12 rounded-full object-cover">
                    <?php else: ?>
                        <span aria-hidden="true" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-slate-200 text-sm font-bold text-slate-700"><?= $e($ini) ?></span>
                    <?php endif; ?>
                    <?php if ($j['numero_camiseta'] !== null): ?>
                        <span class="w-8 shrink-0 text-center text-lg text-slate-600"><span class="sr-only">Camiseta </span><?= (int) $j['numero_camiseta'] ?></span>
                    <?php endif; ?>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate">
                            <?php if ($tieneApellido): ?><?= $e($j['nombre']) ?> <strong><?= $e($j['apellido']) ?></strong>
                            <?php else: ?><strong><?= $e($j['nombre']) ?></strong><?php endif; ?>
                        </span>
                        <?php if (!empty($j['pais'])): ?>
                            <span class="block text-xs text-slate-600"><span class="sr-only">País: </span><?= $e($j['pais']) ?></span>
                        <?php endif; ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section id="estadisticas" aria-labelledby="h-stats" class="mt-12 scroll-mt-4">
        <h2 id="h-stats" class="text-2xl font-black uppercase text-slate-900">Estadísticas</h2>
        <?php if (empty($stats['pj'])): ?>
            <p class="mt-4 text-slate-600">Todavía no hay partidos finalizados.</p>
        <?php else:
            $items = [
                'Partidos jugados' => $stats['pj'], 'Ganados' => $stats['g'], 'Empatados' => $stats['e'],
                'Perdidos' => $stats['p'], 'Goles a favor' => $stats['gf'], 'Goles en contra' => $stats['gc'],
                'Tarjetas amarillas' => $tarjetas['amarillas'] ?? 0, 'Tarjetas rojas' => $tarjetas['rojas'] ?? 0,
            ];
        ?>
        <dl class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            <?php foreach ($items as $etiqueta => $valor): ?>
            <div class="rounded-lg bg-white p-4 shadow-sm ring-1 ring-slate-200">
                <dt class="text-xs font-semibold uppercase text-slate-600"><?= $e($etiqueta) ?></dt>
                <dd class="mt-1 text-3xl font-black tabular-nums text-slate-900"><?= (int) $valor ?></dd>
            </div>
            <?php endforeach; ?>
        </dl>
        <?php endif; ?>
    </section>

    <section id="perfil" aria-labelledby="h-perfil" class="mt-12 scroll-mt-4">
        <h2 id="h-perfil" class="text-2xl font-black uppercase text-slate-900">Perfil</h2>
        <?php if (!$perfil && empty($club['foto'])): ?>
            <p class="mt-4 text-slate-600">Todavía no hay datos del club.</p>
        <?php else: ?>
        <div class="mt-4 grid gap-6 lg:grid-cols-2">
            <?php if (!empty($club['foto'])): ?>
                <img src="<?= $e($club['foto']) ?>" alt="Foto del club <?= $e($nombre) ?>" loading="lazy" class="w-full rounded-lg object-cover">
            <?php endif; ?>
            <?php if ($perfil): ?>
            <dl class="divide-y divide-slate-200 rounded-lg bg-white ring-1 ring-slate-200">
                <?php foreach ($perfil as $etiqueta => $valorHtml): ?>
                <div class="flex justify-between gap-4 px-4 py-3 text-sm">
                    <dt class="text-slate-600"><?= $e($etiqueta) ?></dt>
                    <dd class="text-right font-bold text-slate-900"><?= $valorHtml ?></dd>
                </div>
                <?php endforeach; ?>
            </dl>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
</div>