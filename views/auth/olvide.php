<?php
/**
 * @var \Closure $e
 * @var array    $errores
 * @var bool     $enviado
 * @var string   $csrf
 */
$campo = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30';
$label = 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500';
?>
<h1 class="mb-6 text-2xl font-bold text-slate-900">Recuperar contraseña</h1>

<?php foreach ($errores as $err): ?>
    <div role="alert" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= $e($err) ?></div>
<?php endforeach; ?>

<?php if ($enviado): ?>
    <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        Si el email existe, te enviamos un enlace para restablecer la contraseña.
    </div>
<?php else: ?>
    <form method="post" action="/olvide" class="space-y-4">
        <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
        <div>
            <label for="email" class="<?= $label ?>">Email</label>
            <input id="email" name="email" type="email" required autofocus autocomplete="username" class="<?= $campo ?>">
        </div>
        <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">Enviar enlace</button>
    </form>
<?php endif; ?>

<p class="mt-5 text-sm"><a href="/login" class="font-semibold text-emerald-700 hover:underline">Volver al ingreso</a></p>