<?php
$campo = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30';
$label = 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500';
$activo = 'destacados';
require __DIR__ . '/_menu.php';
?>
<main id="contenido" tabindex="-1" class="mx-auto max-w-4xl px-4 py-8">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Destacados de <?= htmlspecialchars($nombreTorneo ?? '', ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="text-sm text-slate-500">Contenido con foto y titular asociado a este torneo.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <?php if (!empty($idTorneo)): ?>
            <a href="/admin/torneos?torneo=<?= (int) $idTorneo ?>"
                class="inline-flex min-h-10 items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                &larr; Volver al torneo
            </a>
            <?php endif; ?>
            <button id="btn-nuevo" type="button"
                class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">
                + Nuevo destacado
            </button>
        </div>
    </div>

    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white" aria-labelledby="titulo-lista">
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
            <h2 id="titulo-lista" class="text-sm font-semibold text-slate-700">Listado</h2>
            <span id="contador" class="text-xs text-slate-500"></span>
        </div>
        <ul id="lista" class="divide-y divide-slate-100"></ul>
        <p id="vacio" class="hidden p-8 text-center text-sm text-slate-500">Todavía no hay destacados.</p>
    </section>
</main>

<dialog id="dlg-form" aria-labelledby="form-titulo"
    class="m-auto max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-0 shadow-xl backdrop:bg-slate-900/60">
    <form id="form" novalidate>
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
            <h2 id="form-titulo" class="text-lg font-bold text-slate-900">Nuevo destacado</h2>
            <button type="button" data-cerrar aria-label="Cerrar"
                class="text-2xl leading-none text-slate-400 hover:text-slate-600">&times;</button>
        </div>

        <div class="space-y-5 px-6 py-5">
            <div id="alertas" role="alert"></div>
            
            <input type="hidden" id="f-id_torneo" name="id_torneo">

            <div>
                <label for="f-titulo" class="<?= $label ?>">Titular</label>
                <input type="text" id="f-titulo" name="titulo" required maxlength="150" autocomplete="off" class="<?= $campo ?>">
            </div>
            <div>
                <label for="f-bajada" class="<?= $label ?>">Bajada (opcional)</label>
                <input type="text" id="f-bajada" name="bajada" maxlength="255" autocomplete="off" class="<?= $campo ?>">
            </div>
            <div>
                <label for="f-cuerpo" class="<?= $label ?>">Texto completo (opcional)</label>
                <textarea id="f-cuerpo" name="cuerpo" rows="6" maxlength="10000" class="<?= $campo ?>"></textarea>
                <p class="mt-1 text-xs text-slate-500">Deja una línea en blanco entre párrafos.</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="f-tipo" class="<?= $label ?>">Se refiere a</label>
                    <select id="f-tipo" name="tipo" class="<?= $campo ?>">
                        <option value="">Nota libre</option>
                        <option value="partido">Partido</option>
                        <option value="equipo">Equipo</option>
                    </select>
                </div>
                <div id="ref-bloque" class="hidden">
                    <label for="f-ref-select" class="<?= $label ?>">Elemento</label>
                    <select id="f-ref-select" class="<?= $campo ?>"></select>
                    <input type="hidden" id="f-id_referencia" name="id_referencia">
                </div>
                <div>
                    <label for="f-orden" class="<?= $label ?>">Orden</label>
                    <input type="number" id="f-orden" name="orden" value="0" class="<?= $campo ?>">
                    <p class="mt-1 text-xs text-slate-500">0 sale primero; después 1, 2…</p>
                </div>
                <div>
                    <label for="f-activo" class="<?= $label ?>">Estado</label>
                    <select id="f-activo" name="activo" class="<?= $campo ?>">
                        <option value="1">Publicado</option>
                        <option value="0">Oculto</option>
                    </select>
                </div>
            </div>

            <p id="foto-nota" class="text-xs text-slate-500">Guarda el destacado primero; después podrás subir la foto.</p>

            <fieldset id="foto-bloque" class="hidden rounded-lg border border-slate-200 px-4 py-3">
                <legend class="<?= $label ?> px-1">Foto</legend>
                <div class="flex flex-wrap items-center gap-4">
                    <img id="foto-img" src="" alt="Foto actual del destacado" hidden
                        class="h-24 w-32 rounded-lg border border-slate-200 object-cover">
                    <p id="foto-vacio" class="text-sm text-slate-500">Sin foto</p>
                    <div class="min-w-0 flex-1 space-y-2">
                        <label for="foto-archivo" class="sr-only">Elegir imagen</label>
                        <input type="file" id="foto-archivo" accept="image/png,image/webp,image/jpeg"
                            aria-describedby="foto-ayuda"
                            class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                        <p id="foto-ayuda" class="text-xs text-slate-500">PNG, WebP o JPG, hasta 2 MB. Mejor horizontal.</p>
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

<dialog id="dlg-borrar" aria-labelledby="borrar-titulo" aria-describedby="borrar-texto"
    class="m-auto w-full max-w-md rounded-2xl bg-white p-0 shadow-xl backdrop:bg-slate-900/60">
    <div class="space-y-3 px-6 py-5">
        <h2 id="borrar-titulo" class="text-lg font-bold text-slate-900">Eliminar destacado</h2>
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

    const TORNEO = <?= (int) ($idTorneo ?? 0) ?>;

    let crud = null;

    const foto = AdminCrud.imagen({
        tipo: 'destacado',
        prefijo: 'foto',
        campo: 'imagen',
        recargar: () => crud.recargar(),
        textos: { subida: 'Foto actualizada.', quitada: 'Foto quitada.' },
    });

    const tipoSel = document.getElementById('f-tipo');
const refBloque = document.getElementById('ref-bloque');
const refSel = document.getElementById('f-ref-select');
const refHid = document.getElementById('f-id_referencia');
const cache = {};

const pedir = async (url) => {
    const r = await fetch(url, { headers: { Accept: 'application/json' } });
    const j = await r.json();
    return j.ok ? j.data : [];
};

    async function cargarOpciones(tipo, valor) {
        refSel.replaceChildren();
        refHid.value = valor ? String(valor) : '';
        refBloque.classList.toggle('hidden', !tipo);
        if (!tipo) { refHid.value = ''; return; }

        cache.equipos ??= await pedir('/api/equipos');
        const equipos = new Map(cache.equipos.map((e) => [Number(e.id), e.nombre]));

        refSel.add(new Option('Elige…', ''));

        if (tipo === 'equipo') {
            [...cache.equipos]
                .sort((a, b) => a.nombre.localeCompare(b.nombre))
                .forEach((e) => refSel.add(new Option(e.nombre, e.id)));
        } else if (tipo === 'partido') {
            cache.torneos ??= await pedir('/api/torneos');
            cache.partidos ??= await pedir('/api/partidos');
            const nombreTorneo = new Map(cache.torneos.map((t) => [Number(t.id), t.nombre]));

            const grupos = new Map();
            [...cache.partidos]
                .sort((a, b) => String(b.fecha_hora ?? '').localeCompare(String(a.fecha_hora ?? '')) || b.id - a.id)
                .forEach((p) => {
                    const idT = Number(p.id_torneo);
                    if (!grupos.has(idT)) {
                        const g = document.createElement('optgroup');
                        g.label = nombreTorneo.get(idT) ?? `Torneo ${idT}`;
                        grupos.set(idT, g);
                    }
                    const fecha = p.fecha_hora ? ` · ${String(p.fecha_hora).slice(0, 10)}` : '';
                    const txt = `${equipos.get(Number(p.id_equipo_local)) ?? '?'} vs ${equipos.get(Number(p.id_equipo_visitante)) ?? '?'}${fecha}`;
                    grupos.get(idT).append(new Option(txt, p.id));
                });
            grupos.forEach((g) => refSel.append(g));
        }

        refSel.value = valor ? String(valor) : '';
        refHid.value = refSel.value;
    }

    tipoSel.addEventListener('change', () => cargarOpciones(tipoSel.value, ''));
    refSel.addEventListener('change', () => { refHid.value = refSel.value; });

    crud = AdminCrud.iniciar({
        api: '/api/destacados',
        singular: 'destacado',
        reabrirAlCrear: true,
        consulta: () => (TORNEO ? 'id_torneo=' + TORNEO : ''),
        campos: ['titulo', 'bajada', 'cuerpo', 'tipo', 'id_referencia', 'orden', 'activo', 'id_torneo'],
        etiqueta: (r) => r.titulo + (Number(r.activo) === 1 ? '' : ' (oculto)'),
        miniatura: (r) => r.imagen,
        marcador: () => `<span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-600" aria-hidden="true">${esc('★')}</span>`,
        pregunta: (r) => `¿Eliminar «${r.titulo}»?`,
        avisoBorrado: () => 'Se borrará también su foto. Esta acción no se puede deshacer.',
        alAbrir: (form, registro) => {
            foto.abrir(registro);
            cargarOpciones(tipoSel.value, registro?.id_referencia ?? '');
            document.getElementById('f-id_torneo').value = registro?.id_torneo ?? (TORNEO || '');
        },
    });
})();
</script>