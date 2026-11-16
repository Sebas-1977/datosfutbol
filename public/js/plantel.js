(() => {
  'use strict';

  const POS = { PT: 'Portero', DEF: 'Defensa', MED: 'Medio', DEL: 'Delantero' };
  const POS_CLASE = {
    PT: 'bg-amber-100 text-amber-700',
    DEF: 'bg-sky-100 text-sky-700',
    MED: 'bg-emerald-100 text-emerald-700',
    DEL: 'bg-rose-100 text-rose-700',
  };

  let torneos = [], equipos = [], jugadores = [], plantelTorneo = [];
  let editando = null; // fila del plantel que se edita
  let elegido = null;  // jugador elegido al agregar

  const $ = (id) => document.getElementById(id);
  const el = {
    torneo: $('sel-torneo'), equipo: $('sel-equipo'), btnAgregar: $('btn-agregar'),
    aviso: $('aviso'), tabla: $('tabla'), cuerpo: $('cuerpo'),
    contador: $('contador'), sinJugadores: $('sin-jugadores'),
    modal: $('modal'), titulo: $('modal-titulo'), form: $('form'), alertas: $('alertas'),
    bloqueAgregar: $('bloque-agregar'), bloqueEditar: $('bloque-editar'),
    nombreEditar: $('nombre-editar'), buscar: $('buscar'),
    resultados: $('resultados'), elegido: $('elegido'),
    camiseta: $('f-camiseta'), posicion: $('f-posicion'),
    btnGuardar: $('btn-guardar'), toast: $('toast'),
  };

  // ---------- Utilidades ----------
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  const norm = (s) => String(s ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

  const nombreCompleto = (j) =>
    [j.apellido, j.nombre].map((s) => String(s ?? '').trim()).filter(Boolean).join(', ');

  const jugadorPorId = (id) => jugadores.find((j) => j.id === id);

  async function api(url, body) {
    const opts = body === undefined ? {} : {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    };
    try {
      const res = await fetch(url, opts);
      return await res.json();
    } catch {
      return { ok: false, message: 'No se pudo conectar con el servidor.' };
    }
  }

  function llenarSelect(select, items, placeholder) {
    select.innerHTML = '';
    select.add(new Option(placeholder, ''));
    items.forEach((i) => select.add(new Option(i.nombre, i.id)));
  }

  function toast(msg) {
    el.toast.textContent = msg;
    el.toast.classList.remove('hidden');
    setTimeout(() => el.toast.classList.add('hidden'), 2500);
  }

  // ---------- Datos ----------
  async function cargarPlantel() {
    if (!el.torneo.value) { plantelTorneo = []; return; }
    const r = await api('/api/plantel?id_torneo=' + el.torneo.value);
    plantelTorneo = r.data ?? [];
  }

  function plantelEquipo() {
    const idEquipo = Number(el.equipo.value);
    return plantelTorneo
      .filter((p) => p.id_equipo === idEquipo)
      .sort((a, b) => (a.numero_camiseta ?? 999) - (b.numero_camiseta ?? 999));
  }

  // ---------- Render ----------
  function fila(p) {
    const j = jugadorPorId(p.id_jugador);
    const pos = p.posicion
      ? `<span class="rounded-full px-2 py-0.5 text-xs font-semibold ${POS_CLASE[p.posicion]}">${POS[p.posicion]}</span>`
      : '<span class="text-xs text-slate-400">Sin posición</span>';

    return `
      <li class="flex items-center gap-3 px-4 py-3">
        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-slate-700">
          ${p.numero_camiseta ?? '–'}
        </span>
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-semibold text-slate-900">${esc(j ? nombreCompleto(j) : 'Jugador desconocido')}</p>
          <p class="truncate text-xs text-slate-500">${esc(j?.pais ?? '')}</p>
        </div>
        ${pos}
        <button data-accion="editar" data-id="${p.id}"
          class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Editar</button>
        <button data-accion="quitar" data-id="${p.id}"
          class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Quitar</button>
      </li>`;
  }

  function render() {
    const listo = Boolean(el.torneo.value && el.equipo.value);
    el.btnAgregar.disabled = !listo;
    el.aviso.classList.toggle('hidden', listo);
    el.tabla.classList.toggle('hidden', !listo);
    if (!listo) return;

    const filas = plantelEquipo();
    el.contador.textContent = `${filas.length} jugador${filas.length === 1 ? '' : 'es'}`;
    el.cuerpo.innerHTML = filas.map(fila).join('');
    el.sinJugadores.classList.toggle('hidden', filas.length > 0);
  }

  // ---------- Búsqueda de jugadores ----------
  function renderResultados() {
    const usados = new Set(plantelTorneo.map((p) => p.id_jugador));
    const q = norm(el.buscar.value).trim();

    const lista = jugadores
      .filter((j) => !usados.has(j.id) && (!q || norm(`${j.nombre} ${j.apellido}`).includes(q)))
      .slice(0, 40);

    if (!lista.length) {
      el.resultados.innerHTML = '<li class="p-3 text-sm text-slate-500">Sin resultados.</li>';
      return;
    }

    el.resultados.innerHTML = lista.map((j) => `
      <li>
        <button type="button" data-jugador="${j.id}"
          class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-slate-50
                 ${elegido?.id === j.id ? 'bg-emerald-50 font-semibold text-emerald-800' : 'text-slate-800'}">
          <span class="truncate">${esc(nombreCompleto(j))}</span>
          <span class="ml-2 shrink-0 text-xs text-slate-400">${esc(j.pais ?? '')}</span>
        </button>
      </li>`).join('');
  }

  el.buscar.addEventListener('input', renderResultados);

  el.resultados.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-jugador]');
    if (!btn) return;
    elegido = jugadorPorId(Number(btn.dataset.jugador));
    el.elegido.textContent = elegido ? 'Elegido: ' + nombreCompleto(elegido) : '';
    renderResultados();
  });

  // ---------- Modal ----------
  function limpiarAlertas() {
    el.alertas.classList.add('hidden');
    el.alertas.innerHTML = '';
  }

  function mostrarAlertas(mensajes) {
    el.alertas.innerHTML = '<ul class="list-disc space-y-1 pl-5">' +
      mensajes.map((m) => `<li>${esc(m)}</li>`).join('') + '</ul>';
    el.alertas.classList.remove('hidden');
  }

  function abrirModal(registro = null) {
    limpiarAlertas();
    editando = registro;
    elegido = null;
    el.elegido.textContent = '';
    el.buscar.value = '';

    el.titulo.textContent = registro ? 'Editar jugador' : 'Agregar jugador';
    el.bloqueAgregar.classList.toggle('hidden', Boolean(registro));
    el.bloqueEditar.classList.toggle('hidden', !registro);

    if (registro) {
      const j = jugadorPorId(registro.id_jugador);
      el.nombreEditar.textContent = j ? nombreCompleto(j) : '—';
    } else {
      renderResultados();
    }

    el.camiseta.value = registro?.numero_camiseta ?? '';
    el.posicion.value = registro?.posicion ?? '';

    el.modal.classList.remove('hidden');
    el.modal.classList.add('flex');
    (registro ? el.camiseta : el.buscar).focus();
  }

  function cerrarModal() {
    el.modal.classList.add('hidden');
    el.modal.classList.remove('flex');
  }

  el.modal.addEventListener('click', (e) => {
    if (e.target === el.modal || e.target.closest('[data-cerrar]')) cerrarModal();
  });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrarModal(); });

  // ---------- Eventos ----------
  el.btnAgregar.addEventListener('click', () => abrirModal());

  el.torneo.addEventListener('change', async () => {
    await cargarPlantel();
    render();
  });
  el.equipo.addEventListener('change', render);

  el.cuerpo.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-accion]');
    if (!btn) return;
    const registro = plantelTorneo.find((p) => p.id === Number(btn.dataset.id));
    if (!registro) return;

    if (btn.dataset.accion === 'editar') {
      abrirModal(registro);
      return;
    }

    const j = jugadorPorId(registro.id_jugador);
    if (!confirm(`¿Quitar a ${j ? nombreCompleto(j) : 'este jugador'} del plantel?`)) return;

    const r = await api('/api/plantel/eliminar', { id: registro.id });
    if (r.ok) {
      toast('Jugador quitado del plantel');
      await cargarPlantel();
      render();
    } else {
      toast(r.message ?? 'No se pudo quitar');
    }
  });

  el.form.addEventListener('submit', async (e) => {
    e.preventDefault();
    limpiarAlertas();

    if (!editando && !elegido) {
      mostrarAlertas(['Elige un jugador de la lista.']);
      return;
    }

    el.btnGuardar.disabled = true;

    const r = await api('/api/plantel/guardar', {
      id: editando?.id,
      id_torneo: el.torneo.value,
      id_equipo: el.equipo.value,
      id_jugador: editando ? editando.id_jugador : elegido.id,
      numero_camiseta: el.camiseta.value,
      posicion: el.posicion.value,
    });

    el.btnGuardar.disabled = false;

    if (r.ok) {
      cerrarModal();
      toast(editando ? 'Plantel actualizado' : 'Jugador agregado');
      await cargarPlantel();
      render();
      return;
    }

    mostrarAlertas(r.alertas ? Object.values(r.alertas).flat() : [r.message ?? 'No se pudo guardar.']);
  });

  // ---------- Inicio ----------
  (async () => {
    const url = new URLSearchParams(location.search);
    const idTorneoUrl = url.get('id_torneo') || url.get('torneo');

    if (!idTorneoUrl) {
      // Sin torneo elegido, redirigir a Torneos
      location.href = '/admin/torneos';
      return;
    }

    const [t, j, eqTorneo] = await Promise.all([
      api('/api/torneos'),
      api('/api/jugadores'),
      api(`/api/torneos/equipos?id_torneo=${encodeURIComponent(idTorneoUrl)}`),
    ]);

    torneos = t.data ?? [];
    jugadores = (j.data ?? []).sort((a, b) =>
      norm(a.apellido || a.nombre).localeCompare(norm(b.apellido || b.nombre)));

    const torneoActual = torneos.find((x) => String(x.id) === String(idTorneoUrl));
    if (!torneoActual) {
      location.href = '/admin/torneos';
      return;
    }

    // Configurar select de torneo bloqueado
    el.torneo.innerHTML = '';
    el.torneo.add(new Option(torneoActual.nombre, torneoActual.id));
    el.torneo.value = String(torneoActual.id);
    el.torneo.disabled = true;

    // Actualizar nombre en encabezado
    const nombreTitulo = $('nombre-torneo-titulo');
    if (nombreTitulo) {
      nombreTitulo.textContent = `«${torneoActual.nombre}»`;
    }

    const volverTorneo = $('link-volver-torneo');
    if (volverTorneo) volverTorneo.href = `/admin/torneos?torneo=${torneoActual.id}`;
    // Actualizar enlaces de pestañas
    const tabPartidos = $('tab-torneo-partidos');
    if (tabPartidos) tabPartidos.href = `/admin/partidos?torneo=${torneoActual.id}`;
    const tabPlanteles = $('tab-torneo-planteles');
    if (tabPlanteles) tabPlanteles.href = `/admin/plantel?id_torneo=${torneoActual.id}`;
    const tabInicio = $('tab-torneo-fixture');
    if (tabInicio) tabInicio.href = `/admin/torneos?torneo=${torneoActual.id}`;

    // Equipos inscriptos en este torneo
    const listadoInscriptos = (eqTorneo.data?.equipos ?? []).filter((x) => x.inscripto);
    equipos = listadoInscriptos.length > 0 ? listadoInscriptos : (eqTorneo.data?.equipos ?? []);

    llenarSelect(el.equipo, equipos, equipos.length > 0 ? 'Selecciona un equipo' : 'No hay equipos inscriptos');

    const idEquipoUrl = url.get('id_equipo');
    if (idEquipoUrl && equipos.some((x) => String(x.id) === String(idEquipoUrl))) {
      el.equipo.value = String(idEquipoUrl);
    } else if (equipos.length === 1) {
      el.equipo.value = String(equipos[0].id);
    }

    await cargarPlantel();
    render();
  })();
})();