<?php

require __DIR__ . '/../vendor/autoload.php';

// Inicializar Dotenv para cargar el archivo .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

// Variables obligatorias: si falta alguna, el error dice cuál
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER'])->notEmpty();
$dotenv->required('DB_PASS'); // puede ser vacía, pero tiene que existir
$dotenv->required(['APP_URL', 'EMAIL_HOST', 'EMAIL_PORT', 'EMAIL_USER', 'EMAIL_PASS', 'EMAIL_FROM', 'EMAIL_NAME']);

// Zona horaria
date_default_timezone_set('America/Montevideo');

require 'funciones.php';
require 'database.php';

// Conectamos a la base de datos
use Model\ActiveRecord;
ActiveRecord::setDB($db);