<?php
declare(strict_types=1);
namespace App;
final class HttpException extends \RuntimeException {
  public function __construct(public string $type,string $message,public int $status=400){parent::__construct($message);}
}
