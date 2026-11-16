(() => {
  'use strict';

  const ESTADOS = {
    programado: { texto: 'Programado', clase: 'bg-sky-100 text-sky-700' },
    en_juego:   { texto: 'En juego',   clase: 'bg-emerald-100 text-emerald-700' },
    finalizado: { texto: 'Finalizado', clase: 'bg-slate-200 text-slate-700' },
    suspendido: { texto: 'Suspendido', clase: 'bg-amber-100 text-amber-700' },
    cancelado:  { texto: 'Cancelado',  clase: 'bg-rose-100 text-rose-700' },
  };

  const cat = { torneos: [], fases: [], grupos: [], equipos: [], canchas: [], arbitros: [] };
  let partidos = [];

  // Filtros que pueden llegar por la URL: /admin/partidos?torneo=9&fase=30
  const url = new URLSearchParams(location.search);
  let faseFiltro = url.get('fase') ?? '';
  let jornadaSel = null; // clave "idFase-jornada" de la jornada que se está viendo

  const $ = (id) => document.getElementById(id);
  const el = {
    lista: $('lista'), vacio: $('vacio'),
    navJor: $('nav-jornada'), jorSel: $('jor-select'), jorAnt: $('jor-ant'), jorSig: $('jor-sig'),
    filtroTorneo: $('filtro-torneo'), filtroEstado: $('filtro-estado'),
    modal: $('modal'), titulo: $('modal-titulo'), form: $('form'), alertas: $('alertas'),
    btnGuardar: $('btn-guardar'), toast: $('toast'),
    id: $('f-id'), torneo: $('f-torneo'), fase: $('f-fase'), grupo: $('f-grupo'),
    local: $('f-local'), visitante: $('f-visitante'),
    golesLocal: $('f-goles-local'), golesVisitante: $('f-goles-visitante'),
    fecha: $('f-fecha'), jornada: $('f-jornada'),
    cancha: $('f-cancha'), arbitro: $('f-arbitro'), estado: $('f-estado'),
  };

  // ---------- Utilidades ----------
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  const nombre = (lista, id) => {
  const x = lista.find((x) => x.id === id);
  return x ? etiqueta(x) : '—';
  };

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

  function etiqueta(i) {
  return i.apellido ? `${i.nombre} ${i.apellido}` : i.nombre;
  }

  function llenarSelect(select, items, placeholder, seleccionado = '') {
    select.innerHTML = '';
    select.add(new Option(placeholder, ''));
    items.forEach((i) => select.add(new Option(etiqueta(i), i.id)));
    select.value = String(seleccionado ?? '');
  }

  function formatearFecha(valor) {
    if (!valor) return 'Sin fecha';
    const d = new Date(valor.replace(' ', 'T'));
    return d.toLocaleString('es-UY', {
      weekday: 'short', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit',
    });
  }

  function toast(msg) {
    el.toast.textContent = msg;
    el.toast.classList.remove('hidden');
    setTimeout(() => el.toast.classList.add('hidden'), 2500);
  }

  // ---------- Carga de datos ----------
  async function cargarCatalogos() {
    const nombres = Object.keys(cat);
    const resultados = await Promise.all(nombres.map((n) => api(`/api/${n}`)));
    nombres.forEach((n, i) => { cat[n] = resultados[i].data ?? []; });

    // Si llega ?torneo=ID en la URL, se bloquea la vista a ese torneo
    const idTorneoUrl = url.get('torneo');
    const torneoActual = idTorneoUrl ? cat.torneos.find((x) => String(x.id) === String(idTorneoUrl)) : null;

    if (!torneoActual) {
      // Sin torneo elegido en la URL, se redirige a Torneos para seleccionarlo desde su card
      location.href = '/admin/torneos';
      return;
    }

    // Solo se permite este torneo específico en el select, sin opción a ningún otro
    el.filtroTorneo.innerHTML = '';
    el.filtroTorneo.add(new Option(etiqueta(torneoActual), torneoActual.id));
    el.filtroTorneo.value = String(torneoActual.id);
    el.filtroTorneo.disabled = true;

    const nombreTitulo = $('nombre-torneo-titulo');
    if (nombreTitulo) {
      nombreTitulo.textContent = `«${etiqueta(torneoActual)}»`;
    }

    const volverTorneo = $('link-volver-torneo');
    if (volverTorneo) volverTorneo.href = `/admin/torneos?torneo=${torneoActual.id}`;
    const tabPartidos = $('tab-torneo-partidos');
    if (tabPartidos) tabPartidos.href = `/admin/partidos?torneo=${torneoActual.id}`;
    const tabPlanteles = $('tab-torneo-planteles');
    if (tabPlanteles) tabPlanteles.href = `/admin/plantel?id_torneo=${torneoActual.id}`;
    const tabInicio = $('tab-torneo-fixture');
    if (tabInicio) tabInicio.href = `/admin/torneos?torneo=${torneoActual.id}`;
  }

  async function cargarPartidos() {
    const params = new URLSearchParams();
    if (el.filtroTorneo.value) params.set('id_torneo', el.filtroTorneo.value);
    if (faseFiltro) params.set('id_fase', faseFiltro);
    if (el.filtroEstado.value) params.set('estado', el.filtroEstado.value);
    const r = await api('/api/partidos?' + params);
    partidos = r.data ?? [];
    avisoFase();
    render();
  }

  // Aviso cuando se está filtrando por una fase concreta
  function avisoFase() {
    let aviso = $('aviso-fase');
    if (!aviso) {
      aviso = document.createElement('p');
      aviso.id = 'aviso-fase';
      aviso.className = 'mb-3 flex flex-wrap items-center gap-2 text-sm text-slate-600';
      el.lista.before(aviso);
    }
    aviso.classList.toggle('hidden', !faseFiltro);
    if (!faseFiltro) return;
    aviso.innerHTML = `Mostrando solo la fase <strong>${esc(nombre(cat.fases, Number(faseFiltro)))}</strong>. `
      + '<button type="button" id="quitar-fase" class="font-semibold text-emerald-700 underline">Ver todo el torneo</button>';
  }

  // ---------- Render ----------
  // Escudo si existe; si no, disco con iniciales y el color primario del equipo.
  function disco(idEquipo) {
    const eq = cat.equipos.find((x) => x.id === idEquipo);
    if (!eq) return '<span class="h-8 w-8 shrink-0 rounded-full bg-slate-100" aria-hidden="true"></span>';

    if (eq.escudo) {
      return `<img src="${esc(eq.escudo)}" alt="" class="h-8 w-8 shrink-0 object-contain">`;
    }

    const txt = String(eq.nombre ?? '').trim();
    const palabras = txt.split(/\s+/).filter(Boolean);
    const iniciales = (palabras.length > 1 ? palabras[0][0] + palabras[1][0] : txt.slice(0, 2)).toUpperCase();
    const fondo = /^#[0-9a-f]{6}$/i.test(eq.color_primario ?? '') ? eq.color_primario : '#e2e8f0';
    const [r, g, b] = [1, 3, 5].map((i) => parseInt(fondo.slice(i, i + 2), 16));
    const texto = (r * 299 + g * 587 + b * 114) / 1000 > 150 ? '#0f172a' : '#ffffff';
    return `<span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-[11px] font-bold" style="background:${fondo};color:${texto}" aria-hidden="true">${esc(iniciales)}</span>`;
  }

  const pad = (n) => String(n).padStart(2, '0');
  const claveDe = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

  function etiquetaDia(clave) {
    if (clave === 'sin-fecha') return 'Sin fecha';
    const hoy = new Date();
    const manana = new Date(hoy.getTime() + 86400000);
    const ayer = new Date(hoy.getTime() - 86400000);
    if (clave === claveDe(hoy)) return 'Hoy';
    if (clave === claveDe(manana)) return 'Mañana';
    if (clave === claveDe(ayer)) return 'Ayer';
    return new Date(clave + 'T00:00:00').toLocaleDateString('es-UY', {
      weekday: 'long', day: 'numeric', month: 'long',
    });
  }

  const ESTADO_FILA = {
    programado: { texto: 'Programado', clase: 'text-sky-600' },
    en_juego:   { texto: 'En juego',   clase: 'text-emerald-600' },
    finalizado: { texto: 'FT',         clase: 'text-slate-500' },
    suspendido: { texto: 'Suspendido', clase: 'text-amber-600' },
    cancelado:  { texto: 'Cancelado',  clase: 'text-rose-600' },
  };

  function fila(p) {
    const est = ESTADO_FILA[p.estado] ?? ESTADO_FILA.programado;
    const local = nombre(cat.equipos, p.id_equipo_local);
    const visitante = nombre(cat.equipos, p.id_equipo_visitante);
    const centro = p.estado === 'programado'
      ? (p.fecha_hora ? p.fecha_hora.slice(11, 16) : 'vs')
      : `${p.goles_local} - ${p.goles_visitante}`;
    const contexto = [
      nombre(cat.torneos, p.id_torneo),
      nombre(cat.fases, p.id_fase),
      p.id_grupo ? nombre(cat.grupos, p.id_grupo) : null,
      p.jornada ? `Jornada ${p.jornada}` : null,
      p.id_cancha ? nombre(cat.canchas, p.id_cancha) : null,
    ].filter(Boolean).join(' · ');
    const btn = 'rounded-md border px-2 py-1 text-xs font-semibold';

    return `
      <article class="px-4 py-3 transition hover:bg-slate-50">
        <div class="flex items-center gap-3">
          <div class="w-16 shrink-0 text-xs font-semibold ${est.clase}">${est.texto}</div>
          <a href="/partido?id_partido=${p.id}" class="grid min-w-0 flex-1 grid-cols-[1fr_auto_1fr] items-center gap-3">
            <span class="flex min-w-0 items-center justify-end gap-2">
              <span class="truncate text-right text-sm font-semibold text-slate-900">${esc(local)}</span>${disco(p.id_equipo_local)}
            </span>
            <span class="min-w-[4rem] rounded-md bg-slate-100 px-2 py-1 text-center text-sm font-bold text-slate-800">${esc(centro)}</span>
            <span class="flex min-w-0 items-center gap-2">
              ${disco(p.id_equipo_visitante)}<span class="truncate text-sm font-semibold text-slate-900">${esc(visitante)}</span>
            </span>
          </a>
        </div>
        <div class="mt-2 flex flex-wrap items-center justify-between gap-2 pl-[4.75rem]">
          <p class="min-w-0 truncate text-xs text-slate-400">${esc(contexto)}</p>
          <div class="flex gap-1">
            <button type="button" data-accion="editar" data-id="${p.id}" aria-label="Configurar partido"
              title="Primer paso: configurar los datos del partido"
              class="${btn} border-slate-300 text-slate-700 hover:bg-slate-50">⚙️ Configuración</button>
            <a href="/admin/alineacion?id_partido=${p.id}" class="${btn} border-slate-300 text-slate-700 hover:bg-slate-50">Alineación</a>
            <a href="/admin/resumen?id_partido=${p.id}" class="${btn} border-emerald-200 text-emerald-700 hover:bg-emerald-50">Resumen</a>
            <button data-accion="eliminar" data-id="${p.id}" class="${btn} border-rose-200 text-rose-600 hover:bg-rose-50">Eliminar</button>
          </div>
        </div>
      </article>`;
  }

    function renderPorDia(lista) {
    const orden = [...lista].sort((a, b) =>
      (a.fecha_hora ?? '9999').localeCompare(b.fecha_hora ?? '9999'));
    const dias = new Map();
    orden.forEach((p) => {
      const clave = p.fecha_hora ? p.fecha_hora.slice(0, 10) : 'sin-fecha';
      if (!dias.has(clave)) dias.set(clave, []);
      dias.get(clave).push(p);
    });

    el.lista.innerHTML = [...dias].map(([clave, items]) => `
      <section>
        <h2 class="mb-2 text-sm font-bold text-slate-700 first-letter:uppercase">${esc(etiquetaDia(clave))}</h2>
        <div class="divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
          ${items.map(fila).join('')}
        </div>
      </section>`).join('');
  }

  const ordenFase = (id) => cat.fases.find((f) => f.id === id)?.orden ?? 0;

  function agruparJornadas(lista) {
    const mapa = new Map();
    lista.forEach((p) => {
      const clave = `${p.id_fase}-${p.jornada ?? 0}`;
      if (!mapa.has(clave)) {
        mapa.set(clave, { clave, id_fase: p.id_fase, jornada: p.jornada ?? 0, items: [] });
      }
      mapa.get(clave).items.push(p);
    });
    return [...mapa.values()].sort((a, b) =>
      ordenFase(a.id_fase) - ordenFase(b.id_fase) || a.id_fase - b.id_fase || a.jornada - b.jornada);
  }

  function renderPorJornada() {
    const jornadas = agruparJornadas(partidos);
    el.navJor.classList.toggle('hidden', jornadas.length === 0);
    if (!jornadas.length) { el.lista.innerHTML = ''; return; }

    // Si la jornada elegida ya no existe (o es la primera vez), se busca la "actual"
    if (!jornadas.some((j) => j.clave === jornadaSel)) {
      const pendiente = jornadas.find((j) =>
        j.items.some((p) => p.estado === 'programado' || p.estado === 'en_juego'));
      jornadaSel = (pendiente ?? jornadas[jornadas.length - 1]).clave;
    }

    const variasFases = new Set(jornadas.map((j) => j.id_fase)).size > 1;
    const etiqueta = (j) =>
      (variasFases ? nombre(cat.fases, j.id_fase) + ' · ' : '') +
      (j.jornada ? `Jornada ${j.jornada}` : 'Sin jornada');

    el.jorSel.innerHTML = jornadas.map((j) =>
      `<option value="${esc(j.clave)}">${esc(etiqueta(j))} (${j.items.length})</option>`).join('');
    el.jorSel.value = jornadaSel;

    const i = jornadas.findIndex((j) => j.clave === jornadaSel);
    el.jorAnt.disabled = i <= 0;
    el.jorSig.disabled = i >= jornadas.length - 1;

    renderPorDia(jornadas[i].items);
  }

  function render() {
    if (el.filtroTorneo.value) {
      renderPorJornada();
    } else {
      el.navJor.classList.add('hidden');
      renderPorDia(partidos);
    }
    el.vacio.classList.toggle('hidden', partidos.length > 0);
  }

  // ---------- Selects en cascada ----------
  function cargarFases(idTorneo, seleccionada = '') {
    const items = idTorneo ? cat.fases.filter((f) => f.id_torneo === Number(idTorneo)) : [];
    llenarSelect(el.fase, items, items.length || !idTorneo ? 'Selecciona una fase' : 'Este torneo no tiene fases', seleccionada);
  }

  function cargarGrupos(idFase, seleccionado = '') {
    const items = idFase ? cat.grupos.filter((g) => g.id_fase === Number(idFase)) : [];
    const fase = cat.fases.find((f) => f.id === Number(idFase));
    const requiereGrupo = fase?.tipo === 'grupos';
    el.grupo.required = requiereGrupo;
    llenarSelect(
      el.grupo,
      items,
      requiereGrupo ? (items.length ? 'Selecciona un grupo' : 'No hay grupos disponibles') : 'Sin grupo',
      seleccionado
    );
  }

  el.torneo.addEventListener('change', () => { cargarFases(el.torneo.value); cargarGrupos(''); });
  el.fase.addEventListener('change', () => cargarGrupos(el.fase.value));

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

  function validarCamposRequeridos() {
    const errores = [];
    const jornada = Number(el.jornada.value);
    const golesLocal = el.golesLocal.value;
    const golesVisitante = el.golesVisitante.value;

    if (!el.torneo.value) errores.push('Selecciona un torneo.');
    if (!el.fase.value) errores.push('Selecciona una fase.');
    const fase = cat.fases.find((f) => f.id === Number(el.fase.value));
    if (fase?.tipo === 'grupos' && !el.grupo.value) {
      errores.push(cat.grupos.some((g) => g.id_fase === fase.id)
        ? 'Selecciona el grupo del partido.'
        : 'Esta fase requiere un grupo, pero no hay grupos disponibles.');
    }
    if (!el.local.value) errores.push('Selecciona el equipo local.');
    if (!el.visitante.value) errores.push('Selecciona el equipo visitante.');
    if (el.local.value && el.local.value === el.visitante.value) {
      errores.push('El equipo local y el visitante deben ser distintos.');
    }
    if (!el.cancha.value) {
      errores.push(cat.canchas.length ? 'Selecciona la cancha.' : 'No hay canchas registradas para seleccionar.');
    }
    if (!el.arbitro.value) {
      errores.push(cat.arbitros.length ? 'Selecciona el árbitro.' : 'No hay árbitros registrados para seleccionar.');
    }
    if (!el.fecha.value) errores.push('Completa la fecha y hora del partido.');
    if (!el.jornada.value || !Number.isInteger(jornada) || jornada < 1) {
      errores.push('Ingresa una jornada válida (1 o mayor).');
    }
    if (golesLocal === '' || !Number.isInteger(Number(golesLocal)) || Number(golesLocal) < 0) {
      errores.push('Ingresa un marcador válido para el equipo local.');
    }
    if (golesVisitante === '' || !Number.isInteger(Number(golesVisitante)) || Number(golesVisitante) < 0) {
      errores.push('Ingresa un marcador válido para el equipo visitante.');
    }

    if (errores.length) {
      mostrarAlertas(errores);
      return false;
    }

    return true;
  }

  const TIPOS_GOL = ['gol', 'gol_penal', 'autogol'];

  function bloquearMarcador(bloquear) {
    el.golesLocal.disabled = bloquear;
    el.golesVisitante.disabled = bloquear;

    let nota = $('nota-marcador');
    if (!nota) {
      nota = document.createElement('p');
      nota.id = 'nota-marcador';
      nota.className = 'hidden text-xs text-slate-500 sm:col-span-3';
      nota.textContent = 'El marcador se calcula con los goles cargados en el resumen del partido.';
      el.golesVisitante.closest('.grid').appendChild(nota);
    }
    nota.classList.toggle('hidden', !bloquear);
  }

  async function comprobarGoles(idPartido) {
    const r = await api('/api/eventos?id_partido=' + idPartido);
    // El modal pudo cerrarse o abrirse con otro partido mientras se consultaba
    if (el.id.value !== String(idPartido)) return;
    const hayGoles = (r.data ?? []).some((ev) => TIPOS_GOL.includes(ev.tipo));
    bloquearMarcador(hayGoles);
  }

  function abrirModal(p = null) {
    const j = el.filtroTorneo.value && !p ? (el.jorSel.value || '') : '';
    const ini = p ?? (el.filtroTorneo.value ? {
      id_torneo: Number(el.filtroTorneo.value),
      id_fase: faseFiltro ? Number(faseFiltro) : (j ? Number(j.split('-')[0]) : undefined),
      jornada: j ? Number(j.split('-')[1]) || 1 : 1,
    } : {});
    limpiarAlertas();
    el.titulo.textContent = p ? 'Editar partido' : 'Nuevo partido';

    const idTorneoUrl = url.get('torneo');
    const torneoActual = idTorneoUrl ? cat.torneos.find((x) => String(x.id) === String(idTorneoUrl)) : null;
    if (torneoActual) {
      el.torneo.innerHTML = '';
      el.torneo.add(new Option(etiqueta(torneoActual), torneoActual.id));
      el.torneo.value = String(torneoActual.id);
      el.torneo.disabled = true;
    } else {
      llenarSelect(el.torneo, cat.torneos, 'Selecciona un torneo', ini.id_torneo);
    }
    cargarFases(ini.id_torneo, ini.id_fase);
    cargarGrupos(ini.id_fase, p?.id_grupo);
    llenarSelect(el.local, cat.equipos, 'Selecciona equipo', p?.id_equipo_local);
    llenarSelect(el.visitante, cat.equipos, 'Selecciona equipo', p?.id_equipo_visitante);
    llenarSelect(el.cancha, cat.canchas, 'Selecciona cancha', p?.id_cancha);
    llenarSelect(el.arbitro, cat.arbitros, 'Selecciona árbitro', p?.id_arbitro);

    el.id.value = p?.id ?? '';
    el.jornada.value = ini.jornada ?? 1;
    el.estado.value = p?.estado ?? 'programado';
    el.golesLocal.value = p?.goles_local ?? 0;
    el.golesVisitante.value = p?.goles_visitante ?? 0;
    el.fecha.value = p?.fecha_hora ? p.fecha_hora.replace(' ', 'T').slice(0, 16) : '';

    bloquearMarcador(false);
    if (p) comprobarGoles(p.id);
    el.modal.classList.remove('hidden');
    el.modal.classList.add('flex');
  }

  function cerrarModal() {
    el.modal.classList.add('hidden');
    el.modal.classList.remove('flex');
  }

  // ---------- Eventos ----------
  $('btn-nuevo').addEventListener('click', () => abrirModal());

  el.modal.addEventListener('click', (e) => {
    if (e.target === el.modal || e.target.closest('[data-cerrar]')) cerrarModal();
  });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrarModal(); });

  // Al cambiar el torneo a mano se descarta el filtro de fase que venía en la URL
    el.filtroTorneo.addEventListener('change', () => { faseFiltro = ''; jornadaSel = null; cargarPartidos(); });
  el.filtroEstado.addEventListener('change', cargarPartidos);

  document.addEventListener('click', (e) => {
    if (e.target.id === 'quitar-fase') { faseFiltro = ''; jornadaSel = null; cargarPartidos(); }
  });

  // ---- Navegación por jornadas ----
  const moverJornada = (d) => {
    const claves = [...el.jorSel.options].map((o) => o.value);
    const i = claves.indexOf(jornadaSel) + d;
    if (i < 0 || i >= claves.length) return;
    jornadaSel = claves[i];
    render();
  };
  el.jorAnt.addEventListener('click', () => moverJornada(-1));
  el.jorSig.addEventListener('click', () => moverJornada(1));
  el.jorSel.addEventListener('change', () => { jornadaSel = el.jorSel.value; render(); });

  el.lista.addEventListener('click', async (e) => {
    
    const btn = e.target.closest('button[data-accion]');
    if (!btn) return;
    const id = Number(btn.dataset.id);

    if (btn.dataset.accion === 'editar') {
      abrirModal(partidos.find((p) => p.id === id));
      return;
    }

    if (!confirm('¿Eliminar este partido? Esta acción no se puede deshacer.')) return;
    const r = await api('/api/partidos/eliminar', { id });
    if (r.ok) { toast('Partido eliminado'); cargarPartidos(); }
    else toast(r.message ?? 'No se pudo eliminar');
  });

  el.form.addEventListener('submit', async (e) => {
    e.preventDefault();
    limpiarAlertas();
    if (!validarCamposRequeridos()) return;
    el.btnGuardar.disabled = true;

    const body = {
      id: el.id.value || undefined,
      id_torneo: el.torneo.value,
      id_fase: el.fase.value,
      id_grupo: el.grupo.value,
      jornada: el.jornada.value,
      id_equipo_local: el.local.value,
      id_equipo_visitante: el.visitante.value,
      id_cancha: el.cancha.value,
      id_arbitro: el.arbitro.value,
      fecha_hora: el.fecha.value,
      estado: el.estado.value,
      goles_local: Number(el.golesLocal.value) || 0,
      goles_visitante: Number(el.golesVisitante.value) || 0,
    };

    const r = await api('/api/partidos/guardar', body);
    el.btnGuardar.disabled = false;

    if (r.ok) {
      const idPartido = Number(r.data?.id ?? body.id);
      if (idPartido > 0) {
        location.href = `/admin/alineacion?id_partido=${idPartido}`;
        return;
      }

      cerrarModal();
      toast('El partido se guardó, pero no se pudo abrir su alineación.');
      cargarPartidos();
      return;
    }

    const mensajes = r.alertas ? Object.values(r.alertas).flat() : [r.message ?? 'No se pudo guardar.'];
    mostrarAlertas(mensajes);
  });

  // ---------- Inicio ----------
  (async () => {
    await cargarCatalogos();
    await cargarPartidos();

    // /admin/partidos?editar=303 abre directamente el modal de ese partido
    const idEditar = Number(url.get('editar'));
    if (idEditar) {
      const p = partidos.find((x) => x.id === idEditar);
      if (p) {
        jornadaSel = `${p.id_fase}-${p.jornada ?? 0}`; // muestra la jornada del partido detrás del modal
        render();
        abrirModal(p);
      }
      // Se limpia la URL para que F5 no reabra el modal
      history.replaceState(null, '', location.pathname + (el.filtroTorneo.value ? `?torneo=${el.filtroTorneo.value}` : ''));
    }
  })();
})();