<?php
declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) return;
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) require $file;
});

function app(string $service): mixed {
    static $services = [];
    if (isset($services[$service])) return $services[$service];
    if ($service === 'db') return $services[$service] = new App\Database();
    throw new InvalidArgumentException("Unknown service: {$service}");
}

function request_json(bool $throw = true): array {
    if (!str_contains(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) return [];
    $raw = file_get_contents('php://input') ?: '';
    if (strlen($raw) > App\Config::int('MAX_JSON_BYTES', 1048576)) {
        if ($throw) throw new App\HttpException('request_too_large', 'JSON request exceeds the configured size limit.', 413);
        return [];
    }
    if ($raw === '') return [];
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        if ($throw) throw new App\HttpException('invalid_json', 'Request body must be valid JSON.', 400);
        return [];
    }
    return $data;
}
