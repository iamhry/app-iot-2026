<?php

declare(strict_types=1);

final class SensorReading
{
    public static function countForDevice(int $devicePk): int
    {
        $stmt = db()->prepare('SELECT COUNT(*) FROM sensor_readings WHERE device_id = ?');
        $stmt->execute([$devicePk]);
        return (int) $stmt->fetchColumn();
    }

    public static function latestForDevice(int $devicePk): ?array
    {
        $stmt = db()->prepare(
            'SELECT id, device_id, temperature, humidity, recorded_at, received_at
             FROM sensor_readings
             WHERE device_id = ?
             ORDER BY recorded_at DESC, id DESC
             LIMIT 1'
        );
        $stmt->execute([$devicePk]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function store(int $devicePk, string $temperature, string $humidity, string $recordedAt, string $tokenHash): void
    {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare(
                'SELECT id, is_active, token_hash FROM devices WHERE id = ? AND token_hash = ? FOR UPDATE'
            );
            $lock->execute([$devicePk, $tokenHash]);
            $locked = $lock->fetch();
            if ($locked === false || !(int) $locked['is_active']) {
                $pdo->rollBack();
                throw new ReadingRejected((int) ($locked === false ? 401 : 403));
            }
            $receivedAt = now_utc();
            $insert = $pdo->prepare(
                'INSERT INTO sensor_readings (device_id, temperature, humidity, recorded_at, received_at)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $insert->execute([$devicePk, $temperature, $humidity, $recordedAt, $receivedAt]);
            $update = $pdo->prepare('UPDATE devices SET last_seen_at = ? WHERE id = ? AND token_hash = ? AND is_active = 1');
            $update->execute([$receivedAt, $devicePk, $tokenHash]);
            if ($update->rowCount() !== 1) {
                $pdo->rollBack();
                throw new ReadingRejected(401);
            }
            $pdo->commit();
        } catch (ReadingRejected $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function chart(int $devicePk, string $period): array
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $bucketSeconds = null;
        switch ($period) {
            case '6h':
                $from = $now->modify('-6 hours');
                $bucketSeconds = 300;
                break;
            case '24h':
                $from = $now->modify('-24 hours');
                $bucketSeconds = 900;
                break;
            case '7d':
                $from = $now->modify('-7 days');
                $bucketSeconds = 3600;
                break;
            default:
                $period = '1h';
                $from = $now->modify('-1 hour');
                break;
        }
        $fromSql = $from->format('Y-m-d H:i:s');
        if ($bucketSeconds === null) {
            $stmt = db()->prepare(
                'SELECT temperature, humidity, recorded_at
                 FROM sensor_readings
                 WHERE device_id = ? AND recorded_at >= ?
                 ORDER BY recorded_at ASC, id ASC
                 LIMIT 500'
            );
            $stmt->execute([$devicePk, $fromSql]);
            $rows = $stmt->fetchAll();
        } else {
            $stmt = db()->prepare(
                'SELECT
                    FROM_UNIXTIME(FLOOR(UNIX_TIMESTAMP(recorded_at) / ?) * ?) AS bucket,
                    AVG(temperature) AS temperature,
                    AVG(humidity) AS humidity
                 FROM sensor_readings
                 WHERE device_id = ? AND recorded_at >= ?
                 GROUP BY bucket
                 ORDER BY bucket ASC
                 LIMIT 500'
            );
            $stmt->execute([$bucketSeconds, $bucketSeconds, $devicePk, $fromSql]);
            $rows = $stmt->fetchAll();
        }

        $labels = [];
        $temperatures = [];
        $humidities = [];
        foreach ($rows as $row) {
            $stamp = (string) ($row['recorded_at'] ?? $row['bucket']);
            $labels[] = format_jakarta($stamp, 'd M H:i');
            $temperatures[] = round((float) $row['temperature'], 2);
            $humidities[] = round((float) $row['humidity'], 2);
        }

        return [
            'success' => true,
            'period' => $period,
            'labels' => $labels,
            'temperature' => $temperatures,
            'humidity' => $humidities,
            'updated_at' => format_jakarta(now_utc()) . ' WIB',
        ];
    }

    public static function search(array $filters, int $page, int $perPage): array
    {
        [$where, $params] = self::where($filters);
        $count = db()->prepare(
            'SELECT COUNT(*)
             FROM sensor_readings r
             INNER JOIN devices d ON d.id = r.device_id
             WHERE ' . $where
        );
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $sql = 'SELECT r.id, r.temperature, r.humidity, r.recorded_at, r.received_at,
                       d.device_id, d.name, d.location
                FROM sensor_readings r
                INNER JOIN devices d ON d.id = r.device_id
                WHERE ' . $where . '
                ORDER BY r.recorded_at DESC, r.id DESC
                LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return [
            'rows' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    public static function exportStatement(array $filters): PDOStatement
    {
        [$where, $params] = self::where($filters);
        $pdo = db();
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        $sql = 'SELECT r.temperature, r.humidity, r.recorded_at, r.received_at,
                       d.device_id, d.name, d.location
                FROM sensor_readings r
                INNER JOIN devices d ON d.id = r.device_id
                WHERE ' . $where . '
                ORDER BY r.recorded_at DESC, r.id DESC
                LIMIT ' . (int) CSV_MAX_ROWS;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    private static function where(array $filters): array
    {
        $where = ['1 = 1'];
        $params = [];
        if (!empty($filters['device_pk'])) {
            $where[] = 'r.device_id = ?';
            $params[] = (int) $filters['device_pk'];
        }
        if (($filters['location'] ?? '') !== '') {
            $where[] = 'd.location = ?';
            $params[] = (string) $filters['location'];
        }
        if (($filters['q'] ?? '') !== '') {
            $like = '%' . self::likeEscape((string) $filters['q']) . '%';
            $where[] = "(d.name LIKE ? ESCAPE '\\\\' OR d.device_id LIKE ? ESCAPE '\\\\' OR d.location LIKE ? ESCAPE '\\\\')";
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (($filters['from_utc'] ?? '') !== '') {
            $where[] = 'r.recorded_at >= ?';
            $params[] = (string) $filters['from_utc'];
        }
        if (($filters['to_utc'] ?? '') !== '') {
            $where[] = 'r.recorded_at <= ?';
            $params[] = (string) $filters['to_utc'];
        }
        return [implode(' AND ', $where), $params];
    }

    private static function likeEscape(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}

final class ReadingRejected extends RuntimeException
{
    public function __construct(public int $status)
    {
        parent::__construct('Pengukuran ditolak.');
    }
}
