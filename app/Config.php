<?php
declare(strict_types=1);

namespace App;

final class Config {
    private static ?array $values = null;

    public static function get(string $key, ?string $default = null): ?string {
        self::load();
        $value = self::$values[$key] ?? null;
        return $value !== null && $value !== '' ? $value : $default;
    }
    public static function int(string $key, int $default): int { return max(0, (int)(self::get($key, (string)$default))); }
    public static function pricing(): array {
        $data = json_decode(self::get('PRICING_JSON', '{}'), true);
        return is_array($data) ? $data : [];
    }
    private static function load(): void {
        if (self::$values !== null) return;
        self::$values = $_ENV + $_SERVER;
        $file = dirname(__DIR__) . '/.env';
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line=trim($line);
                if ($line==='' || str_starts_with($line,'#') || !str_contains($line,'=')) continue;
                [$k,$v]=explode('=',$line,2); $v=trim($v);
                if ((str_starts_with($v,'"')&&str_ends_with($v,'"'))||(str_starts_with($v,"'")&&str_ends_with($v,"'"))) $v=substr($v,1,-1);
                self::$values[trim($k)]=$v; putenv(trim($k).'='.$v);
            }
        }
    }
}
