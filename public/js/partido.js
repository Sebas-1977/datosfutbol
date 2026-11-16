(() => {
  'use strict';

  const raiz = document.getElementById('cronica');
  if (!raiz) return;

  const partido = JSON.parse(raiz.dataset.partido);

  const ESTADOS = {
    programado: 'Programado', en_juego: 'En juego', finalizado: 'Finalizado',
    suspendido: 'Suspendido', cancelado: 'Cancelado',
  };
  const POS_ORDEN = { PT: 0, DEF: 1, MED: 2, DEL: 3 };

  let equipos = [], torneos = [], jugadores = [], canchas = [], arbitros = [];
  let plantel = [], alineacion = [], eventos = [];

  const $ = (id) => document.getElementById(id);

  // El aviso se anuncia solo a lectores de pantalla (la región debe existir antes de cambiar su texto)
  $('toast')?.setAttribute('role', 'status');

  // ---------- Utilidades ----------
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  async function api(url) {
    try {
      const res = await fetch(url);
      return await res.json();
    } catch {
      return { ok: false, data: [] };
    }
  }

  function toast(msg) {
    const t = $('toast');
    t.textContent = msg;
    t.classList.remove('hidden');
    setTimeout(() => t.classList.add('hidden'), 3000);
  }

  const L = partido.id_equipo_local;
  const V = partido.id_equipo_visitante;
  const rival = (id) => (id === L ? V : L);

  const nombreEquipo = (id) => equipos.find((e) => e.id === id)?.nombre ?? '—';

  const jug = (id) => jugadores.find((x) => x.id === id);

  const seudo = (j) => String(j?.seudonimo ?? '').trim();

  const apellido = (id) => {
    const j = jug(id);
    if (!j) return 'Jugador desconocido';
    return seudo(j) || String(j.apellido ?? '').trim() || String(j.nombre ?? '').trim() || 'Jugador desconocido';
  };
  const nombreCompleto = (id) => {
    const j = jug(id);
    if (!j) return 'Jugador desconocido';
    return seudo(j) || [j.nombre, j.apellido].map((s) => String(s ?? '').trim()).filter(Boolean).join(' ');
  };

  const nombrePersona = (p) =>
    p ? [p.nombre, p.apellido].map((s) => String(s ?? '').trim()).filter(Boolean).join(' ') : '';

  const minutoTxt = (ev) => `${ev.minuto}'` + (ev.minuto_extra ? `+${ev.minuto_extra}` : '');
  // Para lectores de pantalla: "minuto 45 más 2" en lugar de "45 apóstrofo más 2"
  const minutoLeible = (ev) => `minuto ${ev.minuto}` + (ev.minuto_extra ? ` más ${ev.minuto_extra}` : '');
  const tiempo = (ev) => ev.minuto * 1000 + (ev.minuto_extra ?? 0);
  const ordenados = () => [...eventos].sort((a, b) => tiempo(a) - tiempo(b) || a.id - b.id);

  const iniciales = (nombre) => {
  const palabras = String(nombre).split(/\s+/).filter(Boolean);
  const utiles = palabras.filter((p) => !['el', 'la', 'los', 'las'].includes(p.toLowerCase()));
  const base = utiles.length ? utiles : palabras;
  const txt = base.length === 1 ? base[0].slice(0, 2) : base.slice(0, 2).map((w) => w[0]).join('');
  return txt.toUpperCase() || '?';
  };

  // ---------- Escudo o disco con iniciales ----------
  // Decorativo: el nombre del equipo siempre va en texto al lado, por eso alt="" y aria-hidden.
  const escudoDe = (id) => {
    const url = equipos.find((e) => e.id === id)?.escudo;
    // Solo rutas locales (/uploads/...) para no cargar imágenes externas por error
    return typeof url === 'string' && url.startsWith('/') ? url : '';
  };

  const discoIniciales = (id, tam) => `
    <span aria-hidden="true" class="flex ${tam} shrink-0 items-center justify-center rounded-full font-black text-white ring-2 ring-white/30"
          style="background:hsl(${(id * 47) % 360} 50% 38%)">${esc(iniciales(nombreEquipo(id)))}</span>`;

  const disco = (id, tam) => {
    const url = escudoDe(id);
    if (!url) return discoIniciales(id, tam);
    return `<img src="${esc(url)}" alt="" loading="lazy" data-escudo data-id="${id}" data-tam="${esc(tam)}"
                 class="${tam} shrink-0 rounded-full bg-white object-contain p-0.5 ring-2 ring-white/30">`;
  };

  // Si una imagen falla al cargar, se reemplaza por el disco con iniciales.
  // El evento "error" no burbujea, por eso se escucha en fase de captura.
  raiz.addEventListener('error', (e) => {
    const img = e.target;
    if (!(img instanceof HTMLImageElement) || !img.hasAttribute('data-escudo')) return;
    img.outerHTML = discoIniciales(Number(img.dataset.id), img.dataset.tam || 'h-9 w-9 text-xs');
  }, true);

  // ---------- Cabecera ----------
  function cabecera() {
    const torneo = torneos.find((t) => t.id === partido.id_torneo)?.nombre ?? '';
    $('c-torneo').textContent =
      [torneo, partido.jornada ? `Jornada ${partido.jornada}` : null].filter(Boolean).join(' · ');

    const lado = (id) => `${disco(id, 'h-12 w-12 text-base')}
      <p class="text-sm font-bold leading-tight">${esc(nombreEquipo(id))}</p>`;
    $('c-local').innerHTML = lado(L);
    $('c-visitante').innerHTML = lado(V);

    if (partido.estado === 'programado') {
      $('c-marcador').textContent = 'vs';
    } else {
      // Visualmente "2 - 1"; para lector de pantalla "2 a 1"
      $('c-marcador').innerHTML =
        `<span aria-hidden="true">${esc(partido.goles_local)} - ${esc(partido.goles_visitante)}</span>` +
        `<span class="sr-only">${esc(partido.goles_local)} a ${esc(partido.goles_visitante)}</span>`;
    }
    $('c-estado').textContent = ESTADOS[partido.estado] ?? partido.estado;

    const fecha = partido.fecha_hora
      ? new Date(partido.fecha_hora.replace(' ', 'T')).toLocaleString('es-UY', {
          weekday: 'long', day: '2-digit', month: 'long', hour: '2-digit', minute: '2-digit',
        })
      : 'Fecha a confirmar';
    const cancha = canchas.find((c) => c.id === partido.id_cancha)?.nombre;
    const arbitro = nombrePersona(arbitros.find((a) => a.id === partido.id_arbitro));
    $('c-logistica').textContent =
      [fecha, cancha, arbitro ? `Árbitro: ${arbitro}` : null].filter(Boolean).join(' · ');

    const titulo = $('c-titulo');
    if (titulo) titulo.textContent = `${nombreEquipo(L)} contra ${nombreEquipo(V)}`;
    document.title = `${nombreEquipo(L)} vs ${nombreEquipo(V)} | DatosFutbol`;
  }

  // ---------- Goleadores bajo el marcador ----------
  function goleadores(idEquipo) {
    const mapa = new Map();
    for (const ev of ordenados()) {
      const esGol = ev.tipo === 'gol' || ev.tipo === 'gol_penal';
      const esAutogol = ev.tipo === 'autogol';
      if (!esGol && !esAutogol) continue;
      const beneficiado = esAutogol ? rival(ev.id_equipo) : ev.id_equipo;
      if (beneficiado !== idEquipo) continue;

      const clave = `${ev.id_jugador}-${esAutogol ? 'a' : 'g'}`;
      if (!mapa.has(clave)) mapa.set(clave, { id: ev.id_jugador, auto: esAutogol, mins: [] });
      mapa.get(clave).mins.push(minutoTxt(ev) + (ev.tipo === 'gol_penal' ? ' (p)' : ''));
    }
    return [...mapa.values()];
  }

  function renderGoleadores() {
    const html = (idEquipo) => goleadores(idEquipo).map((g) =>
      `<p class="leading-snug"><span aria-hidden="true">⚽</span> <span class="font-semibold text-slate-800">${esc(apellido(g.id))}${g.auto ? ' (e.c.)' : ''}</span>
        <span class="text-slate-600">${esc(g.mins.join(', '))}</span></p>`).join('');
    $('c-goles-local').innerHTML = html(L);
    $('c-goles-visitante').innerHTML = html(V);
  }

  // ---------- Crónica automática ----------
  function parrafos() {
    const nL = nombreEquipo(L), nV = nombreEquipo(V);
    const gl = partido.goles_local, gv = partido.goles_visitante;
    const evs = ordenados();
    const out = [];

    if (partido.estado === 'programado') {
      return [`${nL} y ${nV} se enfrentan en la jornada ${partido.jornada ?? '—'}. Todavía no hay datos del juego.`];
    }
    if (partido.estado === 'suspendido' || partido.estado === 'cancelado') {
      return [`El partido entre ${nL} y ${nV} fue ${partido.estado === 'suspendido' ? 'suspendido' : 'cancelado'}.`];
    }

    const enCurso = partido.estado === 'en_juego';
    if (gl === gv) {
      out.push(enCurso
        ? `${nL} y ${nV} empatan ${gl} a ${gv} por ahora.`
        : `${nL} y ${nV} igualaron ${gl} a ${gv}.`);
    } else {
      const gana = gl > gv ? nL : nV, pierde = gl > gv ? nV : nL;
      const a = Math.max(gl, gv), b = Math.min(gl, gv);
      out.push(enCurso
        ? `${gana} se impone por ${a} a ${b} ante ${pierde} mientras el partido sigue.`
        : `${gana} se impuso ${a} a ${b} ante ${pierde}.`);
    }

    const goles = evs.filter((e) => ['gol', 'gol_penal', 'autogol'].includes(e.tipo));
    if (goles.length) {
      const desc = (e) => {
        const equipo = nombreEquipo(e.tipo === 'autogol' ? rival(e.id_equipo) : e.id_equipo);
        if (e.tipo === 'autogol') return `un gol en contra de ${nombreCompleto(e.id_jugador)} para ${equipo}`;
        const asist = e.id_asistente ? `, con asistencia de ${nombreCompleto(e.id_asistente)}` : '';
        const pen = e.tipo === 'gol_penal' ? ' de penal' : '';
        return `un gol${pen} de ${nombreCompleto(e.id_jugador)}${asist} para ${equipo}`;
      };
      let t = `El marcador se abrió a los ${minutoTxt(goles[0])} con ${desc(goles[0])}.`;
      if (goles.length > 1) {
        const u = goles[goles.length - 1];
        t += ` El último tanto llegó a los ${minutoTxt(u)}: ${desc(u)}.`;
      }
      out.push(t);
    } else if (gl + gv > 0) {
      out.push('Los goles todavía no están detallados en el partido.');
    } else {
      out.push('No hubo goles registrados.');
    }

    const fallados = evs.filter((e) => e.tipo === 'penal_fallado');
    if (fallados.length) {
      out.push(fallados.map((e) =>
        `${nombreCompleto(e.id_jugador)} (${nombreEquipo(e.id_equipo)}) falló un penal a los ${minutoTxt(e)}.`).join(' '));
    }

    const expulsiones = evs.filter((e) => e.tipo === 'tarjeta_roja' || e.tipo === 'doble_amarilla');
    if (expulsiones.length) {
      out.push(expulsiones.map((e) =>
        `${nombreCompleto(e.id_jugador)} (${nombreEquipo(e.id_equipo)}) fue expulsado a los ${minutoTxt(e)}` +
        `${e.tipo === 'doble_amarilla' ? ' por doble amarilla' : ' con roja directa'}.`).join(' '));
    }

    const amarillas = evs.filter((e) => e.tipo === 'tarjeta_amarilla' || e.tipo === 'doble_amarilla').length;
    const cambios = evs.filter((e) => e.tipo === 'cambio').length;
    const extra = [];
    if (amarillas) extra.push(`${amarillas} ${amarillas === 1 ? 'amarilla' : 'amarillas'}`);
    if (cambios) extra.push(`${cambios} ${cambios === 1 ? 'cambio' : 'cambios'}`);
    if (extra.length) out.push(`En el trámite del juego hubo ${extra.join(' y ')}.`);

    return out;
  }

  function renderCronica() {
    $('c-texto').innerHTML = parrafos().map((p) => `<p>${esc(p)}</p>`).join('');
  }

  // ---------- Estadísticas ----------
  function contar(idEquipo, tipos) {
    return eventos.filter((e) => e.id_equipo === idEquipo && tipos.includes(e.tipo)).length;
  }

  function renderStats() {
    const filas = [
      ['Goles', partido.goles_local, partido.goles_visitante],
      ['Amarillas', contar(L, ['tarjeta_amarilla', 'doble_amarilla']), contar(V, ['tarjeta_amarilla', 'doble_amarilla'])],
      ['Rojas', contar(L, ['tarjeta_roja', 'doble_amarilla']), contar(V, ['tarjeta_roja', 'doble_amarilla'])],
      ['Cambios', contar(L, ['cambio']), contar(V, ['cambio'])],
      ['Penales fallados', contar(L, ['penal_fallado']), contar(V, ['penal_fallado'])],
    ];
    const colL = `hsl(${(L * 47) % 360} 50% 38%)`;
    const colV = `hsl(${(V * 47) % 360} 50% 38%)`;

    $('c-stats').innerHTML = filas.map(([nombre, a, b]) => {
      const total = a + b;
      const pa = total ? (a / total) * 100 : 50;
      return `
        <div class="py-2.5">
          <p class="sr-only">${esc(nombre)}: ${esc(nombreEquipo(L))} ${a}, ${esc(nombreEquipo(V))} ${b}</p>
          <div aria-hidden="true">
            <div class="grid grid-cols-[3rem_1fr_3rem] items-center text-sm">
              <span class="text-xl font-bold tabular-nums text-slate-900">${a}</span>
              <span class="text-center text-slate-600">${esc(nombre)}</span>
              <span class="text-right text-xl font-bold tabular-nums text-slate-900">${b}</span>
            </div>
            <div class="mt-1 flex h-1 overflow-hidden rounded-full bg-slate-100">
              ${total ? `<div style="width:${pa}%;background:${colL}"></div><div style="width:${100 - pa}%;background:${colV}"></div>` : ''}
            </div>
          </div>
        </div>`;
    }).join('');
  }

  // ---------- Minuto a minuto ----------
  const ICONO = {
    gol: '⚽', gol_penal: '⚽', autogol: '⚽', penal_fallado: '❌',
    tarjeta_amarilla: '🟨', doble_amarilla: '🟨🟥', tarjeta_roja: '🟥', cambio: '🔁',
  };
  const TXT = {
    gol: 'Gol', gol_penal: 'Gol de penal', autogol: 'Autogol', penal_fallado: 'Penal fallado',
    tarjeta_amarilla: 'Amarilla', doble_amarilla: 'Doble amarilla', tarjeta_roja: 'Roja', cambio: 'Cambio',
  };

  function textoEvento(ev) {
    if (ev.tipo === 'cambio') {
      return `<span class="text-rose-700"><span class="sr-only">Sale </span><span aria-hidden="true">▼</span> ${esc(apellido(ev.id_jugador))}</span>
              <span class="text-emerald-800"><span class="sr-only">Entra </span><span aria-hidden="true">▲</span> ${esc(apellido(ev.id_jugador_entra))}</span>`;
    }
    let t = `<b>${esc(apellido(ev.id_jugador))}</b>`;
    if (ev.id_asistente) t += `<span class="block text-xs text-slate-600">Asistencia: ${esc(apellido(ev.id_asistente))}</span>`;
    if (ev.detalle) t += `<span class="block text-xs text-slate-600">${esc(ev.detalle)}</span>`;
    return t;
  }

  function renderTiempo() {
    const evs = ordenados();
    if (!evs.length) {
      $('c-tiempo').innerHTML = '<p class="py-4 text-center text-sm text-slate-600">Sin eventos registrados.</p>';
      return;
    }
    $('c-tiempo').innerHTML = `<ul class="divide-y divide-slate-100">${evs.map((ev) => {
      const delLocal = ev.id_equipo === L;
      // El lado (izquierda/derecha) indica el equipo solo visualmente: se agrega en texto para lectores
      const bloque = `
        <div class="min-w-0 text-sm ${delLocal ? 'text-right' : 'text-left'}">
          <span class="text-xs font-semibold text-slate-600">${TXT[ev.tipo] ?? esc(ev.tipo)}<span class="sr-only">, ${esc(nombreEquipo(ev.id_equipo))}</span></span>
          <div class="text-slate-800">${textoEvento(ev)}</div>
        </div>`;
      return `
        <li class="grid grid-cols-[1fr_4.5rem_1fr] items-center gap-2 py-2.5">
          ${delLocal ? bloque : '<span></span>'}
          <span class="text-center">
            <span class="block text-lg leading-none" aria-hidden="true">${ICONO[ev.tipo] ?? '•'}</span>
            <span class="text-xs font-bold tabular-nums text-slate-600"><span aria-hidden="true">${esc(minutoTxt(ev))}</span><span class="sr-only">${esc(minutoLeible(ev))}</span></span>
          </span>
          ${delLocal ? '<span></span>' : bloque}
        </li>`;
    }).join('')}</ul>`;
  }

  // ---------- Alineaciones ----------
  const infoPlantel = (idEquipo, idJugador) =>
    plantel.find((p) => p.id_equipo === idEquipo && p.id_jugador === idJugador);

  // Cada marca tiene versión visual (con emoji) y versión para lector de pantalla
  function marcas(idJugador) {
    const m = [];
    for (const ev of ordenados()) {
      const min = minutoTxt(ev);
      const leible = minutoLeible(ev);
      if (ev.id_jugador === idJugador) {
        if (ev.tipo === 'gol' || ev.tipo === 'gol_penal') m.push([`⚽ ${min}`, `Gol, ${leible}`]);
        else if (ev.tipo === 'autogol') m.push([`⚽ (e.c.) ${min}`, `Autogol, ${leible}`]);
        else if (ev.tipo === 'penal_fallado') m.push([`❌ ${min}`, `Penal fallado, ${leible}`]);
        else if (ev.tipo === 'tarjeta_amarilla') m.push(['🟨', `Tarjeta amarilla, ${leible}`]);
        else if (ev.tipo === 'doble_amarilla') m.push(['🟥', `Doble amarilla y expulsión, ${leible}`]);
        else if (ev.tipo === 'tarjeta_roja') m.push(['🟥', `Tarjeta roja, ${leible}`]);
        else if (ev.tipo === 'cambio') m.push([`▼ ${min}`, `Sale, ${leible}`]);
      }
      if (ev.tipo === 'cambio' && ev.id_jugador_entra === idJugador) m.push([`▲ ${min}`, `Entra, ${leible}`]);
    }
    return m.map(([visual, leible]) =>
      `<span class="text-xs text-slate-600"><span aria-hidden="true">${esc(visual)}</span><span class="sr-only">${esc(leible)}. </span></span>`
    ).join(' ');
  }

  function renderAlineacion(destino, idEquipo) {
    const filas = alineacion
      .filter((a) => a.id_equipo === idEquipo)
      .map((a) => ({ ...a, pe: infoPlantel(idEquipo, a.id_jugador) }));

    const orden = (a, b) =>
      (POS_ORDEN[a.pe?.posicion] ?? 9) - (POS_ORDEN[b.pe?.posicion] ?? 9) ||
      (a.pe?.numero_camiseta ?? 999) - (b.pe?.numero_camiseta ?? 999);

    const fila = (a) => `
      <li class="flex items-center gap-3 py-1.5">
        <span class="w-6 shrink-0 text-right text-sm font-bold tabular-nums text-slate-600"><span class="sr-only">Camiseta </span>${esc(a.pe?.numero_camiseta ?? '–')}</span>
        <span class="min-w-0 flex-1 text-sm text-slate-900">${esc(nombreCompleto(a.id_jugador))}
          <span class="text-xs text-slate-600">${esc(a.pe?.posicion ?? '')}</span></span>
        <span class="flex flex-wrap justify-end gap-1.5">${marcas(a.id_jugador)}</span>
      </li>`;

    const bloque = (titulo, items) => `
      <h3 class="mt-4 text-sm font-semibold text-slate-600">${titulo}</h3>
      ${items.length
        ? `<ul class="divide-y divide-slate-100">${items.map(fila).join('')}</ul>`
        : '<p class="py-2 text-sm text-slate-600">Sin jugadores.</p>'}`;

    $(destino).innerHTML = `
      <section class="rounded-xl border border-slate-200 bg-white px-5 pb-5 pt-4">
        <div class="flex items-center gap-3">
          ${disco(idEquipo, 'h-9 w-9 text-xs')}
          <h2 class="text-lg font-bold text-slate-900">${esc(nombreEquipo(idEquipo))}</h2>
        </div>
        ${bloque('Titulares', filas.filter((a) => a.condicion === 'titular').sort(orden))}
        ${bloque('Suplentes', filas.filter((a) => a.condicion === 'suplente').sort(orden))}
      </section>`;
  }

  // ---------- Cancha ----------
  const LINEAS = ['PT', 'DEF', 'MED', 'DEL'];

  const ORDEN_PUESTO = ['PT',
    'LI', 'DCI', 'DFC', 'DCD', 'LD',
    'MI', 'MCI', 'MC', 'MCD', 'MD',
    'MPI', 'MP', 'MPD',
    'EI', 'DI', 'DC', 'DD', 'ED'];

  const LINEA_DE = (c) =>
    c === 'PT' ? 0
    : ['LI', 'DCI', 'DFC', 'DCD', 'LD'].includes(c) ? 1
    : ['MI', 'MCI', 'MC', 'MCD', 'MD'].includes(c) ? 2
    : ['MPI', 'MP', 'MPD'].includes(c) ? 3 : 4;

  function iconos(idJugador) {
    let s = '';
    for (const ev of ordenados()) {
      if (ev.id_jugador === idJugador) {
        if (ev.tipo === 'gol' || ev.tipo === 'gol_penal' || ev.tipo === 'autogol') s += '⚽';
        else if (ev.tipo === 'penal_fallado') s += '❌';
        else if (ev.tipo === 'tarjeta_amarilla') s += '🟨';
        else if (ev.tipo === 'doble_amarilla' || ev.tipo === 'tarjeta_roja') s += '🟥';
        else if (ev.tipo === 'cambio') s += '🔻';
      }
    }
    return s;
  }

  // Líneas del equipo: [PT, DEF, MED, (MP), DEL]
  function lineasDe(idEquipo) {
    const tit = alineacion
      .filter((a) => a.id_equipo === idEquipo && a.condicion === 'titular')
      .map((a) => ({ ...a, pe: infoPlantel(idEquipo, a.id_jugador) }));

    // Con puesto en todos los titulares: se ordena por puesto
    if (tit.length && tit.every((a) => ORDEN_PUESTO.includes(a.posicion_campo))) {
      const lineas = [[], [], [], [], []];
      tit.forEach((a) => lineas[LINEA_DE(a.posicion_campo)].push(a));
      lineas.forEach((l) => l.sort((x, y) =>
        ORDEN_PUESTO.indexOf(x.posicion_campo) - ORDEN_PUESTO.indexOf(y.posicion_campo)));
      // La línea de mediapuntas solo aparece si hay alguien
      return lineas.filter((l, i) => i !== 3 || l.length);
    }

    // Sin puesto: criterio anterior (posición del plantel y número)
    return LINEAS.map((pos) =>
      tit.filter((a) => (LINEAS.includes(a.pe?.posicion) ? a.pe.posicion : 'MED') === pos)
         .sort((a, b) => (a.pe?.numero_camiseta ?? 999) - (b.pe?.numero_camiseta ?? 999)));
  }

  const sistema = (lineas) =>
    lineas.slice(1).map((l) => l.length).filter((n) => n > 0).join('-');

  function renderCancha() {
    const celda = (a, idEquipo) => `
      <div class="flex flex-col items-center text-center">
        <span class="relative flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold text-white ring-2 ring-white"
              style="background:hsl(${(idEquipo * 47) % 360} 50% 38%)">${esc(a.pe?.numero_camiseta ?? '–')}
          <span class="absolute -right-4 -top-2 whitespace-nowrap text-[10px] leading-none">${iconos(a.id_jugador)}</span>
        </span>
        <span class="mt-0.5 max-w-[4.5rem] truncate text-[11px] font-semibold text-white">${esc(apellido(a.id_jugador))}</span>
      </div>`;

    const mitad = (idEquipo, esLocal) => {
      const lineas = lineasDe(idEquipo);
      const orden = esLocal ? lineas : [...lineas].reverse();
      return `<div class="flex w-1/2">${orden.map((l) => {
        const jugadores = esLocal ? l : [...l].reverse();
        return `<div class="flex flex-1 flex-col items-center justify-around">${jugadores.map((a) => celda(a, idEquipo)).join('')}</div>`;
      }).join('')}</div>`;
    };

    // El dibujo es decorativo: la misma información está en la pestaña Alineaciones
    $('c-cancha').innerHTML = `
      <div aria-hidden="true" class="relative flex h-[440px] min-w-[640px] overflow-hidden rounded-lg bg-emerald-700 py-3">
        <div class="pointer-events-none absolute inset-y-0 left-1/2 w-px bg-white/40"></div>
        <div class="pointer-events-none absolute left-1/2 top-1/2 h-24 w-24 -translate-x-1/2 -translate-y-1/2 rounded-full border border-white/40"></div>
        ${mitad(L, true)}${mitad(V, false)}
      </div>`;

    const cab = (id) => `<span class="font-bold text-slate-900">${esc(nombreEquipo(id))}</span>
      <span class="text-slate-600">${esc(sistema(lineasDe(id)))}</span>`;
    $('c-form-local').innerHTML = cab(L);
    $('c-form-visitante').innerHTML = cab(V);
  }

  // ---------- Pestañas ----------
  const tabs = [...document.querySelectorAll('[data-tab]')];
  const paneles = [...document.querySelectorAll('[data-panel]')];

  document.querySelector('[role="tablist"]')?.setAttribute('aria-label', 'Secciones del partido');

  tabs.forEach((t) => {
    t.id = 'tab-' + t.dataset.tab;
    t.setAttribute('aria-controls', 'panel-' + t.dataset.tab);
    // Foco visible (dentro del borde, porque la barra tiene scroll horizontal y recortaría el contorno)
    t.classList.add('focus-visible:outline', 'focus-visible:outline-2', 'focus-visible:-outline-offset-2', 'focus-visible:outline-emerald-600');
  });
  paneles.forEach((p) => {
    p.id = 'panel-' + p.dataset.panel;
    p.setAttribute('role', 'tabpanel');
    p.setAttribute('aria-labelledby', 'tab-' + p.dataset.panel);
    p.tabIndex = 0;
  });

  function activarTab(clave, enfocar = false) {
    tabs.forEach((t) => {
      const on = t.dataset.tab === clave;
      t.classList.toggle('border-emerald-600', on);
      t.classList.toggle('text-emerald-800', on);
      t.classList.toggle('border-transparent', !on);
      t.classList.toggle('text-slate-600', !on);
      t.setAttribute('aria-selected', String(on));
      t.tabIndex = on ? 0 : -1;
      if (on && enfocar) t.focus();
    });
    paneles.forEach((p) => p.classList.toggle('hidden', p.dataset.panel !== clave));
  }

  tabs.forEach((t, i) => {
    t.addEventListener('click', () => activarTab(t.dataset.tab));
    t.addEventListener('keydown', (e) => {
      const destino = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: tabs.length - 1 }[e.key];
      if (destino === undefined) return;
      e.preventDefault();
      activarTab(tabs[(destino + tabs.length) % tabs.length].dataset.tab, true);
    });
  });
  activarTab('cronica');

  // ---------- Botones ----------
  $('btn-imprimir').addEventListener('click', () => window.print());
  $('btn-compartir').addEventListener('click', async () => {
    const url = location.href;
    try {
      if (navigator.share) await navigator.share({ title: document.title, url });
      else { await navigator.clipboard.writeText(url); toast('Enlace copiado'); }
    } catch { /* cancelado por la persona */ }
  });

  // ---------- Carga ----------
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

    cabecera();
    renderGoleadores();
    renderCronica();
    renderStats();
    renderTiempo();
    renderAlineacion('c-alin-local', L);
    renderAlineacion('c-alin-visitante', V);
    renderCancha();
  })();
})();