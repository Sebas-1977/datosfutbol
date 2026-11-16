<?php
/**
 * @var \Closure $e         Escapa HTML (lo define AuthController::vista)
 * @var string   $titulo
 * @var string   $contenido HTML de la vista
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $e($titulo) ?> | DatosFutbol</title>
    <link rel="stylesheet" href="/css/app.css">
    <link rel="stylesheet" href="/css/tema.css">
    <script src="/js/tema.js"></script>
</head>
<body class="grid min-h-screen place-items-center bg-slate-50 px-4 py-8 text-slate-800 antialiased">
    <div class="w-full max-w-sm space-y-3">
        <div class="flex items-center justify-between">
            <a href="/"
               class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-200/50 hover:text-emerald-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Volver a la portada</span>
            </a>

            <button type="button" id="btn-tema" aria-pressed="false"
                class="rounded-lg p-2 text-slate-600 hover:bg-slate-200/50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600">
                <span class="sr-only">Modo oscuro</span>
                <svg data-luna class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                <svg data-sol class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            </button>
        </div>

        <main class="w-full rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
            <?= $contenido ?>
        </main>
    </div>
</body>
</html>
