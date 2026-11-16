<?php

declare(strict_types=1);

namespace Model;

/** Consultas de solo lectura del plantel para el sitio público. */
class PlantelPublico
{
    public const POSICIONES = [
        'PT'  => 'Porteros',
        'DEF' => 'Defensas',
        'MED' => 'Mediocampistas',
        'DEL' => 'Delanteros',
    ];

    private static function filas(string $sql, array $params = []): array
    {
        $st = Partido::conexion()->prepare($sql);
        $st->execute($params);

        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** Clubes que juegan o tienen plantel en el torneo, con su cantidad de jugadores. */
    public static function clubesDelTorneo(int $idTorneo): array
    {
        return static::filas(
            'SELECT e.id, e.nombre, e.escudo,'
            . ' (SELECT COUNT(*) FROM jugadores_equipo je'
            . '   WHERE je.id_equipo = e.id AND je.id_torneo = ?) AS cantidad'
            . ' FROM equipos e'
            . ' WHERE e.id IN (SELECT id_equipo_local FROM partidos WHERE id_torneo = ?)'
            . '    OR e.id IN (SELECT id_equipo_visitante FROM partidos WHERE id_torneo = ?)'
            . '    OR e.id IN (SELECT id_equipo FROM jugadores_equipo WHERE id_torneo = ?)'
            . ' ORDER BY e.nombre',
            [$idTorneo, $idTorneo, $idTorneo, $idTorneo]
        );
    }

    /**
     * Jugadores del torneo agrupados: [id_equipo => [posicion => [jugadores]]].
     * La posición vacía queda en la clave '' (se muestra como "Sin posición").
     */
    public static function jugadoresPorClub(int $idTorneo, ?int $idEquipo = null): array
    {
        $sql = 'SELECT je.id_equipo, je.numero_camiseta, je.posicion,'
        . ' j.id, j.nombre, j.apellido, j.seudonimo, j.pais, j.foto'
        . ' FROM jugadores_equipo je'
        . ' INNER JOIN jugadores j ON j.id = je.id_jugador'
        . ' WHERE je.id_torneo = ?';
        $params = [$idTorneo];

        if ($idEquipo) {
            $sql .= ' AND je.id_equipo = ?';
            $params[] = $idEquipo;
        }

        $sql .= ' ORDER BY je.numero_camiseta IS NULL, je.numero_camiseta, j.apellido, j.nombre';

        $agrupado = [];
        foreach (static::filas($sql, $params) as $fila) {
            $agrupado[(int) $fila['id_equipo']][(string) $fila['posicion']][] = $fila;
        }

        return $agrupado;
    }
}