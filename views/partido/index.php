<?php
/**
 * @var \Model\Partido|null $partido
 */
$foco = 'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600';
?>
<div class="mx-auto max-w-6xl px-4 py-6">
<?php if (!$partido): ?>

    <a href="/partidos" class="text-xs font-semibold text-emerald-800 hover:underline <?= $foco ?>">&larr; Partidos</a>
    <h1 class="mt-4 text-xl font-bold text-slate-900">Partido no encontrado</h1>
    <p class="mt-2 rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-600">
        No se encontró el partido que buscás. Volvé al listado para elegir otro.
    </p>

<?php else: ?>

    <div id="cronica"
         data-partido="<?= htmlspecialchars(json_encode($partido, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>">

        <h1 id="c-titulo" class="sr-only">Crónica del partido</h1>

        <div class="flex items-center justify-between print:hidden">
            <a href="/partidos" class="text-xs font-semibold text-emerald-800 hover:underline <?= $foco ?>">&larr; Partidos</a>
            <div class="flex gap-2">
                <?php if (\Classes\Auth::isAuth()): ?>
                <a href="/admin/resumen?id_partido=<?= (int) $partido->id ?>"
                   class="rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800 <?= $foco ?>">
                    Resumen del partido
                </a>
                <?php endif; ?>
                <button id="btn-compartir" type="button"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 <?= $foco ?>">Compartir</button>
                <button id="btn-imprimir" type="button"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 <?= $foco ?>">Imprimir</button>
            </div>
        </div>

        <div class="mt-3 grid gap-4 lg:grid-cols-[320px_1fr] lg:items-start">

            <!-- Tarjeta de resumen -->
            <section aria-label="Resumen del partido" class="overflow-hidden rounded-2xl border border-slate-200 bg-white lg:sticky lg:top-4">
                <div class="bg-emerald-950 px-4 py-5 text-white">
                    <p id="c-torneo" class="text-center text-xs text-emerald-200"></p>
                    <div class="mt-4 grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                        <div id="c-local" class="flex flex-col items-center gap-2 text-center"></div>
                        <div class="text-center">
                            <p id="c-marcador" class="text-4xl font-black tabular-nums tracking-tight"></p>
                            <span id="c-estado" class="mt-1 inline-block rounded-full bg-white/15 px-2.5 py-0.5 text-[11px] font-semibold"></span>
                        </div>
                        <div id="c-visitante" class="flex flex-col items-center gap-2 text-center"></div>
                    </div>
                </div>
                <div class="px-4 py-4">
                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div id="c-goles-local" class="space-y-0.5 text-right"></div>
                        <div id="c-goles-visitante" class="space-y-0.5 text-left"></div>
                    </div>
                    <p id="c-logistica" class="mt-4 border-t border-slate-100 pt-3 text-center text-xs text-slate-600"></p>
                </div>
            </section>

            <!-- Pestañas -->
            <div class="min-w-0">
                <div class="flex gap-1 overflow-x-auto border-b border-slate-200 print:hidden" role="tablist">
                    <?php foreach (['cronica' => 'Crónica', 'tiempo' => 'Minuto a minuto', 'alineaciones' => 'Alineaciones', 'cancha' => 'Cancha', 'stats' => 'Estadísticas'] as $k => $t): ?>
                    <button type="button" role="tab" data-tab="<?= $k ?>"
                            class="-mb-px shrink-0 border-b-2 border-transparent px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-900"><?= $t ?></button>
                    <?php endforeach; ?>
                </div>

                <section data-panel="cronica" class="mt-4 hidden rounded-xl border border-slate-200 bg-white px-5 py-5 print:mt-4 print:block">
                    <h2 class="text-lg font-bold text-slate-900">La crónica</h2>
                    <div id="c-texto" class="mt-2 space-y-3 text-[15px] leading-relaxed text-slate-700"></div>
                </section>

                <section data-panel="tiempo" class="mt-4 hidden rounded-xl border border-slate-200 bg-white px-5 py-4 print:mt-4 print:block">
                    <h2 class="text-lg font-bold text-slate-900">Minuto a minuto</h2>
                    <div id="c-tiempo" class="mt-2"></div>
                </section>

                <section data-panel="alineaciones" class="mt-4 hidden print:mt-4 print:block">
                    <h2 class="sr-only">Alineaciones</h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div id="c-alin-local"></div>
                        <div id="c-alin-visitante"></div>
                    </div>
                </section>

                <section data-panel="cancha" class="mt-4 hidden rounded-xl border border-slate-200 bg-white px-4 py-4 print:mt-4 print:block">
                    <h2 class="sr-only">Cancha y esquema táctico</h2>
                    <div class="grid grid-cols-2 text-sm">
                        <div id="c-form-local"></div>
                        <div id="c-form-visitante" class="text-right"></div>
                    </div>
                    <div class="mt-3 overflow-x-auto">
                        <div id="c-cancha"></div>
                    </div>
                </section>

                <section data-panel="stats" class="mt-4 hidden rounded-xl border border-slate-200 bg-white px-5 py-4 print:mt-4 print:block">
                    <h2 class="text-lg font-bold text-slate-900">En números</h2>
                    <div id="c-stats" class="mt-2 divide-y divide-slate-100"></div>
                </section>
            </div>
        </div>
    </div>

<?php endif; ?>
</div>

<div id="toast" class="pointer-events-none fixed bottom-4 right-4 z-50 hidden max-w-sm rounded-lg bg-slate-900 px-4 py-2 text-sm text-white shadow-lg"></div>

<?php if ($partido): ?>
<script src="/js/partido.js"></script>
<?php endif; ?>