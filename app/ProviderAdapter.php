<?php
declare(strict_types=1);
namespace App;
interface ProviderAdapter {
  public function generate(array $connector,array $inputs,string $prompt):array;
  public function listModels():array;
}
