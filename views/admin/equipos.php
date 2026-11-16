<?php
$campo = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30';
$label = 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500';
$activo = 'equipos';
require __DIR__ . '/_menu.php';
?>
<main id="contenido" tabindex="-1" class="mx-auto max-w-4xl px-4 py-8">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Equipos</h1>
            <p class="text-sm text-slate-500">Los clubes que participan en la liga.</p>
        </div>
        <button id="btn-nuevo" type="button"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
            + Nuevo equipo
        </button>
    </div>

    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
        <label for="buscar" class="<?= $label ?>">Buscar</label>
        <input type="search" id="buscar" autocomplete="off" placeholder="Nombre o ciudad" class="<?= $campo ?>">
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white" aria-labelledby="titulo-lista">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <h2 id="titulo-lista" class="text-sm font-semibold text-slate-700">Listado</h2>
            <span id="contador" class="text-xs text-slate-500"></span>
        </div>
        <ul id="lista" class="divide-y divide-slate-100"></ul>
        <p id="vacio" class="hidden p-8 text-center text-sm text-slate-500">Todavía no hay equipos cargados.</p>
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
            <h2 id="form-titulo" class="text-lg font-bold text-slate-900">Nuevo equipo</h2>
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
                    <label for="f-nombre_completo" class="<?= $label ?>">Nombre completo</label>
                    <input type="text" id="f-nombre_completo" name="nombre_completo" maxlength="150" autocomplete="off" class="<?= $campo ?>">
                </div>
                <div>
                    <label for="f-fundacion" class="<?= $label ?>">Año de fundación</label>
                    <input type="number" id="f-fundacion" name="fundacion" min="1850" max="<?= (int) date('Y') ?>" inputmode="numeric" class="<?= $campo ?>">
                </div>
                <div>
                    <label for="f-id_cancha_local" class="<?= $label ?>">Cancha local</label>
                    <select id="f-id_cancha_local" name="id_cancha_local" class="<?= $campo ?>">
                        <option value="">Sin cancha local</option>
                    </select>
                </div>
                <div>
                    <label for="f-ciudad" class="<?= $label ?>">Ciudad</label>
                    <input type="text" id="f-ciudad" name="ciudad" maxlength="100" autocomplete="off" class="<?= $campo ?>">
                </div>
                <div>
                    <label for="f-direccion" class="<?= $label ?>">Dirección</label>
                    <input type="text" id="f-direccion" name="direccion" maxlength="150" autocomplete="off" class="<?= $campo ?>">
                </div>
                <div>
                    <label for="f-telefono" class="<?= $label ?>">Teléfono</label>
                    <input type="tel" id="f-telefono" name="telefono" maxlength="30" autocomplete="off" class="<?= $campo ?>">
                </div>
                <div>
                    <label for="f-email" class="<?= $label ?>">Email</label>
                    <input type="email" id="f-email" name="email" maxlength="150" autocomplete="off" class="<?= $campo ?>">
                </div>
            </div>

            <div>
                <label for="f-sitio_web" class="<?= $label ?>">Sitio web</label>
                <input type="url" id="f-sitio_web" name="sitio_web" maxlength="255" autocomplete="off" placeholder="https://" class="<?= $campo ?>">
            </div>

            <fieldset class="grid gap-4 sm:grid-cols-2">
                <legend class="<?= $label ?>">Colores del equipo</legend>
                <?php foreach (['primario' => 'Color primario', 'secundario' => 'Color secundario'] as $clave => $texto): ?>
                    <div class="flex items-center gap-3 rounded-lg border border-slate-200 px-3 py-2">
                        <input type="checkbox" id="def-color_<?= $clave ?>"
                            class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <label for="def-color_<?= $clave ?>" class="flex-1 text-sm text-slate-700"><?= $texto ?></label>
                        <input type="color" id="f-color_<?= $clave ?>" name="color_<?= $clave ?>" disabled
                            aria-label="Elegir <?= strtolower($texto) ?>"
                            class="h-8 w-12 cursor-pointer rounded border border-slate-300 bg-white disabled:cursor-not-allowed disabled:opacity-40">
                    </div>
                <?php endforeach; ?>
            </fieldset>

            <p id="escudo-nota" class="text-xs text-slate-500">
                Guardá el equipo primero; después vas a poder subir el escudo.
            </p>

            <fieldset id="escudo-bloque" class="hidden rounded-lg border border-slate-200 px-4 py-3">
                <legend class="<?= $label ?> px-1">Escudo</legend>
                <div class="flex flex-wrap items-center gap-4">
                    <img id="escudo-img" src="" alt="Escudo actual" hidden
                        class="h-20 w-20 rounded-lg border border-slate-200 object-contain">
                    <p id="escudo-vacio" class="text-sm text-slate-500">Sin escudo</p>

                    <div class="min-w-0 flex-1 space-y-2">
                        <label for="escudo-archivo" class="sr-only">Elegir imagen del escudo</label>
                        <input type="file" id="escudo-archivo" accept="image/png,image/webp,image/jpeg"
                            aria-describedby="escudo-ayuda"
                            class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                        <p id="escudo-ayuda" class="text-xs text-slate-500">PNG, WebP o JPG, hasta 2 MB.</p>
                        <div class="flex gap-2">
                            <button type="button" id="escudo-subir"
                                class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">Subir escudo</button>
                            <button type="button" id="escudo-quitar"
                                class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 disabled:opacity-60">Quitar</button>
                        </div>
                    </div>
                </div>
                <p id="escudo-estado" role="status" class="mt-2 text-sm"></p>
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
        <h2 id="borrar-titulo" class="text-lg font-bold text-slate-900">Eliminar equipo</h2>
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
  const COLORES = ['color_primario', 'color_secundario'];
  const COLOR_BASE = '#10b981';

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

  const ponerColor = (form, nombre, valor) => {
    const chk = form.querySelector('#def-' + nombre);
    const input = form.elements[nombre];
    chk.checked = Boolean(valor);
    input.value = valor || COLOR_BASE;
    input.disabled = !valor;
  };

  const escudo = AdminCrud.imagen({
    tipo: 'equipo_escudo',
    prefijo: 'escudo',
    campo: 'escudo',
    recargar: () => crud.recargar(),
    textos: { subida: 'Escudo actualizado.', quitada: 'Escudo quitado.' },
  });

  crud = AdminCrud.iniciar({
    api: '/api/equipos',
    singular: 'equipo',
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
    campos: ['nombre', 'nombre_completo', 'fundacion', 'ciudad', 'direccion',
             'telefono', 'email', 'sitio_web', 'id_cancha_local'],
    etiqueta: (r) => r.nombre,
    detalle: (r) => [r.ciudad, r.fundacion ? 'Fundado en ' + r.fundacion : ''].filter(Boolean).join(' · '),
    miniatura: (r) => r.escudo,
    marcador: (r) => {
      const ini = AdminCrud.esc(r.nombre.trim().split(/\s+/).slice(0, 2).map((p) => p[0]).join('').toUpperCase());
      const fondo = /^#[0-9a-f]{6}$/i.test(r.color_primario ?? '') ? r.color_primario : '#f1f5f9';
      const [rr, gg, bb] = [1, 3, 5].map((i) => parseInt(fondo.slice(i, i + 2), 16));
      const texto = (rr * 299 + gg * 587 + bb * 114) / 1000 > 150 ? '#0f172a' : '#ffffff';
      return `<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded text-xs font-bold" style="background:${fondo};color:${texto}" aria-hidden="true">${ini}</span>`;
    },
    pregunta: (r) => `¿Eliminar el equipo «${r.nombre}»?`,
    avisoBorrado: () => 'Esta acción no se puede deshacer. Si tiene partidos, eventos, alineaciones o jugadores en plantel, el sistema no lo va a eliminar.',

    alIniciar: async (form) => {
      const r = await AdminCrud.api('/api/canchas');
      (r.data ?? []).forEach((c) => form.elements.id_cancha_local.add(new Option(c.nombre, c.id)));

      COLORES.forEach((nombre) => {
        form.querySelector('#def-' + nombre).addEventListener('change', (e) => {
          form.elements[nombre].disabled = !e.target.checked;
        });
      });
    },

    alAbrir: (form, registro) => {
      COLORES.forEach((nombre) => ponerColor(form, nombre, registro?.[nombre] ?? null));
      escudo.abrir(registro);
    },

    datosExtra: (form) => Object.fromEntries(COLORES.map((nombre) => [
      nombre,
      form.querySelector('#def-' + nombre).checked ? form.elements[nombre].value : '',
    ])),
  });
})();
</script>