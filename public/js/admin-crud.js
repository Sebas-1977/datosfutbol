(() => {
  'use strict';

  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) =>
    ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  async function api(url, body) {
    const opts = body === undefined ? {} : {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    };
    try {
      const res = await fetch(url, opts);
      const data = await res.json();
      data.status = res.status; // se guarda el código HTTP para detectar el 404
      return data;
    } catch {
      return { ok: false, message: 'No se pudo conectar con el servidor.' };
    }
  }

  /**
   * cfg: {
   *   api, singular, campos[], etiqueta(r), avisoBorrado(r),
   *   detalle?(r), pregunta?(r),
   *   alIniciar?(form)        -> async, una vez, antes del primer listado
   *   alAbrir?(form, registro) -> al abrir el formulario (registro = null si es alta)
   *   antesDeEliminar?(registro, abrirForm) -> si devuelve true, reemplaza la confirmación de borrado
   *   datosExtra?(form)       -> objeto que se suma a lo que se envía
   *   miniatura?(r)           -> ruta de imagen o null
   *   marcador?(r)            -> HTML del marcador cuando no hay imagen
   *   fila?(r, botones)       -> HTML completo de cada elemento del listado (por ejemplo una tarjeta);
   *                              debe devolver un <li>, e incluir "botones" (Editar y Eliminar ya armados)
   *   reabrirAlCrear?: boolean -> tras crear, abre el registro en edición
   *   consulta?()             -> querystring para el listado (sin "?")
   *   alListar?(respuesta)    -> después de cada listado
   * }
   */
  function iniciar(cfg) {
    const $ = (id) => document.getElementById(id);
    const el = {
      lista: $('lista'), contador: $('contador'), vacio: $('vacio'), btnNuevo: $('btn-nuevo'),
      dlgForm: $('dlg-form'), form: $('form'), titulo: $('form-titulo'),
      alertas: $('alertas'), btnGuardar: $('btn-guardar'),
      dlgBorrar: $('dlg-borrar'), textoBorrar: $('borrar-texto'), btnBorrar: $('btn-borrar'),
      errorBorrar: $('borrar-error'), // opcional
      toast: $('toast'),
    };

    let registros = [];
    let total = 0;
    let turno = 0;
    let editando = null;
    let aBorrar = null;
    let temporizador = null;

    // ---------- Avisos ----------
    function toast(msg) {
      el.toast.textContent = msg;
      el.toast.classList.remove('opacity-0');
      clearTimeout(temporizador);
      temporizador = setTimeout(() => {
        el.toast.classList.add('opacity-0');
        el.toast.textContent = '';
      }, 4000);
    }

    const cajaError = (html) =>
      '<div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">' +
      html + '</div>';

    function limpiarAlertas() {
      el.alertas.innerHTML = '';
    }

    function mostrarAlertas(mensajes) {
      el.alertas.innerHTML = cajaError(
        '<ul class="list-disc space-y-1 pl-5">' +
        mensajes.map((m) => `<li>${esc(m)}</li>`).join('') + '</ul>');
    }

    // ---------- Listado ----------
    function fila(r) {
      const etiqueta = esc(cfg.etiqueta(r));

      const botones = `
          <button type="button" data-accion="editar" data-id="${r.id}" aria-label="Editar ${etiqueta}"
            class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Editar</button>
          <button type="button" data-accion="eliminar" data-id="${r.id}" aria-label="Eliminar ${etiqueta}"
            class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">Eliminar</button>`;

      // Una pantalla puede dibujar su propio elemento (por ejemplo una tarjeta)
      if (cfg.fila) return cfg.fila(r, botones);

      const detalle = cfg.detalle ? cfg.detalle(r) : '';
      const ruta = cfg.miniatura ? cfg.miniatura(r) : null;
      const miniatura = !cfg.miniatura ? '' : ruta
        ? `<img src="${esc(ruta)}" alt="" class="h-10 w-10 shrink-0 rounded object-contain">`
        : cfg.marcador
          ? cfg.marcador(r)
          : '<span class="h-10 w-10 shrink-0 rounded bg-slate-100" aria-hidden="true"></span>';
      return `
        <li class="flex items-center gap-3 px-4 py-3">
          ${miniatura}
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-slate-900">${etiqueta}</p>
            ${detalle ? `<p class="truncate text-xs text-slate-500">${esc(detalle)}</p>` : ''}
          </div>${botones}
        </li>`;
    }

    function render() {
      const n = total > registros.length ? `${registros.length} de ${total}` : String(registros.length);
      el.contador.textContent = `${n} registro${total === 1 ? '' : 's'}`;
      el.lista.innerHTML = registros.map((r) => fila(r)).join('');
      el.vacio.classList.toggle('hidden', registros.length > 0);
    }

    async function cargar() {
      const mio = ++turno;
      const extra = cfg.consulta ? cfg.consulta() : '';
      const r = await api(cfg.api + (extra ? '?' + extra : ''));
      if (mio !== turno) return; // llegó una respuesta más nueva
      if (!r.ok) {
        toast(r.message ?? 'No se pudo cargar el listado.');
        return;
      }
      registros = r.data ?? [];
      total = r.total ?? registros.length;
      render();
      if (cfg.alListar) cfg.alListar(r);
    }

    // ---------- Formulario ----------
    function abrirForm(registro = null) {
      limpiarAlertas();
      editando = registro;
      el.titulo.textContent = registro ? `Editar ${cfg.singular}` : `Nuevo ${cfg.singular}`;
      cfg.campos.forEach((c) => { el.form.elements[c].value = registro?.[c] ?? ''; });
      if (cfg.alAbrir) cfg.alAbrir(el.form, registro);
      el.dlgForm.showModal();
      el.form.elements[cfg.campos[0]].focus();
    }

    el.form.addEventListener('submit', async (e) => {
      e.preventDefault();
      limpiarAlertas();

      const datos = {};
      cfg.campos.forEach((c) => { datos[c] = el.form.elements[c].value.trim(); });

      // El servidor ignora los textos vacíos obligatorios al editar, así que se controlan acá.
      const faltante = cfg.campos.find((c) => el.form.elements[c].required && datos[c] === '');
      if (faltante) {
        const campo = el.form.elements[faltante];
        mostrarAlertas([`Completá el campo «${campo.labels[0]?.textContent ?? faltante}».`]);
        campo.focus();
        return;
      }

      if (cfg.datosExtra) Object.assign(datos, cfg.datosExtra(el.form));

      el.btnGuardar.disabled = true;
      const r = await api(cfg.api + '/guardar', { id: editando?.id, ...datos });
      el.btnGuardar.disabled = false;

      if (r.ok) {
        const nuevo = !editando;
        const idNuevo = r.data?.id;
        el.dlgForm.close();
        await cargar();

        // Tras un alta, reabre el registro en modo edición (para subir imágenes).
        const creado = nuevo && cfg.reabrirAlCrear
          ? (registros.find((x) => Number(x.id) === Number(idNuevo)) ?? r.data)
          : null;
        if (creado) {
          abrirForm(creado);
          toast(`${cfg.singular[0].toUpperCase()}${cfg.singular.slice(1)} creado. Ya podés subir la imagen.`);
          return;
        }

        toast(nuevo ? 'Registro creado' : 'Cambios guardados');
        el.btnNuevo.focus();
        return;
      }

      mostrarAlertas(r.alertas ? Object.values(r.alertas).flat() : [r.message ?? 'No se pudo guardar.']);
    });

    // ---------- Borrado ----------
    el.btnBorrar.addEventListener('click', async () => {
      if (!aBorrar) return;
      el.btnBorrar.disabled = true;
      const r = await api(cfg.api + '/eliminar', { id: aBorrar.id });
      el.btnBorrar.disabled = false;

      if (r.ok) {
        el.dlgBorrar.close();
        aBorrar = null;
        toast('Registro eliminado');
        await cargar();
        el.btnNuevo.focus();
        return;
      }

      // Si el registro ya no existe, se cierra el diálogo y se refresca el listado
      if (r.status === 404) {
        el.dlgBorrar.close();
        aBorrar = null;
        toast('Ese registro ya no existe. Se actualizó el listado.');
        await cargar();
        el.btnNuevo.focus();
        return;
      }

      const msg = r.message ?? 'No se pudo eliminar.';
      if (el.errorBorrar) {
        // El diálogo queda abierto y el mensaje se anuncia con role="alert".
        el.errorBorrar.innerHTML = cajaError(esc(msg));
      } else {
        el.dlgBorrar.close();
        aBorrar = null;
        toast(msg);
      }
    });

    // ---------- Eventos ----------
    el.btnNuevo.addEventListener('click', () => abrirForm());

    el.lista.addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-accion]');
      if (!btn) return;
      const registro = registros.find((r) => r.id === Number(btn.dataset.id));
      if (!registro) return;

      if (btn.dataset.accion === 'editar') {
        abrirForm(registro);
        return;
      }

      if (cfg.antesDeEliminar && cfg.antesDeEliminar(registro, abrirForm)) return;

      aBorrar = registro;
      if (el.errorBorrar) el.errorBorrar.innerHTML = '';
      const pregunta = cfg.pregunta ? cfg.pregunta(registro) : `¿Eliminar a ${cfg.etiqueta(registro)}?`;
      el.textoBorrar.textContent = `${pregunta} ${cfg.avisoBorrado(registro)}`;
      el.dlgBorrar.showModal();
    });

    [el.dlgForm, el.dlgBorrar].forEach((dlg) => {
      dlg.addEventListener('click', (e) => {
        if (e.target === dlg || e.target.closest('[data-cerrar]')) dlg.close();
      });
    });

    (async () => {
      if (cfg.alIniciar) await cfg.alIniciar(el.form);
      await cargar();
    })();

    return {
      recargar: cargar,
      editar: (id) => {
        const registro = registros.find((r) => r.id === Number(id));
        if (registro) abrirForm(registro);
      },
    };
  }

  /**
   * Bloque de imagen reutilizable (subir / quitar) dentro del diálogo.
   * cfg: { tipo, prefijo, campo, recargar(), textos?: { subida, quitada } }
   *   tipo     -> clave de ImagenSubida::DESTINOS ('equipo_escudo', 'cancha', 'jugador', 'arbitro')
   *   prefijo  -> prefijo de los ids del HTML ('escudo' o 'foto')
   *   campo    -> columna del registro que guarda la ruta ('escudo' o 'foto')
   * Devuelve { abrir(registro) }: llamalo desde alAbrir.
   */
  function imagen(cfg) {
    const $ = (id) => document.getElementById(`${cfg.prefijo}-${id}`);
    const ui = {
      bloque: $('bloque'), nota: $('nota'), img: $('img'), vacio: $('vacio'),
      archivo: $('archivo'), subir: $('subir'), quitar: $('quitar'), estado: $('estado'),
    };
    const textos = { subida: 'Imagen actualizada.', quitada: 'Imagen quitada.', ...cfg.textos };
    let actual = null;

    const mostrar = (ruta) => {
      ui.img.hidden = !ruta;
      ui.vacio.hidden = Boolean(ruta);
      ui.quitar.disabled = !ruta;
      if (ruta) ui.img.src = ruta;
    };

    const aviso = (texto, error = false) => {
      ui.estado.textContent = texto;
      ui.estado.className = 'mt-2 text-sm ' + (error ? 'text-rose-700' : 'text-emerald-700');
    };

    const enviar = async (url, datos) => {
      try {
        const res = await fetch(url, { method: 'POST', body: datos });
        return await res.json();
      } catch {
        return { ok: false, error: 'No se pudo conectar con el servidor.' };
      }
    };

    const ocupado = (v) => {
      ui.subir.disabled = v;
      ui.quitar.disabled = v || !actual?.[cfg.campo];
    };

    const formulario = () => {
      const f = new FormData();
      f.append('tipo', cfg.tipo);
      f.append('id', String(actual.id));
      return f;
    };

    ui.subir.addEventListener('click', async () => {
      if (!actual) return;
      const archivo = ui.archivo.files[0];
      if (!archivo) { aviso('Elegí una imagen primero.', true); ui.archivo.focus(); return; }
      if (archivo.size > 2 * 1024 * 1024) { aviso('La imagen supera los 2 MB.', true); return; }

      const datos = formulario();
      datos.append('imagen', archivo);

      ocupado(true);
      aviso('Subiendo…');
      const r = await enviar('/api/imagenes/subir', datos);
      ocupado(false);

      if (!r.ok) { aviso(r.error ?? 'No se pudo subir la imagen.', true); return; }
      actual[cfg.campo] = r.ruta;
      mostrar(r.ruta);
      ui.archivo.value = '';
      aviso(textos.subida);
      cfg.recargar();
    });

    ui.quitar.addEventListener('click', async () => {
      if (!actual) return;
      ocupado(true);
      const r = await enviar('/api/imagenes/eliminar', formulario());
      ocupado(false);

      if (!r.ok) { aviso(r.error ?? 'No se pudo quitar la imagen.', true); return; }
      actual[cfg.campo] = null;
      mostrar(null);
      aviso(textos.quitada);
      cfg.recargar();
    });

    return {
      abrir(registro) {
        actual = registro;
        ui.bloque.classList.toggle('hidden', !registro);
        ui.nota.hidden = Boolean(registro);
        ui.archivo.value = '';
        ui.estado.textContent = '';
        mostrar(registro?.[cfg.campo] ?? null);
      },
    };
  }

  window.AdminCrud = { iniciar, imagen, esc, api };
})();