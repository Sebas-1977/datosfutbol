<?php
/**
 * @var string      $contenido    HTML de la vista
 * @var string|null $titulo
 * @var string|null $descripcion
 */
$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$menu = [
    '/'              => 'Portada',
    '/partidos'      => 'Partidos',
    '/clasificacion' => 'Clasificación',
    '/clubes'        => 'Clubes',
    // '/en-vivo'       => 'En vivo',
    '/jugadores'     => 'Jugadores',
    '/estadisticas'  => 'Estadísticas',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(($titulo ?? 'DatosFutbol') . ' | DatosFutbol') ?></title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <?php if (!empty($descripcion)): ?>
    <meta name="description" content="<?= htmlspecialchars($descripcion) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/tema.css">
    <script src="/js/tema.js"></script>
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-800 antialiased">

    <a href="#contenido"
       class="sr-only focus:not-sr-only focus:fixed focus:left-2 focus:top-2 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-emerald-800 focus:shadow-lg">
        Saltar al contenido
    </a>

    <header class="border-b border-slate-200 bg-white print:hidden">
        <div class="mx-auto flex max-w-7xl items-center gap-6 px-4">
            <a href="/" class="shrink-0 py-3 text-lg font-black tracking-tight text-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600">
                DatosFutbol
            </a>

            <nav aria-label="Principal" class="min-w-0 flex-1 overflow-x-auto">
                <ul class="flex gap-1">
                    <?php foreach ($menu as $url => $texto):
                        $activo = $url === '/' ? $ruta === '/' : str_starts_with($ruta, $url);
                        if ($url === '/clubes' && $ruta === '/club') {
                            $activo = true;
                            }
                    ?>
                    <li>
                        <a href="<?= $url ?>"
                           <?= $activo ? 'aria-current="page"' : '' ?>
                           class="block whitespace-nowrap border-b-2 px-3 py-4 text-sm font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-emerald-600
                                  <?= $activo ? 'border-emerald-600 text-emerald-800' : 'border-transparent text-slate-600 hover:text-slate-900' ?>">
                            <?= $texto ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <div class="flex shrink-0 items-center gap-2">
                <button type="button" id="btn-tema" aria-pressed="false"
                    class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600">
                    <span class="sr-only">Modo oscuro</span>
                    <svg data-luna class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                    <svg data-sol class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                </button>

                <?php if (\Classes\Auth::isAuth()): ?>
                    <a href="/admin/torneos"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
                        </svg>
                        <span>Panel</span>
                    </a>
                <?php else: ?>
                    <a href="/login"
                       class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        <span>Ingresar</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main id="contenido" class="flex-1">
        <?php echo $contenido; ?>
    </main>

    <footer class="border-t border-slate-200 bg-white print:hidden">
        <p class="mx-auto max-w-7xl px-4 py-4 text-xs text-slate-600">
            DatosFutbol &middot; Resultados, clasificación y estadísticas.
        </p>
    </footer>
</body>
</html>