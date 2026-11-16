<?php
/**
 * @var \Model\Partido|null $partido
 */
?>
<div class="mx-auto max-w-6xl px-4 py-8">
<?php if (!$partido): ?>

    <a href="/admin/partidos?torneo=<?= (int) ($partido->id_torneo ?? 0) ?>" class="text-xs font-semibold text-emerald-700 hover:underline">&larr; Volver a Partidos</a>
    <p class="mt-4 rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
        No se encontró el partido.
    </p>

<?php else: ?>

    <div id="alineacion"
         data-partido="<?= htmlspecialchars(json_encode($partido, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>">

        <div class="mb-6">
            <a href="/admin/partidos?torneo=<?= (int) ($partido->id_torneo ?? 0) ?>" class="text-xs font-semibold text-emerald-700 hover:underline">&larr; Volver a Partidos</a>
            <h1 id="titulo-partido" class="text-2xl font-bold text-slate-900">Alineación</h1>
            <p id="subtitulo-partido" class="text-sm text-slate-500"></p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div id="col-local"></div>
            <div id="col-visitante"></div>
        </div>
    
        <div class="mt-8 border-t border-slate-200 pt-6">
            <p class="text-sm text-slate-500">
                Para continuar al resumen, ambos equipos deben tener al menos 11 titulares.
            </p>
            <div class="mt-4 flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
                <a href="/admin/partidos?torneo=<?= (int) $partido->id_torneo ?>&editar=<?= (int) $partido->id ?>"
                   class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    &larr; Configuración del partido
                </a>
                <a id="btn-finalizar"
                   href="/admin/resumen?id_partido=<?= (int) $partido->id ?>"
                   class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    Continuar al resumen &rarr;
                </a>
            </div>
        </div>
    </div>

<?php endif; ?>
</div>

<div id="toast" class="pointer-events-none fixed bottom-4 right-4 z-50 hidden max-w-sm rounded-lg bg-slate-900 px-4 py-2 text-sm text-white shadow-lg"></div>

<?php if ($partido): ?>
<script src="/js/alineacion.js"></script>
<?php endif; ?>