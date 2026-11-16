<?php
/**
 * @var string $contenido HTML de la vista (lo define Router::render())
 * @var string|null $titulo
 */
$rutaActual = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$esAdmin    = str_starts_with($rutaActual, '/admin');

if ($esAdmin) {
    $grupos = [
        'Competición' => [
            ['/admin/torneos',  'Torneos',  ['/admin/torneos', '/admin/partidos', '/admin/plantel', '/admin/alineacion', '/admin/resumen', '/admin/destacados']],
        ],
        'Entidades' => [
            ['/admin/equipos',   'Equipos',   ['/admin/equipos']],
            ['/admin/jugadores', 'Jugadores', ['/admin/jugadores']],
            ['/admin/arbitros',  'Árbitros',  ['/admin/arbitros']],
            ['/admin/canchas',   'Canchas',   ['/admin/canchas']],
        ],
    ];
    if (\Classes\Auth::isAdmin()) {
        $grupos['Sistema'] = [
            ['/admin/usuarios', 'Usuarios', ['/admin/usuarios']],
        ];
    }

    $estaActivo = static function (array $prefijos) use ($rutaActual): bool {
        foreach ($prefijos as $p) {
            if (str_starts_with($rutaActual, $p)) {
                return true;
            }
        }
        return false;
    };

    $yo = \Classes\Auth::usuario();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo ?? 'Liga') ?></title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/tema.css">
    <script src="/js/tema.js"></script>
    <?php if ($esAdmin): ?>
        <meta name="csrf-token" content="<?= htmlspecialchars(\Classes\Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
        <script src="/js/csrf.js"></script>
    <?php endif; ?>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:left-2 focus:top-2 focus:z-50 focus:rounded-lg focus:bg-white focus:px-3 focus:py-2 focus:text-sm focus:font-semibold focus:text-emerald-700">Saltar al contenido</a>

<?php if (!$esAdmin): ?>

    <?php echo $contenido; ?>

<?php else: ?>

    <div class="flex min-h-screen">

        <!-- Fondo oscuro del menú en móvil -->
        <div id="sidebar-fondo" class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden"></div>

        <!-- Sidebar -->
        <aside id="sidebar"
               class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">

            <div class="flex h-14 shrink-0 items-center border-b border-slate-200 px-5">
                <a href="/admin/torneos" class="text-lg font-black tracking-tight text-emerald-700">DatosFutbol</a>
                <span class="ml-2 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-slate-500">Admin</span>
            </div>

            <nav aria-label="Administración" class="flex-1 overflow-y-auto px-3 py-4">
                <?php foreach ($grupos as $nombreGrupo => $enlaces): ?>
                    <p class="px-3 pb-1 pt-3 text-[11px] font-bold uppercase tracking-wider text-slate-400 first:pt-0">
                        <?= $nombreGrupo ?>
                    </p>
                    <ul class="space-y-0.5">
                        <?php foreach ($enlaces as [$url, $texto, $prefijos]): ?>
                            <?php $activo = $estaActivo($prefijos); ?>
                            <li>
                                <a href="<?= $url ?>"
                                   <?= $activo ? 'aria-current="page"' : '' ?>
                                   class="block rounded-lg px-3 py-2 text-sm font-semibold <?= $activo ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                                    <?= $texto ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endforeach; ?>
            </nav>


        </aside>

        <!-- Columna principal -->
        <div class="flex min-w-0 flex-1 flex-col">

            <header class="sticky top-0 z-20 flex h-14 shrink-0 items-center gap-2 border-b border-slate-200 bg-white px-4">
                <button type="button" id="btn-menu" aria-controls="sidebar" aria-expanded="false"
                        class="rounded-lg p-2 text-slate-500 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600 lg:hidden">
                    <span class="sr-only">Abrir menú</span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <div class="ml-auto flex items-center gap-2 sm:gap-3">
                    <button type="button" id="btn-tema" aria-pressed="false"
                            class="rounded-lg p-2 text-slate-500 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600">
                        <span class="sr-only">Modo oscuro</span>
                        <svg data-luna class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                        <svg data-sol class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    </button>
                    <a href="/" target="_blank" rel="noopener"
                       class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-500 hover:bg-slate-50 hover:text-emerald-700">
                        Ver sitio &nearr;
                    </a>

                    <?php if ($yo): ?>
                        <div class="h-4 w-px bg-slate-200" aria-hidden="true"></div>

                        <div class="flex items-center gap-2">
                            <span class="hidden text-xs font-semibold text-slate-700 sm:inline">
                                <?= htmlspecialchars(trim($yo['nombre'] . ' ' . $yo['apellido']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide <?= $yo['admin'] === 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                                <?= $yo['admin'] === 1 ? 'Admin' : 'Editor' ?>
                            </span>

                            <form method="post" action="/logout" class="inline">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars(\Classes\Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit"
                                        title="Cerrar sesión"
                                        class="rounded-lg px-2.5 py-1.5 text-xs font-semibold text-slate-500 hover:bg-rose-50 hover:text-rose-600 transition">
                                    Cerrar sesión
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </header>

            <div class="flex-1">
                <?php echo $contenido; ?>
            </div>
        </div>
    </div>

    <script nonce="<?= CSP_NONCE ?>">
    (function () {
        var side = document.getElementById('sidebar');
        var fondo = document.getElementById('sidebar-fondo');
        var btn = document.getElementById('btn-menu');
        function abrir(si) {
            side.classList.toggle('-translate-x-full', !si);
            fondo.classList.toggle('hidden', !si);
            btn.setAttribute('aria-expanded', si ? 'true' : 'false');
        }
        btn.addEventListener('click', function () { abrir(side.classList.contains('-translate-x-full')); });
        fondo.addEventListener('click', function () { abrir(false); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') abrir(false); });
    })();
    </script>

<?php endif; ?>
</body>
</html>