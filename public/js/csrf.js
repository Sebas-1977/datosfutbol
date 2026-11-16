(() => {
  'use strict';

  const token = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
  const original = window.fetch.bind(window);
  const LECTURA = ['GET', 'HEAD', 'OPTIONS'];

  window.fetch = async (input, init = {}) => {
    const esRequest = input instanceof Request;
    const url = new URL(esRequest ? input.url : String(input), location.href);
    const metodo = (init.method ?? (esRequest ? input.method : 'GET')).toUpperCase();
    const mismoOrigen = url.origin === location.origin;

    if (mismoOrigen && !LECTURA.includes(metodo)) {
      const headers = new Headers(init.headers ?? (esRequest ? input.headers : undefined));
      headers.set('X-CSRF-Token', token);
      init = { ...init, headers };
    }

    const res = await original(input, init);

    if (mismoOrigen && res.status === 401) {
      location.href = '/login?volver=' + encodeURIComponent(location.pathname + location.search);
    }
    return res;
  };
})();