<?php

declare(strict_types=1);

namespace Model;

class Destacado extends ActiveRecord
{
    public const TIPOS = ['equipo', 'partido', 'jugador', 'nota'];

    protected static string $tabla = 'destacados';
    protected static array $columnasDB = [
        'id', 'titulo', 'bajada', 'cuerpo', 'imagen', 'tipo', 'id_referencia',
        'id_torneo', 'orden', 'activo',
    ];

    public string $titulo = '';
    public ?string $bajada = null;
    public ?string $cuerpo = null;
    public ?string $imagen = null;
    public ?string $tipo = null;
    public ?int $id_referencia = null;
    public ?int $id_torneo = null;
    public int $orden = 0;
    public int $activo = 1;

    public function validar(): array
    {
        parent::validar();

        if ($this->tipo === '') { $this->tipo = null; }

        $this->titulo = trim($this->titulo);
        $this->bajada = $this->bajada !== null ? trim($this->bajada) : null;
        $this->cuerpo = $this->cuerpo !== null ? trim($this->cuerpo) : null;

        if ($this->titulo === '') {
            static::setAlerta('error', 'El titular es obligatorio.');
        } elseif (mb_strlen($this->titulo) > 150) {
            static::setAlerta('error', 'El titular no puede superar los 150 caracteres.');
        }
        if ($this->bajada !== null && mb_strlen($this->bajada) > 255) {
            static::setAlerta('error', 'La bajada no puede superar los 255 caracteres.');
        }
        if ($this->cuerpo !== null && mb_strlen($this->cuerpo) > 10000) {
            static::setAlerta('error', 'El texto no puede superar los 10.000 caracteres.');
        }
        if ($this->tipo !== null && !in_array($this->tipo, self::TIPOS, true)) {
            static::setAlerta('error', 'El tipo no es válido.');
        }
        if ($this->tipo === null || $this->tipo === 'nota') {
            $this->id_referencia = null;
        } elseif ($this->id_referencia === null || $this->id_referencia < 1) {
            static::setAlerta('error', 'Indicá el número del elemento al que se refiere.');
        }

        if ($this->id_torneo === null || $this->id_torneo < 1) {
            static::setAlerta('error', 'El destacado debe pertenecer a un torneo.');
        }

        $this->activo = $this->activo === 1 ? 1 : 0;

        return static::getAlertas();
    }

    /**
     * Destacados visibles para la portada pública, limitados a torneos públicos.
     *
     * @param  int[]  $idsPublicos  IDs de torneos marcados como públicos
     */
    public static function portada(int $limite = 5, array $idsPublicos = []): array
    {
        if (empty($idsPublicos)) {
            return [];
        }

        $db = static::conexion();
        $placeholders = implode(',', array_fill(0, count($idsPublicos), '?'));
        $sql = "SELECT id, titulo, bajada, imagen FROM destacados
                WHERE activo = 1 AND id_torneo IN ({$placeholders})
                ORDER BY orden ASC, id DESC LIMIT ?";
        $stmt = $db->prepare($sql);
        $i = 1;
        foreach ($idsPublicos as $idT) {
            $stmt->bindValue($i++, (int) $idT, \PDO::PARAM_INT);
        }
        $stmt->bindValue($i, $limite, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Destacados de un torneo específico.
     */
    public static function portadaDeTorneo(int $idTorneo, int $limite = 5): array
    {
        $db  = static::conexion();
        $sql = 'SELECT id, titulo, bajada, imagen FROM destacados
                WHERE activo = 1 AND id_torneo = ?
                ORDER BY orden ASC, id DESC
                LIMIT ?';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(1, $idTorneo, \PDO::PARAM_INT);
        $stmt->bindValue(2, $limite, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Un destacado activo para la página de detalle. */
    public static function activo(int $id): ?array
    {
        $stmt = static::conexion()->prepare(
            'SELECT d.* FROM destacados d'
            . ' INNER JOIN torneos t ON t.id = d.id_torneo'
            . ' WHERE d.id = ? AND d.activo = 1 AND t.publico = 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}
