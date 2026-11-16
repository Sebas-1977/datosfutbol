<?php
/**
 * @var \Closure $e
 * @var array    $errores
 * @var string   $csrf
 * @var string   $token
 * @var bool     $valido
 * @var bool     $listo
 */
$campo = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30';
$label = 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500';
?>
<h1 class="mb-6 text-2xl font-bold text-slate-900">Nueva contraseña</h1>

<?php foreach ($errores as $err): ?>
    <div role="alert" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= $e($err) ?></div>
<?php endforeach; ?>

<?php if ($listo): ?>
    <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">Contraseña guardada.</div>
    <p class="mt-5 text-sm"><a href="/login" class="font-semibold text-emerald-700 hover:underline">Ir a ingresar</a></p>
<?php elseif (!$valido): ?>
    <div role="alert" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">El enlace no es válido o ya venció.</div>
    <p class="mt-5 text-sm"><a href="/olvide" class="font-semibold text-emerald-700 hover:underline">Pedir uno nuevo</a></p>
<?php else: ?>
    <form method="post" action="/reestablecer" class="space-y-4">
        <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
        <input type="hidden" name="token" value="<?= $e($token) ?>">
        <div>
            <label for="password" class="<?= $label ?>">Contraseña (mínimo 8)</label>
            <input id="password" name="password" type="password" required autofocus autocomplete="new-password" class="<?= $campo ?>">
        </div>
        <div>
            <label for="password2" class="<?= $label ?>">Repetir contraseña</label>
            <input id="password2" name="password2" type="password" required autocomplete="new-password" class="<?= $campo ?>">
        </div>
        <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">Guardar</button>
    </form>
<?php endif; ?>