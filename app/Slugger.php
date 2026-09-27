<?php
declare(strict_types=1);
namespace App;
final class Slugger {
  public static function make(string $value):string {
    $value=preg_replace('/[^a-z0-9]+/','-',strtolower(trim($value)))?:'connector';
    return trim($value,'-')?:'connector';
  }
  public static function unique(string $value,callable $exists):string {
    $base=self::make($value);$slug=$base;$i=2;
    while($exists($slug))$slug=$base.'-'.$i++;
    return $slug;
  }
}
