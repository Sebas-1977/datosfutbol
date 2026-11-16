<?php

declare(strict_types=1);

// Credenciales obligatorias: sin valores por defecto.
// DB_PASS puede estar vacía en local, pero la variable tiene que existir.
foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $var) {
    if (!array_key_exists($var, $_ENV)) {
        error_log("Falta la variable {$var} en el .env");
        http_response_code(503);
        echo 'El servicio no está disponible en este momento. Intente más tarde.';
        exit;
    }
}

$db_host     = $_ENV['DB_HOST'];
$db_nombre   = $_ENV['DB_NAME'];
$db_usuario  = $_ENV['DB_USER'];
$db_password = $_ENV['DB_PASS'];
$db_puerto   = $_ENV['DB_PORT'] ?? '3306';

try {
    $dsn = "mysql:host={$db_host};port={$db_puerto};dbname={$db_nombre};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    // SSL solo si definís la ruta al certificado de la CA (Aiven la entrega como ca.pem)
    if (!empty($_ENV['DB_SSL_CA'])) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $_ENV['DB_SSL_CA'];
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    $db = new PDO($dsn, $db_usuario, $db_password, $options);

} catch (PDOException $e) {
    error_log('Error de conexión a la base de datos: ' . $e->getMessage());
    http_response_code(503);
    echo 'El servicio no está disponible en este momento. Intente más tarde.';
    exit;
}