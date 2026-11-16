<?php
$items = [
    'partidos'  => ['/admin/partidos', 'Partidos'],
    'torneos'   => ['/admin/torneos', 'Torneos'],
    'plantel'   => ['/admin/plantel', 'Plantel'],
    'arbitros'  => ['/admin/arbitros', 'Árbitros'],
    'equipos'   => ['/admin/equipos', 'Equipos'],
    'canchas'   => ['/admin/canchas', 'Canchas'],
    'jugadores' => ['/admin/jugadores', 'Jugadores'],
];
$activo = $activo ?? '';

if (\Classes\Auth::isAdmin()) {
    $items['usuarios'] = ['/admin/usuarios', 'Usuarios'];
}

$yo = \Classes\Auth::usuario();
?>
<!-- <nav aria-label="Administración" class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-2">

        <ul class="flex min-w-0 flex-1 gap-1 overflow-x-auto">
            <?php foreach ($items as $clave => [$url, $texto]): ?>
                <li class="shrink-0">
                    <a href="<?= $url ?>"
                       <?= $clave === $activo ? 'aria-current="page"' : '' ?>
                       class="block whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-semibold <?= $clave === $activo ? 'bg-emerald-50 text-emerald-700' : 'text-slate-600 hover:bg-slate-50' ?>">
                        <?= $texto ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="flex shrink-0 items-center gap-2 whitespace-nowrap">
            <?php if ($yo): ?>
                <span class="hidden items-center gap-2 text-sm text-slate-600 2xl:flex">
                    <span class="font-semibold text-slate-800"><?= htmlspecialchars(trim($yo['nombre'] . ' ' . $yo['apellido']), ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold <?= $yo['admin'] === 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' ?>">
                        <?= $yo['admin'] === 1 ? 'Admin' : 'Editor' ?>
                    </span>
                </span>
            <?php endif; ?>

            <button type="button" id="btn-tema" aria-pressed="false"
                    class="rounded-lg p-2 text-slate-500 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-emerald-600">
                <span class="sr-only">Modo oscuro</span>
                <svg data-luna class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                <svg data-sol class="hidden h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            </button>

            <a href="/" target="_blank" rel="noopener"
               class="rounded-lg px-3 py-1.5 text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-emerald-700">
                Ver sitio &nearr;
            </a>
            <form method="post" action="/logout">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars(\Classes\Auth::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit"
                    class="rounded-lg px-3 py-1.5 text-sm font-semibold text-slate-500 hover:bg-slate-50 hover:text-rose-600">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </div>
</nav> -->