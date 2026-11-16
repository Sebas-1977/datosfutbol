<?php
$campo = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30';
$label = 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500';
$activo = 'jugadores';
require __DIR__ . '/_menu.php';
?>
<main id="contenido" tabindex="-1" class="mx-auto max-w-4xl px-4 py-8">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Jugadores</h1>
            <p class="text-sm text-slate-500">Las personas que pueden integrar un plantel.</p>
        </div>
        <button id="btn-nuevo" type="button"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
            + Nuevo jugador
        </button>
    </div>

    <div class="mb-4 flex flex-wrap items-end gap-4 rounded-xl border border-slate-200 bg-white p-4">
        <div class="min-w-0 flex-1">
            <label for="buscar" class="<?= $label ?>">Buscar</label>
            <input type="search" id="buscar" autocomplete="off" placeholder="Apellido o nombre" class="<?= $campo ?>">
        </div>
        <div class="flex items-center gap-2 pb-2">
            <input type="checkbox" id="solo-revisar"
                class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
            <label for="solo-revisar" class="text-sm text-slate-700">Solo datos a revisar</label>
        </div>
    </div>

    <div class="w-full sm:w-56">
        <label for="filtro-equipo" class="<?= $label ?>">Equipo</label>
        <select id="filtro-equipo" class="<?= $campo ?>">
            <option value="">Todos</option>
        </select>
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white" aria-labelledby="titulo-lista">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <h2 id="titulo-lista" class="text-sm font-semibold text-slate-700">Listado</h2>
            <span id="contador" role="status" class="text-xs text-slate-500"></span>
        </div>
        <ul id="lista" class="divide-y divide-slate-100"></ul>
        <p id="vacio" class="hidden p-8 text-center text-sm text-slate-500">No hay jugadores que coincidan.</p>

        <nav aria-label="Paginación" class="flex items-center justify-between border-t border-slate-200 px-4 py-3">
            <button type="button" id="pag-anterior"
                class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Anterior</button>
            <span id="pag-info" class="text-xs text-slate-500"></span>
            <button type="button" id="pag-siguiente"
                class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">Siguiente</button>
        </nav>
    </section>
</main>

<!-- Crear / editar -->
<dialog id="dlg-form" aria-labelledby="form-titulo"
    class="m-auto max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-0 shadow-xl backdrop:bg-slate-900/60">
    <form id="form" novalidate>
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h2 id="form-titulo" class="text-lg font-bold text-slate-900">Nuevo jugador</h2>
            <button type="button" data-cerrar aria-label="Cerrar"
                class="text-2xl leading-none text-slate-400 hover:text-slate-600">&times;</button>
        </div>

        <div class="space-y-5 px-6 py-5">
            <div id="alertas" role="alert"></div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="f-nombre" class="<?= $label ?>">Nombre</label>
                    <input type="text" id="f-nombre" name="nombre" required maxlength="100" autocomplete="off" class="<?= $campo ?>">
                </div>
                <div>
                    <label for="f-apellido" class="<?= $label ?>">Apellido</label>
                    <input type="text" id="f-apellido" name="apellido" required maxlength="100" autocomplete="off" class="<?= $campo ?>">
                </div>
                <div class="sm:col-span-2">
                    <label for="f-seudonimo" class="<?= $label ?>">Seudónimo <span class="font-normal normal-case text-slate-400">(opcional)</span></label>
                    <input type="text" id="f-seudonimo" name="seudonimo" maxlength="100" autocomplete="off"
                        placeholder="Ej.: Raphinha" aria-describedby="seudonimo-ayuda" class="<?= $campo ?>">
                    <p id="seudonimo-ayuda" class="mt-1 text-xs text-slate-500">Si lo completás, se muestra en lugar del nombre en estadísticas y en la cancha.</p>
                </div>
                <div>
                    <label for="f-pais" class="<?= $label ?>">País</label>
                    <input type="text" id="f-pais" name="pais" maxlength="100" list="lista-paises" autocomplete="off" class="<?= $campo ?>">
                    <datalist id="lista-paises">
                        <option value="Uruguay"><option value="Argentina"><option value="Brasil">
                        <option value="Paraguay"><option value="Chile"><option value="Colombia">
                        <option value="Perú"><option value="Venezuela"><option value="Bolivia">
                        <option value="Ecuador"><option value="España">
                    </datalist>
                </div>
                <div>
                    <label for="f-fecha_nacimiento" class="<?= $label ?>">Fecha de nacimiento</label>
                    <input type="date" id="f-fecha_nacimiento" name="fecha_nacimiento" max="<?= date('Y-m-d') ?>" class="<?= $campo ?>">
                </div>
            </div>

            <p id="foto-nota" class="text-xs text-slate-500">
                Guardá el jugador primero; después vas a poder subir la foto.
            </p>

            <fieldset id="foto-bloque" class="hidden rounded-lg border border-slate-200 px-4 py-3">
                <legend class="<?= $label ?> px-1">Foto</legend>
                <div class="flex flex-wrap items-center gap-4">
                    <img id="foto-img" src="" alt="Foto actual del jugador" hidden
                        class="h-24 w-24 rounded-lg border border-slate-200 object-cover">
                    <p id="foto-vacio" class="text-sm text-slate-500">Sin foto</p>

                    <div class="min-w-0 flex-1 space-y-2">
                        <label for="foto-archivo" class="sr-only">Elegir imagen del jugador</label>
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
        <h2 id="borrar-titulo" class="text-lg font-bold text-slate-900">Eliminar jugador</h2>
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
  const { esc } = AdminCrud;

  let pagina = 1;
  let crud = null;
  let espera = null;

  // ---------- Búsqueda y paginación ----------
    const ui = {
    buscar: $('buscar'), revisar: $('solo-revisar'), equipo: $('filtro-equipo'),
    anterior: $('pag-anterior'), siguiente: $('pag-siguiente'), info: $('pag-info'),
  };

  const irAPagina = (n) => { pagina = n; crud.recargar(); };

  ui.buscar.addEventListener('input', () => {
    clearTimeout(espera);
    espera = setTimeout(() => irAPagina(1), 300);
  });
  ui.revisar.addEventListener('change', () => irAPagina(1));
    ui.equipo.addEventListener('change', () => irAPagina(1));
  ui.anterior.addEventListener('click', () => irAPagina(pagina - 1));
  ui.siguiente.addEventListener('click', () => irAPagina(pagina + 1));

  // ---------- Foto ----------
  const foto = AdminCrud.imagen({
    tipo: 'jugador',
    prefijo: 'foto',
    campo: 'foto',
    recargar: () => crud.recargar(),
    textos: { subida: 'Foto actualizada.', quitada: 'Foto quitada.' },
  });

  // ---------- Textos del listado ----------
  const nombreCompleto = (r) => {
    const n = String(r.nombre ?? '').trim();
    const a = String(r.apellido ?? '').trim();
    return a ? `${a}, ${n}` : n;
  };

  const problemas = (r) => {
    const n = String(r.nombre ?? '');
    const a = String(r.apellido ?? '');
    const lista = [];
    if (a.trim() === '') lista.push('falta el apellido');
    else if (a.trim().length <= 2 || n.trim().length <= 2) lista.push('nombre o apellido abreviado');
    if (n !== n.trim() || a !== a.trim()) lista.push('espacios sobrantes');
    return lista;
  };

  const fechaCorta = (f) => (f ? f.split('-').reverse().join('/') : '');

    const cargarEquipos = async () => {
    const r = await AdminCrud.api('/api/equipos');
    if (!r.ok) return;
    (r.data ?? []).forEach((e) => {
      const o = document.createElement('option');
      o.value = e.id;
      o.textContent = e.nombre;
      ui.equipo.appendChild(o);
        });
    };

  // ---------- CRUD ----------
  crud = AdminCrud.iniciar({
    api: '/api/jugadores',
    singular: 'jugador',
    alIniciar: cargarEquipos,
    reabrirAlCrear: true,
    campos: ['nombre', 'apellido', 'seudonimo', 'pais', 'fecha_nacimiento'],

    etiqueta: nombreCompleto,
    detalle: (r) => {
    const aRevisar = problemas(r);
    const seudo = String(r.seudonimo ?? '').trim();
    return [seudo ? `«${seudo}»` : '', r.pais, fechaCorta(r.fecha_nacimiento), aRevisar.length ? 'Revisar: ' + aRevisar.join(', ') : '']
        .filter(Boolean).join(' · ');
    },
    miniatura: (r) => r.foto,
    marcador: (r) => {
      const ini = ((String(r.nombre ?? '').trim()[0] ?? '') + (String(r.apellido ?? '').trim()[0] ?? '')).toUpperCase() || '?';
      return `<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-600" aria-hidden="true">${esc(ini)}</span>`;
    },
    pregunta: (r) => `¿Eliminar a «${nombreCompleto(r)}»?`,
    avisoBorrado: () => 'Esta acción no se puede deshacer. Si está en algún plantel, se lo quita también. Si jugó partidos o tiene eventos, el sistema no lo va a eliminar.',

    consulta: () => {
      const p = new URLSearchParams({ pagina: String(pagina) });
      const q = ui.buscar.value.trim();
      if (q) p.set('buscar', q);
      if (ui.revisar.checked) p.set('revisar', '1');
      if (ui.equipo.value) p.set('id_equipo', ui.equipo.value);
      return p.toString();
    },

    alListar: (r) => {
      const paginas = r.paginas ?? 1;
      if (pagina > paginas) { irAPagina(paginas); return; }
      ui.info.textContent = `Página ${pagina} de ${paginas}`;
      ui.anterior.disabled = pagina <= 1;
      ui.siguiente.disabled = pagina >= paginas;
    },

    alAbrir: (form, registro) => {
      foto.abrir(registro);
    },
  });
})();
</script>