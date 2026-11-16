<?php

declare(strict_types=1);

namespace Controllers;

use Model\ActiveRecord;
use Model\ImagenSubida;

class ImagenApiController
{
    /** Por ahora solo equipos: acá confirmé tabla y columnas. Se amplía al llegar a cada pantalla. */
    private const PERMITIDOS = ['equipo_escudo', 'equipo_foto', 'cancha',  'jugador', 'arbitro', 'destacado'];

    /** POST /api/imagenes/subir  (multipart: tipo, id, imagen) */
    public static function subir(): void
    {
        [$tipo, $id] = self::validar();

        [$tabla, $columna] = ImagenSubida::DESTINOS[$tipo];
        $pdo = ActiveRecord::conexion();

        $anterior = self::rutaActual($pdo, $tabla, $columna, $id);
        if ($anterior === false) {
            self::responder(404, ['ok' => false, 'error' => 'El registro no existe.']);
        }

        $resultado = ImagenSubida::guardar($_FILES['imagen'] ?? [], $tipo);
        if (isset($resultado['error'])) {
            self::responder(422, ['ok' => false, 'error' => $resultado['error']]);
        }

        try {
            $stmt = $pdo->prepare("UPDATE `{$tabla}` SET `{$columna}` = ? WHERE id = ?");
            $stmt->execute([$resultado['ruta'], $id]);
        } catch (\PDOException $e) {
            ImagenSubida::eliminar($resultado['ruta']);
            error_log($e->getMessage());
            self::responder(500, ['ok' => false, 'error' => 'No se pudo guardar la imagen.']);
        }

        ImagenSubida::eliminar($anterior);
        self::responder(200, ['ok' => true, 'ruta' => $resultado['ruta']]);
    }

    /** POST /api/imagenes/eliminar  (tipo, id) */
    public static function eliminar(): void
    {
        [$tipo, $id] = self::validar();

        [$tabla, $columna] = ImagenSubida::DESTINOS[$tipo];
        $pdo = ActiveRecord::conexion();

        $anterior = self::rutaActual($pdo, $tabla, $columna, $id);
        if ($anterior === false) {
            self::responder(404, ['ok' => false, 'error' => 'El registro no existe.']);
        }

        try {
            $stmt = $pdo->prepare("UPDATE `{$tabla}` SET `{$columna}` = NULL WHERE id = ?");
            $stmt->execute([$id]);
        } catch (\PDOException $e) {
            error_log($e->getMessage());
            self::responder(500, ['ok' => false, 'error' => 'No se pudo quitar la imagen.']);
        }

        ImagenSubida::eliminar($anterior);
        self::responder(200, ['ok' => true]);
    }

    /** @return array{0: string, 1: int} */
    private static function validar(): array
    {
        $tipo = (string) ($_POST['tipo'] ?? '');
        $id   = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        if (!in_array($tipo, self::PERMITIDOS, true) || !isset(ImagenSubida::DESTINOS[$tipo])) {
            // Si el archivo supera post_max_size, PHP vacía $_POST y llegamos acá.
            self::responder(422, ['ok' => false, 'error' => 'Solicitud no válida o archivo demasiado grande.']);
        }
        if ($id === false || $id < 1) {
            self::responder(422, ['ok' => false, 'error' => 'Identificador no válido.']);
        }

        return [$tipo, $id];
    }

    /** Devuelve la ruta guardada (o null) y false si el registro no existe. */
    private static function rutaActual(\PDO $pdo, string $tabla, string $columna, int $id): string|false|null
    {
        $stmt = $pdo->prepare("SELECT `{$columna}` FROM `{$tabla}` WHERE id = ?");
        $stmt->execute([$id]);
        $fila = $stmt->fetch(\PDO::FETCH_NUM);

        return $fila === false ? false : ($fila[0] !== null ? (string) $fila[0] : null);
    }

    private static function responder(int $codigo, array $datos): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE);
        exit;
    }
}