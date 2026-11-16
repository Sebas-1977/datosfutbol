<?php

declare(strict_types=1);

namespace Model;

class Torneo extends ActiveRecord
{
    public const FORMATOS = [
        'liga' => 'Liga',
        'copa' => 'Copa',
    ];

    /** Tipos de torneo deducidos de la configuración (ver tipo()) */
    public const TIPOS = [
        'liga'           => 'Liga',
        'grupos_playoff' => 'Grupos y playoff',
        'playoff'        => 'Playoff',
    ];

    /** Equipos admitidos en una liga */
    public const LIGA_EQUIPOS = [8, 10, 12, 16, 18, 20, 22, 24, 30];

    /** Equipos admitidos en una copa (con o sin grupos); con 4 solo hay playoff directo */
    public const COPA_EQUIPOS = [4, 8, 16, 32];

    /** Equipos por grupo admitidos según el total de la copa */
    public const TAMANOS_GRUPO = [
        8  => [4],
        16 => [4, 8],
        32 => [4, 8, 16],
    ];

    /** Nombre de la ronda eliminatoria según los equipos que la juegan */
    public const RONDAS = [
        32 => 'Dieciseisavos de final',
        16 => 'Octavos de final',
        8  => 'Cuartos de final',
        4  => 'Semifinales',
        2  => 'Final',
    ];

    protected static string $tabla = 'torneos';

    protected static array $columnasDB = [
        'id', 'nombre', 'formato', 'cantidad_equipos', 'tamano_grupo', 'ida_vuelta', 'publico',
    ];

    public string $nombre = '';
    public string $formato = 'liga';
    public int $cantidad_equipos = 8;
    public ?int $tamano_grupo = null;
    public int $ida_vuelta = 0;
    public int $publico = 0;

    // ---------- Reglas de estructura (fuente única, la usa también la pantalla) ----------

    /** @return int[] */
    public static function cantidadesPermitidas(string $formato): array
    {
        return match ($formato) {
            'liga'  => self::LIGA_EQUIPOS,
            'copa'  => self::COPA_EQUIPOS,
            default => [],
        };
    }

    /** @return int[] vacío si la copa solo puede ser de playoff directo */
    public static function tamanosGrupoPermitidos(int $cantidad): array
    {
        return self::TAMANOS_GRUPO[$cantidad] ?? [];
    }

    /**
     * Clasificados por grupo: mínimo 1, máximo la mitad del grupo, y potencia de 2
     * para que la llave siempre cierre.
     *
     * @return int[]
     */
    public static function clasificanPermitidos(int $tamanoGrupo): array
    {
        $valores = [];
        for ($n = 1; $n <= intdiv($tamanoGrupo, 2); $n *= 2) {
            $valores[] = $n;
        }

        return $valores;
    }

    /**
     * Nombres de las rondas eliminatorias para N equipos, de la primera a la final.
     *
     * @return string[]
     */
    public static function rondas(int $equipos): array
    {
        if (!isset(self::RONDAS[$equipos])) {
            return [];
        }

        $nombres = [];
        for ($n = $equipos; $n >= 2; $n = intdiv($n, 2)) {
            $nombres[] = self::RONDAS[$n];
        }

        return $nombres;
    }

    /** ¿La combinación formato + equipos + tamaño de grupo es válida? */
    public static function combinacionValida(string $formato, int $cantidad, ?int $tamanoGrupo): bool
    {
        if (!in_array($cantidad, self::cantidadesPermitidas($formato), true)) {
            return false;
        }

        if ($formato === 'liga') {
            return $tamanoGrupo === null;
        }

        return $tamanoGrupo === null
            || in_array($tamanoGrupo, self::tamanosGrupoPermitidos($cantidad), true);
    }

    /** Tipo de torneo, deducido de la configuración: liga, grupos_playoff o playoff */
    public static function tipo(string $formato, ?int $tamanoGrupo): string
    {
        if ($formato === 'liga') {
            return 'liga';
        }

        return $tamanoGrupo === null ? 'playoff' : 'grupos_playoff';
    }

    /**
     * Fases (y cantidad de grupos) que corresponden a la configuración, o null si es inválida.
     * $clasifican solo se usa en copas con grupos.
     *
     * @return array{grupos:int, fases:array<int, array{nombre:string,tipo:string,orden:int,clasifican:?int}>}|null
     */
    public static function estructura(string $formato, int $cantidad, ?int $tamanoGrupo, ?int $clasifican = null): ?array
    {
        if (!self::combinacionValida($formato, $cantidad, $tamanoGrupo)) {
            return null;
        }

        if ($formato === 'liga') {
            return [
                'grupos' => 0,
                'fases'  => [['nombre' => 'Fase regular', 'tipo' => 'liga', 'orden' => 1, 'clasifican' => null]],
            ];
        }

        $fases = [];
        $grupos = 0;
        $orden = 1;
        $clasificados = $cantidad;

        if ($tamanoGrupo !== null) {
            if ($clasifican === null || !in_array($clasifican, self::clasificanPermitidos($tamanoGrupo), true)) {
                return null;
            }

            $grupos = intdiv($cantidad, $tamanoGrupo);
            $clasificados = $grupos * $clasifican;
            $fases[] = ['nombre' => 'Fase de grupos', 'tipo' => 'grupos', 'orden' => $orden++, 'clasifican' => $clasifican];
        }

        foreach (self::rondas($clasificados) as $nombre) {
            $fases[] = ['nombre' => $nombre, 'tipo' => 'eliminatoria', 'orden' => $orden++, 'clasifican' => null];
        }

        return ['grupos' => $grupos, 'fases' => $fases];
    }

    // ---------- Validación ----------

    public function validar(): array
    {
        parent::validar();

        $nombre = trim($this->nombre);
        if ($nombre === '') {
            static::setAlerta('error', 'El nombre del torneo es obligatorio.');
        } elseif (mb_strlen($nombre) > 100) {
            static::setAlerta('error', 'El nombre del torneo no puede superar los 100 caracteres.');
        }

        if (!array_key_exists($this->formato, self::FORMATOS)) {
            static::setAlerta('error', 'El formato del torneo no es válido.');
        } else {
            $this->validarConfiguracion();
        }

        if (!in_array($this->ida_vuelta, [0, 1], true)) {
            static::setAlerta('error', 'Las ruedas del torneo no son válidas.');
        }

        $this->publico = $this->publico === 1 ? 1 : 0;

        return static::getAlertas();
    }

    private function validarConfiguracion(): void
    {
        $cantidades = self::cantidadesPermitidas($this->formato);

        if (!in_array($this->cantidad_equipos, $cantidades, true)) {
            static::setAlerta(
                'error',
                'Una ' . mb_strtolower(self::FORMATOS[$this->formato]) . ' admite ' . implode(', ', $cantidades) . ' equipos.'
            );
            return;
        }

        if ($this->tamano_grupo === null) {
            return;
        }

        if ($this->formato === 'liga') {
            static::setAlerta('error', 'Una liga no tiene grupos.');
            return;
        }

        $tamanos = self::tamanosGrupoPermitidos($this->cantidad_equipos);
        if (!in_array($this->tamano_grupo, $tamanos, true)) {
            static::setAlerta(
                'error',
                $tamanos
                    ? 'Con ' . $this->cantidad_equipos . ' equipos los grupos pueden ser de ' . implode(', ', $tamanos) . '.'
                    : 'Con ' . $this->cantidad_equipos . ' equipos la copa es de playoff directo, sin grupos.'
            );
        }
    }

    /** Torneos marcados como públicos, el más nuevo primero */
    public static function publicos(): array
    {
        $db   = static::conexion();
        $stmt = $db->prepare('SELECT id, nombre FROM torneos WHERE publico = 1 ORDER BY id DESC');
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
