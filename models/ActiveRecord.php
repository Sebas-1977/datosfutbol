<?php

declare(strict_types=1);

namespace Model;

use PDO;
use PDOException;
use PDOStatement;
use ReflectionProperty;
use ReflectionNamedType;

abstract class ActiveRecord
{
    protected static PDO $db;
    protected static string $tabla = '';
    protected static string $campoBusqueda = 'id';
    protected static array $columnasDB = [];
    protected static array $alertas = [];

    // Propiedad común para todos los modelos
    public ?int $id = null;

    // Definir la conexión a la BD
    public static function setDB(PDO $database) {
        self::$db = $database;
    }

    public static function conexion(): PDO {
        return self::$db;
    }

    public static function setAlerta(string $tipo, string $mensaje): void {
        static::$alertas[$tipo][] = $mensaje;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function getAlertas(): array {
        return static::$alertas;
    }

    public function validar() {
        static::$alertas = [];
        return static::$alertas;
    }

    // Registros - CRUD
    public function guardar(): int|array {
        if (!is_null($this->id)) {
            // actualizar
            return $this->actualizar();
        } else {
            // Creando un nuevo registro
            return $this->crear();
        }
    }

    public static function all(): array {
        $query = "SELECT * FROM " . static::$tabla;
        return self::consultarSQL($query);
    }

    // Busca un registro por su id
    public static function find(int|string $id): ?static {
        $stmt = self::$db->prepare("SELECT * FROM " . static::$tabla . " WHERE id = ?");
        $stmt->execute([(int) $id]);
        $resultado = [];
        while ($registro = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resultado[] = static::crearObjeto($registro);
        }
        $stmt->closeCursor();
        return array_shift($resultado);
    }

    // Obtener un número limitado de registros
    public static function get(int $limite): ?static {
        $stmt = self::$db->prepare("SELECT * FROM " . static::$tabla . " LIMIT ?");
        $stmt->bindValue(1, $limite, PDO::PARAM_INT);
        $stmt->execute();
        $resultado = [];
        while ($registro = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resultado[] = static::crearObjeto($registro);
        }
        $stmt->closeCursor();
        return array_shift($resultado);
    }

    /**
     * Búsqueda WHERE con columna validada contra la lista blanca del modelo.
     * $columna debe ser una columna declarada en static::$columnasDB.
     */
    public static function where(string $columna, mixed $valor): ?static {
        $columna = self::columnaSegura($columna);
        $stmt = self::$db->prepare("SELECT * FROM " . static::$tabla . " WHERE {$columna} = ?");
        $stmt->execute([$valor]);
        $resultado = [];
        while ($registro = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resultado[] = static::crearObjeto($registro);
        }
        $stmt->closeCursor();
        return array_shift($resultado);
    }

    /**
     * Busca todos los registros que pertenecen a un valor específico.
     * $columna debe ser una columna declarada en static::$columnasDB.
     */
    public static function belongsTo(string $columna, mixed $valor): array {
        $columna = self::columnaSegura($columna);
        $stmt = self::$db->prepare("SELECT * FROM " . static::$tabla . " WHERE {$columna} = ?");
        $stmt->execute([$valor]);
        $resultado = [];
        while ($registro = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resultado[] = static::crearObjeto($registro);
        }
        $stmt->closeCursor();
        return $resultado;
    }

    /**
     * SQL para consultas avanzadas internas.
     * IMPORTANTE: nunca pasar input del usuario directamente en $consulta.
     * Para consultas con parámetros externos, usar $db->prepare() directamente.
     */
    public static function SQL(string $consulta): array {
        return self::consultarSQL($consulta);
    }

    // Crea un nuevo registro usando sentencias preparadas
    public function crear(): array {
        $atributos = $this->atributos();

        $columnas   = array_keys($atributos);
        $valores    = array_values($atributos);
        $marcadores = array_map(fn() => '?', $columnas);

        $query = "INSERT INTO " . static::$tabla
            . " (" . implode(', ', $columnas) . ")"
            . " VALUES (" . implode(', ', $marcadores) . ")";

        $stmt = self::$db->prepare($query);
        $stmt->execute($valores);

        return [
            'resultado' => $stmt->rowCount(),
            'id'        => self::$db->lastInsertId(),
        ];
    }

    // Actualiza un registro usando sentencias preparadas
    public function actualizar(): int {
        $atributos = $this->atributos();

        $sets    = [];
        $valores = [];

        foreach ($atributos as $key => $value) {
            $sets[]    = "{$key} = ?";
            $valores[] = $value;
        }

        $valores[] = (int) $this->id;

        $query = "UPDATE " . static::$tabla
            . " SET " . implode(', ', $sets)
            . " WHERE id = ?"
            . " LIMIT 1";

        $stmt = self::$db->prepare($query);
        $stmt->execute($valores);

        return $stmt->rowCount();
    }

    // Elimina un registro usando sentencias preparadas
    public function eliminar(): int {
        $stmt = self::$db->prepare(
            "DELETE FROM " . static::$tabla . " WHERE id = ? LIMIT 1"
        );
        $stmt->execute([(int) $this->id]);
        return $stmt->rowCount();
    }

    public static function consultarSQL(string $query): array {
        // Consultar la base de datos
        $stmt = self::$db->query($query);

        // Iterar los resultados
        $array = [];
        if ($stmt) {
            while ($registro = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $array[] = static::crearObjeto($registro);
            }
            // Liberar la memoria
            $stmt->closeCursor();
        }

        // retornar los resultados
        return $array;
    }

    protected static function crearObjeto(array $registro): static
    {
        $objeto = new static();

        foreach ($registro as $key => $value) {
            if (!property_exists($objeto, $key)) {
                continue;
            }

            if ($value !== null) {
                $value = self::castearValor($objeto, $key, $value);
            }

            $objeto->$key = $value;
        }

        return $objeto;
    }

    private static function castearValor(object $objeto, string $key, mixed $value): mixed
    {
        $propiedad = new ReflectionProperty($objeto, $key);
        $tipo = $propiedad->getType();

        if ($tipo instanceof ReflectionNamedType) {
            return match ($tipo->getName()) {
                'int'    => (int) $value,
                'float'  => (float) $value,
                'bool'   => (bool) $value,
                'string' => (string) $value,
                default  => $value,
            };
        }

        return $value;
    }

    // Identificar y unir los atributos de la BD
    public function atributos(): array {
        $atributos = [];
        foreach (static::$columnasDB as $columna) {
            if ($columna === 'id') continue;
            $atributos[$columna] = $this->$columna;
        }
        return $atributos;
    }

    public function sanitizarAtributos(): array {
        $atributos = $this->atributos();
        $sanitizado = [];
        foreach ($atributos as $key => $value) {
            $sanitizado[$key] = is_null($value) ? '' : addslashes((string) $value);
        }
        return $sanitizado;
    }

    public function sincronizar(array $args = []): void
    {
        foreach ($args as $key => $value) {
            if (!property_exists($this, $key)) {
                continue;
            }

            $propiedad = new ReflectionProperty($this, $key);
            $tipo = $propiedad->getType();

            // Si el campo es nullable y llega vacío ('' o null), lo dejamos en NULL
            if ($tipo instanceof ReflectionNamedType && $tipo->allowsNull() && ($value === '' || $value === null)) {
                $this->$key = null;
                continue;
            }

            // Si no es nullable pero llega vacío, no lo tocamos (evita castear '' a 0 sin querer)
            if ($value === '' || $value === null) {
                continue;
            }

            if ($tipo instanceof ReflectionNamedType) {
                $this->$key = match ($tipo->getName()) {
                    'int'    => (int) $value,
                    'float'  => (float) $value,
                    'bool'   => (bool) $value,
                    'string' => (string) $value,
                    default  => $value,
                };
            } else {
                $this->$key = $value;
            }
        }
    }

    /**
     * Valida que $columna esté declarada en static::$columnasDB para evitar
     * inyección de nombres de columna arbitrarios en where() y belongsTo().
     *
     * @throws \InvalidArgumentException si la columna no está en la lista blanca.
     */
    private static function columnaSegura(string $columna): string
    {
        if (!in_array($columna, static::$columnasDB, true)) {
            throw new \InvalidArgumentException(
                "Columna '{$columna}' no está declarada en el modelo " . static::class . "."
            );
        }
        return $columna;
    }
}
