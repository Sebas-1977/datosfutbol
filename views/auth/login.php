<?php
/**
 * @var \Closure $e
 * @var array    $errores
 * @var string   $csrf
 * @var string   $email
 * @var string   $volver
 */
$campo = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-800 focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500/30';
$label = 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500';
?>
<h1 class="mb-6 text-2xl font-bold text-slate-900">Ingresar</h1>

<?php foreach ($errores as $err): ?>
    <div role="alert" class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><?= $e($err) ?></div>
<?php endforeach; ?>

<form method="post" action="/login" class="space-y-4">
    <input type="hidden" name="csrf" value="<?= $e($csrf) ?>">
    <input type="hidden" name="volver" value="<?= $e($volver) ?>">

    <div>
        <label for="email" class="<?= $label ?>">Email</label>
        <input id="email" name="email" type="email" value="<?= $e($email) ?>" required autofocus autocomplete="username" class="<?= $campo ?>">
    </div>
    <div>
        <label for="password" class="<?= $label ?>">Contraseña</label>
        <input id="password" name="password" type="password" required autocomplete="current-password" class="<?= $campo ?>">
    </div>

    <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700">Entrar</button>
</form>

<p class="mt-5 text-sm"><a href="/olvide" class="font-semibold text-emerald-700 hover:underline">Olvidé mi contraseña</a></p>