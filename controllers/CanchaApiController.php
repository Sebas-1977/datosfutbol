<?php

declare(strict_types=1);

namespace Controllers;

use Model\Cancha;

class CanchaApiController extends ApiController
{
    private const POR_PAGINA = 20;

    protected static string $modelo = Cancha::class;
    protected static array $camposPermitidos = [
        'nombre', 'tipo', 'id_equipo_propietario', 'capacidad', 'direccion', 'ciudad',
    ];
    protected static array $camposImagen = ['foto'];
    protected static string $orden = 'nombre ASC';

    /**
     * Sin ?pagina= devuelve todo, como siempre.
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
            $where[] = '(nombre LIKE :b1 OR ciudad LIKE :b2 OR direccion LIKE :b3)';
            foreach (['b1', 'b2', 'b3'] as $clave) {
                $params[$clave] = $like;
            }
        }

        $condicion = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $pdo = Cancha::conexion();

        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM canchas' . $condicion);
            $stmt->execute($params);
            $total = (int) $stmt->fetchColumn();

            $stmt = $pdo->prepare(
                'SELECT * FROM canchas' . $condicion
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
            foreach (['id_equipo_propietario', 'capacidad'] as $campo) {
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
}