(() => {
  'use strict';

  const raiz = document.getElementById('resumen');
  if (!raiz) return;

  const partido = JSON.parse(raiz.dataset.partido);

  const TIPOS = {
    gol:              { txt: 'Gol',            cls: 'bg-emerald-100 text-emerald-700' },
    gol_penal:        { txt: 'Gol de penal',   cls: 'bg-emerald-100 text-emerald-700' },
    autogol:          { txt: 'Autogol',        cls: 'bg-orange-100 text-orange-700' },
    penal_fallado:    { txt: 'Penal fallado',  cls: 'bg-slate-200 text-slate-700' },
    tarjeta_amarilla: { txt: 'Amarilla',       cls: 'bg-yellow-100 text-yellow-800' },
    doble_amarilla:   { txt: 'Doble amarilla', cls: 'bg-orange-100 text-orange-800' },
    tarjeta_roja:     { txt: 'Roja',           cls: 'bg-rose-100 text-rose-700' },
    cambio:           { txt: 'Cambio',         cls: 'bg-sky-100 text-sky-700' },
  };
  const ESTADOS = {
    programado: 'Programado', en_juego: 'En juego', finalizado: 'Finalizado',
    suspendido: 'Suspendido', cancelado: 'Cancelado',
  };
  const POS = { PT: 'PT', DEF: 'DEF', MED: 'MED', DEL: 'DEL' };
  const POS_ORDEN = { PT: 0, DEF: 1, MED: 2, DEL: 3 };

  let equipos = [], torneos = [], jugadores = [], canchas = [], arbitros = [];
  let plantel = [], alineacion = [], eventos = [];

  const $ = (id) => document.getElementById(id);
  const toastEl = $('toast');

  // ---------- Utilidades ----------
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  const nombreEquipo = (id) => equipos.find((e) => e.id === id)?.nombre ?? '—';

  const nombreJugador = (id) => {
    const j = jugadores.find((x) => x.id === id);
    if (!j) return 'Jugador desconocido';
    return [j.apellido, j.nombre].map((s) => String(s ?? '').trim()).filter(Boolean).join(', ');
  };

  const nombrePersona = (p) =>
    p ? [p.nombre, p.apellido].map((s) => String(s ?? '').trim()).filter(Boolean).join(' ') : '';

  const minutoTxt = (ev) => `${ev.minuto}'` + (ev.minuto_extra ? `+${ev.minuto_extra}` : '');
  const tiempo = (ev) => ev.minuto * 1000 + (ev.minuto_extra ?? 0);

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

  function toast(msg) {
    toastEl.textContent = msg;
    toastEl.classList.remove('hidden');
    setTimeout(() => toastEl.classList.add('hidden'), 3500);
  }

  const mensajes = (r) =>
    r.alertas ? Object.values(r.alertas).flat().join(' ') : (r.message ?? 'No se pudo completar la acción.');

  // ---------- Encabezado ----------
  function encabezado() {
    const torneo = torneos.find((t) => t.id === partido.id_torneo)?.nombre ?? '';
    $('meta-torneo').textContent =
      [torneo, partido.jornada ? `Jornada ${partido.jornada}` : null].filter(Boolean).join(' · ');

    $('eq-local').textContent = nombreEquipo(partido.id_equipo_local);
    $('eq-visitante').textContent = nombreEquipo(partido.id_equipo_visitante);
    $('marcador').textContent = `${partido.goles_local} - ${partido.goles_visitante}`;
    $('estado').textContent = ESTADOS[partido.estado] ?? partido.estado;

    const fecha = partido.fecha_hora
      ? new Date(partido.fecha_hora.replace(' ', 'T')).toLocaleString('es-UY', {
          weekday: 'short', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit',
        })
      : 'Sin fecha';
    const cancha = canchas.find((c) => c.id === partido.id_cancha)?.nombre ?? 'Cancha sin asignar';
    const arbitro = nombrePersona(arbitros.find((a) => a.id === partido.id_arbitro)) || 'Árbitro sin asignar';

    $('meta-logistica').textContent = [fecha, cancha, arbitro].join(' · ');
    document.title = `${nombreEquipo(partido.id_equipo_local)} vs ${nombreEquipo(partido.id_equipo_visitante)}`;
  }

  // ---------- Línea de tiempo ----------
  function descripcion(ev) {
    if (ev.tipo === 'cambio') {
      return `Sale <b>${esc(nombreJugador(ev.id_jugador))}</b> · Entra <b>${esc(nombreJugador(ev.id_jugador_entra))}</b>`;
    }
    let t = `<b>${esc(nombreJugador(ev.id_jugador))}</b>`;
    if (ev.id_asistente) t += ` <span class="text-slate-500">(asist. ${esc(nombreJugador(ev.id_asistente))})</span>`;
    if (ev.detalle) t += ` <span class="text-slate-500">— ${esc(ev.detalle)}</span>`;
    return t;
  }

  function renderEventos() {
    const ordenados = [...eventos].sort((a, b) => tiempo(a) - tiempo(b) || a.id - b.id);

    if (!ordenados.length) {
      $('eventos').innerHTML = '<p class="py-6 text-center text-sm text-slate-400">Todavía no hay eventos.</p>';
      return;
    }

    $('eventos').innerHTML = `<ul class="divide-y divide-slate-100">${ordenados.map((ev) => {
      const t = TIPOS[ev.tipo] ?? { txt: ev.tipo, cls: 'bg-slate-100 text-slate-700' };
      return `
        <li class="flex items-center gap-3 py-2.5">
          <span class="w-14 shrink-0 text-sm font-bold text-slate-700">${minutoTxt(ev)}</span>
          <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold ${t.cls}">${t.txt}</span>
          <span class="min-w-0 flex-1 text-sm text-slate-800">${descripcion(ev)}
            <span class="block text-xs text-slate-400">${esc(nombreEquipo(ev.id_equipo))}</span>
          </span>
          <button data-accion="borrar" data-id="${ev.id}"
            class="rounded-lg border border-rose-200 px-2.5 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50">Borrar</button>
        </li>`;
    }).join('')}</ul>`;
  }

  // ---------- Alineaciones con marcas ----------
  function marcas(idJugador) {
    const m = [];
    for (const ev of [...eventos].sort((a, b) => tiempo(a) - tiempo(b))) {
      if (ev.id_jugador === idJugador) {
        if (ev.tipo === 'gol' || ev.tipo === 'gol_penal') m.push(['Gol ' + minutoTxt(ev), 'bg-emerald-100 text-emerald-700']);
        else if (ev.tipo === 'autogol') m.push(['Autogol ' + minutoTxt(ev), 'bg-orange-100 text-orange-700']);
        else if (ev.tipo === 'penal_fallado') m.push(['Penal fallado ' + minutoTxt(ev), 'bg-slate-200 text-slate-700']);
        else if (ev.tipo === 'tarjeta_amarilla') m.push(['Amarilla ' + minutoTxt(ev), 'bg-yellow-100 text-yellow-800']);
        else if (ev.tipo === 'doble_amarilla') m.push(['Doble amarilla ' + minutoTxt(ev), 'bg-orange-100 text-orange-800']);
        else if (ev.tipo === 'tarjeta_roja') m.push(['Roja ' + minutoTxt(ev), 'bg-rose-100 text-rose-700']);
        else if (ev.tipo === 'cambio') m.push(['Sale ' + minutoTxt(ev), 'bg-sky-100 text-sky-700']);
      }
      if (ev.tipo === 'cambio' && ev.id_jugador_entra === idJugador) {
        m.push(['Entra ' + minutoTxt(ev), 'bg-sky-100 text-sky-700']);
      }
    }
    return m.map(([t, c]) => `<span class="rounded-full px-2 py-0.5 text-xs font-semibold ${c}">${esc(t)}</span>`).join(' ');
  }

  function infoPlantel(idEquipo, idJugador) {
    return plantel.find((p) => p.id_equipo === idEquipo && p.id_jugador === idJugador);
  }

  function renderAlineacion(destino, idEquipo, rol) {
    const filas = alineacion
      .filter((a) => a.id_equipo === idEquipo)
      .map((a) => ({ ...a, pe: infoPlantel(idEquipo, a.id_jugador) }));

    const orden = (a, b) =>
      (POS_ORDEN[a.pe?.posicion] ?? 9) - (POS_ORDEN[b.pe?.posicion] ?? 9) ||
      (a.pe?.numero_camiseta ?? 999) - (b.pe?.numero_camiseta ?? 999);

    const fila = (a) => `
      <li class="flex items-center gap-3 py-2">
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-slate-700">${a.pe?.numero_camiseta ?? '–'}</span>
        <span class="min-w-0 flex-1 text-sm text-slate-900">${esc(nombreJugador(a.id_jugador))}
          <span class="text-xs text-slate-400">${POS[a.pe?.posicion] ?? ''}</span></span>
        <span class="flex flex-wrap justify-end gap-1">${marcas(a.id_jugador)}</span>
      </li>`;

    const bloque = (titulo, items) => `
      <h3 class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">${titulo} (${items.length})</h3>
      ${items.length
        ? `<ul class="divide-y divide-slate-100">${items.map(fila).join('')}</ul>`
        : '<p class="py-2 text-sm text-slate-400">Sin jugadores.</p>'}`;

    $(destino).innerHTML = `
      <section class="rounded-xl border border-slate-200 bg-white">
        <header class="border-b border-slate-200 px-4 py-3">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">${rol}</p>
          <h2 class="text-lg font-bold text-slate-900">${esc(nombreEquipo(idEquipo))}</h2>
        </header>
        <div class="px-4 pb-4">
          ${bloque('Titulares', filas.filter((a) => a.condicion === 'titular').sort(orden))}
          ${bloque('Suplentes', filas.filter((a) => a.condicion === 'suplente').sort(orden))}
        </div>
      </section>`;
  }

  // ---------- Formulario ----------
  const f = {
    equipo: $('f-equipo'), tipo: $('f-tipo'), jugador: $('f-jugador'), entra: $('f-entra'),
    asistente: $('f-asistente'), minuto: $('f-minuto'), extra: $('f-extra'), detalle: $('f-detalle'),
  };

  function opcionesJugadores(idEquipo, soloSuplentes = false) {
    return alineacion
      .filter((a) => a.id_equipo === idEquipo && (!soloSuplentes || a.condicion === 'suplente'))
      .map((a) => ({ a, pe: infoPlantel(idEquipo, a.id_jugador) }))
      .sort((x, y) => (x.pe?.numero_camiseta ?? 999) - (y.pe?.numero_camiseta ?? 999))
      .map(({ a, pe }) =>
        `<option value="${a.id_jugador}">${pe?.numero_camiseta ?? '–'} · ${esc(nombreJugador(a.id_jugador))} (${a.condicion === 'titular' ? 'T' : 'S'})</option>`)
      .join('');
  }

  function llenarJugadores() {
    const idEquipo = Number(f.equipo.value);
    f.jugador.innerHTML = opcionesJugadores(idEquipo);
    f.entra.innerHTML = opcionesJugadores(idEquipo, true);
    f.asistente.innerHTML = '<option value="">Sin asistente</option>' + opcionesJugadores(idEquipo);
  }

  function actualizarCampos() {
    const tipo = f.tipo.value;
    $('w-entra').classList.toggle('hidden', tipo !== 'cambio');
    $('w-asistente').classList.toggle('hidden', tipo !== 'gol');
    $('f-jugador-label').textContent = tipo === 'cambio' ? 'Sale' : 'Jugador';
  }

  function iniciarFormulario() {
    f.equipo.innerHTML = `
      <option value="${partido.id_equipo_local}">${esc(nombreEquipo(partido.id_equipo_local))} (local)</option>
      <option value="${partido.id_equipo_visitante}">${esc(nombreEquipo(partido.id_equipo_visitante))} (visitante)</option>`;
    f.tipo.innerHTML = Object.entries(TIPOS).map(([v, t]) => `<option value="${v}">${t.txt}</option>`).join('');
    llenarJugadores();
    actualizarCampos();
  }

  f.equipo.addEventListener('change', llenarJugadores);
  f.tipo.addEventListener('change', actualizarCampos);

  $('form-evento').addEventListener('submit', async (e) => {
    e.preventDefault();
    const tipo = f.tipo.value;

    const body = {
      id_partido: partido.id,
      id_equipo: Number(f.equipo.value),
      id_jugador: Number(f.jugador.value),
      tipo,
      minuto: f.minuto.value === '' ? null : Number(f.minuto.value),
    };
    if (f.extra.value !== '') body.minuto_extra = Number(f.extra.value);
    if (tipo === 'cambio') body.id_jugador_entra = Number(f.entra.value);
    if (tipo === 'gol' && f.asistente.value) body.id_asistente = Number(f.asistente.value);
    if (f.detalle.value.trim()) body.detalle = f.detalle.value.trim();

    const btn = e.submitter;
    if (btn) btn.disabled = true;
    const r = await api('/api/eventos/guardar', body);
    if (btn) btn.disabled = false;

    if (!r.ok) { toast(mensajes(r)); return; }

    f.minuto.value = '';
    f.extra.value = '';
    f.detalle.value = '';
    await recargarEventos();
  });

  // ---------- Borrado con protección (solo en el navegador) ----------
  function motivoNoBorrar(ev) {
    const otros = eventos.filter((x) => x.id !== ev.id);

    if (ev.tipo === 'tarjeta_amarilla' &&
        otros.some((x) => x.tipo === 'doble_amarilla' && x.id_jugador === ev.id_jugador)) {
      return 'Primero borrá la doble amarilla de este jugador.';
    }
    if (ev.tipo === 'cambio') {
      const entra = ev.id_jugador_entra;
      if (otros.some((x) => x.id_jugador === entra || x.id_asistente === entra || x.id_jugador_entra === entra)) {
        return 'El jugador que entró ya tiene eventos. Borralos primero.';
      }
    }
    return null;
  }

  raiz.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-accion="borrar"]');
    if (!btn || btn.disabled) return;

    const ev = eventos.find((x) => x.id === Number(btn.dataset.id));
    if (!ev) return;

    const motivo = motivoNoBorrar(ev);
    if (motivo) { toast(motivo); return; }
    if (!confirm('¿Borrar este evento?')) return;

    btn.disabled = true;
    const r = await api('/api/eventos/eliminar', { id: ev.id });
    if (r.ok) await recargarEventos();
    else { toast(mensajes(r)); btn.disabled = false; }
  });

    // ---------- Logística ----------
  const L = {
    estado: $('l-estado'), fecha: $('l-fecha'), cancha: $('l-cancha'), arbitro: $('l-arbitro'),
    acciones: $('l-acciones'),
  };

  const ACCIONES = {
    programado: [['en_juego', 'Iniciar partido', 'bg-emerald-600 text-white hover:bg-emerald-700']],
    en_juego:   [['finalizado', 'Finalizar partido', 'bg-slate-800 text-white hover:bg-slate-900']],
    finalizado: [['en_juego', 'Reabrir partido', 'border border-slate-300 text-slate-700 hover:bg-slate-50']],
  };

  function llenarSelect(select, items, placeholder, seleccionado) {
    select.innerHTML = `<option value="">${placeholder}</option>` +
      items.map((i) => `<option value="${i.id}">${esc(i.texto)}</option>`).join('');
    select.value = String(seleccionado ?? '');
  }

  function renderLogistica() {
    L.estado.innerHTML = Object.entries(ESTADOS)
      .map(([v, t]) => `<option value="${v}">${t}</option>`).join('');
    L.estado.value = partido.estado;
    L.fecha.value = partido.fecha_hora ? partido.fecha_hora.replace(' ', 'T').slice(0, 16) : '';
    llenarSelect(L.cancha, canchas.map((c) => ({ id: c.id, texto: c.nombre })), 'Sin asignar', partido.id_cancha);
    llenarSelect(L.arbitro, arbitros.map((a) => ({ id: a.id, texto: nombrePersona(a) || a.nombre })), 'Sin asignar', partido.id_arbitro);

    L.acciones.innerHTML = (ACCIONES[partido.estado] ?? []).map(([estado, txt, cls]) =>
      `<button type="button" data-estado="${estado}" class="rounded-lg px-4 py-2 text-sm font-semibold ${cls}">${txt}</button>`
    ).join('');
  }

  async function guardarPartido(estado) {
    const body = {
      id: partido.id,
      id_torneo: partido.id_torneo,
      id_fase: partido.id_fase,
      id_grupo: partido.id_grupo ?? '',
      jornada: partido.jornada,
      id_equipo_local: partido.id_equipo_local,
      id_equipo_visitante: partido.id_equipo_visitante,
      goles_local: partido.goles_local,
      goles_visitante: partido.goles_visitante,
      id_cancha: L.cancha.value,
      id_arbitro: L.arbitro.value,
      fecha_hora: L.fecha.value,
      estado,
    };

    const r = await api('/api/partidos/guardar', body);
    if (!r.ok) { toast(mensajes(r)); return false; }

    await recargarPartido();
    renderLogistica();
    toast('Partido actualizado');
    return true;
  }

  $('form-logistica').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = e.submitter;
    if (btn) btn.disabled = true;
    await guardarPartido(L.estado.value);
    if (btn) btn.disabled = false;
  });

    L.acciones.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-estado]');
    if (!btn || btn.disabled) return;

    btn.disabled = true;
    const ok = await guardarPartido(btn.dataset.estado);
    if (!ok) btn.disabled = false;
  });

    // ---------- Finalizar y ver crónica ----------
  function revisarPartido() {
    const faltan = [];
    const avisos = [];

    // Se usan los valores del formulario, porque guardarPartido() los guarda al finalizar
    if (!L.fecha.value) faltan.push('Falta la fecha y hora.');
    if (!L.cancha.value) faltan.push('Falta la cancha.');
    if (!L.arbitro.value) avisos.push('Falta el árbitro.');

    [['local', partido.id_equipo_local], ['visitante', partido.id_equipo_visitante]].forEach(([rol, id]) => {
      const n = alineacion.filter((a) => a.id_equipo === id && a.condicion === 'titular').length;
      if (n < 11) faltan.push(`El ${rol} (${nombreEquipo(id)}) tiene ${n}/11 titulares.`);
    });

    if (!eventos.length) avisos.push('No hay eventos cargados.');

    // El marcador guardado debe coincidir con los goles cargados
    const goles = (idEquipo, idRival) => eventos.filter((e) =>
      (['gol', 'gol_penal'].includes(e.tipo) && e.id_equipo === idEquipo) ||
      (e.tipo === 'autogol' && e.id_equipo === idRival)).length;
    const gl = goles(partido.id_equipo_local, partido.id_equipo_visitante);
    const gv = goles(partido.id_equipo_visitante, partido.id_equipo_local);
    if (gl !== Number(partido.goles_local) || gv !== Number(partido.goles_visitante)) {
      avisos.push(`El marcador guardado (${partido.goles_local}-${partido.goles_visitante}) no coincide con los goles cargados (${gl}-${gv}).`);
    }

    return { faltan, avisos };
  }

  async function finalizarYVerCronica(btn) {
    if (['suspendido', 'cancelado'].includes(partido.estado) &&
        !confirm(`El partido está ${ESTADOS[partido.estado]}. ¿Pasarlo a Finalizado igual?`)) return;

    btn.disabled = true;
    await recargarPartido(); // datos frescos de la base

    const { faltan, avisos } = revisarPartido();
    const lista = [...faltan, ...avisos].map((m) => '• ' + m).join('\n');

    if (lista && !confirm(`Faltan datos por completar:\n\n${lista}\n\n¿Finalizar y ver la crónica igual?`)) {
      btn.disabled = false;
      return;
    }

    // Pasa a Finalizado (y guarda la logística del formulario) si hace falta
    if (partido.estado !== 'finalizado' || L.fecha.value !== (partido.fecha_hora ?? '').replace(' ', 'T').slice(0, 16)) {
      const ok = await guardarPartido('finalizado');
      if (!ok) { btn.disabled = false; return; }
      location.href = '/partido?id_partido=' + partido.id;
    }
    location.href = '/partido?id_partido=' + partido.id;
  }

  function crearBotonFinalizar() {
    $('btn-finalizar-resumen')?.addEventListener('click', (e) => finalizarYVerCronica(e.currentTarget));
  }

  // ---------- Carga ----------
  function renderTodo() {
    renderEventos();
    renderAlineacion('alin-local', partido.id_equipo_local, 'Local');
    renderAlineacion('alin-visitante', partido.id_equipo_visitante, 'Visitante');
  }

    async function recargarPartido() {
    const r = await api('/api/partidos?id_torneo=' + partido.id_torneo);
    const p = (r.data ?? []).find((x) => x.id === partido.id);
    if (p) { Object.assign(partido, p); encabezado(); }
  }

    async function recargarEventos() {
    const r = await api('/api/eventos?id_partido=' + partido.id);
    eventos = r.data ?? [];
    renderTodo();
    await recargarPartido();
  }

  (async () => {
    const [eq, to, ju, ca, ar, pl, al, ev] = await Promise.all([
      api('/api/equipos'),
      api('/api/torneos'),
      api('/api/jugadores'),
      api('/api/canchas'),
      api('/api/arbitros'),
      api('/api/plantel?id_torneo=' + partido.id_torneo),
      api('/api/alineacion?id_partido=' + partido.id),
      api('/api/eventos?id_partido=' + partido.id),
    ]);

    equipos = eq.data ?? [];
    torneos = to.data ?? [];
    jugadores = ju.data ?? [];
    canchas = ca.data ?? [];
    arbitros = ar.data ?? [];
    plantel = pl.data ?? [];
    alineacion = al.data ?? [];
    eventos = ev.data ?? [];

    encabezado();
    iniciarFormulario();
    renderLogistica();
    renderTodo();
    crearBotonFinalizar();
  })();
})();