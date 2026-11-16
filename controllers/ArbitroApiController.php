<?php

declare(strict_types=1);

namespace Controllers;

use Model\Arbitro;

class ArbitroApiController extends ApiController
{
    protected static string $modelo = Arbitro::class;
    protected static array $camposPermitidos = ['nombre', 'apellido'];
    protected static array $camposImagen = ['foto'];
    protected static string $orden = 'apellido ASC, nombre ASC';

    /** Sin ?buscar= devuelve todo, como siempre (lo usan los selectores de partidos). */
    public static function listar(): void
    {
        $buscar = trim((string) ($_GET['buscar'] ?? ''));
        if ($buscar === '') {
            parent::listar();
            return;
        }

        $like = '%' . addcslashes($buscar, '\\%_') . '%';

        try {
            $stmt = Arbitro::conexion()->prepare(
                "SELECT * FROM arbitros
                 WHERE nombre LIKE :b1 OR apellido LIKE :b2
                    OR CONCAT(nombre, ' ', apellido) LIKE :b3
                    OR CONCAT(apellido, ' ', nombre) LIKE :b4
                 ORDER BY apellido ASC, nombre ASC, id ASC"
            );
            $stmt->execute(['b1' => $like, 'b2' => $like, 'b3' => $like, 'b4' => $like]);
            $filas = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            static::json(['ok' => false, 'message' => 'No se pudo consultar la base de datos.'], 500);
        }

        foreach ($filas as &$fila) {
            $fila['id'] = (int) $fila['id'];
        }
        unset($fila);

        static::json(['ok' => true, 'data' => $filas, 'total' => count($filas)]);
    }
}