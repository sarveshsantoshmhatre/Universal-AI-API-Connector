<?php
declare(strict_types=1);
namespace App;
final class SchemaTools {
  public static function toGemini(array $schema):array {
    if(!isset($schema['type'])) return $schema;
    $map=['object'=>'OBJECT','string'=>'STRING','number'=>'NUMBER','integer'=>'INTEGER','boolean'=>'BOOLEAN','array'=>'ARRAY'];
    $out=$schema;
    $out['type']=$map[strtolower((string)$schema['type'])]??strtoupper((string)$schema['type']);
    if(isset($schema['properties'])&&is_array($schema['properties'])){
      $out['properties']=[];
      foreach($schema['properties'] as $k=>$v) $out['properties'][$k]=self::toGemini((array)$v);
    }
    if(isset($schema['items'])&&is_array($schema['items'])) $out['items']=self::toGemini($schema['items']);
    unset($out['additionalProperties']);
    return $out;
  }
}
