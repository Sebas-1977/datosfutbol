<?php


declare(strict_types=1);

namespace Controllers;

use Model\ActiveRecord;
use Model\Equipo;

class EquipoApiController extends ApiController
{
    private const POR_PAGINA = 20;

    protected static string $modelo = Equipo::class;
    protected static array $camposPermitidos = [
        'nombre', 'nombre_completo', 'fundacion', 'color_primario', 'color_secundario',
        'ciudad', 'direccion', 'telefono', 'email', 'sitio_web', 'id_cancha_local',
    ];
    protected static array $camposImagen = ['escudo', 'foto'];
    protected static string $orden = 'nombre ASC';

    /**
     * Sin ?pagina= devuelve todo, como siempre (lo usan los selectores de otras pantallas).
     * Con ?pagina=N admite ?buscar= y devuelve una página de 20.
     */
    public static function listar(): void
    {
        if (!isset($_GET['pagina'])) {
            parent::listar();
            return;
        }

        $buscar = trim((string) ($_GET['buscar'] ?? ''));
        $pagina = max(1, (int) $_GET['pagina']);

        $where  = [];
        $params = [];

        if ($buscar !== '') {
            $like = '%' . addcslashes($buscar, '\\%_') . '%';
            $where[] = '(nombre LIKE :b1 OR nombre_completo LIKE :b2 OR ciudad LIKE :b3)';
            foreach (['b1', 'b2', 'b3'] as $clave) {
                $params[$clave] = $like;
            }
        }

        $condicion = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $pdo = Equipo::conexion();

        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM equipos' . $condicion);
            $stmt->execute($params);
            $total = (int) $stmt->fetchColumn();

            $stmt = $pdo->prepare(
                'SELECT * FROM equipos' . $condicion
                . ' ORDER BY nombre ASC, id ASC LIMIT :limite OFFSET :desde'
            );
            foreach ($params as $clave => $valor) {
                $stmt->bindValue(':' . $clave, $valor);
            }
            $stmt->bindValue(':limite', self::POR_PAGINA, \PDO::PARAM_INT);
            $stmt->bindValue(':desde', ($pagina - 1) * self::POR_PAGINA, \PDO::PARAM_INT);
            $stmt->execute();
            $filas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            static::json(['ok' => false, 'message' => 'No se pudo consultar la base de datos.'], 500);
        }

        foreach ($filas as &$fila) {
            $fila['id'] = (int) $fila['id'];
            foreach (['fundacion', 'id_cancha_local'] as $campo) {
                $fila[$campo] = $fila[$campo] === null ? null : (int) $fila[$campo];
            }
        }
        unset($fila);

        static::json([
            'ok'      => true,
            'data'    => $filas,
            'total'   => $total,
            'paginas' => max(1, (int) ceil($total / self::POR_PAGINA)),
        ]);
    }

    /** No se borra un equipo con historia: se informa cuánto tiene. */
    protected static function bloqueoEliminar(ActiveRecord $registro): ?string
    {
        if (!$registro instanceof Equipo) {
            return null;
        }

        $pdo = Equipo::conexion();
        $id = (int) $registro->id;

        $consultas = [
            ['partido', 'partidos',
                'SELECT COUNT(*) FROM partidos WHERE id_equipo_local = ? OR id_equipo_visitante = ?', [$id, $id]],
            ['evento', 'eventos',
                'SELECT COUNT(*) FROM eventos_partido WHERE id_equipo = ?', [$id]],
            ['alineación', 'alineaciones',
                'SELECT COUNT(*) FROM partido_jugadores WHERE id_equipo = ?', [$id]],
            ['jugador en plantel', 'jugadores en plantel',
                'SELECT COUNT(*) FROM jugadores_equipo WHERE id_equipo = ?', [$id]],
        ];

        $partes = [];
        foreach ($consultas as [$singular, $plural, $sql, $params]) {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $cantidad = (int) $stmt->fetchColumn();
            if ($cantidad > 0) {
                $partes[] = $cantidad . ' ' . ($cantidad === 1 ? $singular : $plural);
            }
        }

        if (!$partes) {
            return null;
        }

        return 'No se puede eliminar «' . $registro->nombre . '»: tiene ' . implode(', ', $partes) . '.';
    }
}