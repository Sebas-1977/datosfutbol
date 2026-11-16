<?php
$campo = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30';
$label = 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500';
$activo = 'canchas';
require __DIR__ . '/_menu.php';
?>
<main id="contenido" tabindex="-1" class="mx-auto max-w-4xl px-4 py-8">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Canchas</h1>
            <p class="text-sm text-slate-500">Dónde se juegan los partidos.</p>
        </div>
        <button id="btn-nuevo" type="button"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
            + Nueva cancha
        </button>
    </div>

    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
        <label for="buscar" class="<?= $label ?>">Buscar</label>
        <input type="search" id="buscar" autocomplete="off" placeholder="Nombre, ciudad o dirección" class="<?= $campo ?>">
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white" aria-labelledby="titulo-lista">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <h2 id="titulo-lista" class="text-sm font-semibold text-slate-700">Listado</h2>
            <span id="contador" class="text-xs text-slate-500"></span>
        </div>
        <ul id="lista" class="divide-y divide-slate-100"></ul>
        <p id="vacio" class="hidden p-8 text-center text-sm text-slate-500">Todavía no hay canchas cargadas.</p>
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
            <h2 id="form-titulo" class="text-lg font-bold text-slate-900">Nueva cancha</h2>
            <button type="button" data-cerrar aria-label="Cerrar"
                class="text-2xl leading-none text-slate-400 hover:text-slate-600">&times;</button>
        </div>

        <div class="space-y-5 px-6 py-5">
            <div id="alertas" role="alert"></div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="f-nombre" class="<?= $label ?>">Nombre</label>
                    <input type="text" id="f-nombre" name="nombre" required maxlength="100" autocomplete="off" class="<?= $campo ?>">
                </div>
                <div>
                    <label for="f-tipo" class="<?= $label ?>">Tipo</label>
                    <select id="f-tipo" name="tipo" class="<?= $campo ?>">
                        <option value="privada">Privada</option>
                        <option value="club">De club</option>
                        <option value="estatal">Estatal</option>
                    </select>
                </div>
                <div>
                    <label for="f-id_equipo_propietario" class="<?= $label ?>">Equipo propietario</label>
                    <select id="f-id_equipo_propietario" name="id_equipo_propietario" aria-describedby="ayuda-propietario" class="<?= $campo ?>">
                        <option value="">Sin propietario</option>
                    </select>
                    <p id="ayuda-propietario" class="mt-1 text-xs text-slate-500">Obligatorio si la cancha es de club.</p>
                </div>
                <div>
                    <label for="f-capacidad" class="<?= $label ?>">Capacidad</label>
                    <input type="number" id="f-capacidad" name="capacidad" min="1" inputmode="numeric" class="<?= $campo ?>">
                </div>
                <div>
                    <label for="f-ciudad" class="<?= $label ?>">Ciudad</label>
                    <input type="text" id="f-ciudad" name="ciudad" maxlength="100" autocomplete="off" class="<?= $campo ?>">
                </div>
                <div class="sm:col-span-2">
                    <label for="f-direccion" class="<?= $label ?>">Dirección</label>
                    <input type="text" id="f-direccion" name="direccion" maxlength="150" autocomplete="off" class="<?= $campo ?>">
                </div>
            </div>

            <p id="foto-nota" class="text-xs text-slate-500">
                Guardá la cancha primero; después vas a poder subir la foto.
            </p>

            <fieldset id="foto-bloque" class="hidden rounded-lg border border-slate-200 px-4 py-3">
                <legend class="<?= $label ?> px-1">Foto</legend>
                <div class="flex flex-wrap items-center gap-4">
                    <img id="foto-img" src="" alt="Foto actual de la cancha" hidden
                        class="h-20 w-28 rounded-lg border border-slate-200 object-cover">
                    <p id="foto-vacio" class="text-sm text-slate-500">Sin foto</p>

                    <div class="min-w-0 flex-1 space-y-2">
                        <label for="foto-archivo" class="sr-only">Elegir imagen de la cancha</label>
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
        <h2 id="borrar-titulo" class="text-lg font-bold text-slate-900">Eliminar cancha</h2>
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
  const TIPOS = { club: 'De club', estatal: 'Estatal', privada: 'Privada' };

  let equipos = [];
  let partidos = [];
    const $ = (id) => document.getElementById(id);
  let crud = null;
  let pagina = 1;
  let espera = null;

  const ui = {
    buscar: $('buscar'),
    anterior: $('pag-anterior'), siguiente: $('pag-siguiente'), info: $('pag-info'),
  };

  const irAPagina = (n) => { pagina = n; crud.recargar(); };

  ui.buscar.addEventListener('input', () => {
    clearTimeout(espera);
    espera = setTimeout(() => irAPagina(1), 300);
  });
  ui.anterior.addEventListener('click', () => irAPagina(pagina - 1));
  ui.siguiente.addEventListener('click', () => irAPagina(pagina + 1));

  const foto = AdminCrud.imagen({
    tipo: 'cancha',
    prefijo: 'foto',
    campo: 'foto',
    recargar: () => crud.recargar(),
    textos: { subida: 'Foto actualizada.', quitada: 'Foto quitada.' },
  });

  crud = AdminCrud.iniciar({
    api: '/api/canchas',
    singular: 'cancha',
        consulta: () => {
      const p = new URLSearchParams({ pagina: String(pagina) });
      const q = ui.buscar.value.trim();
      if (q) p.set('buscar', q);
      return p.toString();
    },

    alListar: (r) => {
      const paginas = r.paginas ?? 1;
      if (pagina > paginas) { irAPagina(paginas); return; }
      ui.info.textContent = `Página ${pagina} de ${paginas}`;
      ui.anterior.disabled = pagina <= 1;
      ui.siguiente.disabled = pagina >= paginas;
    },
    reabrirAlCrear: true,
    campos: ['nombre', 'tipo', 'id_equipo_propietario', 'capacidad', 'ciudad', 'direccion'],
    etiqueta: (r) => r.nombre,
    detalle: (r) => [
      TIPOS[r.tipo] ?? r.tipo,
      r.ciudad,
      r.capacidad ? r.capacidad + ' personas' : '',
    ].filter(Boolean).join(' · '),
    miniatura: (r) => r.foto,
    pregunta: (r) => `¿Eliminar la cancha «${r.nombre}»?`,

    avisoBorrado: (r) => {
      const id = Number(r.id);
      const nPartidos = partidos.filter((p) => Number(p.id_cancha) === id).length;
      const nEquipos = equipos.filter((e) => Number(e.id_cancha_local) === id).length;
      const partes = [];
      if (nPartidos) partes.push(`${nPartidos} partido${nPartidos === 1 ? '' : 's'} quedará${nPartidos === 1 ? '' : 'n'} sin cancha`);
      if (nEquipos) partes.push(`${nEquipos} equipo${nEquipos === 1 ? '' : 's'} perderá${nEquipos === 1 ? '' : 'n'} su cancha local`);
      return (partes.length ? partes.join(' y ') + '. ' : 'No la usa ningún partido ni equipo. ') +
             'Esta acción no se puede deshacer.';
    },

    alIniciar: async (form) => {
      const [e, p] = await Promise.all([AdminCrud.api('/api/equipos'), AdminCrud.api('/api/partidos')]);
      equipos = e.data ?? [];
      partidos = p.data ?? [];
      equipos.forEach((x) => form.elements.id_equipo_propietario.add(new Option(x.nombre, x.id)));
    },

    alAbrir: (form, registro) => {
      form.elements.tipo.value = registro?.tipo ?? 'privada';
      foto.abrir(registro);
    },
  });
})();
</script>