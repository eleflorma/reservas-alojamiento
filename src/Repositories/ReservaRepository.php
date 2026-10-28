<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

class ReservaRepository
{
    public function __construct(
        private PDO $conexion
    ) {
    }

    /**
     * Obtiene las reservas aplicando los filtros disponibles.
     */
    public function obtenerTodas(
        ?string $estado = null,
        ?string $busqueda = null,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null
    ): array {
        $sql = "SELECT
                    id,
                    nombre_huesped,
                    email_huesped,
                    nombre_alojamiento,
                    fecha_entrada,
                    fecha_salida,
                    estado,
                    importe,
                    fecha_creacion
                FROM reserva";

        $condiciones = [];
        $parametros = [];

        if ($estado !== null) {
            $condiciones[] = "estado = :estado";
            $parametros['estado'] = $estado;
        }

        if ($busqueda !== null && $busqueda !== '') {
            $condiciones[] = "nombre_huesped LIKE :busqueda";
            $parametros['busqueda'] = '%' . $busqueda . '%';
        }

        // El rango se aplica a la fecha de entrada.
        if ($fechaDesde !== null) {
            $condiciones[] = "fecha_entrada >= :fechaDesde";
            $parametros['fechaDesde'] = $fechaDesde;
        }

        if ($fechaHasta !== null) {
            $condiciones[] = "fecha_entrada <= :fechaHasta";
            $parametros['fechaHasta'] = $fechaHasta;
        }

        if ($condiciones !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        $sql .= ' ORDER BY fecha_creacion DESC';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll();
    }

    /**
     * Obtiene una reserva por su identificador.
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql = "SELECT
                    id,
                    nombre_huesped,
                    email_huesped,
                    nombre_alojamiento,
                    fecha_entrada,
                    fecha_salida,
                    estado,
                    importe,
                    notas,
                    fecha_creacion
                FROM reserva
                WHERE id = :id";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(['id' => $id]);

        $reserva = $consulta->fetch();

        return $reserva === false ? null : $reserva;
    }

    /**
     * Obtiene el historial de una reserva.
     */
    public function obtenerEventosPorReserva(int $reservaId): array
    {
        $sql = "SELECT
                    id,
                    reserva_id,
                    tipo,
                    descripcion,
                    fecha_creacion
                FROM reserva_evento
                WHERE reserva_id = :reserva_id
                ORDER BY fecha_creacion ASC, id ASC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute(['reserva_id' => $reservaId]);

        return $consulta->fetchAll();
    }

    /**
     * Crea una reserva y registra su evento inicial.
     *
     * La reserva y su evento se guardan en una transacción.
     */
    public function crear(array $datos): int
    {
        try {
            $this->conexion->beginTransaction();

            $sql = "INSERT INTO reserva (
                        nombre_huesped,
                        email_huesped,
                        nombre_alojamiento,
                        fecha_entrada,
                        fecha_salida,
                        estado,
                        importe,
                        notas
                    ) VALUES (
                        :nombre_huesped,
                        :email_huesped,
                        :nombre_alojamiento,
                        :fecha_entrada,
                        :fecha_salida,
                        'pendiente',
                        :importe,
                        :notas
                    )";

            $consulta = $this->conexion->prepare($sql);

            $consulta->execute([
                'nombre_huesped' => $datos['nombre_huesped'],
                'email_huesped' => $datos['email_huesped'],
                'nombre_alojamiento' => $datos['nombre_alojamiento'],
                'fecha_entrada' => $datos['fecha_entrada'],
                'fecha_salida' => $datos['fecha_salida'],
                'importe' => $datos['importe'],
                'notas' => $datos['notas'] ?? null,
            ]);

            $id = (int) $this->conexion->lastInsertId();

            $this->registrarEvento(
                $id,
                'creacion',
                'Reserva creada'
            );

            $this->conexion->commit();

            return $id;
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Cambia el estado y registra el cambio en el historial.
     *
     * Devuelve la reserva actualizada o null si no existe.
     */
    public function cambiarEstado(
        int $id,
        string $nuevoEstado
    ): ?array {
        try {
            $this->conexion->beginTransaction();

            // Bloqueamos la fila mientras hacemos el cambio.
            $consulta = $this->conexion->prepare(
                "SELECT id, estado
                 FROM reserva
                 WHERE id = :id
                 FOR UPDATE"
            );

            $consulta->execute(['id' => $id]);
            $reservaActual = $consulta->fetch();

            if ($reservaActual === false) {
                $this->conexion->rollBack();

                return null;
            }

            $estadoAnterior = $reservaActual['estado'];

            if ($estadoAnterior !== $nuevoEstado) {
                $consulta = $this->conexion->prepare(
                    "UPDATE reserva
                     SET estado = :estado
                     WHERE id = :id"
                );

                $consulta->execute([
                    'estado' => $nuevoEstado,
                    'id' => $id,
                ]);

                $this->registrarEvento(
                    $id,
                    'estado',
                    "Estado cambiado de {$estadoAnterior} a {$nuevoEstado}"
                );
            }

            $consulta = $this->conexion->prepare(
                "SELECT
                    id,
                    nombre_huesped,
                    email_huesped,
                    nombre_alojamiento,
                    fecha_entrada,
                    fecha_salida,
                    estado,
                    importe,
                    notas,
                    fecha_creacion
                 FROM reserva
                 WHERE id = :id"
            );

            $consulta->execute(['id' => $id]);
            $reservaActualizada = $consulta->fetch();

            $this->conexion->commit();

            return $reservaActualizada === false
                ? null
                : $reservaActualizada;
        } catch (Throwable $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Guarda un evento en el historial.
     */
    private function registrarEvento(
        int $reservaId,
        string $tipo,
        string $descripcion
    ): void {
        $sql = "INSERT INTO reserva_evento (
                    reserva_id,
                    tipo,
                    descripcion
                ) VALUES (
                    :reserva_id,
                    :tipo,
                    :descripcion
                )";

        $consulta = $this->conexion->prepare($sql);

        $consulta->execute([
            'reserva_id' => $reservaId,
            'tipo' => $tipo,
            'descripcion' => $descripcion,
        ]);
    }
}
