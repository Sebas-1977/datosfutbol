<?php
$campo = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30';
$label = 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500';
$activo = 'plantel';
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
                    <span id="nombre-torneo-titulo">Planteles</span>
                </h1>
                <p class="text-sm text-slate-500">Gestión de jugadores y dorsales por equipo para este torneo.</p>
            </div>
            <div class="flex items-center gap-2">
                <button id="btn-agregar" type="button" disabled
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                    + Agregar jugador
                </button>
            </div>
        </div>

        <!-- Pestañas del Torneo -->
        <div class="mt-4 border-b border-slate-200">
            <nav class="-mb-px flex space-x-2 sm:space-x-4" aria-label="Secciones del torneo">
                <a href="#" id="tab-torneo-partidos"
                   class="inline-flex items-center gap-2 border-b-2 border-transparent px-3 py-2 text-sm font-semibold text-slate-500 hover:border-slate-300 hover:text-slate-800 transition dark:text-slate-400 dark:hover:text-slate-200">
                    ⚽ Partidos
                </a>
                <a href="#" id="tab-torneo-planteles"
                   class="inline-flex items-center gap-2 border-b-2 border-emerald-600 px-3 py-2 text-sm font-bold text-emerald-700 dark:text-emerald-400">
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

    <div class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-2">
        <div>
            <div class="flex items-center justify-between">
                <label for="sel-torneo" class="<?= $label ?>">Torneo</label>
                <a href="/admin/torneos"
                   class="text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400"
                   title="Para ver otro torneo, salí e ingresá por su card">
                    Cambiar torneo &rarr;
                </a>
            </div>
            <select id="sel-torneo" class="<?= $campo ?> cursor-not-allowed opacity-90 font-medium" disabled></select>
        </div>
        <div>
            <label for="sel-equipo" class="<?= $label ?>">Equipo</label>
            <select id="sel-equipo" class="<?= $campo ?>"></select>
        </div>
    </div>

    <p id="aviso" class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
        Elige un torneo y un equipo para ver su plantel.
    </p>

    <section id="tabla" class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <h2 class="text-sm font-semibold text-slate-700">Jugadores</h2>
            <span id="contador" class="text-xs text-slate-500"></span>
        </div>
        <ul id="cuerpo" class="divide-y divide-slate-100"></ul>
        <p id="sin-jugadores" class="hidden p-8 text-center text-sm text-slate-500">Este equipo todavía no tiene jugadores en el torneo.</p>
    </section>
</main>

<!-- Modal -->
<div id="modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 p-4" role="dialog" aria-modal="true" aria-labelledby="modal-titulo">
    <div class="max-h-full w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-xl">
        <form id="form" novalidate>
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <h2 id="modal-titulo" class="text-lg font-bold text-slate-900">Agregar jugador</h2>
                <button type="button" data-cerrar class="text-2xl leading-none text-slate-400 hover:text-slate-600" aria-label="Cerrar">&times;</button>
            </div>

            <div class="space-y-5 px-6 py-5">
                <div id="alertas" class="hidden rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>

                <div id="bloque-agregar">
                    <label for="buscar" class="<?= $label ?>">Buscar jugador</label>
                    <input type="search" id="buscar" autocomplete="off" placeholder="Apellido o nombre" class="<?= $campo ?>">
                    <ul id="resultados" class="mt-2 max-h-56 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200"></ul>
                    <p id="elegido" class="mt-2 text-sm font-semibold text-emerald-700"></p>
                </div>

                <div id="bloque-editar" class="hidden">
                    <span class="<?= $label ?>">Jugador</span>
                    <p id="nombre-editar" class="text-sm font-semibold text-slate-900"></p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="f-camiseta" class="<?= $label ?>">Camiseta</label>
                        <input type="number" min="1" max="99" id="f-camiseta" class="<?= $campo ?>">
                    </div>
                    <div>
                        <label for="f-posicion" class="<?= $label ?>">Posición</label>
                        <select id="f-posicion" class="<?= $campo ?>">
                            <option value="">Sin definir</option>
                            <option value="PT">Portero</option>
                            <option value="DEF">Defensa</option>
                            <option value="MED">Medio</option>
                            <option value="DEL">Delantero</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
                <button type="button" data-cerrar class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</button>
                <button type="submit" id="btn-guardar" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">Guardar</button>
            </div>
        </form>
    </div>
</div>

<div id="toast" class="pointer-events-none fixed bottom-4 right-4 z-50 hidden rounded-lg bg-slate-900 px-4 py-2 text-sm text-white shadow-lg"></div>

<script src="/js/plantel.js"></script>