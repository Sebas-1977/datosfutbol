<?php
$campo = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 disabled:bg-slate-100 disabled:text-slate-500';
$label = 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500';
$activo = 'usuarios';
require __DIR__ . '/_menu.php';
?>
<main id="contenido" tabindex="-1" class="mx-auto max-w-4xl px-4 py-8">

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Usuarios</h1>
            <p class="text-sm text-slate-500">Quiénes pueden entrar al panel. Las cuentas se crean por invitación.</p>
        </div>
        <button id="btn-nuevo" type="button"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
            + Invitar usuario
        </button>
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white" aria-labelledby="titulo-lista">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <h2 id="titulo-lista" class="text-sm font-semibold text-slate-700">Listado</h2>
            <span id="contador" class="text-xs text-slate-500"></span>
        </div>
        <ul id="lista" class="divide-y divide-slate-100"></ul>
        <p id="vacio" class="hidden p-8 text-center text-sm text-slate-500">Todavía no hay usuarios.</p>
    </section>
</main>

<!-- Invitar / editar -->
<dialog id="dlg-form" aria-labelledby="form-titulo"
    class="m-auto max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-0 shadow-xl backdrop:bg-slate-900/60">
    <form id="form" novalidate>
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h2 id="form-titulo" class="text-lg font-bold text-slate-900">Nuevo usuario</h2>
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
            <div>
                <label for="f-email" class="<?= $label ?>">Email</label>
                <input type="email" id="f-email" name="email" required maxlength="190" autocomplete="off" class="<?= $campo ?>">
            </div>

            <label class="flex items-start gap-2 text-sm text-slate-700">
                <input type="checkbox" id="f-admin" name="admin" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-emerald-600">
                <span>Administrador <span class="block text-xs text-slate-500">Puede además gestionar usuarios. Todas las cuentas activas pueden editar los datos de la liga.</span></span>
            </label>

            <p id="invitacion-nota" class="text-xs text-slate-500">
                Le llega un correo con un enlace para elegir su contraseña (vence en 72 horas).
            </p>
        </div>

        <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
            <button type="button" data-cerrar
                class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancelar</button>
            <button type="submit" id="btn-guardar"
                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">Guardar e invitar</button>
        </div>
    </form>
</dialog>

<!-- Confirmar borrado -->
<dialog id="dlg-borrar" aria-labelledby="borrar-titulo" aria-describedby="borrar-texto"
    class="m-auto w-full max-w-md rounded-2xl bg-white p-0 shadow-xl backdrop:bg-slate-900/60">
    <div class="space-y-3 px-6 py-5">
        <h2 id="borrar-titulo" class="text-lg font-bold text-slate-900">Eliminar usuario</h2>
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

  const nombreCompleto = (r) =>
    [r.apellido, r.nombre].map((s) => String(s ?? '').trim()).filter(Boolean).join(', ');

  const fecha = (s) => {
    const d = new Date(String(s).replace(' ', 'T'));
    return isNaN(d) ? '' : d.toLocaleString('es-UY', { dateStyle: 'short', timeStyle: 'short' });
  };

  const CHIPS = {
    verde: 'bg-emerald-50 text-emerald-700',
    ambar: 'bg-amber-50 text-amber-700',
    rosa: 'bg-rose-50 text-rose-700',
    gris: 'bg-slate-100 text-slate-600',
  };
  const chip = (texto, color) =>
    `<span class="rounded-full px-2 py-0.5 text-xs font-semibold ${CHIPS[color]}">${texto}</span>`;

  const toastEl = document.getElementById('toast');
  let temporizador = null;
  const aviso = (msg) => {
    toastEl.textContent = msg;
    toastEl.classList.remove('opacity-0');
    clearTimeout(temporizador);
    temporizador = setTimeout(() => toastEl.classList.add('opacity-0'), 4000);
  };

  const BTN = 'rounded-lg border px-3 py-1.5 text-xs font-semibold';

  const crud = AdminCrud.iniciar({
    api: '/api/usuarios',
    singular: 'usuario',
    campos: ['nombre', 'apellido', 'email'],
    etiqueta: nombreCompleto,
    pregunta: (r) => `¿Eliminar a «${nombreCompleto(r)}»?`,
    avisoBorrado: () =>
      'Perderá el acceso al panel. Si solo querés suspender la cuenta, usá «Desactivar». Esta acción no se puede deshacer.',
    datosExtra: (form) => ({ admin: form.elements.admin.checked ? 1 : 0 }),

    alAbrir: (form, r) => {
      form.elements.email.disabled = Boolean(r);        // el email no se cambia al editar
      form.elements.admin.checked = Boolean(r?.admin);
      form.elements.admin.disabled = Boolean(r?.es_yo);  // nadie se quita su propio permiso
      document.getElementById('invitacion-nota').hidden = Boolean(r);
      document.getElementById('btn-guardar').textContent = r ? 'Guardar' : 'Guardar e invitar';
    },

    fila: (r) => {
      const estado = !r.activo ? chip('Desactivada', 'rosa')
        : r.invitacion_vigente ? chip('Invitación enviada', 'ambar')
        : !r.ultimo_login ? chip('Nunca ingresó', 'gris')
        : '';
      const ultimo = r.ultimo_login ? ` · Último ingreso: ${esc(fecha(r.ultimo_login))}` : '';

      // Los botones propios usan data-uaccion. Los de admin-crud usan data-accion.
      const botones = [
        `<button type="button" data-accion="editar" data-id="${r.id}" class="${BTN} border-slate-300 text-slate-700 hover:bg-slate-50">Editar</button>`,
        r.activo && !r.ultimo_login
          ? `<button type="button" data-uaccion="reenviar" data-id="${r.id}" class="${BTN} border-slate-300 text-slate-700 hover:bg-slate-50">Reenviar invitación</button>` : '',
        !r.es_yo
          ? `<button type="button" data-uaccion="estado" data-id="${r.id}" data-activo="${r.activo ? 0 : 1}" class="${BTN} border-slate-300 text-slate-700 hover:bg-slate-50">${r.activo ? 'Desactivar' : 'Activar'}</button>` : '',
        !r.es_yo
          ? `<button type="button" data-accion="eliminar" data-id="${r.id}" class="${BTN} border-rose-200 text-rose-600 hover:bg-rose-50">Eliminar</button>` : '',
      ].join('');

      return `
        <li class="flex flex-wrap items-center gap-3 px-4 py-3">
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-slate-900">${esc(nombreCompleto(r))}${r.es_yo ? ' <span class="text-xs font-normal text-slate-500">(vos)</span>' : ''}</p>
            <p class="truncate text-xs text-slate-500">${esc(r.email)}${ultimo}</p>
          </div>
          ${r.admin ? chip('Admin', 'verde') : ''}${estado}
          <div class="flex flex-wrap gap-2">${botones}</div>
        </li>`;
    },
  });

  document.getElementById('lista').addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-uaccion]');
    if (!btn) return;

    const id = Number(btn.dataset.id);
    const reenviar = btn.dataset.uaccion === 'reenviar';
    btn.disabled = true;

    const r = reenviar
      ? await AdminCrud.api('/api/usuarios/reenviar', { id })
      : await AdminCrud.api('/api/usuarios/estado', { id, activo: Number(btn.dataset.activo) });

    aviso(r.ok
      ? (reenviar ? 'Invitación reenviada.' : 'Estado actualizado.')
      : (r.message ?? 'No se pudo completar la acción.'));
    crud.recargar();
  });
})();
</script>