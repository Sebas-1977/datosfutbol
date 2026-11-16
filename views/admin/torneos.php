<?php
$campo = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 disabled:bg-slate-100 disabled:text-slate-500';
$label = 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500';
$activo = 'torneos';
require __DIR__ . '/_menu.php';

$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP;

// Fuente única: las reglas viven en los modelos
$clasifican = [];
foreach (\Model\Torneo::TAMANOS_GRUPO as $tamanos) {
    foreach ($tamanos as $t) {
        $clasifican[$t] = \Model\Torneo::clasificanPermitidos($t);
    }
}
?>
<main id="contenido" tabindex="-1" class="mx-auto max-w-6xl px-4 py-8">

    <div id="vista-listado" class="<?= !empty($idTorneo) ? 'hidden' : '' ?>">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Torneos</h1>
            <p class="text-sm text-slate-500">Ligas y copas. Las fases y los grupos se generan solos según la configuración.</p>
        </div>
        <button id="btn-nuevo" type="button"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
            + Nuevo torneo
        </button>
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white" aria-labelledby="titulo-lista">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <h2 id="titulo-lista" class="text-sm font-semibold text-slate-700">Listado</h2>
            <span id="contador" role="status" class="text-xs text-slate-500"></span>
        </div>
        <ul id="lista" class="grid gap-5 bg-slate-50 p-5 empty:hidden sm:grid-cols-2"></ul>
        <p id="vacio" class="hidden p-8 text-center text-sm text-slate-500">Todavía no hay torneos.</p>
    </section>
    </div>

    <section id="vista-torneo" class="<?= !empty($idTorneo) ? '' : 'hidden' ?>" aria-labelledby="torneo-vista-titulo">
        <a href="/admin/torneos"
           class="mb-4 inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            &larr; Todos los torneos
        </a>
        <header class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-700">Espacio del torneo</p>
                    <h1 id="torneo-vista-titulo" class="mt-1 text-3xl font-black text-slate-900">Cargando torneo…</h1>
                    <p id="torneo-vista-descripcion" class="mt-2 text-sm text-slate-500"></p>
                </div>
                <span id="torneo-vista-visibilidad" class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide"></span>
            </div>
            <p id="torneo-vista-error" role="alert" class="mt-4 hidden rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700"></p>
        </header>

        <div id="torneo-vista-contenido" class="mt-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <article class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Equipos</p>
                    <p id="torneo-vista-equipos" class="mt-2 text-2xl font-bold text-slate-900"></p>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div id="torneo-vista-progreso" class="h-full rounded-full bg-emerald-600 transition-all"></div>
                    </div>
                </article>
                <article class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Partidos</p>
                    <p id="torneo-vista-partidos" class="mt-2 text-2xl font-bold text-slate-900"></p>
                </article>
                <article class="rounded-xl border border-slate-200 bg-white p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fases</p>
                    <p id="torneo-vista-fases-total" class="mt-2 text-2xl font-bold text-slate-900"></p>
                </article>
            </div>

            <section class="mt-6" aria-labelledby="torneo-vista-secciones">
                <h2 id="torneo-vista-secciones" class="mb-3 text-lg font-bold text-slate-900">Administrar el torneo</h2>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <a id="torneo-vista-link-partidos" href="#" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 transition hover:border-emerald-400 hover:bg-emerald-100">
                        <span class="text-2xl" aria-hidden="true">⚽</span>
                        <span class="mt-2 block font-bold text-slate-900">Partidos</span>
                        <span class="mt-1 block text-sm text-slate-600">Fixture, fechas y resultados</span>
                    </a>
                    <a id="torneo-vista-link-plantel" href="#" class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-emerald-300 hover:bg-slate-50">
                        <span class="text-2xl" aria-hidden="true">👥</span>
                        <span class="mt-2 block font-bold text-slate-900">Planteles</span>
                        <span class="mt-1 block text-sm text-slate-600">Jugadores por equipo</span>
                    </a>
                    <a id="torneo-vista-link-destacados" href="#" class="rounded-xl border border-slate-200 bg-white p-4 transition hover:border-emerald-300 hover:bg-slate-50">
                        <span class="text-2xl" aria-hidden="true">⭐</span>
                        <span class="mt-2 block font-bold text-slate-900">Destacados</span>
                        <span class="mt-1 block text-sm text-slate-600">Contenido de portada</span>
                    </a>
                    <button id="torneo-vista-gestionar" type="button" class="rounded-xl border border-slate-200 bg-white p-4 text-left transition hover:border-emerald-300 hover:bg-slate-50">
                        <span class="text-2xl" aria-hidden="true">⚙️</span>
                        <span class="mt-2 block font-bold text-slate-900">Equipos y fases</span>
                        <span class="mt-1 block text-sm text-slate-600">Inscripción, grupos y configuración</span>
                    </button>
                </div>
            </section>

            <section class="mt-6 rounded-xl border border-slate-200 bg-white p-5" aria-labelledby="torneo-vista-fases">
                <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="torneo-vista-fases" class="text-lg font-bold text-slate-900">Fases y fixture</h2>
                    <div class="text-right">
                        <button id="torneo-vista-armar" type="button" disabled
                            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50">
                            Administrar fixture
                        </button>
                        <p id="torneo-vista-armar-ayuda" class="mt-1 text-xs text-slate-500"></p>
                    </div>
                </div>
                <ul id="torneo-vista-lista-fases" class="divide-y divide-slate-100"></ul>
            </section>
        </div>
    </section>
</main>

<!-- Crear / editar torneo -->
<dialog id="dlg-form" aria-labelledby="form-titulo"
    class="m-auto max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-0 shadow-xl backdrop:bg-slate-900/60">

    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
        <h2 id="form-titulo" class="text-lg font-bold text-slate-900">Nuevo torneo</h2>
        <button type="button" data-cerrar aria-label="Cerrar"
            class="text-2xl leading-none text-slate-400 hover:text-slate-600">&times;</button>
    </div>

    <form id="form" novalidate>
        <div class="space-y-4 px-6 py-5">
            <div id="alertas" role="alert"></div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="f-nombre" class="<?= $label ?>">Nombre</label>
                    <input type="text" id="f-nombre" name="nombre" required maxlength="100" autocomplete="off" class="<?= $campo ?>">
                </div>
                <div>
                    <label for="f-formato" class="<?= $label ?>">Formato</label>
                    <select id="f-formato" name="formato" required class="<?= $campo ?>">
                        <?php foreach (\Model\Torneo::FORMATOS as $valor => $texto): ?>
                            <option value="<?= htmlspecialchars($valor) ?>"><?= htmlspecialchars($texto) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="f-cantidad" class="<?= $label ?>">Equipos</label>
                    <select id="f-cantidad" class="<?= $campo ?>"></select>
                </div>
                <div id="f-grupos-bloque" hidden>
                    <label for="f-tamano" class="<?= $label ?>">Grupos</label>
                    <select id="f-tamano" class="<?= $campo ?>"></select>
                </div>
                <div id="f-clasifican-bloque" hidden>
                    <label for="f-clasifican" class="<?= $label ?>">Clasifican</label>
                    <select id="f-clasifican" class="<?= $campo ?>"></select>
                </div>
            </div>

            <p id="config-ayuda" class="text-xs text-slate-500"></p>
            <div class="flex items-center gap-3 rounded-lg bg-slate-50 px-3 py-2">
                <span id="config-icono" class="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-slate-200 text-slate-700" aria-hidden="true"></span>
                <p id="config-resumen" class="text-sm text-slate-700"></p>
            </div>
            <p id="config-aviso" role="status" class="text-sm text-amber-700"></p>

            <div class="flex justify-end">
                <button type="submit" id="btn-guardar"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">Guardar torneo</button>
            </div>

            <p id="fases-nota" class="text-xs text-slate-500">
                Al guardar se generan las fases y los grupos. La configuración inicial no se puede cambiar después; si todavía no hay partidos, podés eliminar el torneo y crearlo de nuevo. El nombre, las fases, el fixture y los equipos se gestionan por separado.
            </p>
        </div>
    </form>

    <section id="fases-bloque" class="hidden border-t border-slate-200 px-6 py-5" aria-labelledby="fases-titulo">
        <div class="mb-3">
            <h3 id="fases-titulo" class="text-sm font-bold text-slate-900">Fases y Fixture</h3>
            <p class="text-xs text-slate-500">Configurá las ruedas de cada fase y generá o reseteá el fixture de partidos.</p>
        </div>

        <p id="fases-estado" role="status" class="mb-2 text-sm text-emerald-700"></p>

        <ul id="fases-lista" class="divide-y divide-slate-100 rounded-lg border border-slate-200 bg-white"></ul>
        <p id="fases-vacio" class="hidden rounded-lg border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500">No hay fases creadas.</p>

        <!-- Editor de fase -->
        <div id="fase-editor" hidden
            class="mt-4 space-y-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
            <h4 id="fase-editor-titulo" class="text-sm font-semibold text-slate-800">Editar fase</h4>
            <div id="fase-alertas" role="alert"></div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="fase-nombre" class="<?= $label ?>">Nombre</label>
                    <input type="text" id="fase-nombre" maxlength="100" autocomplete="off" class="<?= $campo ?>">
                </div>
                <div>
                    <label for="fase-ida-vuelta" class="<?= $label ?>">Ruedas</label>
                    <select id="fase-ida-vuelta" class="<?= $campo ?>">
                        <option value="0">Solo ida</option>
                        <option value="1">Ida y vuelta</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" id="btn-fase-cancelar"
                    class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-white">Cancelar</button>
                <button type="button" id="btn-fase-guardar"
                    class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">Guardar fase</button>
            </div>
        </div>
    </section>

    <section id="equipos-bloque" class="hidden border-t border-slate-200 px-6 py-5" aria-labelledby="equipos-titulo">
        <div class="mb-3">
            <h3 id="equipos-titulo" class="text-sm font-bold text-slate-900">Equipos</h3>
            <p id="eq-ayuda" class="text-xs text-slate-500">Tildá los equipos que juegan el torneo, hasta completar el cupo. En las copas con grupos, elegí el grupo de cada uno. Los cambios se guardan al instante.</p>
        </div>

        <p id="eq-resumen" class="text-sm font-semibold text-slate-700"></p>
        <p id="eq-grupos-resumen" class="mb-2 text-xs text-slate-500"></p>
        <p id="eq-estado" role="status" class="mb-2 text-sm text-emerald-700"></p>
        <div id="eq-alertas" role="alert" class="mb-2"></div>

        <ul id="eq-lista" class="max-h-80 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200"></ul>
        <p id="eq-vacio" class="hidden rounded-lg border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500">Todavía no hay equipos cargados. Creálos en la pantalla Equipos.</p>
    </section>

    <div class="flex justify-end border-t border-slate-200 px-6 py-4">
        <button type="button" data-cerrar
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cerrar</button>
    </div>
</dialog>

<!-- Diálogo de Gestión de Fixture -->
<dialog id="dlg-fixture" aria-labelledby="fix-titulo"
    class="m-auto max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-0 shadow-xl backdrop:bg-slate-900/60">
    <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
        <div>
            <h2 id="fix-titulo" class="text-lg font-bold text-slate-900">Armar Fixture</h2>
            <p id="fix-subtitulo" class="text-xs text-slate-500"></p>
        </div>
        <button type="button" data-cerrar-fix aria-label="Cerrar"
            class="text-2xl leading-none text-slate-400 hover:text-slate-600">&times;</button>
    </div>

    <div class="space-y-4 px-6 py-5">
        <div id="fix-alertas" role="alert"></div>

        <!-- Vista cuando ya existen partidos cargados -->
        <div id="fix-cargado-bloque" hidden class="space-y-4">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <div class="flex items-start gap-3">
                    <span class="text-xl leading-none" aria-hidden="true">⚠️</span>
                    <div class="space-y-1">
                        <p class="font-bold text-amber-900" id="fix-cargado-info"></p>
                        <p class="text-xs text-amber-800">Para regenerar el fixture, primero debés borrar los partidos existentes. Esta acción solo se permite si los partidos están programados y ninguno ha comenzado.</p>
                    </div>
                </div>
            </div>

            <!-- Botonera: Enlace a partidos a la izquierda, botón de eliminación apartado a la derecha -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                <div>
                    <a id="fix-ver-partidos" href="/admin/partidos"
                       class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        <span>Ver partidos de esta fase &rarr;</span>
                    </a>
                </div>

                <!-- Botón eliminar apartado a la derecha para evitar clics accidentales -->
                <div class="ml-auto">
                    <button type="button" id="btn-fix-iniciar-borrado"
                        class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700 hover:bg-rose-100 hover:border-rose-300 transition">
                        🗑️ Eliminar fixture de esta fase
                    </button>
                </div>
            </div>

            <!-- Panel de confirmación de seguridad con palabra clave (oculto por defecto) -->
            <div id="fix-confirmar-bloque" hidden class="rounded-xl border border-rose-300 bg-rose-50/80 p-4 space-y-3">
                <div class="flex items-start gap-2">
                    <span class="text-rose-600 font-bold" aria-hidden="true">⚠️ Seguridad:</span>
                    <p class="text-xs text-rose-900">
                        Esta acción borrará definitivamente todos los partidos de esta fase. Para confirmar, escribí la palabra <strong class="font-bold tracking-wider text-rose-800">ELIMINAR</strong> a continuación:
                    </p>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <input type="text" id="fix-confirmar-input" autocomplete="off"
                        placeholder="Escribí ELIMINAR"
                        class="rounded-lg border border-rose-300 bg-white px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-rose-900 placeholder:normal-case placeholder:font-normal placeholder:tracking-normal placeholder:text-slate-400 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-500/30 sm:w-48">
                    <button type="button" id="btn-fix-cancelar-borrado"
                        class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Cancelar
                    </button>
                    <button type="button" id="btn-fix-borrar" disabled
                        class="rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700 disabled:opacity-40 disabled:cursor-not-allowed transition">
                        Confirmar eliminación
                    </button>
                </div>
            </div>
        </div>

        <!-- Vista para crear fixture -->
        <div id="fix-crear-bloque" class="space-y-4">
            <!-- Pestañas de modo -->
            <div class="flex border-b border-slate-200 text-sm font-semibold">
                <button type="button" id="tab-auto" class="border-b-2 border-emerald-600 px-4 py-2 text-emerald-800">⚡ 1. Automático / Por PC</button>
                <button type="button" id="tab-manual" class="border-b-2 border-transparent px-4 py-2 text-slate-500 hover:text-slate-800">✍️ 2. Cruces Manuales por Usuario</button>
            </div>

            <!-- Panel 1: Automático / Orden de Equipos -->
            <div id="panel-auto" class="space-y-3">
                <p id="fix-auto-desc" class="text-xs text-slate-600"></p>
                
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-2">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-800">
                        <input type="checkbox" id="fix-sortear" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        Sorteo aleatorio por PC (mezclar al azar)
                    </label>
                    <p class="text-xs text-slate-500">Si desmarcás esta casilla, podés ordenar los equipos a mano (Equipo 1, Equipo 2...) usando las flechas de abajo para que el sistema calcule los cruces en base a esa numeración.</p>
                </div>

                <div id="fix-orden-bloque" class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Orden de equipos</p>
                    <ul id="fix-orden-lista" class="max-h-56 divide-y divide-slate-100 overflow-y-auto rounded-lg border border-slate-200 bg-white text-xs"></ul>
                </div>
            </div>

            <!-- Panel 2: Cruces Manuales Libres -->
            <div id="panel-manual" hidden class="space-y-3">
                <p class="text-xs text-slate-600">Tú defines los cruces fecha por fecha seleccionando el local y el visitante. El sistema verifica que ningún equipo juegue más de una vez en la misma fecha ni se repitan cruces.</p>
                <div class="flex items-center justify-between">
                    <span id="fix-man-resumen" class="text-xs font-semibold text-slate-700"></span>
                    <button type="button" id="btn-fix-agregar-fecha" class="rounded border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">+ Agregar fecha</button>
                </div>
                <div id="fix-man-fechas" class="max-h-72 space-y-4 overflow-y-auto pr-1"></div>
            </div>

            <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
                <button type="button" data-cerrar-fix class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</button>
                <button type="button" id="btn-fix-generar" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">Generar fixture</button>
            </div>
        </div>
    </div>
</dialog>

<!-- Confirmar borrado de torneo -->
<dialog id="dlg-borrar" aria-labelledby="borrar-titulo" aria-describedby="borrar-texto"
    class="m-auto w-full max-w-md rounded-2xl bg-white p-0 shadow-xl backdrop:bg-slate-900/60">
    <div class="space-y-3 px-6 py-5">
        <h2 id="borrar-titulo" class="text-lg font-bold text-slate-900">Eliminar torneo</h2>
        <p id="borrar-texto" class="text-sm text-slate-600"></p>
        <div id="borrar-error" role="alert"></div>
    </div>
    <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
        <button type="button" data-cerrar
            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</button>
        <button type="button" id="btn-borrar"
            class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700 disabled:opacity-60">Eliminar</button>
    </div>
</dialog>

<div id="toast" role="status"
    class="pointer-events-none fixed bottom-4 right-4 z-50 rounded-lg bg-slate-900 px-4 py-2 text-sm text-white opacity-0 shadow-lg transition-opacity"></div>

<script src="/js/admin-crud.js"></script>
<script nonce="<?= CSP_NONCE ?>">
(() => {
  const $ = (id) => document.getElementById(id);
  const { esc, api } = AdminCrud;

  // Fuente única: las constantes de los modelos
  const TIPOS = <?= json_encode(\Model\Fase::TIPOS, $jsonFlags) ?>;
  const TIPOS_TORNEO = <?= json_encode(\Model\Torneo::TIPOS, $jsonFlags) ?>;
  const EQUIPOS = {
    liga: <?= json_encode(\Model\Torneo::LIGA_EQUIPOS) ?>,
    copa: <?= json_encode(\Model\Torneo::COPA_EQUIPOS) ?>,
  };
  const TAMANOS = <?= json_encode(\Model\Torneo::TAMANOS_GRUPO) ?>;
  const CLASIFICAN = <?= json_encode($clasifican) ?>;
  const RONDAS = <?= json_encode(\Model\Torneo::RONDAS, $jsonFlags | JSON_FORCE_OBJECT) ?>;
  const TORNEO_SELECCIONADO = <?= (int) ($idTorneo ?? 0) ?>;

  const ICONOS = {
    liga: '<path d="M3 9h18M3 14h18M9 9v11"/><rect x="3" y="4" width="18" height="16" rx="2"/>',
    grupos_playoff: '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/>'
      + '<path d="M10 6.5h3M10 17.5h3M13 6.5v11M13 12h8"/>',
    playoff: '<path d="M3 4h4M3 8h4M7 4v4M7 6h5M3 16h4M3 20h4M7 16v4M7 18h5M12 6v12M12 12h9"/>',
  };

  const svgTipo = (tipo) =>
    '<svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" '
    + 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
    + (ICONOS[tipo] ?? '') + '</svg>';

  const marcadorTipo = (tipo) =>
    '<span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-800" aria-hidden="true">'
    + svgTipo(tipo) + '</span>';

  const marcadorEquipo = (e) => {
    const ini = esc(String(e.nombre ?? '').trim().split(/\s+/).slice(0, 2).map((p) => p[0] ?? '').join('').toUpperCase());
    const fondo = /^#[0-9a-f]{6}$/i.test(e.color_primario ?? '') ? e.color_primario : '#f1f5f9';
    const [rr, gg, bb] = [1, 3, 5].map((i) => parseInt(fondo.slice(i, i + 2), 16));
    const texto = (rr * 299 + gg * 587 + bb * 114) / 1000 > 150 ? '#0f172a' : '#ffffff';
    return `<span class="flex h-6 w-6 shrink-0 items-center justify-center rounded border border-slate-200 text-[10px] font-bold leading-none" `
      + `style="background:${fondo};color:${texto}" aria-hidden="true">${ini}</span>`;
  };

  const tipoDe = (formato, tamano) =>
    formato === 'liga' ? 'liga' : (tamano === '' || tamano === null ? 'playoff' : 'grupos_playoff');

  let crud = null;
  let abierto = null;
  let torneoPendienteEliminar = null;
  let faseEditando = null;
  let eqDatos = null;
  let eqTurno = 0;

  // Variables de Fixture
  let faseFixture = null;
  let torneoFixture = null;
  let fixEquiposInscriptos = [];
  let fixModo = 'auto';

  const f = {
    formato: $('f-formato'), cantidad: $('f-cantidad'),
    gruposBloque: $('f-grupos-bloque'), tamano: $('f-tamano'),
    clasificanBloque: $('f-clasifican-bloque'), clasifican: $('f-clasifican'),
    ayuda: $('config-ayuda'), resumen: $('config-resumen'), aviso: $('config-aviso'),
    icono: $('config-icono'),
  };

  const ui = {
    dlg: $('dlg-form'),
    titulo: $('form-titulo'),
    nota: $('fases-nota'), bloque: $('fases-bloque'), estado: $('fases-estado'),
    lista: $('fases-lista'), vacio: $('fases-vacio'),
    editor: $('fase-editor'), alertas: $('fase-alertas'),
    nombre: $('fase-nombre'), idaVuelta: $('fase-ida-vuelta'),
    cancelar: $('btn-fase-cancelar'), guardar: $('btn-fase-guardar'),
  };

  const hub = {
    listado: $('vista-listado'), vista: $('vista-torneo'), titulo: $('torneo-vista-titulo'),
    descripcion: $('torneo-vista-descripcion'), visibilidad: $('torneo-vista-visibilidad'),
    error: $('torneo-vista-error'), contenido: $('torneo-vista-contenido'),
    equipos: $('torneo-vista-equipos'), progreso: $('torneo-vista-progreso'),
    partidos: $('torneo-vista-partidos'), totalFases: $('torneo-vista-fases-total'),
    linkPartidos: $('torneo-vista-link-partidos'), linkPlantel: $('torneo-vista-link-plantel'),
    linkDestacados: $('torneo-vista-link-destacados'), gestionar: $('torneo-vista-gestionar'),
    armar: $('torneo-vista-armar'), armarAyuda: $('torneo-vista-armar-ayuda'),
    fases: $('torneo-vista-lista-fases'),
  };

  const eq = {
    bloque: $('equipos-bloque'), resumen: $('eq-resumen'), grupos: $('eq-grupos-resumen'),
    estado: $('eq-estado'), alertas: $('eq-alertas'), lista: $('eq-lista'), vacio: $('eq-vacio'),
    ayuda: $('eq-ayuda'),
  };

  const fix = {
    dlg: $('dlg-fixture'),
    subtitulo: $('fix-subtitulo'),
    alertas: $('fix-alertas'),
    cargadoBloque: $('fix-cargado-bloque'),
    cargadoInfo: $('fix-cargado-info'),
    btnIniciarBorrado: $('btn-fix-iniciar-borrado'),
    confirmarBloque: $('fix-confirmar-bloque'),
    confirmarInput: $('fix-confirmar-input'),
    btnCancelarBorrado: $('btn-fix-cancelar-borrado'),
    btnBorrar: $('btn-fix-borrar'),
    crearBloque: $('fix-crear-bloque'),
    tabAuto: $('tab-auto'),
    tabManual: $('tab-manual'),
    panelAuto: $('panel-auto'),
    panelManual: $('panel-manual'),
    autoDesc: $('fix-auto-desc'),
    sortear: $('fix-sortear'),
    ordenBloque: $('fix-orden-bloque'),
    ordenLista: $('fix-orden-lista'),
    manFechas: $('fix-man-fechas'),
    manResumen: $('fix-man-resumen'),
    btnAgregarFecha: $('btn-fix-agregar-fecha'),
    btnGenerar: $('btn-fix-generar'),
  };

  const ruedas = (v) => (Number(v) === 1 ? 'ida y vuelta' : 'solo ida');
  const plural = (n, uno, varios) => `${n} ${n === 1 ? uno : varios}`;

  const cajaError = (mensajes) =>
    '<div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">' +
    '<ul class="list-disc space-y-1 pl-5">' + mensajes.map((m) => `<li>${esc(m)}</li>`).join('') + '</ul></div>';

  const mensajesDe = (r, defecto) =>
    r.alertas ? Object.values(r.alertas).flat() : [r.message ?? defecto];

  const opciones = (valores, texto) =>
    valores.map((v) => `<option value="${v}">${esc(texto(v))}</option>`).join('');

  const rondas = (equipos) => {
    const nombres = [];
    for (let n = equipos; n >= 2; n = Math.floor(n / 2)) {
      if (!RONDAS[n]) return [];
      nombres.push(RONDAS[n]);
    }
    return nombres;
  };

  function faseInicial(torneo) {
    const fases = torneo.fases ?? [];
    return fases.find((fase) => fase.tipo === 'grupos')
      || fases.find((fase) => fase.tipo === 'liga')
      || fases.find((fase) => fase.tipo === 'eliminatoria' && fase.orden === 1)
      || fases[0]
      || null;
  }

  function renderHub(torneo) {
    const cupo = Number(torneo.cantidad_equipos);
    const inscriptos = Number(torneo.total_equipos);
    const porcentaje = cupo > 0 ? Math.min(100, Math.round((inscriptos / cupo) * 100)) : 0;
    const fases = torneo.fases ?? [];

    hub.titulo.textContent = torneo.nombre;
    hub.descripcion.textContent = [
      TIPOS_TORNEO[torneo.tipo] ?? torneo.tipo,
      `${cupo} equipos`,
      Number(torneo.ida_vuelta) === 1 ? 'Ida y vuelta' : 'Solo ida',
    ].join(' · ');
    hub.visibilidad.textContent = Number(torneo.publico) === 1 ? '🌐 Público' : '🔒 Privado';
    hub.visibilidad.className = Number(torneo.publico) === 1
      ? 'inline-flex items-center rounded-full border border-emerald-300 bg-emerald-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700'
      : 'inline-flex items-center rounded-full border border-slate-300 bg-slate-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-slate-600';
    hub.equipos.textContent = `${inscriptos} de ${cupo}`;
    hub.progreso.style.width = `${porcentaje}%`;
    hub.partidos.textContent = plural(Number(torneo.total_partidos), 'partido', 'partidos');
    hub.totalFases.textContent = plural(fases.length, 'fase', 'fases');
    hub.armar.disabled = fases.length <= 1;
    hub.armarAyuda.textContent = fases.length === 1
      ? 'Usá el botón «Fixture» de la fase.'
      : fases.length > 1
        ? 'Abre la fase inicial del torneo.'
        : 'El torneo no tiene fases para administrar.';
    hub.linkPartidos.href = `/admin/partidos?torneo=${torneo.id}`;
    hub.linkPlantel.href = `/admin/plantel?id_torneo=${torneo.id}`;
    hub.linkDestacados.href = `/admin/destacados?torneo=${torneo.id}`;

    hub.fases.innerHTML = fases.map((fase) => {
      const partidos = Number(fase.total_partidos ?? 0);
      const disponible = fase.tipo === 'liga' || fase.tipo === 'grupos'
        || (fase.tipo === 'eliminatoria' && fase.orden === 1) || partidos > 0;
      const accion = disponible
        ? `<button type="button" data-dashboard-fase="${fase.id}" class="inline-flex min-h-9 items-center rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-sm font-semibold text-emerald-800 hover:bg-emerald-100">Fixture${partidos > 0 ? ` (${partidos})` : ''}</button>`
        : '<span class="text-xs text-slate-400">Se habilita al avanzar el torneo</span>';

      return `<li class="flex flex-wrap items-center justify-between gap-3 py-3">
        <div class="min-w-0">
          <p class="font-semibold text-slate-900">${esc(fase.nombre)}</p>
          <p class="text-xs text-slate-500">${esc(TIPOS[fase.tipo] ?? fase.tipo)} · ${ruedas(fase.ida_vuelta)}</p>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-sm text-slate-600">${plural(partidos, 'partido', 'partidos')}</span>
          ${accion}
        </div>
      </li>`;
    }).join('');
  }

  function tarjeta(r) {
    const nombre = esc(r.nombre);
    const cupo = r.cantidad_equipos;
    const inscriptos = r.total_equipos;
    const pct = cupo > 0 ? Math.min(100, Math.round((inscriptos / cupo) * 100)) : 0;
    const totalPartidos = Number(r.total_partidos ?? 0);

    const datos = [plural(cupo, 'equipo', 'equipos')];
    if (r.tamano_grupo) {
      datos.push(`${plural(cupo / r.tamano_grupo, 'grupo', 'grupos')} de ${r.tamano_grupo}`
        + (r.clasifican ? ` · pasan ${r.clasifican}` : ''));
    }
    datos.push(Number(r.ida_vuelta) === 1 ? 'Ida y vuelta' : 'Solo ida');
    datos.push(plural(totalPartidos, 'partido', 'partidos'));

    // Botón de Fixture en la Card para TODOS los formatos
    let btnCardFixture = '';
    if (totalPartidos > 0) {
      btnCardFixture = `<button type="button" data-card-fixture="${r.id}" class="inline-flex min-h-10 items-center rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-100">📅 Fixture (${totalPartidos})</button>`;
    } else if (inscriptos >= cupo) {
      btnCardFixture = `<button type="button" data-card-fixture="${r.id}" class="inline-flex min-h-10 items-center rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">📅 Armar Fixture</button>`;
    } else {
      btnCardFixture = `<button type="button" data-card-fixture="${r.id}" class="inline-flex min-h-10 items-center rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100" title="Inscribí los ${cupo} equipos para generar">📅 Fixture</button>`;
    }

    return `
      <li id="torneo-${r.id}" tabindex="-1" class="flex min-h-full flex-col justify-between rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600" data-card-torneo-id="${r.id}">
        <div>
          <div class="flex items-start gap-3">
            ${marcadorTipo(r.tipo)}
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-start justify-between gap-2">
                <h3 class="min-w-0 flex-1 text-lg font-bold leading-tight text-slate-900"><a href="/admin/torneos?torneo=${r.id}" class="hover:text-emerald-700 transition" title="Entrar al espacio del torneo">${nombre}</a></h3>
                <button type="button" data-toggle-publico="${r.id}"
                  class="shrink-0 inline-flex min-h-8 items-center gap-1 rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-wide border transition
                    ${Number(r.publico) === 1
                      ? 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                      : 'border-slate-300 bg-slate-100 text-slate-500 hover:bg-slate-200'}"
                  title="${Number(r.publico) === 1 ? 'Torneo público: clic para hacer privado' : 'Torneo privado: clic para publicar'}">
                  ${Number(r.publico) === 1 ? '🌐 Público' : '🔒 Privado'}
                </button>
              </div>
              <p class="mt-1 text-sm font-semibold text-emerald-800">${esc(TIPOS_TORNEO[r.tipo] ?? r.tipo)}</p>
              <p class="mt-1 text-sm leading-relaxed text-slate-500">${esc(datos.join(' · '))}</p>
            </div>
          </div>

          <div class="mt-5">
            <div class="flex justify-between text-sm font-semibold">
              <span class="text-slate-600">Inscripción</span>
              <span class="${inscriptos === cupo ? 'text-emerald-700 font-bold' : 'text-slate-500'}">${inscriptos} de ${cupo}</span>
            </div>
            <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
              <div class="h-full rounded-full transition-all ${inscriptos === cupo ? 'bg-emerald-600' : 'bg-slate-400'}" style="width:${pct}%"></div>
            </div>
          </div>
        </div>

        <div class="mt-5 border-t border-slate-100 pt-4">
          <div class="flex flex-wrap gap-2">
            <a href="/admin/torneos?torneo=${r.id}" class="inline-flex min-h-10 items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700">
              🏟️ Abrir torneo
            </a>
            <a href="/admin/partidos?torneo=${r.id}" class="inline-flex min-h-10 items-center rounded-xl ${totalPartidos > 0 ? 'bg-emerald-600 text-white shadow-sm hover:bg-emerald-700' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50'} px-4 py-2 text-sm font-semibold transition">
              ⚽ Partidos ${totalPartidos > 0 ? `(${totalPartidos})` : ''}
            </a>
            <a href="/admin/plantel?id_torneo=${r.id}" class="inline-flex min-h-10 items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
              👥 Plantel
            </a>
            <a href="/admin/destacados?torneo=${r.id}" class="inline-flex min-h-10 items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">
              ⭐ Destacados
            </a>
            ${btnCardFixture}
          </div>
          <div class="mt-3 flex flex-wrap items-center justify-end gap-2">
            <button type="button" data-accion="editar" data-id="${r.id}" aria-label="Editar el nombre de ${nombre}"
              class="inline-flex min-h-10 items-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
              ✏️ Editar nombre
            </button>
            <button type="button" data-accion="eliminar" data-id="${r.id}" aria-label="Eliminar ${nombre}"
              class="inline-flex min-h-10 items-center rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">
              🗑️ Eliminar
            </button>
          </div>
        </div>
      </li>`;
  }

  function armar(pref = {}) {
    const formato = f.formato.value;
    const cantidades = EQUIPOS[formato] ?? [];
    const cantidadActual = 'cantidad' in pref ? pref.cantidad : Number(f.cantidad.value);
    f.cantidad.innerHTML = opciones(cantidades, (v) => `${v} equipos`);
    f.cantidad.value = String(cantidades.includes(cantidadActual) ? cantidadActual : cantidades[0]);

    const esCopa = formato === 'copa';
    f.gruposBloque.hidden = !esCopa;
    let tamano = '';

    if (esCopa) {
      const tamanos = TAMANOS[Number(f.cantidad.value)] ?? [];
      const quiereT = 'tamano' in pref
        ? pref.tamano
        : (f.tamano.value === '' ? null : Number(f.tamano.value));
      f.tamano.innerHTML = '<option value="">Sin grupos (playoff directo)</option>'
        + opciones(tamanos, (v) => `Grupos de ${v}`);
      tamano = tamanos.includes(quiereT) ? String(quiereT) : '';
      f.tamano.value = tamano;
    } else {
      f.tamano.innerHTML = '';
    }

    f.clasificanBloque.hidden = tamano === '';
    if (tamano !== '') {
      const lista = CLASIFICAN[tamano] ?? [];
      const quiereC = 'clasifican' in pref ? pref.clasifican : Number(f.clasifican.value);
      f.clasifican.innerHTML = opciones(lista, (v) => `${v} por grupo`);
      f.clasifican.value = String(lista.includes(quiereC) ? quiereC : (lista.includes(2) ? 2 : lista[0]));
    } else {
      f.clasifican.innerHTML = '';
    }

    actualizarResumen();
  }

  function actualizarResumen() {
    const cantidad = Number(f.cantidad.value);
    let texto;

    if (f.formato.value === 'liga') {
      texto = 'Fase regular';
    } else {
      const partes = [];
      let clasificados = cantidad;
      if (f.tamano.value !== '') {
        const t = Number(f.tamano.value);
        const c = Number(f.clasifican.value);
        const grupos = cantidad / t;
        clasificados = grupos * c;
        partes.push(`Fase de grupos (${plural(grupos, 'grupo', 'grupos')} de ${t}, pasan ${c} por grupo)`);
      }
      partes.push(...rondas(clasificados));
      texto = partes.join(' → ');
    }

    const tipo = tipoDe(f.formato.value, f.tamano.value);
    f.icono.innerHTML = svgTipo(tipo);
    f.resumen.textContent = `Tipo: ${TIPOS_TORNEO[tipo]}. Fases que se generan: ${texto}.`;

    const cambia = abierto && (
      f.formato.value !== abierto.formato
      || cantidad !== abierto.cantidad_equipos
      || (f.tamano.value === '' ? null : Number(f.tamano.value)) !== abierto.tamano_grupo
      || (f.clasifican.value === '' ? null : Number(f.clasifican.value)) !== abierto.clasifican
    );
    f.aviso.textContent = cambia
      ? 'Al guardar se regeneran las fases y los grupos: se pierden los nombres y las ruedas que hayas editado'
        + (abierto.total_equipos > 0 ? ', y los equipos inscriptos quedan sin grupo.' : '.')
      : '';
  }

  f.formato.addEventListener('change', () => armar());
  f.cantidad.addEventListener('change', () => armar());
  f.tamano.addEventListener('change', () => armar());
  f.clasifican.addEventListener('change', actualizarResumen);

  // ---------- Fases: lista ----------
  function renderFases() {
    if (!abierto) return;
    const fases = abierto.fases;

    ui.lista.hidden = fases.length === 0;
    ui.vacio.hidden = fases.length > 0;
    ui.lista.innerHTML = fases.map((x) => {
      const nombre = esc(x.nombre);
      const partes = [TIPOS[x.tipo] ?? x.tipo, ruedas(x.ida_vuelta), `orden ${x.orden}`];
      if (x.clasifican !== null) partes.push(`clasifican ${x.clasifican} por grupo`);
      
      const totalPartidos = Number(x.total_partidos ?? 0);
      const badgeFixture = (totalPartidos > 0 
        ? `<span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">${plural(totalPartidos, 'partido', 'partidos')}</span>`
        : '<span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-500">Sin fixture</span>');

      // Botón Fixture disponible para liga, grupos y la primera fase eliminatoria
      const habilitado = x.tipo === 'liga' || x.tipo === 'grupos' || (x.tipo === 'eliminatoria' && x.orden === 1);
      const btnFixture = habilitado || totalPartidos > 0
        ? `<button type="button" data-fase-accion="fixture" data-id="${x.id}" class="rounded-lg border border-emerald-300 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">📅 Fixture${totalPartidos > 0 ? ` (${totalPartidos})` : ''}</button>`
        : '';

      return `
        <li class="flex items-center justify-between gap-3 px-3 py-2.5">
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
              <p class="truncate text-sm font-semibold text-slate-900">${nombre}</p>
              ${badgeFixture}
            </div>
            <p class="truncate text-xs text-slate-500">${esc(partes.join(' · '))}</p>
          </div>
          <div class="flex items-center gap-1.5 shrink-0">
            ${btnFixture}
            <button type="button" data-fase-accion="editar" data-id="${x.id}" aria-label="Editar la fase ${nombre}"
              class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Editar</button>
          </div>
        </li>`;
    }).join('');
  }

  function abrirEditor(fase) {
    faseEditando = fase;
    ui.alertas.innerHTML = '';
    ui.estado.textContent = '';
    ui.nombre.value = fase.nombre;
    ui.idaVuelta.value = String(fase.ida_vuelta);
    ui.editor.hidden = false;
    ui.nombre.focus();
  }

  function cerrarEditor(devolverFoco = true) {
    const fase = faseEditando;
    ui.editor.hidden = true;
    ui.alertas.innerHTML = '';
    faseEditando = null;
    if (devolverFoco && fase) {
      ui.lista.querySelector(`button[data-id="${fase.id}"]`)?.focus();
    }
  }

  async function guardarFase() {
    if (!faseEditando) return;
    ui.alertas.innerHTML = '';

    const nombre = ui.nombre.value.trim();
    if (nombre === '') {
      ui.alertas.innerHTML = cajaError(['Completá el nombre de la fase.']);
      ui.nombre.focus();
      return;
    }

    const id = faseEditando.id;
    ui.guardar.disabled = true;
    const r = await api('/api/fases/guardar', {
      id,
      nombre,
      ida_vuelta: Number(ui.idaVuelta.value),
    });
    ui.guardar.disabled = false;

    if (!r.ok) {
      ui.alertas.innerHTML = cajaError(mensajesDe(r, 'No se pudo guardar la fase.'));
      return;
    }

    cerrarEditor(false);
    await crud.recargar();
    ui.estado.textContent = 'Fase actualizada.';
    ui.lista.querySelector(`button[data-id="${id}"]`)?.focus();
  }

  ui.cancelar.addEventListener('click', () => cerrarEditor());
  ui.guardar.addEventListener('click', guardarFase);
  ui.editor.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
      e.preventDefault();
      guardarFase();
    }
  });

  ui.lista.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-fase-accion]');
    if (!btn || !abierto) return;
    const accion = btn.dataset.faseAccion;
    const fase = abierto.fases.find((x) => x.id === Number(btn.dataset.id));
    if (!fase) return;

    if (accion === 'editar') {
      abrirEditor(fase);
    } else if (accion === 'fixture') {
      abrirModalFixture(fase, abierto);
    }
  });

  // Event listener en las Cards para el botón Fixture directo (Liga o Copa)
  document.getElementById('lista').addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-card-fixture]');
    if (!btn) return;
    const torneoId = Number(btn.dataset.cardFixture);
    const torneo = (crud.registros ?? []).find((t) => t.id === torneoId);
    if (!torneo) return;

    const faseTarget = faseInicial(torneo);

    if (!faseTarget) {
      alert('Este torneo no cuenta con fases configuradas para armar fixture.');
      return;
    }

    abrirModalFixture(faseTarget, torneo);
  });

  // ---------- GESTIÓN DE FIXTURE ----------
  async function abrirModalFixture(fase, torneoPadre) {
    faseFixture = fase;
    torneoFixture = torneoPadre;
    fix.alertas.innerHTML = '';
    fix.subtitulo.textContent = `${torneoPadre.nombre} · ${fase.nombre} (${ruedas(fase.ida_vuelta)})`;

    // Cargar equipos inscriptos
    const r = await api('/api/torneos/equipos?id_torneo=' + torneoPadre.id);
    if (!r.ok) {
      alert('No se pudieron obtener los equipos inscriptos.');
      return;
    }

    fixEquiposInscriptos = (r.data?.equipos ?? []).filter((e) => e.inscripto);
    const cupo = Number(torneoPadre.cantidad_equipos);
    const totalPartidos = Number(fase.total_partidos ?? 0);

    if (totalPartidos > 0) {
      fix.cargadoBloque.hidden = false;
      fix.crearBloque.hidden = true;
      fix.cargadoInfo.textContent = `Esta fase ya cuenta con ${plural(totalPartidos, 'partido cargado', 'partidos cargados')}.`;
      $('fix-ver-partidos').href = `/admin/partidos?torneo=${torneoPadre.id}&fase=${fase.id}`;
      // Resetear panel de seguridad
      if (fix.confirmarBloque) fix.confirmarBloque.hidden = true;
      if (fix.confirmarInput) fix.confirmarInput.value = '';
      if (fix.btnBorrar) fix.btnBorrar.disabled = true;
      if (fix.btnIniciarBorrado) fix.btnIniciarBorrado.hidden = false;
    } else {
      fix.cargadoBloque.hidden = true;
      fix.crearBloque.hidden = false;

      // Validar cupo
      if (fixEquiposInscriptos.length < cupo) {
        fix.alertas.innerHTML = cajaError([
          `El torneo tiene ${fixEquiposInscriptos.length} de ${cupo} equipos inscriptos. Completá el cupo en el torneo para poder generar el fixture.`
        ]);
        fix.btnGenerar.disabled = true;
      } else if (fase.tipo === 'grupos') {
        // En fase de grupos, validar que todos tengan grupo asignado
        const sinGrupo = fixEquiposInscriptos.filter((e) => !e.id_grupo);
        if (sinGrupo.length > 0) {
          fix.alertas.innerHTML = cajaError([
            `Hay ${plural(sinGrupo.length, 'equipo', 'equipos')} sin grupo asignado. Asigná los grupos en la sección Equipos del torneo.`
          ]);
          fix.btnGenerar.disabled = true;
        } else {
          fix.btnGenerar.disabled = false;
        }
      } else {
        fix.btnGenerar.disabled = false;
      }

      // Explicación contextual según el tipo de fase
      if (fase.tipo === 'grupos') {
        fix.autoDesc.textContent = 'Calcula automáticamente las fechas de todos contra todos dentro de cada grupo.';
      } else if (fase.tipo === 'eliminatoria') {
        fix.autoDesc.textContent = 'Cruces de eliminación directa para la primera ronda eliminatoria.';
      } else {
        fix.autoDesc.textContent = 'Calcula automáticamente todas las jornadas de todos contra todos (círculo de Berger).';
      }

      activarTab('auto');
      renderOrdenEquipos();
      iniciarCrucesManuales();
    }

    fix.dlg.showModal();
  }

  function cerrarModalFixture() {
    fix.dlg.close();
    faseFixture = null;
    torneoFixture = null;
  }

  document.querySelectorAll('[data-cerrar-fix]').forEach((btn) => {
    btn.addEventListener('click', cerrarModalFixture);
  });

  function activarTab(modo) {
    fixModo = modo;
    if (modo === 'auto') {
      fix.tabAuto.className = 'border-b-2 border-emerald-600 px-4 py-2 text-emerald-800';
      fix.tabManual.className = 'border-b-2 border-transparent px-4 py-2 text-slate-500 hover:text-slate-800';
      fix.panelAuto.hidden = false;
      fix.panelManual.hidden = true;
    } else {
      fix.tabAuto.className = 'border-b-2 border-transparent px-4 py-2 text-slate-500 hover:text-slate-800';
      fix.tabManual.className = 'border-b-2 border-emerald-600 px-4 py-2 text-emerald-800';
      fix.panelAuto.hidden = true;
      fix.panelManual.hidden = false;
    }
  }

  fix.tabAuto.addEventListener('click', () => activarTab('auto'));
  fix.tabManual.addEventListener('click', () => activarTab('manual'));

  // Panel 1: Orden de Equipos
  function renderOrdenEquipos() {
    fix.sortear.checked = true;
    fix.ordenBloque.hidden = true;
    fix.ordenLista.innerHTML = fixEquiposInscriptos.map((e, i) => `
      <li class="flex items-center justify-between gap-2 px-3 py-1.5" data-eq-id="${e.id}">
        <div class="flex items-center gap-2">
          <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700">${i + 1}</span>
          <span class="font-medium text-slate-800">${esc(e.nombre)}</span>
        </div>
        <div class="flex gap-1">
          <button type="button" data-orden-dir="-1" class="rounded px-1.5 py-0.5 text-xs text-slate-500 hover:bg-slate-200" ${i === 0 ? 'disabled' : ''}>▲</button>
          <button type="button" data-orden-dir="1" class="rounded px-1.5 py-0.5 text-xs text-slate-500 hover:bg-slate-200" ${i === fixEquiposInscriptos.length - 1 ? 'disabled' : ''}>▼</button>
        </div>
      </li>
    `).join('');
  }

  fix.sortear.addEventListener('change', () => {
    fix.ordenBloque.hidden = fix.sortear.checked;
  });

  fix.ordenLista.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-orden-dir]');
    if (!btn) return;
    const li = btn.closest('li');
    const id = Number(li.dataset.eqId);
    const dir = Number(btn.dataset.ordenDir);
    const idx = fixEquiposInscriptos.findIndex((x) => x.id === id);
    if (idx < 0) return;

    const targetIdx = idx + dir;
    if (targetIdx < 0 || targetIdx >= fixEquiposInscriptos.length) return;

    const tmp = fixEquiposInscriptos[idx];
    fixEquiposInscriptos[idx] = fixEquiposInscriptos[targetIdx];
    fixEquiposInscriptos[targetIdx] = tmp;

    renderOrdenEquipos();
    fix.sortear.checked = false;
    fix.ordenBloque.hidden = false;
  });

  // Panel 2: Cruces Manuales Fecha por Fecha
  let manFechasData = [];

  function iniciarCrucesManuales() {
    manFechasData = [];
    const n = fixEquiposInscriptos.length;
    const partidosPorFecha = Math.max(1, Math.floor(n / 2));
    agregarFechaManual(partidosPorFecha);
    renderFechasManuales();
  }

  function agregarFechaManual(partidosPorFecha = null) {
    const ppf = partidosPorFecha ?? Math.max(1, Math.floor(fixEquiposInscriptos.length / 2));
    const cruces = [];
    for (let i = 0; i < ppf; i++) {
      cruces.push({ local: '', visitante: '' });
    }
    manFechasData.push(cruces);
  }

  fix.btnAgregarFecha.addEventListener('click', () => {
    agregarFechaManual();
    renderFechasManuales();
  });

  function renderFechasManuales() {
    fix.manResumen.textContent = `Total fechas armadas: ${manFechasData.length}`;
    const optsEquipos = '<option value="">(Elegir)</option>' +
      fixEquiposInscriptos.map((e) => `<option value="${e.id}">${esc(e.nombre)}</option>`).join('');

    fix.manFechas.innerHTML = manFechasData.map((fecha, idxFecha) => `
      <div class="rounded-lg border border-slate-200 bg-slate-50 p-3" data-fecha-idx="${idxFecha}">
        <div class="mb-2 flex items-center justify-between">
          <span class="text-xs font-bold text-slate-800">Fecha ${idxFecha + 1}</span>
          ${manFechasData.length > 1 ? `<button type="button" data-quitar-fecha="${idxFecha}" class="text-[11px] text-rose-600 hover:underline">Quitar fecha</button>` : ''}
        </div>
        <div class="space-y-2">
          ${fecha.map((cruce, idxCruce) => `
            <div class="flex items-center gap-2 text-xs" data-cruce-idx="${idxCruce}">
              <select class="w-1/2 rounded border border-slate-300 bg-white p-1 text-slate-800" data-campo="local">
                ${optsEquipos}
              </select>
              <span class="font-bold text-slate-400">vs</span>
              <select class="w-1/2 rounded border border-slate-300 bg-white p-1 text-slate-800" data-campo="visitante">
                ${optsEquipos}
              </select>
            </div>
          `).join('')}
        </div>
      </div>
    `).join('');

    // Sincronizar selects con datos existentes
    manFechasData.forEach((fecha, idxFecha) => {
      const bloq = fix.manFechas.querySelector(`[data-fecha-idx="${idxFecha}"]`);
      if (!bloq) return;
      fecha.forEach((cruce, idxCruce) => {
        const row = bloq.querySelector(`[data-cruce-idx="${idxCruce}"]`);
        if (!row) return;
        row.querySelector('[data-campo="local"]').value = cruce.local;
        row.querySelector('[data-campo="visitante"]').value = cruce.visitante;
      });
    });
  }

  fix.manFechas.addEventListener('change', (e) => {
    const sel = e.target.closest('select[data-campo]');
    if (!sel) return;
    const fechaDiv = sel.closest('[data-fecha-idx]');
    const cruceDiv = sel.closest('[data-cruce-idx]');
    const fIdx = Number(fechaDiv.dataset.fechaIdx);
    const cIdx = Number(cruceDiv.dataset.cruceIdx);
    const campo = sel.dataset.campo;
    manFechasData[fIdx][cIdx][campo] = sel.value;
  });

  fix.manFechas.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-quitar-fecha]');
    if (!btn) return;
    const idx = Number(btn.dataset.quitarFecha);
    manFechasData.splice(idx, 1);
    renderFechasManuales();
  });

  // Generar Fixture (llamada API)
  fix.btnGenerar.addEventListener('click', async () => {
    if (!faseFixture) return;
    fix.alertas.innerHTML = '';

    const payload = { id_fase: faseFixture.id };

    if (fixModo === 'auto') {
      if (fix.sortear.checked) {
        payload.sortear = true;
      } else {
        payload.equipos_orden = fixEquiposInscriptos.map((e) => e.id);
      }
    } else {
      const manualPayload = [];
      for (let fIdx = 0; fIdx < manFechasData.length; fIdx++) {
        const fecha = manFechasData[fIdx];
        const partidos = [];
        for (let cIdx = 0; cIdx < fecha.length; cIdx++) {
          const c = fecha[cIdx];
          const loc = Number(c.local);
          const vis = Number(c.visitante);
          if (!loc || !vis) {
            fix.alertas.innerHTML = cajaError([`En la Fecha ${fIdx + 1} hay cruces incompletos.`]);
            return;
          }
          if (loc === vis) {
            fix.alertas.innerHTML = cajaError([`En la Fecha ${fIdx + 1} un equipo no puede jugar contra sí mismo.`]);
            return;
          }
          partidos.push([loc, vis]);
        }
        manualPayload.push(partidos);
      }
      payload.jornadas_manuales = manualPayload;
    }

    fix.btnGenerar.disabled = true;
    const r = await api('/api/fases/fixture', payload);
    fix.btnGenerar.disabled = false;

    if (!r.ok) {
      fix.alertas.innerHTML = cajaError(mensajesDe(r, 'No se pudo generar el fixture.'));
      return;
    }

    cerrarModalFixture();
    await crud.recargar();
    ui.estado.textContent = '¡Fixture generado con éxito!';
  });

  // Borrar Fixture con confirmación de seguridad
  if (fix.btnIniciarBorrado) {
    fix.btnIniciarBorrado.addEventListener('click', () => {
      fix.confirmarBloque.hidden = false;
      fix.btnIniciarBorrado.hidden = true;
      fix.confirmarInput.value = '';
      fix.btnBorrar.disabled = true;
      fix.confirmarInput.focus();
    });
  }

  if (fix.btnCancelarBorrado) {
    fix.btnCancelarBorrado.addEventListener('click', () => {
      fix.confirmarBloque.hidden = true;
      fix.btnIniciarBorrado.hidden = false;
      fix.confirmarInput.value = '';
      fix.btnBorrar.disabled = true;
    });
  }

  if (fix.confirmarInput) {
    fix.confirmarInput.addEventListener('input', () => {
      const val = fix.confirmarInput.value.trim().toUpperCase();
      fix.btnBorrar.disabled = (val !== 'ELIMINAR' && val !== 'BORRAR');
    });

    fix.confirmarInput.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        if (!fix.btnBorrar.disabled) {
          fix.btnBorrar.click();
        }
      }
    });
  }

  fix.btnBorrar.addEventListener('click', async () => {
    if (!faseFixture) return;
    const idTorneo = Number(torneoFixture?.id ?? 0);
    const val = (fix.confirmarInput ? fix.confirmarInput.value : '').trim().toUpperCase();
    if (val !== 'ELIMINAR' && val !== 'BORRAR') {
      alert('Para confirmar la eliminación debés escribir la palabra ELIMINAR.');
      return;
    }

    fix.btnBorrar.disabled = true;
    const r = await api('/api/fases/fixture/borrar', { id_fase: faseFixture.id });
    fix.btnBorrar.disabled = false;

    if (!r.ok) {
      fix.alertas.innerHTML = cajaError(mensajesDe(r, 'No se pudo eliminar el fixture.'));
      return;
    }

    cerrarModalFixture();
    await crud.recargar();
    if (torneoPendienteEliminar === idTorneo) {
      const actualizado = (crud.registros ?? []).find((t) => t.id === idTorneo);
      const restantes = Number(actualizado?.total_partidos ?? 0);
      ui.estado.className = 'mb-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900';
      ui.estado.textContent = restantes > 0
        ? `Fixture eliminado. Todavía quedan ${plural(restantes, 'partido', 'partidos')} en otras fases; eliminá también esos fixtures antes de borrar el torneo.`
        : 'Ya no quedan partidos. Cerrá este panel y presioná «Eliminar» nuevamente para borrar el torneo.';
    } else {
      ui.estado.textContent = 'Fixture eliminado.';
    }
  });

  // ---------- Equipos: inscripción y grupos ----------
  const claseSelectGrupo = 'rounded-lg border border-slate-300 bg-white px-2 py-1 text-xs text-slate-800 '
    + 'focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 '
    + 'disabled:bg-slate-100 disabled:text-slate-500';

  async function cargarEquipos(enfocar = null) {
    if (!abierto) return;
    const id = abierto.id;
    const mio = ++eqTurno;

    const r = await api('/api/torneos/equipos?id_torneo=' + id);
    if (mio !== eqTurno || !abierto || abierto.id !== id) return;

    if (!r.ok) {
      eq.alertas.innerHTML = cajaError(mensajesDe(r, 'No se pudieron cargar los equipos.'));
      return;
    }

    eqDatos = r.data;
    const cupo = eqDatos.cupo;
    const inscriptos = eqDatos.inscriptos;
    const completo = inscriptos >= cupo;

    eq.resumen.textContent = `${inscriptos} de ${cupo} equipos inscriptos`
      + (completo ? ' (cupo completo)' : ` (quedan ${cupo - inscriptos} lugares)`);
    eq.ayuda.textContent = completo
      ? 'Cupo completo: solo se muestran los equipos elegidos. Quitá uno para liberar un lugar y ver otras opciones.'
      : 'Tildá los equipos que juegan el torneo, hasta completar el cupo. En las copas con grupos, elegí el grupo de cada uno. Los cambios se guardan al instante.';

    const grupos = eqDatos.grupos;
    const tamano = eqDatos.tamano_grupo;
    if (grupos.length) {
      const conteo = {};
      grupos.forEach((g) => { conteo[g.id] = 0; });
      eqDatos.equipos.forEach((e) => {
        if (e.inscripto && e.id_grupo && conteo[e.id_grupo] !== undefined) {
          conteo[e.id_grupo]++;
        }
      });
      eq.grupos.textContent = grupos.map((g) => `${g.nombre}: ${conteo[g.id]} de ${tamano}`).join(' · ');
    } else {
      eq.grupos.textContent = '';
    }

    const equiposVisibles = completo
      ? eqDatos.equipos.filter((e) => e.inscripto)
      : eqDatos.equipos;
    eq.vacio.hidden = equiposVisibles.length > 0;
    eq.lista.innerHTML = equiposVisibles.map((e) => {
      const id = e.id;
      const inscripto = e.inscripto;
      const conPartidos = e.total_partidos > 0;
      const deshabilitarCheck = conPartidos || (!inscripto && completo);

      let selectGrupo = '';
      if (grupos.length) {
        const opts = ['<option value="">(Sin grupo)</option>']
          .concat(grupos.map((g) => `<option value="${g.id}" ${e.id_grupo === g.id ? 'selected' : ''}>${esc(g.nombre)}</option>`));
        selectGrupo = `
          <select data-eq-grupo="${id}" class="${claseSelectGrupo}" ${(!inscripto || conPartidos) ? 'disabled' : ''}
            aria-label="Grupo de ${esc(e.nombre)}">
            ${opts.join('')}
          </select>`;
      }

      return `
        <li class="flex items-center justify-between gap-3 p-3">
          <label class="flex min-w-0 flex-1 items-center gap-3 ${deshabilitarCheck ? 'opacity-70' : 'cursor-pointer'}">
            <input type="checkbox" data-eq-check="${id}" ${inscripto ? 'checked' : ''} ${deshabilitarCheck ? 'disabled' : ''}
              class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 disabled:opacity-50">
            ${e.escudo ? `<img src="${esc(e.escudo)}" alt="" class="h-6 w-6 rounded object-contain">` : marcadorEquipo(e)}
            <span class="truncate text-sm font-medium text-slate-800">${esc(e.nombre)}</span>
          </label>
          <div class="flex items-center gap-3">
            ${selectGrupo}
            ${conPartidos ? '<span class="text-xs text-slate-500">Con partidos</span>' : ''}
          </div>
        </li>`;
    }).join('');

    if (enfocar) {
      eq.lista.querySelector(enfocar)?.focus();
    }
  }

  eq.lista.addEventListener('change', async (e) => {
    if (!abierto) return;
    const check = e.target.closest('input[data-eq-check]');
    const grupo = e.target.closest('select[data-eq-grupo]');

    eq.alertas.innerHTML = '';
    eq.estado.textContent = '';

    if (check) {
      const id = Number(check.dataset.eqCheck);
      const inscribir = check.checked;
      check.disabled = true;

      const r = await api(inscribir ? '/api/torneos/equipos/inscribir' : '/api/torneos/equipos/quitar', {
        id_torneo: abierto.id,
        id_equipo: id,
      });

      if (!r.ok) eq.alertas.innerHTML = cajaError(mensajesDe(r, 'No se pudo guardar el cambio.'));

      await cargarEquipos(`input[data-eq-check="${id}"]`);
      if (r.ok) eq.estado.textContent = inscribir ? 'Equipo inscripto.' : 'Equipo quitado.';
      await crud.recargar();
      return;
    }

    const id = Number(grupo.dataset.eqGrupo);
    grupo.disabled = true;

    const r = await api('/api/torneos/equipos/grupo', {
      id_torneo: abierto.id,
      id_equipo: id,
      id_grupo: grupo.value === '' ? null : Number(grupo.value),
    });

    if (!r.ok) eq.alertas.innerHTML = cajaError(mensajesDe(r, 'No se pudo asignar el grupo.'));

    await cargarEquipos(`select[data-eq-grupo="${id}"]`);
    if (r.ok) eq.estado.textContent = 'Grupo actualizado.';
  });

  ui.dlg.addEventListener('close', () => {
    abierto = null;
    torneoPendienteEliminar = null;
    eqDatos = null;
    eqTurno++;
    cerrarEditor(false);
  });

  // ---------- CRUD de torneos ----------
  crud = AdminCrud.iniciar({
    api: '/api/torneos',
    singular: 'torneo',
    campos: ['nombre', 'formato'],

    datosExtra: () => ({
      cantidad_equipos: Number(f.cantidad.value),
      tamano_grupo: f.tamano.value,
      clasifican: f.clasifican.value,
    }),

    fila: tarjeta,

    etiqueta: (r) => r.nombre,
    pregunta: (r) => `¿Eliminar el torneo «${r.nombre}»?`,
    avisoBorrado: (r) => r.total_partidos > 0
      ? 'Primero eliminá los fixtures de todas las fases, confirmando cada eliminación con la palabra ELIMINAR.'
      : 'Esta acción no se puede deshacer. Se eliminan también '
        + `${plural(r.total_fases, 'fase', 'fases')}, `
        + `${plural(r.total_plantel, 'jugador de plantel', 'jugadores de plantel')} `
        + `y ${plural(r.total_equipos, 'equipo inscripto', 'equipos inscriptos')}.`,
    antesDeEliminar: (r, abrirForm) => {
      if (Number(r.total_partidos) <= 0) return false;

      abrirForm(r);
      torneoPendienteEliminar = r.id;
      ui.estado.className = 'mb-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900';
      ui.estado.textContent = `Antes de eliminar el torneo, borrá el fixture de cada fase con partidos (${r.total_partidos} en total). Abrí «Fixture» en cada fase y confirmá cada borrado escribiendo ELIMINAR. Cuando no queden partidos, cerrá este panel y volvé a presionar «Eliminar».`;
      return true;
    },

    alListar: (r) => {
      crud.registros = r.data ?? [];
      if (TORNEO_SELECCIONADO) {
        hub.listado.classList.add('hidden');
        hub.vista.classList.remove('hidden');
        const torneo = crud.registros.find((t) => Number(t.id) === TORNEO_SELECCIONADO);
        hub.error.classList.toggle('hidden', Boolean(torneo));
        hub.contenido.classList.toggle('hidden', !torneo);
        if (torneo) {
          renderHub(torneo);
        } else {
          hub.titulo.textContent = 'Torneo no encontrado';
          hub.error.textContent = 'No se encontró el torneo solicitado. Volvé al listado para elegir otro.';
        }
      } else {
        hub.listado.classList.remove('hidden');
        hub.vista.classList.add('hidden');
      }

      const destino = location.hash.match(/^#torneo-(\d+)$/);
      if (destino) {
        requestAnimationFrame(() => {
          document.getElementById(`torneo-${destino[1]}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
      }

      if (!abierto) return;
      const actualizado = (r.data ?? []).find((t) => t.id === abierto.id);
      if (actualizado) {
        abierto = actualizado;
        renderFases();
      }
    },

    alAbrir: (form, registro) => {
      abierto = registro;
      torneoPendienteEliminar = null;
      ui.titulo.textContent = registro ? 'Editar nombre del torneo' : 'Nuevo torneo';
      faseEditando = null;
      ui.editor.hidden = true;
      ui.alertas.innerHTML = '';
      ui.estado.className = 'mb-2 text-sm text-emerald-700';
      ui.estado.textContent = '';

      f.formato.value = registro ? registro.formato : 'liga';
      armar({
        cantidad: registro?.cantidad_equipos ?? 8,
        tamano: registro?.tamano_grupo ?? null,
        clasifican: registro?.clasifican ?? null,
      });

      const bloqueado = Boolean(registro);
      [f.formato, f.cantidad, f.tamano, f.clasifican].forEach((c) => { c.disabled = bloqueado; });
      f.ayuda.textContent = bloqueado
        ? 'La configuración inicial no se puede cambiar después de crear el torneo.'
        : 'Liga: 8 a 30 equipos en una sola fase. Copa: 4, 8, 16 o 32 equipos, con o sin grupos. '
          + 'Elegí bien el formato y la cantidad de equipos antes de crearlo.';

      ui.bloque.classList.toggle('hidden', !registro);
      ui.nota.hidden = Boolean(registro);
      renderFases();

      eqDatos = null;
      eq.alertas.innerHTML = '';
      eq.estado.textContent = '';
      eq.resumen.textContent = '';
      eq.grupos.textContent = '';
      eq.lista.innerHTML = '';
      eq.bloque.classList.toggle('hidden', !registro);
      if (registro) cargarEquipos();
    },
  });

  hub.gestionar.addEventListener('click', () => {
    if (crud) crud.editar(TORNEO_SELECCIONADO);
  });

  hub.armar.addEventListener('click', () => {
    const torneo = (crud?.registros ?? []).find((t) => Number(t.id) === TORNEO_SELECCIONADO);
    const fase = torneo ? faseInicial(torneo) : null;
    if (!torneo || !fase) {
      alert('Este torneo no tiene fases para administrar.');
      return;
    }
    abrirModalFixture(fase, torneo);
  });

  hub.fases.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-dashboard-fase]');
    if (!btn) return;
    const torneo = (crud?.registros ?? []).find((t) => Number(t.id) === TORNEO_SELECCIONADO);
    const fase = torneo?.fases.find((x) => Number(x.id) === Number(btn.dataset.dashboardFase));
    if (torneo && fase) abrirModalFixture(fase, torneo);
  });

  // Toggle público / privado en las tarjetas
  document.getElementById('lista').addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-toggle-publico]');
    if (!btn) return;
    btn.disabled = true;
    const idTorneo = Number(btn.dataset.togglePublico);
    const { api } = AdminCrud;
    const r = await api('/api/torneos/visibilidad', { id: idTorneo });
    btn.disabled = false;
    if (r.ok) {
      const esPublico = Number(r.publico) === 1;
      btn.dataset.togglePublico = String(idTorneo);
      btn.className = btn.className.replace(
        /border-(?:emerald|slate)-\d+\s+bg-(?:emerald|slate)-\d+\s+text-(?:emerald|slate)-\d+\s+hover:bg-(?:emerald|slate)-\d+/g, ''
      ).trim();
      if (esPublico) {
        btn.classList.add('border-emerald-300', 'bg-emerald-50', 'text-emerald-700', 'hover:bg-emerald-100');
      } else {
        btn.classList.add('border-slate-300', 'bg-slate-100', 'text-slate-500', 'hover:bg-slate-200');
      }
      btn.textContent = esPublico ? '🌐 Público' : '🔒 Privado';
      btn.title = esPublico ? 'Torneo público: clic para hacer privado' : 'Torneo privado: clic para publicar';
      // update cached data
      const tr = crud?.datos?.find ? crud.datos.find((x) => x.id === idTorneo) : null;
      if (tr) tr.publico = r.publico;
    }
  });
})();
</script>
