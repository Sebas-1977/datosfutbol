<?php
$campo = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30';
$label = 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500';
$activo = 'arbitros';
require __DIR__ . '/_menu.php';
?>
<main id="contenido" tabindex="-1" class="mx-auto max-w-4xl px-4 py-8">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Árbitros</h1>
            <p class="text-sm text-slate-500">Quiénes pueden dirigir los partidos del torneo.</p>
        </div>
        <button id="btn-nuevo" type="button"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
            + Nuevo árbitro
        </button>
    </div>

    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
        <label for="buscar" class="<?= $label ?>">Buscar</label>
        <input type="search" id="buscar" autocomplete="off" placeholder="Apellido o nombre" class="<?= $campo ?>">
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white" aria-labelledby="titulo-lista">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <h2 id="titulo-lista" class="text-sm font-semibold text-slate-700">Listado</h2>
            <span id="contador" class="text-xs text-slate-500"></span>
        </div>
        <ul id="lista" class="divide-y divide-slate-100"></ul>
        <p id="vacio" class="hidden p-8 text-center text-sm text-slate-500">Todavía no hay árbitros cargados.</p>
    </section>
</main>

<!-- Crear / editar -->
<dialog id="dlg-form" aria-labelledby="form-titulo"
    class="m-auto max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-0 shadow-xl backdrop:bg-slate-900/60">
    <form id="form" novalidate>
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h2 id="form-titulo" class="text-lg font-bold text-slate-900">Nuevo árbitro</h2>
            <button type="button" data-cerrar aria-label="Cerrar"
                class="text-2xl leading-none text-slate-400 hover:text-slate-600">&times;</button>
        </div>

        <div class="space-y-5 px-6 py-5">
            <div id="alertas" role="alert"></div>

            <div>
                <label for="f-nombre" class="<?= $label ?>">Nombre</label>
                <input type="text" id="f-nombre" name="nombre" required maxlength="100" autocomplete="off" class="<?= $campo ?>">
            </div>
            <div>
                <label for="f-apellido" class="<?= $label ?>">Apellido</label>
                <input type="text" id="f-apellido" name="apellido" required maxlength="100" autocomplete="off" class="<?= $campo ?>">
            </div>

            <p id="foto-nota" class="text-xs text-slate-500">
                Guardá el árbitro primero; después vas a poder subir la foto.
            </p>

            <fieldset id="foto-bloque" class="hidden rounded-lg border border-slate-200 px-4 py-3">
                <legend class="<?= $label ?> px-1">Foto</legend>
                <div class="flex flex-wrap items-center gap-4">
                    <img id="foto-img" src="" alt="Foto actual del árbitro" hidden
                        class="h-24 w-24 rounded-lg border border-slate-200 object-cover">
                    <p id="foto-vacio" class="text-sm text-slate-500">Sin foto</p>

                    <div class="min-w-0 flex-1 space-y-2">
                        <label for="foto-archivo" class="sr-only">Elegir imagen del árbitro</label>
                        <input type="file" id="foto-archivo" accept="image/png,image/webp,image/jpeg"
                            aria-describedby="foto-ayuda"
                            class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                        <p id="foto-ayuda" class="text-xs text-slate-500">PNG, WebP o JPG, hasta 2 MB.</p>
                        <div class="flex gap-2">
                            <button type="button" id="foto-subir"
                                class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">Subir foto</button>
                            <button type="button" id="foto-quitar"
                                class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 disabled:opacity-60">Quitar</button>
                        </div>
                    </div>
                </div>
                <p id="foto-estado" role="status" class="mt-2 text-sm"></p>
            </fieldset>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
            <button type="button" data-cerrar
                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</button>
            <button type="submit" id="btn-guardar"
                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">Guardar</button>
        </div>
    </form>
</dialog>

<!-- Confirmar borrado -->
<dialog id="dlg-borrar" aria-labelledby="borrar-titulo" aria-describedby="borrar-texto"
    class="m-auto w-full max-w-md rounded-2xl bg-white p-0 shadow-xl backdrop:bg-slate-900/60">
    <div class="space-y-3 px-6 py-5">
        <h2 id="borrar-titulo" class="text-lg font-bold text-slate-900">Eliminar árbitro</h2>
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
    const { esc } = AdminCrud;

    let partidos = [];
    let crud = null;
    let espera = null;
    const buscar = document.getElementById('buscar');

    buscar.addEventListener('input', () => {
    clearTimeout(espera);
    espera = setTimeout(() => crud.recargar(), 300);
    });

    const foto = AdminCrud.imagen({
    tipo: 'arbitro',
    prefijo: 'foto',
    campo: 'foto',
    recargar: () => crud.recargar(),
    textos: { subida: 'Foto actualizada.', quitada: 'Foto quitada.' },
  });

  const nombreCompleto = (r) =>
    [r.apellido, r.nombre].map((s) => String(s ?? '').trim()).filter(Boolean).join(', ');

  crud = AdminCrud.iniciar({
    api: '/api/arbitros',
    singular: 'árbitro',
    consulta: () => {
      const q = buscar.value.trim();
      return q ? new URLSearchParams({ buscar: q }).toString() : '';
    },
    reabrirAlCrear: true,
    campos: ['nombre', 'apellido'],
    etiqueta: nombreCompleto,
    miniatura: (r) => r.foto,
    marcador: (r) => {
      const ini = ((String(r.nombre ?? '').trim()[0] ?? '') + (String(r.apellido ?? '').trim()[0] ?? '')).toUpperCase() || '?';
      return `<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-600" aria-hidden="true">${esc(ini)}</span>`;
    },
    pregunta: (r) => `¿Eliminar a «${nombreCompleto(r)}»?`,

    avisoBorrado: (r) => {
      const n = partidos.filter((p) => Number(p.id_arbitro) === Number(r.id)).length;
      return (n
        ? `${n} partido${n === 1 ? '' : 's'} asignado${n === 1 ? '' : 's'} a este árbitro quedará${n === 1 ? '' : 'n'} sin árbitro. `
        : 'No dirigió ni tiene asignado ningún partido. ') +
        'Esta acción no se puede deshacer.';
    },

    alIniciar: async () => {
      const r = await AdminCrud.api('/api/partidos');
      partidos = r.data ?? [];
    },

    alAbrir: (form, registro) => {
      foto.abrir(registro);
    },
  });
})();
</script>