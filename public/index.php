<?php

require_once __DIR__ . '/../includes/cabeceras.php';
require_once __DIR__ . '/../includes/app.php';

use MVC\Router;
use Controllers\PartidoApiController;
use Controllers\TorneoApiController;
use Controllers\TorneoEquipoApiController;
use Controllers\FaseApiController;
use Controllers\GrupoApiController;
use Controllers\EquipoApiController;
use Controllers\CanchaApiController;
use Controllers\ArbitroApiController;
use Controllers\JugadorApiController;
use Controllers\JugadorEquipoApiController;
use Controllers\PartidoJugadorApiController;
use Controllers\EventoPartidoApiController;
use Controllers\ImagenApiController;
use Controllers\FixtureApiController;
use Controllers\DestacadoApiController;

use Controllers\AlineacionController;
use Controllers\PartidoController;
use Controllers\PlantelController;
use Controllers\ResumenController;
use Controllers\PartidoPublicoController;
use Controllers\PortadaController;
use Controllers\ListadoPartidosController;
use Controllers\ClasificacionController;
use Controllers\JugadoresPublicoController;
use Controllers\ClubesPublicoController; 
use Controllers\FichaClubController;
use Controllers\EstadisticasPublicoController;
use Controllers\DestacadoPublicoController;

use Controllers\ArbitroAdminController;
use Controllers\EquipoAdminController;
use Controllers\CanchaAdminController;
use Controllers\JugadorAdminController;
use Controllers\TorneoAdminController;
use Controllers\DestacadoAdminController;

use Classes\Auth;
use Controllers\AuthController;

use Controllers\UsuarioAdminController;
use Controllers\UsuarioApiController;

$router = new Router();

// Autenticación
$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/olvide', [AuthController::class, 'olvide']);
$router->post('/olvide', [AuthController::class, 'olvide']);
$router->get('/reestablecer', [AuthController::class, 'reestablecer']);
$router->post('/reestablecer', [AuthController::class, 'reestablecer']);

// Público
$router->get('/', [PortadaController::class, 'index']);
$router->get('/partidos', [ListadoPartidosController::class, 'index']);
$router->get('/clasificacion', [ClasificacionController::class, 'index']);
$router->get('/clubes', [ClubesPublicoController::class, 'index']);
$router->get('/partido', [PartidoPublicoController::class, 'index']);
$router->get('/jugadores', [JugadoresPublicoController::class, 'index']);
$router->get('/club', [FichaClubController::class, 'index']);
$router->get('/estadisticas', [EstadisticasPublicoController::class, 'index']);
$router->get('/destacado', [DestacadoPublicoController::class, 'index']);

// Administración (más adelante se protege todo lo que empiece con /admin)
$router->get('/admin/partidos', [PartidoController::class, 'index']);
$router->get('/admin/plantel', [PlantelController::class, 'index']);
$router->get('/admin/resumen', [ResumenController::class, 'index']);
$router->get('/admin/alineacion', [AlineacionController::class, 'index']);
$router->get('/admin/torneos', [TorneoAdminController::class, 'index']);

$router->get('/admin/arbitros', [ArbitroAdminController::class, 'index']);
$router->get('/admin/equipos', [EquipoAdminController::class, 'index']);
$router->get('/admin/canchas', [CanchaAdminController::class, 'index']);
$router->get('/admin/jugadores', [JugadorAdminController::class, 'index']);
$router->get('/admin/usuarios', [UsuarioAdminController::class, 'index']);
$router->get('/admin/destacados', [DestacadoAdminController::class, 'index']);

$router->get('/api/usuarios', [UsuarioApiController::class, 'listar']);
$router->post('/api/usuarios/guardar', [UsuarioApiController::class, 'guardar']);
$router->post('/api/usuarios/eliminar', [UsuarioApiController::class, 'eliminar']);
$router->post('/api/usuarios/estado', [UsuarioApiController::class, 'estado']);
$router->post('/api/usuarios/reenviar', [UsuarioApiController::class, 'reenviar']);

// Torneos
$router->get('/api/torneos', [TorneoApiController::class, 'listar']);
$router->post('/api/torneos/guardar', [TorneoApiController::class, 'guardar']);
$router->post('/api/torneos/eliminar', [TorneoApiController::class, 'eliminar']);
$router->post('/api/torneos/visibilidad', [TorneoApiController::class, 'visibilidad']);

// Inscripción de equipos en un torneo (?id_torneo=) y asignación a grupos
$router->get('/api/torneos/equipos', [TorneoEquipoApiController::class, 'inscriptos']);
$router->post('/api/torneos/equipos/inscribir', [TorneoEquipoApiController::class, 'inscribir']);
$router->post('/api/torneos/equipos/quitar', [TorneoEquipoApiController::class, 'quitar']);
$router->post('/api/torneos/equipos/grupo', [TorneoEquipoApiController::class, 'asignarGrupo']);

// Fases (?id_torneo=)
$router->get('/api/fases', [FaseApiController::class, 'listar']);
$router->post('/api/fases/guardar', [FaseApiController::class, 'guardar']);
$router->post('/api/fases/eliminar', [FaseApiController::class, 'eliminar']);

// Fixture de una fase de liga: {id_fase, sortear?, equipos_orden?, jornadas_manuales?}
$router->post('/api/fases/fixture', [FixtureApiController::class, 'generar']);
$router->post('/api/fases/fixture/borrar', [FixtureApiController::class, 'borrar']);

// Grupos (?id_fase=)
$router->get('/api/grupos', [GrupoApiController::class, 'listar']);
$router->post('/api/grupos/guardar', [GrupoApiController::class, 'guardar']);
$router->post('/api/grupos/eliminar', [GrupoApiController::class, 'eliminar']);

// Equipos
$router->get('/api/equipos', [EquipoApiController::class, 'listar']);
$router->post('/api/equipos/guardar', [EquipoApiController::class, 'guardar']);
$router->post('/api/equipos/eliminar', [EquipoApiController::class, 'eliminar']);

// Canchas
$router->get('/api/canchas', [CanchaApiController::class, 'listar']);
$router->post('/api/canchas/guardar', [CanchaApiController::class, 'guardar']);
$router->post('/api/canchas/eliminar', [CanchaApiController::class, 'eliminar']);

// Árbitros
$router->get('/api/arbitros', [ArbitroApiController::class, 'listar']);
$router->post('/api/arbitros/guardar', [ArbitroApiController::class, 'guardar']);
$router->post('/api/arbitros/eliminar', [ArbitroApiController::class, 'eliminar']);

// Partidos
$router->get('/api/partidos', [PartidoApiController::class, 'listar']);
$router->post('/api/partidos/guardar', [PartidoApiController::class, 'guardar']);
$router->post('/api/partidos/eliminar', [PartidoApiController::class, 'eliminar']);

// Jugadores
$router->get('/api/jugadores', [JugadorApiController::class, 'listar']);
$router->post('/api/jugadores/guardar', [JugadorApiController::class, 'guardar']);
$router->post('/api/jugadores/eliminar', [JugadorApiController::class, 'eliminar']);

// Plantel (?id_torneo=&id_equipo=)
$router->get('/api/plantel', [JugadorEquipoApiController::class, 'listar']);
$router->post('/api/plantel/guardar', [JugadorEquipoApiController::class, 'guardar']);
$router->post('/api/plantel/eliminar', [JugadorEquipoApiController::class, 'eliminar']);

// Alineación (API)
$router->get('/api/alineacion', [PartidoJugadorApiController::class, 'listar']);
$router->post('/api/alineacion/guardar', [PartidoJugadorApiController::class, 'guardar']);
$router->post('/api/alineacion/eliminar', [PartidoJugadorApiController::class, 'eliminar']);

// API
$router->get('/api/destacados', [DestacadoApiController::class, 'listar']);
$router->post('/api/destacados/guardar', [DestacadoApiController::class, 'guardar']);
$router->post('/api/destacados/eliminar', [DestacadoApiController::class, 'eliminar']);

// Eventos del partido (?id_partido=&id_equipo=)
$router->get('/api/eventos', [EventoPartidoApiController::class, 'listar']);
$router->post('/api/eventos/guardar', [EventoPartidoApiController::class, 'guardar']);
$router->post('/api/eventos/eliminar', [EventoPartidoApiController::class, 'eliminar']);

$router->post('/api/imagenes/subir', [ImagenApiController::class, 'subir']);
$router->post('/api/imagenes/eliminar', [ImagenApiController::class, 'eliminar']);

$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
Auth::proteger($ruta, $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

$router->comprobarRutas();
