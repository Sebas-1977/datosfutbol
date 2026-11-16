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

    <div id="resumen"
         data-partido="<?= htmlspecialchars(json_encode($partido, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>">

        <a href="/admin/partidos?torneo=<?= (int) ($partido->id_torneo ?? 0) ?>" class="text-xs font-semibold text-emerald-700 hover:underline">&larr; Volver a Partidos</a>

        <!-- Encabezado -->
        <section class="mt-2 rounded-xl border border-slate-200 bg-white p-6">
            <p id="meta-torneo" class="text-center text-xs font-semibold uppercase tracking-wide text-slate-400"></p>
            <div class="mt-3 grid grid-cols-[1fr_auto_1fr] items-center gap-4">
                <h1 id="eq-local" class="text-right text-xl font-bold text-slate-900"></h1>
                <div class="text-center">
                    <p id="marcador" class="text-4xl font-extrabold text-slate-900"></p>
                    <span id="estado" class="mt-1 inline-block rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600"></span>
                </div>
                <h1 id="eq-visitante" class="text-left text-xl font-bold text-slate-900"></h1>
            </div>
            <p id="meta-logistica" class="mt-4 text-center text-sm text-slate-500"></p>
            <p class="mt-3 text-center text-xs">
                <a href="/admin/alineacion?id_partido=<?= (int) $partido->id ?>" class="font-semibold text-emerald-700 hover:underline">Editar alineación</a>
                <span class="mx-2 text-slate-300">|</span>
                <a href="/partido?id_partido=<?= (int) $partido->id ?>" class="font-semibold text-emerald-700 hover:underline">Ver crónica</a>
            </p>
        </section>

                <!-- Logística -->
        <section class="mt-6 rounded-xl border border-slate-200 bg-white">
            <h2 class="border-b border-slate-200 px-4 py-3 text-sm font-bold text-slate-900">Logística</h2>
            <div class="px-4 py-4">
                <div id="l-acciones" class="mb-4 flex flex-wrap gap-2"></div>
                <form id="form-logistica" class="grid items-end gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <label class="block text-xs font-semibold text-slate-500">Estado
                        <select id="l-estado" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900"></select>
                    </label>
                    <label class="block text-xs font-semibold text-slate-500">Fecha y hora
                        <input id="l-fecha" type="datetime-local" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900">
                    </label>
                    <label class="block text-xs font-semibold text-slate-500">Cancha
                        <select id="l-cancha" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900"></select>
                    </label>
                    <label class="block text-xs font-semibold text-slate-500">Árbitro
                        <select id="l-arbitro" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900"></select>
                    </label>
                    <button type="submit"
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
                        Guardar logística
                    </button>
                                </form>

                <div class="mt-4 border-t border-slate-100 pt-4">
                    <button type="button" id="btn-finalizar-resumen"
                            class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60">
                        Finalizar y ver crónica
                    </button>
                    <p class="mt-2 text-xs text-slate-400">Revisa los datos, pasa el partido a Finalizado y abre la crónica.</p>
                </div>
            </div>
        </section>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem]">

            <!-- Línea de tiempo -->
            <section class="rounded-xl border border-slate-200 bg-white">
                <h2 class="border-b border-slate-200 px-4 py-3 text-sm font-bold text-slate-900">Eventos</h2>
                <div id="eventos" class="px-4 py-2"></div>
            </section>

            <!-- Formulario -->
            <section class="h-fit rounded-xl border border-slate-200 bg-white">
                <h2 class="border-b border-slate-200 px-4 py-3 text-sm font-bold text-slate-900">Agregar evento</h2>
                <form id="form-evento" class="space-y-3 px-4 py-4">
                    <label class="block text-xs font-semibold text-slate-500">Equipo
                        <select id="f-equipo" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900"></select>
                    </label>
                    <label class="block text-xs font-semibold text-slate-500">Tipo
                        <select id="f-tipo" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900"></select>
                    </label>
                    <label class="block text-xs font-semibold text-slate-500"><span id="f-jugador-label">Jugador</span>
                        <select id="f-jugador" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900"></select>
                    </label>
                    <label id="w-entra" class="hidden block text-xs font-semibold text-slate-500">Entra
                        <select id="f-entra" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900"></select>
                    </label>
                    <label id="w-asistente" class="hidden block text-xs font-semibold text-slate-500">Asistente (opcional)
                        <select id="f-asistente" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900"></select>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="block text-xs font-semibold text-slate-500">Minuto
                            <input id="f-minuto" type="number" min="0" max="150" required
                                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900">
                        </label>
                        <label class="block text-xs font-semibold text-slate-500">Extra (45+, 90+)
                            <input id="f-extra" type="number" min="1" max="30"
                                   class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900">
                        </label>
                    </div>
                    <label class="block text-xs font-semibold text-slate-500">Detalle (opcional)
                        <input id="f-detalle" type="text" maxlength="255"
                               class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900">
                    </label>
                    <button type="submit"
                            class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        Guardar evento
                    </button>
                </form>
            </section>
        </div>

        <!-- Alineaciones -->
        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <div id="alin-local"></div>
            <div id="alin-visitante"></div>
        </div>
    </div>

<?php endif; ?>
</div>

<div id="toast" class="pointer-events-none fixed bottom-4 right-4 z-50 hidden max-w-sm rounded-lg bg-slate-900 px-4 py-2 text-sm text-white shadow-lg"></div>

<?php if ($partido): ?>
<script src="/js/resumen.js"></script>
<?php endif; ?>