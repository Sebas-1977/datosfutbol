<?php

declare(strict_types=1);

namespace Model;

class Fase extends ActiveRecord
{
    public const TIPOS = [
        'liga'         => 'Liga',
        'grupos'       => 'Fase de grupos',
        'eliminatoria' => 'Eliminatoria',
    ];

    /** Tipos de fase que admite cada formato de torneo */
    public const TIPOS_POR_FORMATO = [
        'liga' => ['liga'],
        'copa' => ['grupos', 'eliminatoria'],
    ];

    /** Tipos que el torneo puede tener una sola vez (las eliminatorias se repiten) */
    private const TIPOS_UNICOS = ['liga', 'grupos'];

    protected static string $tabla = 'fases';

    protected static array $columnasDB = [
        'id', 'id_torneo', 'nombre', 'tipo', 'ida_vuelta', 'orden', 'clasifican',
    ];

    public ?int $id_torneo = null;
    public string $nombre = '';
    public string $tipo = 'liga';
    public int $ida_vuelta = 0;
    public int $orden = 1;
    public ?int $clasifican = null;

    public function validar(): array
    {
        parent::validar();

        // "Clasifican" solo tiene sentido en fases de grupos: en las demás se descarta
        if ($this->tipo !== 'grupos') {
            $this->clasifican = null;
        }

        $nombre = trim($this->nombre);
        if ($nombre === '') {
            static::setAlerta('error', 'El nombre de la fase es obligatorio.');
        } elseif (mb_strlen($nombre) > 100) {
            static::setAlerta('error', 'El nombre de la fase no puede superar los 100 caracteres.');
        }

        $tipoValido = array_key_exists($this->tipo, self::TIPOS);
        if (!$tipoValido) {
            static::setAlerta('error', 'El tipo de fase no es válido.');
        }

        if (!in_array($this->ida_vuelta, [0, 1], true)) {
            static::setAlerta('error', 'Las ruedas de la fase no son válidas.');
        }

        $ordenValido = $this->orden >= 1 && $this->orden <= 255;
        if (!$ordenValido) {
            static::setAlerta('error', 'El orden de la fase debe estar entre 1 y 255.');
        }

        $this->validarTorneo($tipoValido, $ordenValido);

        return static::getAlertas();
    }

    /** Guarda la fase y deja torneos.ida_vuelta igual al de la primera fase */
    public function guardar(): int|array
    {
        $resultado = parent::guardar();
        $this->sincronizarTorneo();

        return $resultado;
    }

    /** Elimina la fase y, si se borró, recalcula torneos.ida_vuelta */
    public function eliminar(): int
    {
        $filas = parent::eliminar();

        if ($filas > 0) {
            $this->sincronizarTorneo();
        }

        return $filas;
    }

    private function validarTorneo(bool $tipoValido, bool $ordenValido): void
    {
        if (!$this->id_torneo) {
            static::setAlerta('error', 'El torneo es obligatorio.');
            return;
        }

        $torneo = Torneo::find($this->id_torneo);
        if (!$torneo) {
            static::setAlerta('error', 'El torneo seleccionado no existe.');
            return;
        }

        if ($tipoValido) {
            $this->validarTipo($torneo);
        }

        if ($ordenValido && $this->existeEnTorneo('orden', $this->orden)) {
            static::setAlerta('error', 'Ya hay otra fase con el orden ' . $this->orden . ' en este torneo.');
        }
    }

    private function validarTipo(Torneo $torneo): void
    {
        $permitidos = self::TIPOS_POR_FORMATO[$torneo->formato] ?? [];

        if (!in_array($this->tipo, $permitidos, true)) {
            static::setAlerta(
                'error',
                'Un torneo de formato «' . (Torneo::FORMATOS[$torneo->formato] ?? $torneo->formato)
                . '» no admite fases de tipo «' . self::TIPOS[$this->tipo] . '».'
            );
            return;
        }

        if (in_array($this->tipo, self::TIPOS_UNICOS, true) && $this->existeEnTorneo('tipo', $this->tipo)) {
            static::setAlerta('error', 'El torneo ya tiene una fase de tipo «' . self::TIPOS[$this->tipo] . '».');
        }

        if ($this->tipo !== 'grupos') {
            return;
        }

        if ($torneo->tamano_grupo === null) {
            static::setAlerta('error', 'El torneo no está configurado con grupos.');
            return;
        }

        $validos = Torneo::clasificanPermitidos($torneo->tamano_grupo);
        if ($this->clasifican === null || !in_array($this->clasifican, $validos, true)) {
            static::setAlerta(
                'error',
                'Con grupos de ' . $torneo->tamano_grupo . ' pueden clasificar ' . implode(', ', $validos) . ' por grupo.'
            );
        }
    }

    /** ¿Otra fase del mismo torneo (distinta de esta) tiene ese valor? */
    private function existeEnTorneo(string $columna, string|int $valor): bool
    {
        // $columna es siempre un literal interno ('tipo' u 'orden'), nunca input del cliente
        $stmt = self::conexion()->prepare(
            "SELECT COUNT(*) FROM fases WHERE id_torneo = ? AND {$columna} = ? AND id <> ?"
        );
        $stmt->execute([$this->id_torneo, $valor, $this->id ?? 0]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * torneos.ida_vuelta copia el valor de la fase de menor orden.
     * Si el torneo se quedó sin fases, no se toca (conserva el último valor).
     */
    private function sincronizarTorneo(): void
    {
        if (!$this->id_torneo) {
            return;
        }

        $db = self::conexion();

        $stmt = $db->prepare(
            'SELECT ida_vuelta FROM fases WHERE id_torneo = ? ORDER BY orden ASC, id ASC LIMIT 1'
        );
        $stmt->execute([$this->id_torneo]);
        $idaVuelta = $stmt->fetchColumn();

        if ($idaVuelta === false) {
            return;
        }

        $upd = $db->prepare('UPDATE torneos SET ida_vuelta = ? WHERE id = ?');
        $upd->execute([(int) $idaVuelta, $this->id_torneo]);
    }
}
