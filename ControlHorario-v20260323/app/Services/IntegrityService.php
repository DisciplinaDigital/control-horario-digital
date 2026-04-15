<?php

namespace App\Services;

use App\Core\Database;

class IntegrityService
{
    private Database $db;
    private string $secret;

    public function __construct()
    {
        $this->db     = Database::getInstance();
        $this->secret = trim((string)($_ENV['HMAC_SECRET'] ?? ''));
        if ($this->secret === '') {
            throw new \RuntimeException('HMAC_SECRET no configurado.');
        }
    }

    /**
     * Generates HMAC-SHA256 hash for a fichaje record
     */
    public function generateHash(array $fichajeData, ?string $hashAnterior = null): string
    {
        $hashData = implode('|', [
            $this->normalizeScalar($fichajeData['usuario_id'] ?? null),
            $this->normalizeScalar($fichajeData['tipo'] ?? null),
            $this->normalizeScalar($fichajeData['fecha_hora'] ?? null),
            $this->normalizeCoordinate($fichajeData['latitud'] ?? null),
            $this->normalizeCoordinate($fichajeData['longitud'] ?? null),
            $hashAnterior ?? 'GENESIS',
        ]);

        return hash_hmac('sha256', $hashData, $this->secret);
    }

    private function normalizeScalar(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string)$value);
    }

    private function normalizeCoordinate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return number_format((float)$value, 8, '.', '');
    }

    /**
     * Gets the last hash in the chain for a user
     */
    public function getLastHash(int $userId): ?string
    {
        $result = $this->db->fetchOne(
            "SELECT hash_integridad FROM fichajes
             WHERE usuario_id = ?
             ORDER BY id DESC
             LIMIT 1",
            [$userId]
        );

        return $result ? $result['hash_integridad'] : null;
    }

    /**
     * Verifies a single fichaje record integrity
     */
    public function verifyFichaje(array $fichaje): bool
    {
        // Get previous hash
        $previous = $this->db->fetchOne(
            "SELECT hash_integridad FROM fichajes
             WHERE usuario_id = ?
               AND id < ?
             ORDER BY id DESC
             LIMIT 1",
            [
                $fichaje['usuario_id'],
                $fichaje['id'],
            ]
        );

        $hashAnterior = $previous ? $previous['hash_integridad'] : null;

        // Verify stored hash_anterior matches
        $storedAnterior = $fichaje['hash_anterior'];
        if ($storedAnterior !== $hashAnterior && !($storedAnterior === null && $hashAnterior === null)) {
            return false;
        }

        $expectedHash = $this->generateHash($fichaje, $hashAnterior);

        return hash_equals($expectedHash, $fichaje['hash_integridad']);
    }

    /**
     * Verifies the entire chain for a user
     * Returns [valid => bool, errors => array, total => int, verified => int]
     */
    public function verifyFichajeChain(int $userId): array
    {
        $fichajes = $this->db->fetchAll(
            "SELECT * FROM fichajes WHERE usuario_id = ? ORDER BY id ASC",
            [$userId]
        );

        $errors   = [];
        $total    = count($fichajes);
        $verified = 0;
        $prevHash = null;

        foreach ($fichajes as $fichaje) {
            $expectedHash = $this->generateHash($fichaje, $prevHash);

            // Check stored previous hash
            $storedAnterior = $fichaje['hash_anterior'];
            $anteriorOk     = ($storedAnterior === $prevHash) ||
                              ($storedAnterior === null && $prevHash === null);

            if (!$anteriorOk) {
                $errors[] = [
                    'id'      => $fichaje['id'],
                    'fecha'   => $fichaje['fecha_hora'],
                    'tipo'    => $fichaje['tipo'],
                    'error'   => 'Hash anterior no coincide (posible manipulación)',
                ];
            } elseif (!hash_equals($expectedHash, $fichaje['hash_integridad'])) {
                $errors[] = [
                    'id'    => $fichaje['id'],
                    'fecha' => $fichaje['fecha_hora'],
                    'tipo'  => $fichaje['tipo'],
                    'error' => 'Hash de integridad inválido (registro modificado)',
                ];
            } else {
                $verified++;
            }

            if ($anteriorOk && hash_equals($expectedHash, $fichaje['hash_integridad'])) {
                $prevHash = $fichaje['hash_integridad'];
            } else {
                $prevHash = $expectedHash;
            }
        }

        return [
            'valid'    => empty($errors),
            'errors'   => $errors,
            'total'    => $total,
            'verified' => $verified,
        ];
    }

    /**
     * Verifies all users' chains
     * Returns array of [usuario_id, nombre, apellidos, valid, total, verified, errors]
     */
    public function verifyAllChains(): array
    {
        $usuarios = $this->db->fetchAll(
            "SELECT DISTINCT f.usuario_id, u.nombre, u.apellidos, u.email
             FROM fichajes f
             JOIN usuarios u ON u.id = f.usuario_id
             ORDER BY u.apellidos, u.nombre"
        );

        $results = [];

        foreach ($usuarios as $usuario) {
            $chain = $this->verifyFichajeChain($usuario['usuario_id']);
            $results[] = array_merge($usuario, $chain);
        }

        return $results;
    }

    /**
     * Generates a verification report
     */
    public function generateVerificationReport(): array
    {
        $allChains  = $this->verifyAllChains();
        $totalUsers = count($allChains);
        $validUsers = count(array_filter($allChains, fn($c) => $c['valid']));
        $totalRecords = array_sum(array_column($allChains, 'total'));
        $verifiedRecords = array_sum(array_column($allChains, 'verified'));

        return [
            'generated_at'     => date('Y-m-d H:i:s'),
            'total_users'      => $totalUsers,
            'valid_users'      => $validUsers,
            'invalid_users'    => $totalUsers - $validUsers,
            'total_records'    => $totalRecords,
            'verified_records' => $verifiedRecords,
            'chains'           => $allChains,
        ];
    }
}
