<?php
declare(strict_types=1);
namespace App;
final class JsonResponse {
  public static function send(array $payload,int $status=200):never{
    http_response_code($status); header('Content-Type: application/json; charset=utf-8'); header('Cache-Control: no-store');
    echo json_encode($payload,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); exit;
  }
}
