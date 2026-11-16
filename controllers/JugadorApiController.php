<?php

declare(strict_types=1);

namespace Controllers;

use Model\ActiveRecord;
use Model\Jugador;

class JugadorApiController extends ApiController
{
    private const POR_PAGINA = 50;

    protected static string $modelo = Jugador::class;
    protected static array $camposPermitidos = ['nombre', 'apellido', 'seudonimo', 'pais', 'fecha_nacimiento'];
    protected static array $camposImagen = ['foto'];
    protected static string $orden = 'apellido ASC, nombre ASC';
    protected static array $camposPrivados = ['fecha_nacimiento'];

    /**
     * Sin ?pagina= se comporta como siempre (lo usa Plantel).
     * Con ?pagina=N admite ?buscar= y ?revisar=1 y devuelve una página de 50.
     */
    public static function listar(): void
    {
        if (!isset($_GET['pagina'])) {
            parent::listar();
            return;
        }

        $buscar  = trim((string) ($_GET['buscar'] ?? ''));
        $revisar = ($_GET['revisar'] ?? '') === '1';
        $pagina  = max(1, (int) $_GET['pagina']);

        $where = [];
        $params = [];

        if ($buscar !== '') {
            $like = '%' . addcslashes($buscar, '\\%_') . '%';
            $where[] = "(nombre LIKE :b1 OR apellido LIKE :b2
                        OR CONCAT(nombre, ' ', apellido) LIKE :b3
                        OR CONCAT(apellido, ' ', nombre) LIKE :b4
                        OR seudonimo LIKE :b5)";
            foreach (['b1', 'b2', 'b3', 'b4', 'b5'] as $clave) {
                $params[$clave] = $like;
            }
        }

        // Datos para corregir: apellido vacío, abreviados (1-2 letras) o con espacios sobrantes.
        // Se compara el largo porque la collation ignora los espacios finales.
        if ($revisar) {
            $where[] = "(apellido = '' OR CHAR_LENGTH(apellido) <= 2 OR CHAR_LENGTH(nombre) <= 2
                         OR CHAR_LENGTH(nombre) <> CHAR_LENGTH(TRIM(nombre))
                         OR CHAR_LENGTH(apellido) <> CHAR_LENGTH(TRIM(apellido)))";
        }

        // ---- NUEVO: filtro por equipo ----
        $idEquipo = (int) ($_GET['id_equipo'] ?? 0);
        if ($idEquipo > 0) {
            $where[] = 'EXISTS (SELECT 1 FROM jugadores_equipo je
                                WHERE je.id_jugador = jugadores.id AND je.id_equipo = :eq)';
            $params['eq'] = $idEquipo;
        }
    
        $condicion = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $pdo = Jugador::conexion();

        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM jugadores' . $condicion);
            $stmt->execute($params);
            $total = (int) $stmt->fetchColumn();

            $columnas = static::haySesion()
            ? 'id, nombre, apellido, seudonimo, pais, fecha_nacimiento, foto'
            : 'id, nombre, apellido, seudonimo, pais, foto';

            $stmt = $pdo->prepare(
                "SELECT {$columnas} FROM jugadores"
                . $condicion
                . ' ORDER BY apellido ASC, nombre ASC, id ASC LIMIT :limite OFFSET :desde'
            );
            foreach ($params as $clave => $valor) {
                $stmt->bindValue(':' . $clave, $valor);
            }
            $stmt->bindValue(':limite', self::POR_PAGINA, \PDO::PARAM_INT);
            $stmt->bindValue(':desde', ($pagina - 1) * self::POR_PAGINA, \PDO::PARAM_INT);
            $stmt->execute();
            $filas = $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            static::json(['ok' => false, 'message' => 'No se pudo consultar la base de datos.'], 500);
        }

        foreach ($filas as &$fila) {
            $fila['id'] = (int) $fila['id'];
        }
        unset($fila);

        static::json([
            'ok'      => true,
            'data'    => $filas,
            'total'   => $total,
            'paginas' => max(1, (int) ceil($total / self::POR_PAGINA)),
        ]);
    }

    /** Un jugador con partidos o eventos no se borra. El plantel sí se borra en cascada. */
    protected static function bloqueoEliminar(ActiveRecord $registro): ?string
    {
        if (!$registro instanceof Jugador) {
            return null;
        }

        $pdo = Jugador::conexion();
        $id = (int) $registro->id;

        $consultas = [
            ['alineación', 'alineaciones',
                'SELECT COUNT(*) FROM partido_jugadores WHERE id_jugador = ?', [$id]],
            ['evento', 'eventos',
                'SELECT COUNT(*) FROM eventos_partido WHERE id_jugador = ? OR id_jugador_entra = ? OR id_asistente = ?',
                [$id, $id, $id]],
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

        $nombre = trim($registro->nombre . ' ' . $registro->apellido);
        return 'No se puede eliminar «' . $nombre . '»: tiene ' . implode(' y ', $partes) . '.';
    }
}