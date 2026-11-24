<?php

declare(strict_types=1);

final class Device
{
    public static function findById(int $id): ?array
    {
        $stmt = db()->prepare('SELECT * FROM devices WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function findByDeviceId(string $deviceId): ?array
    {
        $stmt = db()->prepare('SELECT * FROM devices WHERE device_id = ?');
        $stmt->execute([$deviceId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function findByTokenHash(string $tokenHash): ?array
    {
        $stmt = db()->prepare('SELECT * FROM devices WHERE token_hash = ?');
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function all(): array
    {
        $stmt = db()->query('SELECT * FROM devices ORDER BY name ASC, id ASC');
        return $stmt->fetchAll();
    }

    public static function locations(): array
    {
        $stmt = db()->query('SELECT DISTINCT location FROM devices ORDER BY location ASC');
        return array_column($stmt->fetchAll(), 'location');
    }

    public static function create(array $data): array
    {
        $pdo = db();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $deviceId = 'DEV-' . strtoupper(bin2hex(random_bytes(8)));
            $token = bin2hex(random_bytes(32));
            $now = now_utc();
            try {
                $stmt = $pdo->prepare(
                    'INSERT INTO devices
                    (device_id, name, location, sensor_type, token_hash, token_prefix, min_temperature, max_temperature, reporting_interval, is_active, last_seen_at, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?)'
                );
                $stmt->execute([
                    $deviceId,
                    $data['name'],
                    $data['location'],
                    $data['sensor_type'],
                    hash('sha256', $token),
                    substr($token, 0, 8),
                    $data['min_temperature'],
                    $data['max_temperature'],
                    $data['reporting_interval'],
                    $data['is_active'],
                    $now,
                    $now,
                ]);
                return [
                    'id' => (int) $pdo->lastInsertId(),
                    'device_id' => $deviceId,
                    'token' => $token,
                ];
            } catch (PDOException $e) {
                if (!self::isUniqueViolation($e)) {
                    throw $e;
                }
            }
        }
        throw new RuntimeException('Gagal menghasilkan kredensial unik.');
    }

    public static function update(int $id, array $data): void
    {
        $stmt = db()->prepare(
            'UPDATE devices
             SET name = ?, location = ?, sensor_type = ?, min_temperature = ?, max_temperature = ?, reporting_interval = ?, is_active = ?, updated_at = ?
             WHERE id = ?'
        );
        $stmt->execute([
            $data['name'],
            $data['location'],
            $data['sensor_type'],
            $data['min_temperature'],
            $data['max_temperature'],
            $data['reporting_interval'],
            $data['is_active'],
            now_utc(),
            $id,
        ]);
    }

    public static function regenerateToken(int $id): array
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT id, device_id FROM devices WHERE id = ? FOR UPDATE');
            $lock->execute([$id]);
            $device = $lock->fetch();
            if ($device === false) {
                $pdo->rollBack();
                throw new RuntimeException('Perangkat tidak ditemukan.');
            }
            $token = '';
            $saved = false;
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $token = bin2hex(random_bytes(32));
                try {
                    $stmt = $pdo->prepare(
                        'UPDATE devices SET token_hash = ?, token_prefix = ?, updated_at = ? WHERE id = ?'
                    );
                    $stmt->execute([hash('sha256', $token), substr($token, 0, 8), now_utc(), $id]);
                    $saved = true;
                    break;
                } catch (PDOException $e) {
                    if (!self::isUniqueViolation($e)) {
                        throw $e;
                    }
                }
            }
            if (!$saved) {
                throw new RuntimeException('Gagal menghasilkan token unik.');
            }
            $pdo->commit();
            return [
                'id' => (int) $device['id'],
                'device_id' => (string) $device['device_id'],
                'token' => $token,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function deactivate(int $id): void
    {
        $stmt = db()->prepare('UPDATE devices SET is_active = 0, updated_at = ? WHERE id = ?');
        $stmt->execute([now_utc(), $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = db()->prepare('DELETE FROM devices WHERE id = ?');
        $stmt->execute([$id]);
    }

    public static function listWithLatest(): array
    {
        $sql = 'SELECT d.*,
                       r.temperature AS latest_temperature,
                       r.humidity AS latest_humidity,
                       r.recorded_at AS latest_recorded_at
                FROM devices d
                LEFT JOIN sensor_readings r ON r.id = (
                    SELECT sr.id
                    FROM sensor_readings sr
                    WHERE sr.device_id = d.id
                    ORDER BY sr.recorded_at DESC, sr.id DESC
                    LIMIT 1
                )
                ORDER BY d.name ASC, d.id ASC';
        return db()->query($sql)->fetchAll();
    }

    public static function dashboardPayload(): array
    {
        $rows = self::listWithLatest();
        $now = time();
        $summary = [
            'total' => count($rows),
            'online' => 0,
            'offline' => 0,
            'warnings' => 0,
        ];
        $devices = [];
        foreach ($rows as $row) {
            $connection = device_connection_status($row, $now);
            if ($connection === 'online') {
                $summary['online']++;
            } elseif ($connection === 'offline') {
                $summary['offline']++;
            }
            $tempStatus = null;
            $stale = $connection !== 'online';
            if ($row['latest_temperature'] !== null) {
                $tempStatus = temperature_status(
                    $row['latest_temperature'],
                    $row['min_temperature'],
                    $row['max_temperature']
                );
                if ($connection === 'online' && $tempStatus !== 'normal') {
                    $summary['warnings']++;
                }
            }
            $devices[] = [
                'id' => (int) $row['id'],
                'device_id' => (string) $row['device_id'],
                'name' => (string) $row['name'],
                'location' => (string) $row['location'],
                'temperature' => $row['latest_temperature'] === null ? null : format_decimal($row['latest_temperature']),
                'humidity' => $row['latest_humidity'] === null ? null : format_decimal($row['latest_humidity']),
                'temperature_status' => $tempStatus,
                'temperature_label' => $tempStatus === null ? '—' : temperature_status_label($tempStatus),
                'connection_status' => $connection,
                'connection_label' => connection_status_label($connection),
                'last_seen_label' => format_jakarta($row['last_seen_at'] === null ? null : (string) $row['last_seen_at']),
                'stale' => $stale,
            ];
        }

        return [
            'success' => true,
            'summary' => $summary,
            'devices' => $devices,
            'updated_at' => format_jakarta(now_utc()) . ' WIB',
        ];
    }

    private static function isUniqueViolation(PDOException $e): bool
    {
        return (string) ($e->errorInfo[0] ?? '') === '23000';
    }
}
