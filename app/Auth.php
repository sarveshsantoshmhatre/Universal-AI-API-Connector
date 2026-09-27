<?php
declare(strict_types=1);
namespace App;
final class Auth {
  public static function requireAdmin():void{
    $expected=Config::get('ADMIN_KEY'); if($expected===null)return;
    $provided=self::bearer();
    if($provided===null||!hash_equals($expected,$provided)) throw new HttpException('unauthorized','Administrator authentication required.',401);
  }
  public static function requireConnectorKey(string $hash):void{
    $provided=$_SERVER['HTTP_X_API_KEY']??self::bearer();
    if(!$provided||!hash_equals($hash,hash('sha256',$provided))) throw new HttpException('unauthorized','A valid connector API key is required.',401);
  }
  private static function bearer():?string{
    $header=$_SERVER['HTTP_AUTHORIZATION']??'';
    return preg_match('/^Bearer\s+(.+)$/i',trim($header),$m)?trim($m[1]):null;
  }
}
