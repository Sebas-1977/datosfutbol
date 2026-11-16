(() => {
    const raiz = document.documentElement;
    let guardado = null;
    try { guardado = localStorage.getItem('tema'); } catch {}

    const oscuro = guardado
        ? guardado === 'dark'
        : matchMedia('(prefers-color-scheme: dark)').matches;
    raiz.classList.toggle('dark', oscuro);   // se aplica antes de pintar: sin parpadeo

    document.addEventListener('DOMContentLoaded', () => {
        const btn = document.getElementById('btn-tema');
        if (!btn) return;
        const pintar = () => {
            const d = raiz.classList.contains('dark');
            btn.setAttribute('aria-pressed', String(d));
            btn.querySelector('[data-luna]').classList.toggle('hidden', d);
            btn.querySelector('[data-sol]').classList.toggle('hidden', !d);
        };
        btn.addEventListener('click', () => {
            const d = raiz.classList.toggle('dark');
            try { localStorage.setItem('tema', d ? 'dark' : 'light'); } catch {}
            pintar();
        });
        pintar();
    });
})();