<?php

declare(strict_types=1);

use Model\Usuario;

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require __DIR__ . '/../includes/app.php';

function pedir(string $texto, bool $oculto = false): string
{
    echo $texto;
    $ocultar = $oculto && PHP_OS_FAMILY !== 'Windows';
    if ($ocultar) system('stty -echo');
    $v = trim((string) fgets(STDIN));
    if ($ocultar) { system('stty echo'); echo PHP_EOL; }
    return $v;
}

$u = new Usuario();
$u->nombre   = pedir('Nombre: ');
$u->apellido = pedir('Apellido: ');
$u->email    = pedir('Email: ');
$pass        = pedir('Contraseña (mín. 8): ', true);
$pass2       = pedir('Repetir contraseña: ', true);

$errores = array_merge(
    Usuario::erroresPassword($pass, $pass2),
    ...array_values($u->validar())
);
if ($errores) {
    fwrite(STDERR, "No se creó el usuario:\n- " . implode("\n- ", $errores) . "\n");
    exit(1);
}

$u->password = password_hash($pass, PASSWORD_DEFAULT);
$u->admin = 1;
$u->activo = 1;
$u->crear();

echo "Administrador creado: {$u->email}\n";