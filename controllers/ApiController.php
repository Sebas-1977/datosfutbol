<?php

declare(strict_types=1);

namespace Controllers;

use Model\ActiveRecord;
use Model\ImagenSubida;

abstract class ApiController
{
    /** @var class-string<ActiveRecord> */
    protected static string $modelo;

    /** Campos que se aceptan desde el cliente */
    protected static array $camposPermitidos = [];

    /** Filtros permitidos en GET (se castean a int) */
    protected static array $filtros = [];

    /** Columnas de imagen del modelo: se borran del disco al eliminar el registro */
    protected static array $camposImagen = [];

    /** Campos que solo se devuelven con sesión iniciada */
    protected static array $camposPrivados = [];

    /** Devuelve un mensaje si el registro no se puede eliminar; null si se puede */
    protected static function bloqueoEliminar(ActiveRecord $registro): ?string
    {
        return null;
    }

    /** Orden del listado */
    protected static string $orden = 'id ASC';

    // GET — usa sentencias preparadas para los filtros
    public static function listar(): void
    {
        $modelo    = static::$modelo;
        $clausulas = [];
        $params    = [];

        foreach (static::$filtros as $campo) {
            if (isset($_GET[$campo]) && $_GET[$campo] !== '') {
                $clausulas[] = "{$campo} = ?";
                $params[]    = (int) $_GET[$campo];
            }
        }

        $sql = 'SELECT * FROM ' . static::tabla();
        if ($clausulas) {
            $sql .= ' WHERE ' . implode(' AND ', $clausulas);
        }
        $sql .= ' ORDER BY ' . static::$orden;

        $db   = $modelo::conexion();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        // Hidrata cada fila en el objeto del modelo correspondiente
        $objetos = array_map(
            fn(array $fila) => $modelo::find((int) $fila['id']),
            $data
        );

        $lista = array_values(array_filter($objetos));

        if (static::$camposPrivados && !static::haySesion()) {
            $lista = array_map(function ($objeto) {
                $fila = get_object_vars($objeto);
                foreach (static::$camposPrivados as $campo) {
                    unset($fila[$campo]);
                }
                return $fila;
            }, $lista);
        }

        static::json(['ok' => true, 'data' => $lista]);
    }

    // POST: crea (sin id) o actualiza (con id)
    public static function guardar(): void
    {
        static::soloPost();

        $modelo = static::$modelo;
        $input = static::leerInput();
        $datos = array_intersect_key($input, array_flip(static::$camposPermitidos));
        $id = (int) ($input['id'] ?? 0);

        if ($id > 0) {
            $registro = $modelo::find($id);
            if (!$registro) {
                static::json(['ok' => false, 'message' => 'Registro no encontrado.'], 404);
            }
        } else {
            $registro = new $modelo();
        }

        $registro->sincronizar($datos);
        $alertas = $registro->validar();

        if (!empty($alertas)) {
            static::json(['ok' => false, 'alertas' => $alertas], 422);
        }

        try {
            $resultado = $registro->guardar();
        } catch (\PDOException $e) {
            static::responderErrorBD($e, 'Los datos no se pudieron guardar: revisá que los elementos relacionados existan.');
        }

        if (is_array($resultado)) { // crear()
            $registro->id = (int) $resultado['id'];
        }

        static::json(['ok' => true, 'data' => $registro], $id > 0 ? 200 : 201);
    }

    // POST: elimina por id
    public static function eliminar(): void
    {
        static::soloPost();

        $modelo = static::$modelo;
        $input = static::leerInput();
        $registro = $modelo::find((int) ($input['id'] ?? 0));

        if (!$registro) {
            static::json(['ok' => false, 'message' => 'Registro no encontrado.'], 404);
        }

        $bloqueo = static::bloqueoEliminar($registro);
        if ($bloqueo !== null) {
            static::json(['ok' => false, 'message' => $bloqueo], 409);
        }

        $imagenes = [];
        foreach (static::$camposImagen as $campo) {
            $imagenes[] = $registro->$campo;
        }

        try {
            $filas = $registro->eliminar();
        } catch (\PDOException $e) {
            static::responderErrorBD($e, 'No se puede eliminar: tiene registros relacionados.');
        }

        if ($filas === false || $filas < 1) {
            static::json(['ok' => false, 'message' => 'No se pudo eliminar el registro.'], 409);
        }

        // Recién ahora, con el DELETE confirmado, se borran los archivos
        foreach ($imagenes as $ruta) {
            ImagenSubida::eliminar($ruta);
        }

        static::json(['ok' => true]);
    }

    // ---------- Utilidades ----------

    /** Lee el nombre de la tabla sin modificar ActiveRecord */
    protected static function tabla(): string
    {
        $prop = new \ReflectionProperty(static::$modelo, 'tabla');
        $prop->setAccessible(true);
        return (string) $prop->getValue();
    }

    protected static function soloPost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            static::json(['ok' => false, 'message' => 'Método no permitido.'], 405);
        }
    }

    /** Traduce un error de PDO a una respuesta JSON limpia */
    protected static function responderErrorBD(\PDOException $e, string $mensajeIntegridad): never
    {
        $sqlstate = (string) ($e->errorInfo[0] ?? $e->getCode());

        if ($sqlstate === '23000') {
            static::json(['ok' => false, 'message' => $mensajeIntegridad], 409);
        }

        error_log($e->getMessage());
        static::json(['ok' => false, 'message' => 'Error en la base de datos. Probá de nuevo.'], 500);
    }

    // Acepta JSON (fetch) o form-data
    protected static function leerInput(): array
    {
        $tipo = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($tipo, 'application/json')) {
            $json = json_decode((string) file_get_contents('php://input'), true);
            return is_array($json) ? $json : [];
        }

        return $_POST;
    }

    protected static function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Hay sesión de admin. Mira la cookie primero para no abrir sesiones a visitantes anónimos. */
    protected static function haySesion(): bool
    {
        return isset($_COOKIE['liga_sid']) && \Classes\Auth::isAuth();
    }
}
