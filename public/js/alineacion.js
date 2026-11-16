(() => {
  'use strict';

  const raiz = document.getElementById('alineacion');
  if (!raiz) return;

  const partido = JSON.parse(raiz.dataset.partido);
  const MAX_TITULARES = 11;

  const POS = { PT: 'Portero', DEF: 'Defensa', MED: 'Medio', DEL: 'Delantero' };
  const POS_CLASE = {
    PT: 'bg-amber-100 text-amber-700',
    DEF: 'bg-sky-100 text-sky-700',
    MED: 'bg-emerald-100 text-emerald-700',
    DEL: 'bg-rose-100 text-rose-700',
  };
  const POS_ORDEN = { PT: 0, DEF: 1, MED: 2, DEL: 3 };

    const PUESTOS = [
    ['Arquero', ['PT']],
    ['Defensa', ['LI', 'DCI', 'DFC', 'DCD', 'LD']],
    ['Medios', ['MI', 'MCI', 'MC', 'MCD', 'MD']],
    ['Mediapuntas', ['MPI', 'MP', 'MPD']],
    ['Ataque', ['EI', 'DI', 'DC', 'DD', 'ED']],
  ];
  const SUGERIDO = { PT: 'PT', DEF: 'DFC', MED: 'MC', DEL: 'DC' };

  function selectorPuesto(a) {
    const opciones = PUESTOS.map(([grupo, codigos]) =>
      `<optgroup label="${grupo}">${codigos.map((c) =>
        `<option value="${c}" ${a.posicion_campo === c ? 'selected' : ''}>${c}</option>`).join('')}</optgroup>`).join('');
    const sugerido = SUGERIDO[a.posicion] ?? '';
    return `<select data-accion="puesto" data-id="${a.id}" aria-label="Puesto en la cancha"
      class="rounded-lg border border-slate-300 bg-white px-1.5 py-1 text-xs text-slate-700">
      <option value="">${sugerido ? `Puesto (${sugerido}?)` : 'Puesto'}</option>${opciones}</select>`;
  }

  let equipos = [], torneos = [], jugadores = [], plantel = [], alineacion = [];

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

  // ---------- Datos de un equipo ----------
  function datosEquipo(idEquipo) {
    const plantelEq = plantel.filter((p) => p.id_equipo === idEquipo);
    const ocupados = new Set(alineacion.map((a) => a.id_jugador)); // un jugador, una vez por partido

    const enPartido = alineacion
      .filter((a) => a.id_equipo === idEquipo)
      .map((a) => {
        const pe = plantelEq.find((p) => p.id_jugador === a.id_jugador);
        return { ...a, camiseta: pe?.numero_camiseta ?? null, posicion: pe?.posicion ?? null };
      });

    const titulares = enPartido
      .filter((a) => a.condicion === 'titular')
      .sort((a, b) =>
        (POS_ORDEN[a.posicion] ?? 9) - (POS_ORDEN[b.posicion] ?? 9) ||
        (a.camiseta ?? 999) - (b.camiseta ?? 999));

    const suplentes = enPartido
      .filter((a) => a.condicion === 'suplente')
      .sort((a, b) => (a.camiseta ?? 999) - (b.camiseta ?? 999));

    const disponibles = plantelEq
      .filter((p) => !ocupados.has(p.id_jugador))
      .map((p) => ({ id_jugador: p.id_jugador, camiseta: p.numero_camiseta, posicion: p.posicion }))
      .sort((a, b) => (a.camiseta ?? 999) - (b.camiseta ?? 999));

    return { titulares, suplentes, disponibles, sinPlantel: plantelEq.length === 0 };
  }

  // ---------- Render ----------
  const insignia = (pos) => pos
    ? `<span class="rounded-full px-2 py-0.5 text-xs font-semibold ${POS_CLASE[pos]}">${POS[pos]}</span>`
    : '';

  const circulo = (n) =>
    `<span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-bold text-slate-700">${n ?? '–'}</span>`;

  function filaEnPartido(a) {
    const destino = a.condicion === 'titular' ? 'suplente' : 'titular';
    const texto = a.condicion === 'titular' ? 'A suplente' : 'A titular';
    return `
            <li data-fila-jugador="${a.id_jugador}" class="flex items-center gap-3 py-2 transition-colors duration-700">
        ${circulo(a.camiseta)}
        <span class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-900">${esc(nombreJugador(a.id_jugador))}</span>
        ${insignia(a.posicion)}
        ${a.condicion === 'titular' ? selectorPuesto(a) : ''}
        <button data-accion="cambiar" data-id="${a.id}" data-condicion="${destino}"
          class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">${texto}</button>
        <button data-accion="quitar" data-id="${a.id}"
          class="rounded-lg border border-rose-200 px-2.5 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50">Quitar</button>
      </li>`;
  }

  function filaDisponible(d, idEquipo, titularesLlenos) {
    const base = `data-accion="agregar" data-equipo="${idEquipo}" data-jugador="${d.id_jugador}"`;
    return `
      <li class="flex items-center gap-3 py-2">
        ${circulo(d.camiseta)}
        <span class="min-w-0 flex-1 truncate text-sm text-slate-800">${esc(nombreJugador(d.id_jugador))}</span>
        ${insignia(d.posicion)}
        <button ${base} data-condicion="titular" ${titularesLlenos ? 'disabled' : ''}
          class="rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-40">Titular</button>
        <button ${base} data-condicion="suplente"
          class="rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Suplente</button>
      </li>`;
  }

  const lista = (items, vacio) => items.length
    ? `<ul class="divide-y divide-slate-100">${items.join('')}</ul>`
    : `<p class="py-3 text-sm text-slate-400">${vacio}</p>`;

  function renderColumna(destino, idEquipo, rol) {
    const d = datosEquipo(idEquipo);
    const llenos = d.titulares.length >= MAX_TITULARES;

    $(destino).innerHTML = `
      <section class="rounded-xl border border-slate-200 bg-white">
        <header class="border-b border-slate-200 px-4 py-3">
          <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">${rol}</p>
          <h2 class="text-lg font-bold text-slate-900">${esc(nombreEquipo(idEquipo))}</h2>
        </header>

        <div class="space-y-5 px-4 py-4">
          <div>
            <h3 class="flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-slate-500">
              <span>Titulares</span>
              <span class="${llenos ? 'text-emerald-700' : ''}">${d.titulares.length}/${MAX_TITULARES}</span>
            </h3>
            ${lista(d.titulares.map(filaEnPartido), 'Sin titulares todavía.')}
          </div>

          <div>
            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Suplentes (${d.suplentes.length})</h3>
            ${lista(d.suplentes.map(filaEnPartido), 'Sin suplentes.')}
          </div>

          <div class="rounded-lg bg-slate-50 p-3">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Disponibles del plantel (${d.disponibles.length})</h3>
            ${d.sinPlantel
              ? `<p class="py-3 text-sm text-slate-500">Este equipo no tiene plantel en el torneo. <a href="/admin/plantel" class="font-semibold text-emerald-700 hover:underline">Armar plantel</a></p>`
              : lista(d.disponibles.map((x) => filaDisponible(x, idEquipo, llenos)), 'Todos los jugadores del plantel ya están en la alineación.')}
          </div>
        </div>
      </section>`;
  }

  function render() {
    renderColumna('col-local', partido.id_equipo_local, 'Local');
    renderColumna('col-visitante', partido.id_equipo_visitante, 'Visitante');
  }

  function encabezado() {
    $('titulo-partido').textContent =
      `${nombreEquipo(partido.id_equipo_local)} vs ${nombreEquipo(partido.id_equipo_visitante)}`;

    const torneo = torneos.find((t) => t.id === partido.id_torneo)?.nombre ?? '';
    const fecha = partido.fecha_hora
      ? new Date(partido.fecha_hora.replace(' ', 'T')).toLocaleString('es-UY', {
          weekday: 'short', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit',
        })
      : 'Sin fecha';

    $('subtitulo-partido').textContent =
      [torneo, partido.jornada ? `Jornada ${partido.jornada}` : null, fecha].filter(Boolean).join(' · ');
  }

  // ---------- Acciones ----------
    let ultimoAgregado = null; // id_jugador recién agregado como titular

    async function recargar() {
      const r = await api('/api/alineacion?id_partido=' + partido.id);
      alineacion = r.data ?? [];
      render();

      if (ultimoAgregado) {
        const fila = document.querySelector(`[data-fila-jugador="${ultimoAgregado}"]`);
        ultimoAgregado = null;
        if (fila) {
          fila.scrollIntoView({ behavior: 'smooth', block: 'center' });
          fila.classList.add('bg-emerald-50');
          setTimeout(() => fila.classList.remove('bg-emerald-50'), 1800);
          fila.querySelector('select[data-accion="puesto"]')?.focus({ preventScroll: true });
        }
      }
    }

  raiz.addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-accion]');
    if (!btn || btn.disabled) return;
    btn.disabled = true;

    const { accion } = btn.dataset;
    let r;

      if (accion === 'agregar') {
      if (btn.dataset.condicion === 'titular') ultimoAgregado = Number(btn.dataset.jugador);
      r = await api('/api/alineacion/guardar', {
        id_partido: partido.id,
        id_equipo: Number(btn.dataset.equipo),
        id_jugador: Number(btn.dataset.jugador),
        condicion: btn.dataset.condicion,
      });
        } else if (accion === 'cambiar') {
      if (btn.dataset.condicion === 'titular') ultimoAgregado = Number(btn.dataset.id_jugador ?? 0) || null;
      r = await api('/api/alineacion/guardar', {
        id: Number(btn.dataset.id),
        condicion: btn.dataset.condicion,
      });
    } else if (accion === 'quitar') {
      r = await api('/api/alineacion/eliminar', { id: Number(btn.dataset.id) });
    }

    if (r?.ok) await recargar();
    else { toast(mensajes(r ?? {})); btn.disabled = false; }
  });

  raiz.addEventListener('change', async (e) => {
    const sel = e.target.closest('select[data-accion="puesto"]');
    if (!sel) return;
    sel.disabled = true;
    const r = await api('/api/alineacion/guardar', {
      id: Number(sel.dataset.id),
      posicion_campo: sel.value,
    });
    if (r?.ok) await recargar();
    else { toast(mensajes(r ?? {})); sel.disabled = false; }
  });

  // No permite avanzar al resumen hasta completar el mínimo de titulares
  $('btn-finalizar')?.addEventListener('click', (e) => {
    const cuenta = (idEquipo) => alineacion.filter(
      (a) => Number(a.id_equipo) === Number(idEquipo) && a.condicion === 'titular').length;
    const nl = cuenta(partido.id_equipo_local);
    const nv = cuenta(partido.id_equipo_visitante);

    if (nl < MAX_TITULARES || nv < MAX_TITULARES) {
      e.preventDefault();
      const faltantes = [];
      if (nl < MAX_TITULARES) {
        faltantes.push(`${nombreEquipo(partido.id_equipo_local)}: ${nl}/11`);
      }
      if (nv < MAX_TITULARES) {
        faltantes.push(`${nombreEquipo(partido.id_equipo_visitante)}: ${nv}/11`);
      }
      toast(`Para continuar al resumen, completa al menos 11 titulares por equipo. ${faltantes.join(' · ')}`);
    }
  });

  // ---------- Inicio ----------
  (async () => {
    const [eq, to, ju, pl, al] = await Promise.all([
      api('/api/equipos'),
      api('/api/torneos'),
      api('/api/jugadores'),
      api('/api/plantel?id_torneo=' + partido.id_torneo),
      api('/api/alineacion?id_partido=' + partido.id),
    ]);

    equipos = eq.data ?? [];
    torneos = to.data ?? [];
    jugadores = ju.data ?? [];
    plantel = pl.data ?? [];
    alineacion = al.data ?? [];

    encabezado();
    render();
  })();
})();