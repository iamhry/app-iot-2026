<?php

declare(strict_types=1);

final class RateLimit
{
    public static function count(string $bucket, int $windowSeconds): int
    {
        $stmt = db()->prepare('SELECT hit_count FROM rate_limits WHERE bucket_key = ? AND window_start = ?');
        $stmt->execute([self::storeKey($bucket), self::windowStart($windowSeconds)]);
        return (int) $stmt->fetchColumn();
    }

    public static function hit(string $bucket, int $windowSeconds): int
    {
        $key = self::storeKey($bucket);
        $window = self::windowStart($windowSeconds);
        $stmt = db()->prepare(
            'INSERT INTO rate_limits (bucket_key, window_start, hit_count)
             VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE hit_count = hit_count + 1'
        );
        $stmt->execute([$key, $window]);
        if (random_int(1, 100) === 1) {
            $cleanup = db()->prepare('DELETE FROM rate_limits WHERE window_start < ?');
            $cleanup->execute([gmdate('Y-m-d H:i:s', time() - 86400)]);
        }
        return self::count($bucket, $windowSeconds);
    }

    private static function windowStart(int $windowSeconds): string
    {
        $windowSeconds = max(1, $windowSeconds);
        $start = (int) (floor(time() / $windowSeconds) * $windowSeconds);
        return gmdate('Y-m-d H:i:s', $start);
    }

    private static function storeKey(string $bucket): string
    {
        if (strlen($bucket) <= 120) {
            return $bucket;
        }
        return 'h:' . hash('sha256', $bucket);
    }
}
