<?php
$campo = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30';
$label = 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500';
$activo = 'partidos';
$anchoMenu = 'max-w-6xl';
require dirname(__DIR__) . '/admin/_menu.php';
?>
<main id="contenido" tabindex="-1" class="mx-auto max-w-6xl px-4 py-8">

    <!-- Encabezado del Torneo -->
    <div class="mb-6">
        <a id="link-volver-torneo" href="/admin/torneos"
           class="mb-2 inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-emerald-700 transition dark:text-slate-400 dark:hover:text-emerald-400">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Volver al torneo</span>
        </a>
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    <span id="nombre-torneo-titulo">Torneo</span>
                </h1>
                <p class="text-sm text-slate-500">Gestión de partidos, resultados y fechas.</p>
            </div>
            <div class="flex items-center gap-2">
                <button id="btn-nuevo" type="button"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                    + Nuevo partido
                </button>
            </div>
        </div>

        <!-- Pestañas del Torneo -->
        <div class="mt-4 border-b border-slate-200">
            <nav class="-mb-px flex space-x-2 sm:space-x-4" aria-label="Secciones del torneo">
                <a href="#" id="tab-torneo-partidos"
                   class="inline-flex items-center gap-2 border-b-2 border-emerald-600 px-3 py-2 text-sm font-bold text-emerald-700 dark:text-emerald-400">
                    ⚽ Partidos
                </a>
                <a href="#" id="tab-torneo-planteles"
                   class="inline-flex items-center gap-2 border-b-2 border-transparent px-3 py-2 text-sm font-semibold text-slate-500 hover:border-slate-300 hover:text-slate-800 transition dark:text-slate-400 dark:hover:text-slate-200">
                    👥 Planteles
                </a>
                <a href="/admin/torneos" id="tab-torneo-fixture"
                   class="inline-flex items-center gap-2 border-b-2 border-transparent px-3 py-2 text-sm font-semibold text-slate-500 hover:border-slate-300 hover:text-slate-800 transition dark:text-slate-400 dark:hover:text-slate-200"
                   title="Volver al espacio del torneo">
                    🏟️ Inicio del torneo
                </a>
            </nav>
        </div>
    </div>

    <!-- Filtros -->
    <div class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-2">
        <div>
            <div class="flex items-center justify-between">
                <label for="filtro-torneo" class="<?= $label ?>">Torneo</label>
                <a href="/admin/torneos"
                   class="text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400"
                   title="Para ver otro torneo, salí e ingresá por su card">
                    Cambiar torneo &rarr;
                </a>
            </div>
            <select id="filtro-torneo" class="<?= $campo ?> cursor-not-allowed opacity-90 font-medium" disabled></select>
        </div>
        <div>
            <label for="filtro-estado" class="<?= $label ?>">Estado</label>
            <select id="filtro-estado" class="<?= $campo ?>">
                <option value="">Todos</option>
                <option value="programado">Programado</option>
                <option value="en_juego">En juego</option>
                <option value="finalizado">Finalizado</option>
                <option value="suspendido">Suspendido</option>
                <option value="cancelado">Cancelado</option>
            </select>
        </div>
    </div>

    <!-- Navegación por jornadas (solo con un torneo elegido) -->
    <div id="nav-jornada" class="mb-4 hidden">
        <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-3">
            <button type="button" id="jor-ant"
                class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">‹ Anterior</button>
            <div class="min-w-0 flex-1 sm:flex-none">
                <label for="jor-select" class="sr-only">Jornada</label>
                <select id="jor-select" class="<?= $campo ?> sm:min-w-[16rem]"></select>
            </div>
            <button type="button" id="jor-sig"
                class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Siguiente ›</button>
        </div>
    </div>

    <!-- Listado -->
    <div id="lista" class="space-y-6"></div>
    <p id="vacio" class="hidden rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
        No hay partidos para mostrar.
    </p>
</main>

<!-- Modal -->
<div id="modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4"
     role="dialog" aria-modal="true" aria-labelledby="modal-titulo">
    <div class="max-h-full w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-xl">
        <form id="form" novalidate>
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h2 id="modal-titulo" class="text-lg font-bold text-slate-900">Nuevo partido</h2>
                <button type="button" data-cerrar class="text-2xl leading-none text-slate-400 hover:text-slate-600" aria-label="Cerrar">&times;</button>
            </div>

            <div class="space-y-5 px-6 py-5">
                <div id="alertas" class="hidden rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>

                <input type="hidden" id="f-id">

                <!-- Competencia -->
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label for="f-torneo" class="<?= $label ?>">Torneo</label>
                        <select id="f-torneo" class="<?= $campo ?>" required></select>
                    </div>
                    <div>
                        <label for="f-fase" class="<?= $label ?>">Fase</label>
                        <select id="f-fase" class="<?= $campo ?>" required></select>
                    </div>
                    <div>
                        <label for="f-grupo" class="<?= $label ?>">Grupo</label>
                        <select id="f-grupo" class="<?= $campo ?>"></select>
                    </div>
                </div>

                <!-- Equipos y marcador -->
                <div class="grid items-end gap-4 sm:grid-cols-[1fr_auto_1fr]">
                    <div>
                        <label for="f-local" class="<?= $label ?>">Local</label>
                        <select id="f-local" class="<?= $campo ?>" required></select>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="number" min="0" step="1" id="f-goles-local" value="0" class="<?= $campo ?> w-16 text-center" aria-label="Goles local" required>
                        <span class="text-slate-400">-</span>
                        <input type="number" min="0" step="1" id="f-goles-visitante" value="0" class="<?= $campo ?> w-16 text-center" aria-label="Goles visitante" required>
                    </div>
                    <div>
                        <label for="f-visitante" class="<?= $label ?>">Visitante</label>
                        <select id="f-visitante" class="<?= $campo ?>" required></select>
                    </div>
                </div>

                <!-- Detalles -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="f-fecha" class="<?= $label ?>">Fecha y hora</label>
                        <input type="datetime-local" id="f-fecha" class="<?= $campo ?>" required>
                    </div>
                    <div>
                        <label for="f-jornada" class="<?= $label ?>">Jornada</label>
                        <input type="number" min="1" id="f-jornada" value="1" class="<?= $campo ?>" required>
                    </div>
                    <div>
                        <label for="f-cancha" class="<?= $label ?>">Cancha</label>
                        <select id="f-cancha" class="<?= $campo ?>" required></select>
                    </div>
                    <div>
                        <label for="f-arbitro" class="<?= $label ?>">Árbitro</label>
                        <select id="f-arbitro" class="<?= $campo ?>" required></select>
                    </div>
                    <div>
                        <label for="f-estado" class="<?= $label ?>">Estado</label>
                        <select id="f-estado" class="<?= $campo ?>">
                            <option value="programado">Programado</option>
                            <option value="en_juego">En juego</option>
                            <option value="finalizado">Finalizado</option>
                            <option value="suspendido">Suspendido</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                <button type="button" data-cerrar
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    Cancelar
                </button>
                <button type="submit" id="btn-guardar"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<div id="toast" class="pointer-events-none fixed bottom-4 right-4 z-50 hidden rounded-lg bg-slate-900 px-4 py-2 text-sm text-white shadow-lg"></div>

<script src="/js/partidos.js"></script>